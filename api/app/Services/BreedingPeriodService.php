<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\BreedingPeriod;
use App\Models\ColonyPen;
use App\Support\TypeValue;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BreedingPeriodService
{
    private const INDEX_RELATIONS = [
        'colonyPen:id,pen_code,colony_phase,colony_type,capacity,is_active',
        'maleAnimal:id,tag_number,sex,male_role,life_status',
    ];

    /**
     * @return LengthAwarePaginator<int, BreedingPeriod>
     */
    public function paginate(Request $request, int $perPage): LengthAwarePaginator
    {
        $query = BreedingPeriod::query()->with(self::INDEX_RELATIONS);

        if ($request->filled('status')) {
            $query->where('status', TypeValue::string($request->query('status')));
        } elseif (! TypeValue::int($request->query('include_closed', 0))) {
            $query->where('status', 'active');
        }

        if ($request->filled('colony_pen_id')) {
            $query->where('colony_pen_id', TypeValue::int($request->query('colony_pen_id')));
        }

        if ($request->filled('male_animal_id')) {
            $query->where('male_animal_id', TypeValue::int($request->query('male_animal_id')));
        }

        if ($request->filled('search')) {
            $search = trim(TypeValue::string($request->query('search')));
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
        return DB::transaction(function () use ($data) {
            ColonyPen::query()->whereKey(TypeValue::int($data['colony_pen_id'] ?? null))->lockForUpdate()->first();
            $validation = $this->validatePayload($data);
            if (! $validation['ok']) {
                return $validation;
            }

            if ($this->periodCodeExists(TypeValue::int($data['colony_pen_id'] ?? null), TypeValue::string($data['period_code'] ?? ''))) {
                return $this->error('Peringatan: Kode periode sudah digunakan pada kandang tersebut.');
            }

            $data['status'] = $data['status'] ?? 'active';
            $data['inbreeding_policy'] = $data['inbreeding_policy'] ?? 'block_high_risk';

            if ($data['status'] === 'active' && $this->activePeriodExistsForColony(TypeValue::int($data['colony_pen_id'] ?? null))) {
                return $this->error('Peringatan: Tutup periode perkawinan yang masih aktif sebelum membuat periode baru pada koloni ini.');
            }

            $row = BreedingPeriod::query()->create($data);

            return $this->success('Sukses: Periode kawin berhasil disimpan.', $this->loadSummary($row), 201);
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $requestedFields
     * @return array<string, mixed>
     */
    public function update(BreedingPeriod $breedingPeriod, array $data, array $requestedFields): array
    {
        return DB::transaction(function () use ($breedingPeriod, $data, $requestedFields) {
            $penId = TypeValue::int($data['colony_pen_id'] ?? $breedingPeriod->colony_pen_id);
            ColonyPen::query()->whereKey($penId)->lockForUpdate()->first();
            $breedingPeriod = BreedingPeriod::query()->whereKey($breedingPeriod->id)->lockForUpdate()->firstOrFail();
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

            if ($this->periodCodeExists(TypeValue::int($payload['colony_pen_id'] ?? null), TypeValue::string($payload['period_code'] ?? ''), TypeValue::int($breedingPeriod->id))) {
                return $this->error('Peringatan: Kode periode sudah digunakan pada kandang tersebut.');
            }

            $newStatus = $data['status'] ?? $breedingPeriod->status;
            if ($newStatus === 'closed' && $breedingPeriod->females()->whereNull('exit_date')->exists()) {
                return $this->error('Peringatan: Keluarkan seluruh betina dari periode kawin sebelum menutupnya.');
            }
            if ($newStatus === 'active' && $this->activePeriodExistsForColony(TypeValue::int($payload['colony_pen_id'] ?? null), TypeValue::int($breedingPeriod->id))) {
                return $this->error('Peringatan: Koloni kawin ini masih memiliki periode aktif lain. Tutup periode tersebut sebelum mengaktifkan periode ini.');
            }

            $breedingPeriod->fill($data)->save();

            return $this->success('Sukses: Periode kawin berhasil diperbarui.', $this->loadSummary($breedingPeriod));
        }, 3);
    }

    /**
     * @return array<string, mixed>
     */
    public function close(BreedingPeriod $breedingPeriod): array
    {
        return DB::transaction(function () use ($breedingPeriod) {
            $breedingPeriod = BreedingPeriod::query()->whereKey($breedingPeriod->id)->lockForUpdate()->firstOrFail();
            if (($breedingPeriod->status ?? 'active') === 'closed') {
                return $this->error('Peringatan: Periode kawin ini sudah ditutup.');
            }
            if ($breedingPeriod->females()->whereNull('exit_date')->exists()) {
                return $this->error('Peringatan: Keluarkan seluruh betina dari periode kawin sebelum menutupnya.');
            }

            $breedingPeriod->forceFill(['status' => 'closed'])->save();

            return $this->success('Sukses: Periode kawin berhasil ditutup.', $this->loadSummary($breedingPeriod));
        }, 3);
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
        $maleAnimal = Animal::query()->whereKey(TypeValue::int($data['male_animal_id'] ?? null))->lockForUpdate()->first();
        if (! $maleAnimal || $maleAnimal->sex !== 'male' || $maleAnimal->life_status !== 'alive') {
            return $this->error('Peringatan: Tag pejantan harus mengarah ke kambing jantan yang tercatat hidup.');
        }

        $pen = ColonyPen::query()->whereKey(TypeValue::int($data['colony_pen_id'] ?? null))->first();
        if (! $pen || ! $pen->is_active || ! in_array($pen->colony_phase ?? $pen->colony_type, ['koloni_kawin'], true)) {
            return $this->error('Peringatan: Kode kandang harus mengarah ke kandang perkawinan yang masih aktif.');
        }

        $startDate = TypeValue::nullableString($data['start_date'] ?? null);
        $endDate = TypeValue::nullableString($data['end_date'] ?? null);
        if ($endDate !== null && $startDate !== null && $endDate < $startDate) {
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
