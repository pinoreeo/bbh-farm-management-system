<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\BirthEvent;
use App\Models\PregnancyCheck;
use App\Support\PureBreedSireMarker;
use App\Support\TypeValue;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BirthEventService
{
    public function __construct(
        private readonly PureBreedSireMarker $sireMarker,
        private readonly ReproductiveStatusService $statuses,
    ) {}

    /**
     * @return LengthAwarePaginator<int, BirthEvent>
     */
    public function paginate(Request $request, int $perPage): LengthAwarePaginator
    {
        $query = BirthEvent::query()->with([
            'dam:id,tag_number,sex,life_status',
            'sire:id,tag_number,sex,male_role,life_status',
        ]);

        if ($request->filled('dam_id')) {
            $query->where('dam_id', TypeValue::int($request->query('dam_id')));
        }

        if ($request->filled('birth_date')) {
            $query->where('birth_date', TypeValue::string($request->query('birth_date')));
        }

        return $query->orderByDesc('birth_date')->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function store(array $data): array
    {
        return DB::transaction(function () use ($data) {
            Animal::query()->whereKey(TypeValue::int($data['dam_id'] ?? null))->lockForUpdate()->firstOrFail();

            return $this->storeLocked($data);
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function storeLocked(array $data): array
    {
        $damId = TypeValue::int($data['dam_id'] ?? null);
        $sireId = isset($data['sire_id']) ? TypeValue::int($data['sire_id']) : null;

        $validation = $this->validateDamAndSire($damId, $sireId);
        if (! $validation['ok']) {
            return $validation;
        }

        $dam = $validation['dam'] ?? null;
        if (! $dam instanceof Animal) {
            return $this->error('Peringatan: Tag induk harus mengarah ke kambing betina.');
        }

        $pregnancyCheck = $this->latestPregnantCheck($damId);
        if (! $pregnancyCheck) {
            return $this->missingPregnancyError($dam);
        }

        $dateError = $this->validateBirthDateAgainstMating(TypeValue::nullableString($data['birth_date'] ?? null), $pregnancyCheck);
        if ($dateError !== null) {
            return $this->error($dateError);
        }

        $expectedSireId = $pregnancyCheck->breedingPeriod?->male_animal_id;
        if ($expectedSireId !== null) {
            $data['sire_id'] = $expectedSireId;
        }

        $data['breeding_female_id'] = $pregnancyCheck->breeding_female_id;
        $row = DB::transaction(function () use ($data, $pregnancyCheck, $dam) {
            $birthEvent = BirthEvent::query()->create($data);

            PregnancyCheck::query()->where('breeding_female_id', $pregnancyCheck->breeding_female_id)
                ->update(['outcome_status' => 'born']);
            $this->statuses->sync($dam);

            return $birthEvent;
        });

        return $this->success('Sukses: Data kelahiran berhasil disimpan.', $this->loadSummary($row), 201);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(BirthEvent $birthEvent, array $data): array
    {
        return DB::transaction(function () use ($birthEvent, $data) {
            $dam = Animal::query()->whereKey($birthEvent->dam_id)->lockForUpdate()->firstOrFail();
            $birthEvent = BirthEvent::query()->whereKey($birthEvent->id)->lockForUpdate()->firstOrFail();

            foreach (['dam_id', 'sire_id'] as $field) {
                if (array_key_exists($field, $data)
                    && TypeValue::nullableString($data[$field]) !== TypeValue::nullableString($birthEvent->$field)) {
                    return $this->error('Peringatan: Induk dan pejantan pada catatan kelahiran tidak dapat diganti.');
                }
            }

            $childCount = $birthEvent->offspringBirths()->count();
            if (isset($data['offspring_count']) && TypeValue::int($data['offspring_count']) < $childCount) {
                return $this->error('Peringatan: Jumlah anak tidak boleh kurang dari jumlah cempe yang sudah dicatat.');
            }

            $oldDate = $birthEvent->birth_date->toDateString();
            $dateChanged = isset($data['birth_date']) && $data['birth_date'] !== $oldDate;
            if ($dateChanged) {
                if ($childCount > 0 || $birthEvent->postnatalCareRecords()->exists() || $birthEvent->certificates()->exists()) {
                    return $this->error('Peringatan: Tanggal lahir tidak dapat diubah karena sudah memiliki catatan cempe, perawatan, atau sertifikat.');
                }

                $registration = $birthEvent->breedingFemale;
                if (! $registration) {
                    return $this->error('Peringatan: Tanggal lahir belum dapat diubah karena catatan kelahiran lama belum terhubung ke periode perkawinan.');
                }

                $lastCheck = PregnancyCheck::query()->where('breeding_female_id', $registration->id)
                    ->orderByDesc('check_date')->first();
                if (($registration->mating_date && $data['birth_date'] < $registration->mating_date->toDateString())
                    || ($lastCheck && $data['birth_date'] < $lastCheck->check_date->toDateString())) {
                    return $this->error('Peringatan: Tanggal lahir tidak boleh sebelum tanggal kawin atau pemeriksaan kebuntingan.');
                }
            }

            $birthEvent->fill($data)->save();
            $this->syncOffspringMarkers($birthEvent);
            if ($dateChanged) {
                $this->statuses->sync($dam, $oldDate);
            }

            return $this->success('Sukses: Data kelahiran berhasil diperbarui.', $this->loadSummary($birthEvent));
        }, 3);
    }

    public function loadDetail(BirthEvent $birthEvent): BirthEvent
    {
        return $birthEvent->load(['dam', 'sire', 'offspringBirths', 'postnatalCareRecords']);
    }

    private function loadSummary(BirthEvent $birthEvent): BirthEvent
    {
        return $birthEvent->load(['dam', 'sire']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateDamAndSire(int|string|null $damId, int|string|null $sireId): array
    {
        $dam = $damId === null ? null : Animal::query()->whereKey($damId)->first();
        if (! $dam || $dam->sex !== 'female') {
            return $this->error('Peringatan: Tag induk harus mengarah ke kambing betina.');
        }

        if ($dam->life_status !== 'alive') {
            return $this->error("Peringatan: Kelahiran hanya dapat dicatat untuk induk {$dam->tag_number} yang masih hidup.");
        }

        if (! empty($sireId)) {
            if (TypeValue::int($sireId) === TypeValue::int($damId)) {
                return $this->error('Peringatan: Tag induk dan tag pejantan tidak boleh menggunakan kambing yang sama.');
            }

            $sire = Animal::query()->whereKey(TypeValue::int($sireId))->first();
            if (! $sire || $sire->sex !== 'male') {
                return $this->error('Peringatan: Tag pejantan harus mengarah ke kambing jantan.');
            }

            if ($sire->life_status !== 'alive') {
                return $this->error("Peringatan: Tag pejantan {$sire->tag_number} sudah tidak berstatus hidup.");
            }
        }

        return ['ok' => true, 'dam' => $dam];
    }

    private function latestPregnantCheck(int $damId): ?PregnancyCheck
    {
        $check = PregnancyCheck::query()
            ->with(['breedingPeriod', 'breedingFemale'])
            ->where('female_animal_id', $damId)
            ->orderByDesc('check_date')->orderByDesc('id')->first();

        if (! $check || ! $check->is_pregnant
            || PregnancyCheck::query()->where('breeding_female_id', $check->breeding_female_id)->where('outcome_status', 'born')->exists()
            || BirthEvent::query()->where('breeding_female_id', $check->breeding_female_id)->exists()) {
            return null;
        }

        return $check;
    }

    private function validateBirthDateAgainstMating(?string $birthDate, PregnancyCheck $pregnancyCheck): ?string
    {
        $matingDate = $pregnancyCheck->breedingFemale?->mating_date?->toDateString();

        if ($birthDate && $matingDate && $birthDate < $matingDate) {
            return 'Peringatan: Tanggal lahir tidak boleh lebih awal dari tanggal kawin induk.';
        }

        if ($birthDate && $birthDate < $pregnancyCheck->check_date->toDateString()) {
            return 'Peringatan: Tanggal lahir tidak boleh sebelum pemeriksaan kebuntingan.';
        }

        return null;
    }

    private function syncOffspringMarkers(BirthEvent $birthEvent): void
    {
        $birthEvent->load(['sire', 'offspringBirths.offspringAnimal']);

        foreach ($birthEvent->offspringBirths as $offspringBirth) {
            if ($offspringBirth->offspringAnimal) {
                $this->sireMarker->syncOffspring($offspringBirth->offspringAnimal, $birthEvent);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function missingPregnancyError(Animal $dam): array
    {
        return $this->error("Peringatan: Kelahiran hanya dapat dicatat untuk induk {$dam->tag_number} yang sudah berstatus bunting dan belum tercatat melahirkan.");
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
