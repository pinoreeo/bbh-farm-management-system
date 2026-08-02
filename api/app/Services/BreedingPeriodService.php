<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\BreedingPeriod;
use App\Models\ColonyPen;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class BreedingPeriodService
{
    private const INDEX_RELATIONS = [
        'colonyPen:id,pen_code,colony_phase,colony_type,capacity,is_active',
        'maleAnimal:id,tag_number,sex,male_role,life_status',
    ];

    public function paginate(Request $request, int $perPage): LengthAwarePaginator
    {
        $query = BreedingPeriod::query()->with(self::INDEX_RELATIONS);

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        } elseif (! (int) $request->query('include_closed', 0)) {
            $query->where('status', 'active');
        }

        if ($request->filled('colony_pen_id')) {
            $query->where('colony_pen_id', (int) $request->query('colony_pen_id'));
        }

        if ($request->filled('male_animal_id')) {
            $query->where('male_animal_id', (int) $request->query('male_animal_id'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where('period_code', 'like', "%{$search}%");
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function store(array $data): array
    {
        $validation = $this->validatePayload($data);
        if (! $validation['ok']) {
            return $validation;
        }

        if ($this->periodCodeExists((int) $data['colony_pen_id'], (string) $data['period_code'])) {
            return $this->error('Peringatan: Kode periode sudah digunakan pada kandang tersebut.');
        }

        $data['status'] = $data['status'] ?? 'active';
        $data['inbreeding_policy'] = $data['inbreeding_policy'] ?? 'block_high_risk';

        if ($data['status'] === 'active' && $this->activePeriodExistsForColony((int) $data['colony_pen_id'])) {
            return $this->error('Peringatan: Koloni kawin ini masih memiliki periode aktif. Tutup periode sebelumnya sebelum membuat periode baru.');
        }

        $row = BreedingPeriod::query()->create($data);

        return $this->success('Sukses: Periode kawin berhasil disimpan.', $this->loadSummary($row), 201);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(BreedingPeriod $breedingPeriod, array $data, array $requestedFields): array
    {
        $lockedFields = collect(['colony_pen_id', 'period_code', 'start_date', 'male_animal_id'])
            ->intersect($requestedFields);

        if ($lockedFields->isNotEmpty() && $breedingPeriod->females()->exists()) {
            return $this->error('Peringatan: Koloni, kode periode, tanggal mulai, dan pejantan tidak dapat diubah karena periode kawin sudah berisi betina.');
        }

        $payload = [
            'colony_pen_id' => $data['colony_pen_id'] ?? $breedingPeriod->colony_pen_id,
            'period_code' => $data['period_code'] ?? $breedingPeriod->period_code,
            'start_date' => $data['start_date'] ?? $breedingPeriod->start_date?->toDateString(),
            'end_date' => array_key_exists('end_date', $data) ? $data['end_date'] : $breedingPeriod->end_date?->toDateString(),
            'male_animal_id' => $data['male_animal_id'] ?? $breedingPeriod->male_animal_id,
        ];

        $validation = $this->validatePayload($payload);
        if (! $validation['ok']) {
            return $validation;
        }

        if ($this->periodCodeExists((int) $payload['colony_pen_id'], (string) $payload['period_code'], $breedingPeriod->id)) {
            return $this->error('Peringatan: Kode periode sudah digunakan pada kandang tersebut.');
        }

        $newStatus = $data['status'] ?? $breedingPeriod->status;
        if ($newStatus === 'active' && $this->activePeriodExistsForColony((int) $payload['colony_pen_id'], $breedingPeriod->id)) {
            return $this->error('Peringatan: Koloni kawin ini masih memiliki periode aktif lain. Tutup periode tersebut sebelum mengaktifkan periode ini.');
        }

        $breedingPeriod->fill($data)->save();

        return $this->success('Sukses: Periode kawin berhasil diperbarui.', $this->loadSummary($breedingPeriod));
    }

    /**
     * @return array<string, mixed>
     */
    public function close(BreedingPeriod $breedingPeriod): array
    {
        if (($breedingPeriod->status ?? 'active') === 'closed') {
            return $this->error('Peringatan: Periode kawin ini sudah ditutup.');
        }

        $breedingPeriod->forceFill(['status' => 'closed'])->save();

        return $this->success('Sukses: Periode kawin berhasil ditutup.', $this->loadSummary($breedingPeriod));
    }

    public function loadDetail(BreedingPeriod $breedingPeriod): BreedingPeriod
    {
        return $breedingPeriod->load(['colonyPen', 'maleAnimal', 'females', 'pregnancyChecks']);
    }

    private function loadSummary(BreedingPeriod $breedingPeriod): BreedingPeriod
    {
        return $breedingPeriod->load(['colonyPen', 'maleAnimal']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validatePayload(array $data): array
    {
        $maleAnimal = Animal::query()->find($data['male_animal_id'] ?? null);
        if (! $maleAnimal || $maleAnimal->sex !== 'male' || $maleAnimal->life_status !== 'alive') {
            return $this->error('Peringatan: Tag pejantan harus mengarah ke kambing jantan yang masih hidup.');
        }

        $pen = ColonyPen::query()->find($data['colony_pen_id'] ?? null);
        if (! $pen || ! $pen->is_active || ! in_array($pen->colony_phase ?? $pen->colony_type, ['koloni_kawin'], true)) {
            return $this->error('Peringatan: Kode kandang harus mengarah ke kandang perkawinan yang masih aktif.');
        }

        if (! empty($data['end_date']) && ! empty($data['start_date']) && $data['end_date'] < $data['start_date']) {
            return $this->error('Peringatan: Tanggal selesai periode kawin tidak boleh lebih awal dari tanggal mulai.');
        }

        return ['ok' => true];
    }

    private function periodCodeExists(int $colonyPenId, string $periodCode, ?int $ignoreId = null): bool
    {
        return BreedingPeriod::query()
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('colony_pen_id', $colonyPenId)
            ->where('period_code', $periodCode)
            ->exists();
    }

    private function activePeriodExistsForColony(int $colonyPenId, ?int $ignoreId = null): bool
    {
        return BreedingPeriod::query()
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('colony_pen_id', $colonyPenId)
            ->where('status', 'active')
            ->exists();
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
