<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\BreedingFemale;
use App\Models\BreedingPeriod;
use App\Models\ColonyPen;
use App\Support\TypeValue;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

    public function __construct(
        private readonly InbreedingRiskService $inbreeding,
        private readonly AnimalPenMovementService $movements,
        private readonly ReproductiveStatusService $statuses,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array<array-key, mixed>
     */
    public function store(array $data): array
    {
        $periodId = TypeValue::int($data['breeding_period_id']);
        $rawFemaleIds = $data['female_animal_ids'] ?? [$data['female_animal_id']];
        $femaleIds = collect(is_array($rawFemaleIds) ? $rawFemaleIds : [$rawFemaleIds])
            ->map(fn (mixed $femaleId): int => TypeValue::int($femaleId))
            ->unique()
            ->values()
            ->all();
        $matingDate = TypeValue::nullableString($data['mating_date'] ?? null);
        $expectedBirthDate = $matingDate ? $this->expectedBirthDate($matingDate) : null;
        $cycleStage = TypeValue::string($data['cycle_stage'] ?? 'kawin');
        $entryDate = TypeValue::string($data['entry_date']);

        return DB::transaction(function () use ($periodId, $femaleIds, $matingDate, $expectedBirthDate, $cycleStage, $entryDate, $data) {
            $period = BreedingPeriod::query()->whereKey($periodId)->first();
            $pen = ColonyPen::query()->whereKey($period?->colony_pen_id)->lockForUpdate()->first();
            $period = BreedingPeriod::query()->with('colonyPen')->whereKey($periodId)->lockForUpdate()->first();
            if (! $period || $period->status !== 'active' || ! $pen?->is_active || $pen->colony_phase !== 'koloni_kawin') {
                return $this->error('Peringatan: Kode periode harus mengarah ke periode kawin yang masih aktif.');
            }
            $capacityError = $this->validateCapacity($period, count($femaleIds));
            if ($capacityError !== null) {
                return $capacityError;
            }
            $animals = Animal::query()->whereIn('id', $femaleIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach ($femaleIds as $femaleId) {
                $femaleError = $this->validateFemaleForEntry($femaleId, $period, $animals->get($femaleId));
                if ($femaleError !== null) {
                    return $femaleError;
                }
            }
            $dateError = $this->validateEntryAndMatingDates($data, $period);
            if ($dateError !== null) {
                return $dateError;
            }

            $rows = collect();
            foreach ($femaleIds as $femaleId) {
                $animal = $animals->get($femaleId);
                if (! $animal instanceof Animal) {
                    throw new \LogicException('A validated breeding female disappeared during registration.');
                }
                if ((string) $animal->current_pen_id !== (string) $period->colony_pen_id) {
                    $move = $this->movements->record($animal, $period->colony_pen_id, $entryDate, 'Masuk periode kawin', null, true);
                    if (isset($move['error'])) {
                        throw ValidationException::withMessages(['entry_date' => [$move['error']]]);
                    }
                }

                $row = BreedingFemale::query()->create([
                    'breeding_period_id' => $periodId,
                    'female_animal_id' => $femaleId,
                    'entry_date' => $entryDate,
                    'mating_date' => $matingDate,
                    'expected_birth_date' => $expectedBirthDate,
                    'cycle_stage' => $cycleStage,
                    'inbreeding_status' => 'clear',
                    'inbreeding_note' => null,
                    'exit_date' => null,
                    'exit_reason' => null,
                    'exit_reason_code' => null,
                    'exit_notes' => null,
                ]);
                if (! $animal->status_date || $animal->status_date->toDateString() <= $entryDate) {
                    $animal->forceFill(['reproductive_status' => $cycleStage, 'status_date' => $entryDate])->save();
                    $this->statuses->sync($animal);
                }
                $rows->push($row->load(['breedingPeriod', 'femaleAnimal']));
            }

            return $this->success('Data betina kawin berhasil disimpan.', count($femaleIds) > 1 ? $rows->all() : $rows->first(), 201);
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<array-key, mixed>
     */
    public function update(BreedingFemale $breedingFemale, array $data): array
    {
        $period = $breedingFemale->breedingPeriod;
        $entryDate = TypeValue::nullableString($data['entry_date'] ?? $breedingFemale->entry_date?->toDateString());

        if ($period?->start_date && $entryDate && $entryDate < $period->start_date->toDateString()) {
            return $this->error('Peringatan: Tanggal masuk tidak boleh lebih awal dari tanggal mulai periode kawin.');
        }

        if ($period?->end_date && $entryDate && $entryDate > $period->end_date->toDateString()) {
            return $this->error('Peringatan: Tanggal masuk tidak boleh melewati tanggal selesai periode kawin.');
        }

        if ($breedingFemale->mating_date && $entryDate && $entryDate > $breedingFemale->mating_date->toDateString()) {
            return $this->error('Peringatan: Tanggal masuk tidak boleh melewati tanggal kawin yang sudah dicatat.');
        }
        if ($breedingFemale->exit_date && $entryDate && $entryDate > $breedingFemale->exit_date->toDateString()) {
            return $this->error('Peringatan: Tanggal masuk tidak boleh melewati tanggal keluar betina.');
        }

        return DB::transaction(function () use ($breedingFemale, $data, $entryDate) {
            $animal = Animal::query()->whereKey($breedingFemale->female_animal_id)->lockForUpdate()->firstOrFail();
            $oldEntryDate = $breedingFemale->entry_date?->toDateString();
            if ($entryDate && $oldEntryDate && $entryDate !== $oldEntryDate) {
                $entryMovement = $animal->penMovements()
                    ->where('to_pen_id', $breedingFemale->breedingPeriod?->colony_pen_id)
                    ->whereDate('movement_date', $oldEntryDate)
                    ->where('reason', 'Masuk periode kawin')->first();
                if ($entryMovement) {
                    $error = $this->movements->reschedule($animal, $entryMovement, $entryDate);
                    if ($error !== null) {
                        throw ValidationException::withMessages(['entry_date' => [$error]]);
                    }
                }
            }
            $breedingFemale->fill($data)->save();
            if ($animal->life_status === 'alive' && $entryDate && $oldEntryDate && $entryDate !== $oldEntryDate
                && $animal->status_date?->toDateString() === $oldEntryDate) {
                $animal->forceFill(['status_date' => $entryDate])->save();
                $this->statuses->sync($animal);
            }
            if ($animal->life_status === 'alive' && isset($data['cycle_stage']) && $entryDate && (! $animal->status_date || $animal->status_date->toDateString() <= $entryDate)) {
                $animal->forceFill(['reproductive_status' => TypeValue::string($data['cycle_stage']), 'status_date' => $entryDate])->save();
                $this->statuses->sync($animal);
            }

            return $this->success('Sukses: Data betina kawin berhasil diperbarui.', $breedingFemale->load(['breedingPeriod', 'femaleAnimal']));
        }, 3);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function recordMating(BreedingFemale $breedingFemale, string $matingDate): array
    {
        if ($breedingFemale->exit_date !== null) {
            return $this->error('Peringatan: Tanggal kawin tidak dapat dicatat karena betina sudah keluar dari periode kawin.');
        }

        $period = $breedingFemale->breedingPeriod;
        if ($breedingFemale->entry_date && $matingDate < $breedingFemale->entry_date->toDateString()) {
            return $this->error('Peringatan: Tanggal kawin tidak boleh sebelum tanggal masuk betina.');
        }

        if ($period?->start_date && $matingDate < $period->start_date->toDateString()) {
            return $this->error('Peringatan: Tanggal kawin tidak boleh lebih awal dari tanggal mulai periode kawin.');
        }

        if ($period?->end_date && $matingDate > $period->end_date->toDateString()) {
            return $this->error('Peringatan: Tanggal kawin tidak boleh melewati tanggal selesai periode kawin.');
        }

        return DB::transaction(function () use ($breedingFemale, $matingDate) {
            $animal = Animal::query()->whereKey($breedingFemale->female_animal_id)->lockForUpdate()->firstOrFail();
            $breedingFemale = BreedingFemale::query()->whereKey($breedingFemale->id)->lockForUpdate()->firstOrFail();
            if ($breedingFemale->exit_date !== null || $animal->life_status !== 'alive') {
                return $this->error('Peringatan: Tanggal kawin tidak dapat dicatat karena betina tidak lagi aktif dalam periode kawin.');
            }
            $breedingFemale->forceFill([
                'mating_date' => $matingDate,
                'expected_birth_date' => $this->expectedBirthDate($matingDate),
                'cycle_stage' => 'kawin',
            ])->save();
            if (! $animal->status_date || $animal->status_date->toDateString() <= $matingDate) {
                $animal->forceFill(['reproductive_status' => 'kawin', 'status_date' => $matingDate])->save();
                $this->statuses->sync($animal);
            }

            return $this->success('Sukses: Tanggal kawin berhasil dicatat.', $breedingFemale->load(['breedingPeriod.colonyPen', 'breedingPeriod.maleAnimal', 'femaleAnimal']));
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<array-key, mixed>
     */
    public function exit(BreedingFemale $breedingFemale, array $data): array
    {
        if ($breedingFemale->exit_date !== null) {
            return $this->error('Peringatan: Betina ini sudah keluar dari periode kawin.');
        }

        $breedingFemale->load(['breedingPeriod.colonyPen', 'breedingPeriod.maleAnimal', 'femaleAnimal']);
        $period = $breedingFemale->breedingPeriod;
        $reasonLabel = $this->exitReasonLabel($data);

        $exitDate = TypeValue::string($data['exit_date']);
        $exitReasonCode = TypeValue::string($data['exit_reason_code']);
        $toPenId = isset($data['to_pen_id']) ? TypeValue::int($data['to_pen_id']) : null;

        if ($breedingFemale->entry_date && $exitDate < $breedingFemale->entry_date->toDateString()) {
            return $this->error('Peringatan: Tanggal keluar tidak boleh lebih awal dari tanggal masuk betina.');
        }

        if ($breedingFemale->mating_date && $exitDate < $breedingFemale->mating_date->toDateString()) {
            return $this->error('Peringatan: Tanggal keluar tidak boleh sebelum tanggal kawin.');
        }

        return DB::transaction(function () use ($breedingFemale, $data, $period, $reasonLabel, $exitDate, $exitReasonCode, $toPenId) {
            $animal = Animal::query()->whereKey($breedingFemale->female_animal_id)->lockForUpdate()->firstOrFail();
            $breedingFemale = BreedingFemale::query()->whereKey($breedingFemale->id)->lockForUpdate()->firstOrFail();
            if ($breedingFemale->exit_date !== null) {
                return $this->error('Peringatan: Betina ini sudah keluar dari periode kawin.');
            }
            if ($toPenId !== null) {
                $error = $this->movements->destinationError($animal, ColonyPen::query()->whereKey($toPenId)->first(), $exitDate);
                if ($error !== null) {
                    return $this->error($error);
                }
            }

            $move = $this->movements->record(
                $animal, $toPenId, $exitDate, $reasonLabel,
                TypeValue::nullableString($data['exit_notes'] ?? null) ?? 'Keluar dari periode kawin '.($period?->period_code ?? '-').'.',
            );
            if (isset($move['error'])) {
                return $this->error($move['error']);
            }

            $breedingFemale->forceFill([
                'exit_date' => $exitDate,
                'exit_reason' => $reasonLabel,
                'exit_reason_code' => $exitReasonCode,
                'exit_notes' => $data['exit_notes'] ?? null,
            ])->save();

            $reproductiveStatus = $this->reproductiveStatusFromExitReasonCode($exitReasonCode);
            if ($reproductiveStatus !== null && (! $animal->status_date || $animal->status_date->toDateString() <= $exitDate)) {
                $animal->forceFill(['reproductive_status' => $reproductiveStatus, 'status_date' => $exitDate])->save();
                $this->statuses->sync($animal);
            }

            return $this->success('Sukses: Catatan keluar betina dari periode perkawinan berhasil disimpan.', $breedingFemale->load(['breedingPeriod.colonyPen', 'breedingPeriod.maleAnimal', 'femaleAnimal']));
        }, 3);
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

    /**
     * @return array<array-key, mixed>|null
     */
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

    /**
     * @return array<array-key, mixed>|null
     */
    private function validateFemaleForEntry(int $femaleId, BreedingPeriod $period, ?Animal $femaleAnimal = null): ?array
    {
        $femaleAnimal ??= Animal::query()->whereKey($femaleId)->first();
        if (! $femaleAnimal || $femaleAnimal->sex !== 'female' || $femaleAnimal->life_status !== 'alive' || $femaleAnimal->exit_status !== null) {
            return $this->error('Peringatan: Tag betina harus mengarah ke kambing betina yang tercatat hidup dan tersedia.');
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
            return $this->error("Peringatan: Betina dengan eartag {$femaleAnimal->tag_number} sudah terdaftar pada periode perkawinan ini.");
        }

        $risk = $this->inbreeding->evaluate((int) $period->male_animal_id, $femaleId);
        if ($risk['status'] !== 'clear') {
            return $this->error($risk['message'], 422, ['inbreeding' => $risk]);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<array-key, mixed>|null
     */
    private function validateEntryAndMatingDates(array $data, BreedingPeriod $period): ?array
    {
        $entryDate = TypeValue::string($data['entry_date']);
        $matingDate = TypeValue::nullableString($data['mating_date'] ?? null);

        if ($entryDate < $period->start_date->toDateString()) {
            return $this->error('Peringatan: Tanggal masuk tidak boleh lebih awal dari tanggal mulai periode kawin.');
        }

        if ($period->end_date && $entryDate > $period->end_date->toDateString()) {
            return $this->error('Peringatan: Tanggal masuk tidak boleh melewati tanggal selesai periode kawin.');
        }

        if ($matingDate === null || $matingDate === '') {
            return null;
        }

        if ($matingDate < $entryDate) {
            return $this->error('Peringatan: Tanggal kawin tidak boleh sebelum tanggal masuk betina.');
        }

        if ($matingDate < $period->start_date->toDateString()) {
            return $this->error('Peringatan: Tanggal kawin tidak boleh lebih awal dari tanggal mulai periode kawin.');
        }

        if ($period->end_date && $matingDate > $period->end_date->toDateString()) {
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

    /**
     * @param  array<string, mixed>  $data
     */
    private function exitReasonLabel(array $data): string
    {
        $reasonCode = TypeValue::string($data['exit_reason_code']);

        if ($reasonCode === 'lainnya') {
            return TypeValue::string($data['exit_reason']);
        }

        return self::EXIT_REASONS[$reasonCode] ?? 'Lainnya';
    }

    /**
     * @return array<array-key, mixed>
     */
    private function success(string $message, mixed $data, int $status = 200): array
    {
        return ['ok' => true, 'status' => $status, 'message' => $message, 'data' => $data];
    }

    /**
     * @param  array<array-key, mixed>  $extra
     * @return array<array-key, mixed>
     */
    private function error(string $message, int $status = 422, array $extra = []): array
    {
        return ['ok' => false, 'status' => $status, 'message' => $message] + $extra;
    }
}
