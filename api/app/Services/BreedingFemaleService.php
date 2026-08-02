<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\AnimalPenMovement;
use App\Models\BreedingFemale;
use App\Models\BreedingPeriod;
use App\Models\ColonyPen;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BreedingFemaleService
{
    public const EXIT_REASONS = [
        'bunting_pindah_koloni_bunting' => 'Bunting, pindah ke koloni bunting',
        'tidak_bunting' => 'Tidak bunting / gagal kawin',
        'sakit' => 'Sakit',
        'pejantan_mati' => 'Pejantan mati / periode dihentikan',
        'periode_selesai' => 'Periode selesai',
        'salah_input' => 'Salah input',
        'lainnya' => 'Lainnya',
    ];

    public function __construct(private readonly InbreedingRiskService $inbreeding) {}

    public function store(array $data): array
    {
        $period = BreedingPeriod::query()->with('colonyPen')->find($data['breeding_period_id']);
        if (! $period || $period->status !== 'active') {
            return $this->error('Peringatan: Kode periode harus mengarah ke periode kawin yang masih aktif.');
        }

        $femaleIds = array_values(array_unique(array_map('intval', $data['female_animal_ids'] ?? [$data['female_animal_id']])));
        $capacityError = $this->validateCapacity($period, count($femaleIds));
        if ($capacityError !== null) {
            return $capacityError;
        }

        foreach ($femaleIds as $femaleId) {
            $femaleError = $this->validateFemaleForEntry($femaleId, $period);
            if ($femaleError !== null) {
                return $femaleError;
            }
        }

        $dateError = $this->validateEntryAndMatingDates($data, $period);
        if ($dateError !== null) {
            return $dateError;
        }

        $matingDate = $data['mating_date'] ?? null;
        $expectedBirthDate = $matingDate ? $this->expectedBirthDate($matingDate) : null;
        $cycleStage = $data['cycle_stage'] ?? 'kawin';

        $rows = DB::transaction(function () use ($data, $femaleIds, $period, $matingDate, $expectedBirthDate, $cycleStage) {
            return collect($femaleIds)->map(function (int $femaleId) use ($data, $period, $matingDate, $expectedBirthDate, $cycleStage) {
                Animal::query()->whereKey($femaleId)->update([
                    'current_pen_id' => $period->colony_pen_id,
                    'reproductive_status' => $cycleStage,
                    'status_date' => $data['entry_date'],
                ]);

                return BreedingFemale::query()->create([
                    'breeding_period_id' => $data['breeding_period_id'],
                    'female_animal_id' => $femaleId,
                    'entry_date' => $data['entry_date'],
                    'mating_date' => $matingDate,
                    'expected_birth_date' => $expectedBirthDate,
                    'cycle_stage' => $cycleStage,
                    'inbreeding_status' => 'clear',
                    'inbreeding_note' => null,
                    'exit_date' => null,
                    'exit_reason' => null,
                    'exit_reason_code' => null,
                    'exit_notes' => null,
                ])->load(['breedingPeriod', 'femaleAnimal']);
            })->values();
        });

        return $this->success('Data betina kawin berhasil disimpan.', count($femaleIds) > 1 ? $rows->all() : $rows->first(), 201);
    }

    public function update(BreedingFemale $breedingFemale, array $data): array
    {
        $period = $breedingFemale->breedingPeriod;
        $entryDate = $data['entry_date'] ?? $breedingFemale->entry_date?->toDateString();

        if ($period?->start_date && $entryDate && $entryDate < $period->start_date->toDateString()) {
            return $this->error('Peringatan: Tanggal masuk tidak boleh lebih awal dari tanggal mulai periode kawin.');
        }

        if ($period?->end_date && $entryDate && $entryDate > $period->end_date->toDateString()) {
            return $this->error('Peringatan: Tanggal masuk tidak boleh melewati tanggal selesai periode kawin.');
        }

        if ($breedingFemale->mating_date && $entryDate && $entryDate > $breedingFemale->mating_date->toDateString()) {
            return $this->error('Peringatan: Tanggal masuk tidak boleh melewati tanggal kawin yang sudah dicatat.');
        }

        $breedingFemale->fill($data)->save();

        if (isset($data['cycle_stage'])) {
            $breedingFemale->femaleAnimal?->forceFill([
                'reproductive_status' => $data['cycle_stage'],
                'status_date' => $entryDate,
            ])->save();
        }

        return $this->success('Sukses: Data betina kawin berhasil diperbarui.', $breedingFemale->load(['breedingPeriod', 'femaleAnimal']));
    }

    public function recordMating(BreedingFemale $breedingFemale, string $matingDate): array
    {
        if ($breedingFemale->exit_date !== null) {
            return $this->error('Peringatan: Tanggal kawin tidak dapat dicatat karena betina sudah keluar dari periode kawin.');
        }

        $period = $breedingFemale->breedingPeriod;
        if ($breedingFemale->entry_date && $matingDate < $breedingFemale->entry_date->toDateString()) {
            return $this->error('Peringatan: Tanggal kawin tidak boleh lebih awal dari tanggal masuk betina.');
        }

        if ($period?->start_date && $matingDate < $period->start_date->toDateString()) {
            return $this->error('Peringatan: Tanggal kawin tidak boleh lebih awal dari tanggal mulai periode kawin.');
        }

        if ($period?->end_date && $matingDate > $period->end_date->toDateString()) {
            return $this->error('Peringatan: Tanggal kawin tidak boleh melewati tanggal selesai periode kawin.');
        }

        $breedingFemale->forceFill([
            'mating_date' => $matingDate,
            'expected_birth_date' => $this->expectedBirthDate($matingDate),
            'cycle_stage' => 'kawin',
        ])->save();

        $breedingFemale->femaleAnimal?->forceFill([
            'reproductive_status' => 'kawin',
            'status_date' => $matingDate,
        ])->save();

        return $this->success('Sukses: Tanggal kawin berhasil dicatat.', $breedingFemale->load(['breedingPeriod.colonyPen', 'breedingPeriod.maleAnimal', 'femaleAnimal']));
    }

    public function exit(BreedingFemale $breedingFemale, array $data): array
    {
        if ($breedingFemale->exit_date !== null) {
            return $this->error('Peringatan: Betina ini sudah keluar dari periode kawin.');
        }

        $breedingFemale->load(['breedingPeriod.colonyPen', 'breedingPeriod.maleAnimal', 'femaleAnimal']);
        $period = $breedingFemale->breedingPeriod;
        $reasonLabel = $this->exitReasonLabel($data);

        if ($breedingFemale->entry_date && $data['exit_date'] < $breedingFemale->entry_date->toDateString()) {
            return $this->error('Peringatan: Tanggal keluar tidak boleh lebih awal dari tanggal masuk betina.');
        }

        if ($breedingFemale->mating_date && $data['exit_date'] < $breedingFemale->mating_date->toDateString()) {
            return $this->error('Peringatan: Tanggal keluar tidak boleh lebih awal dari tanggal kawin yang sudah dicatat.');
        }

        if (! empty($data['to_pen_id'])) {
            $destinationError = $this->validateDestinationPen($breedingFemale->femaleAnimal, ColonyPen::find($data['to_pen_id']));
            if ($destinationError !== null) {
                return $destinationError;
            }
        }

        $result = DB::transaction(function () use ($breedingFemale, $data, $period, $reasonLabel) {
            $breedingFemale->forceFill([
                'exit_date' => $data['exit_date'],
                'exit_reason' => $reasonLabel,
                'exit_reason_code' => $data['exit_reason_code'],
                'exit_notes' => $data['exit_notes'] ?? null,
            ])->save();

            $animal = $breedingFemale->femaleAnimal;
            if ($animal !== null) {
                $animalUpdates = ['status_date' => $data['exit_date']];
                $reproductiveStatus = $this->reproductiveStatusFromExitReasonCode($data['exit_reason_code']);

                if ($reproductiveStatus !== null) {
                    $animalUpdates['reproductive_status'] = $reproductiveStatus;
                }

                if (! empty($data['to_pen_id'])) {
                    AnimalPenMovement::query()->create([
                        'animal_id' => $animal->id,
                        'from_pen_id' => $animal->current_pen_id ?: $period?->colony_pen_id,
                        'to_pen_id' => $data['to_pen_id'],
                        'movement_date' => $data['exit_date'],
                        'reason' => $reasonLabel,
                        'notes' => $data['exit_notes'] ?? 'Keluar dari periode kawin '.($period?->period_code ?? '-').'.',
                    ]);

                    $animalUpdates['current_pen_id'] = $data['to_pen_id'];
                } else {
                    $animalUpdates['current_pen_id'] = null;
                }

                $animal->forceFill($animalUpdates)->save();
            }

            return $breedingFemale->load(['breedingPeriod.colonyPen', 'breedingPeriod.maleAnimal', 'femaleAnimal']);
        });

        return $this->success('Sukses: Betina berhasil dikeluarkan dari periode kawin.', $result);
    }

    public function hasExitPayload(Request $request): bool
    {
        return collect(['exit_date', 'exit_reason', 'exit_reason_code', 'exit_notes', 'to_pen_id'])
            ->contains(fn (string $key) => $request->exists($key));
    }

    public function hasMatingPayload(Request $request): bool
    {
        return $request->exists('mating_date') || $request->exists('expected_birth_date');
    }

    private function validateCapacity(BreedingPeriod $period, int $incomingCount): ?array
    {
        $capacity = (int) ($period->colonyPen?->capacity ?? 0);
        $activeCount = BreedingFemale::query()
            ->where('breeding_period_id', $period->id)
            ->whereNull('exit_date')
            ->count();

        if ($capacity > 0 && ($activeCount + $incomingCount) > $capacity) {
            return $this->error('Peringatan: Kapasitas kandang pada periode kawin ini sudah penuh.');
        }

        return null;
    }

    private function validateFemaleForEntry(int $femaleId, BreedingPeriod $period): ?array
    {
        $femaleAnimal = Animal::find($femaleId);
        if (! $femaleAnimal || $femaleAnimal->sex !== 'female' || $femaleAnimal->life_status !== 'alive' || $femaleAnimal->exit_status !== null) {
            return $this->error('Peringatan: Tag betina harus mengarah ke kambing betina yang masih hidup dan tersedia.');
        }

        $activeInOtherPeriod = BreedingFemale::query()
            ->where('female_animal_id', $femaleId)
            ->where('breeding_period_id', '!=', $period->id)
            ->whereNull('exit_date')
            ->exists();

        if ($activeInOtherPeriod) {
            return $this->error("Betina dengan tag {$femaleAnimal->tag_number} masih aktif pada periode kawin lain.");
        }

        $exists = BreedingFemale::query()
            ->where('breeding_period_id', $period->id)
            ->where('female_animal_id', $femaleId)
            ->exists();

        if ($exists) {
            return $this->error("Betina dengan tag {$femaleAnimal->tag_number} sudah terdaftar pada periode kawin ini.");
        }

        $risk = $this->inbreeding->evaluate((int) $period->male_animal_id, $femaleId);
        if ($risk['status'] !== 'clear') {
            return $this->error($risk['message'], 422, ['inbreeding' => $risk]);
        }

        return null;
    }

    private function validateEntryAndMatingDates(array $data, BreedingPeriod $period): ?array
    {
        if ($data['entry_date'] < $period->start_date->toDateString()) {
            return $this->error('Peringatan: Tanggal masuk tidak boleh lebih awal dari tanggal mulai periode kawin.');
        }

        if ($period->end_date && $data['entry_date'] > $period->end_date->toDateString()) {
            return $this->error('Peringatan: Tanggal masuk tidak boleh melewati tanggal selesai periode kawin.');
        }

        if (empty($data['mating_date'])) {
            return null;
        }

        if ($data['mating_date'] < $data['entry_date']) {
            return $this->error('Peringatan: Tanggal kawin tidak boleh lebih awal dari tanggal masuk betina.');
        }

        if ($data['mating_date'] < $period->start_date->toDateString()) {
            return $this->error('Peringatan: Tanggal kawin tidak boleh lebih awal dari tanggal mulai periode kawin.');
        }

        if ($period->end_date && $data['mating_date'] > $period->end_date->toDateString()) {
            return $this->error('Peringatan: Tanggal kawin tidak boleh melewati tanggal selesai periode kawin.');
        }

        return null;
    }

    private function expectedBirthDate(string $matingDate): string
    {
        return Carbon::parse($matingDate)->addMonthsNoOverflow(5)->addDays(10)->toDateString();
    }

    private function reproductiveStatusFromExitReasonCode(string $reasonCode): ?string
    {
        return match ($reasonCode) {
            'bunting_pindah_koloni_bunting' => 'bunting',
            'tidak_bunting',
            'periode_selesai',
            'salah_input' => 'kosong',
            default => null,
        };
    }

    private function exitReasonLabel(array $data): string
    {
        if (($data['exit_reason_code'] ?? null) === 'lainnya') {
            return (string) $data['exit_reason'];
        }

        return self::EXIT_REASONS[$data['exit_reason_code']] ?? 'Lainnya';
    }

    private function validateDestinationPen(?Animal $animal, ?ColonyPen $destinationPen): ?array
    {
        if (! $animal) {
            return $this->error('Peringatan: Data betina tidak tersedia sehingga tujuan koloni tidak dapat divalidasi.');
        }

        $phase = $destinationPen?->colony_phase ?? $destinationPen?->colony_type;

        if (! $destinationPen || ! $destinationPen->is_active) {
            return $this->error('Peringatan: Koloni tujuan tidak aktif atau tidak ditemukan.');
        }

        if ($phase === 'koloni_kawin') {
            return $this->error('Peringatan: Pindah ke koloni kawin harus diproses melalui menu Periode Kawin agar pengecekan hubungan darah tetap berjalan.');
        }

        if ($phase === 'koloni_anak' && $animal->kategori_umur !== 'cempe') {
            return $this->error('Peringatan: Koloni anak hanya dapat diisi oleh cempe berdasarkan kategori umur ternak.');
        }

        if (in_array($phase, ['koloni_bunting', 'koloni_kering', 'koloni_laktasi'], true) && $animal->sex !== 'female') {
            return $this->error('Peringatan: Koloni bunting, kering, dan laktasi hanya dapat diisi oleh kambing betina.');
        }

        return null;
    }

    private function success(string $message, mixed $data, int $status = 200): array
    {
        return ['ok' => true, 'status' => $status, 'message' => $message, 'data' => $data];
    }

    private function error(string $message, int $status = 422, array $extra = []): array
    {
        return ['ok' => false, 'status' => $status, 'message' => $message] + $extra;
    }
}
