<?php

namespace App\Services;

use App\Models\BreedingFemale;
use App\Models\PregnancyCheck;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class PregnancyCheckService
{
    private const RELATIONS = [
        'breedingFemale.breedingPeriod.colonyPen',
        'breedingFemale.breedingPeriod.maleAnimal',
        'breedingFemale.femaleAnimal',
        'breedingPeriod.colonyPen',
        'breedingPeriod.maleAnimal',
        'femaleAnimal',
    ];

    public function paginate(Request $request, int $perPage): LengthAwarePaginator
    {
        $query = PregnancyCheck::query()->with(self::RELATIONS);

        if ($request->filled('breeding_period_id')) {
            $query->where('breeding_period_id', (int) $request->query('breeding_period_id'));
        }

        if ($request->filled('breeding_female_id')) {
            $query->where('breeding_female_id', (int) $request->query('breeding_female_id'));
        }

        if ($request->filled('female_animal_id')) {
            $query->where('female_animal_id', (int) $request->query('female_animal_id'));
        }

        if ($request->filled('is_pregnant')) {
            $query->where('is_pregnant', (int) $request->query('is_pregnant') ? 1 : 0);
        }

        return $query->orderByDesc('check_date')->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function store(array $data): array
    {
        $context = $this->resolveContext($data);
        if (! $context['ok']) {
            return $context;
        }

        /** @var BreedingFemale $breedingFemale */
        $breedingFemale = $context['data'];
        $data = $this->hydrateContextPayload($data, $breedingFemale);

        $exists = PregnancyCheck::query()
            ->where('breeding_female_id', $data['breeding_female_id'])
            ->whereDate('check_date', $data['check_date'])
            ->exists();

        if ($exists) {
            return $this->error('Peringatan: Pemeriksaan kebuntingan untuk betina dan tanggal tersebut sudah pernah dicatat.');
        }

        if (! $data['is_pregnant']) {
            $data['estimated_gestation_days'] = null;
        }

        $row = PregnancyCheck::query()->create($data);
        $this->syncFemaleStatus($breedingFemale, (bool) $data['is_pregnant'], (string) $data['check_date']);

        return $this->success('Sukses: Pemeriksaan kebuntingan berhasil disimpan.', $this->loadRelations($row), 201);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(PregnancyCheck $pregnancyCheck, array $data): array
    {
        $newDate = $data['check_date'] ?? $pregnancyCheck->check_date?->format('Y-m-d');

        $context = $this->resolveContext([
            'breeding_female_id' => $data['breeding_female_id'] ?? $pregnancyCheck->breeding_female_id,
            'breeding_period_id' => $data['breeding_period_id'] ?? $pregnancyCheck->breeding_period_id,
            'female_animal_id' => $data['female_animal_id'] ?? $pregnancyCheck->female_animal_id,
            'check_date' => $newDate,
            'is_pregnant' => $data['is_pregnant'] ?? $pregnancyCheck->is_pregnant,
        ]);

        if (! $context['ok']) {
            return $context;
        }

        /** @var BreedingFemale $breedingFemale */
        $breedingFemale = $context['data'];
        $data = $this->hydrateContextPayload($data, $breedingFemale);

        $exists = PregnancyCheck::query()
            ->whereKeyNot($pregnancyCheck->id)
            ->where('breeding_female_id', $breedingFemale->id)
            ->whereDate('check_date', $newDate)
            ->exists();

        if ($exists) {
            return $this->error('Peringatan: Pemeriksaan kebuntingan lain untuk betina dan tanggal tersebut sudah pernah dicatat.');
        }

        if (array_key_exists('is_pregnant', $data) && ! $data['is_pregnant']) {
            $data['estimated_gestation_days'] = null;
        }

        $pregnancyCheck->fill($data)->save();

        if (array_key_exists('is_pregnant', $data) || array_key_exists('check_date', $data)) {
            $this->syncFemaleStatus(
                $breedingFemale,
                (bool) $pregnancyCheck->is_pregnant,
                $pregnancyCheck->check_date?->toDateString(),
            );
        }

        return $this->success('Sukses: Pemeriksaan kebuntingan berhasil diperbarui.', $this->loadRelations($pregnancyCheck));
    }

    public function loadRelations(PregnancyCheck $pregnancyCheck): PregnancyCheck
    {
        return $pregnancyCheck->load(self::RELATIONS);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{ok: bool, status?: int, message?: string, data?: BreedingFemale}
     */
    private function resolveContext(array $data): array
    {
        $breedingFemale = isset($data['breeding_female_id'])
            ? BreedingFemale::query()->with(['breedingPeriod', 'femaleAnimal'])->find($data['breeding_female_id'])
            : BreedingFemale::query()
                ->with(['breedingPeriod', 'femaleAnimal'])
                ->where('breeding_period_id', $data['breeding_period_id'])
                ->where('female_animal_id', $data['female_animal_id'])
                ->whereNull('exit_date')
                ->first();

        if (! $breedingFemale || $breedingFemale->exit_date !== null) {
            return $this->error(isset($data['breeding_female_id'])
                ? 'Peringatan: Data betina dalam periode kawin tidak valid atau sudah tidak aktif.'
                : 'Peringatan: Betina yang dipilih belum terdaftar aktif pada periode kawin tersebut.');
        }

        $period = $breedingFemale->breedingPeriod;
        if (! $period || $period->status !== 'active') {
            return $this->error('Peringatan: Kode periode harus mengarah ke periode kawin yang masih aktif.');
        }

        $femaleAnimal = $breedingFemale->femaleAnimal;
        if (! $femaleAnimal || strtolower((string) $femaleAnimal->sex) !== 'female') {
            return $this->error('Peringatan: Tag betina harus mengarah ke kambing betina.');
        }

        $dateError = $this->validateDates($data, $breedingFemale);
        if ($dateError !== null) {
            return $this->error($dateError);
        }

        return ['ok' => true, 'data' => $breedingFemale];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validateDates(array $data, BreedingFemale $breedingFemale): ?string
    {
        $checkDate = $data['check_date'] ?? null;
        $period = $breedingFemale->breedingPeriod;

        if ($period?->start_date && $checkDate < $period->start_date->toDateString()) {
            return 'Peringatan: Tanggal periksa tidak boleh lebih awal dari tanggal mulai periode kawin.';
        }

        if ($breedingFemale->entry_date && $checkDate < $breedingFemale->entry_date->toDateString()) {
            return 'Peringatan: Tanggal periksa tidak boleh lebih awal dari tanggal masuk betina ke periode kawin.';
        }

        if (! empty($data['is_pregnant']) && ! $breedingFemale->mating_date) {
            return 'Peringatan: Status bunting hanya dapat dicatat setelah tanggal kawin betina tersebut diisi.';
        }

        if ($breedingFemale->mating_date && $checkDate < $breedingFemale->mating_date->toDateString()) {
            return 'Peringatan: Tanggal periksa tidak boleh lebih awal dari tanggal kawin betina tersebut.';
        }

        if ($breedingFemale->exit_date && $checkDate > $breedingFemale->exit_date->toDateString()) {
            return 'Peringatan: Tanggal periksa tidak boleh melewati tanggal keluar betina dari periode kawin.';
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function hydrateContextPayload(array $data, BreedingFemale $breedingFemale): array
    {
        $data['breeding_female_id'] = $breedingFemale->id;
        $data['breeding_period_id'] = $breedingFemale->breeding_period_id;
        $data['female_animal_id'] = $breedingFemale->female_animal_id;

        return $data;
    }

    private function syncFemaleStatus(BreedingFemale $breedingFemale, bool $isPregnant, ?string $statusDate): void
    {
        $breedingFemale->femaleAnimal?->forceFill([
            'reproductive_status' => $isPregnant ? 'bunting' : 'kosong',
            'status_date' => $statusDate,
        ])->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function success(string $message, mixed $data, int $status = 200): array
    {
        return compact('message', 'data', 'status') + ['ok' => true];
    }

    /**
     * @return array<string, mixed>
     */
    private function error(string $message, int $status = 422): array
    {
        return compact('message', 'status') + ['ok' => false];
    }
}
