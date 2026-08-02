<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\BirthEvent;
use App\Models\PregnancyCheck;
use App\Support\PureBreedSireMarker;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BirthEventService
{
    public function __construct(private readonly PureBreedSireMarker $sireMarker) {}

    public function paginate(Request $request, int $perPage): LengthAwarePaginator
    {
        $query = BirthEvent::query()->with([
            'dam:id,tag_number,sex,life_status',
            'sire:id,tag_number,sex,male_role,life_status',
        ]);

        if ($request->filled('dam_id')) {
            $query->where('dam_id', (int) $request->query('dam_id'));
        }

        if ($request->filled('birth_date')) {
            $query->where('birth_date', $request->query('birth_date'));
        }

        return $query->orderByDesc('birth_date')->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function store(array $data): array
    {
        $validation = $this->validateDamAndSire($data['dam_id'], $data['sire_id'] ?? null);
        if (! $validation['ok']) {
            return $validation;
        }

        /** @var Animal $dam */
        $dam = $validation['dam'];
        $pregnancyCheck = $this->latestPregnantCheck((int) $data['dam_id']);
        if (! $pregnancyCheck) {
            return $this->missingPregnancyError($dam);
        }

        $dateError = $this->validateBirthDateAgainstMating($data['birth_date'], $pregnancyCheck);
        if ($dateError !== null) {
            return $this->error($dateError);
        }

        $expectedSireId = $pregnancyCheck->breedingPeriod?->male_animal_id;
        if ($expectedSireId !== null) {
            $data['sire_id'] = $expectedSireId;
        }

        $row = DB::transaction(function () use ($data, $pregnancyCheck) {
            $birthEvent = BirthEvent::query()->create($data);

            $pregnancyCheck->forceFill(['outcome_status' => 'born'])->save();
            $pregnancyCheck->femaleAnimal?->forceFill([
                'reproductive_status' => 'melahirkan',
                'status_date' => $birthEvent->birth_date?->toDateString(),
            ])->save();

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
        $newDamId = $data['dam_id'] ?? $birthEvent->dam_id;
        $newSireId = array_key_exists('sire_id', $data) ? $data['sire_id'] : $birthEvent->sire_id;

        $validation = $this->validateDamAndSire($newDamId, $newSireId);
        if (! $validation['ok']) {
            return $validation;
        }

        /** @var Animal $dam */
        $dam = $validation['dam'];
        $damChanged = array_key_exists('dam_id', $data) && (int) $data['dam_id'] !== (int) $birthEvent->dam_id;
        $birthDateChanged = array_key_exists('birth_date', $data);

        if ($damChanged) {
            $pregnancyCheck = $this->latestPregnantCheck((int) $newDamId);
            if (! $pregnancyCheck) {
                return $this->missingPregnancyError($dam);
            }

            $dateError = $this->validateBirthDateAgainstMating($data['birth_date'] ?? $birthEvent->birth_date?->toDateString(), $pregnancyCheck);
            if ($dateError !== null) {
                return $this->error($dateError);
            }

            if ($pregnancyCheck->breedingPeriod?->male_animal_id !== null) {
                $data['sire_id'] = $pregnancyCheck->breedingPeriod->male_animal_id;
            }
        } elseif ($birthDateChanged) {
            $pregnancyCheck = $this->latestPregnantCheck((int) $newDamId);
            if ($pregnancyCheck) {
                $dateError = $this->validateBirthDateAgainstMating($data['birth_date'], $pregnancyCheck);
                if ($dateError !== null) {
                    return $this->error($dateError);
                }
            }
        } elseif (array_key_exists('sire_id', $data)) {
            unset($data['sire_id']);
        }

        $birthEvent->fill($data)->save();
        $this->syncOffspringMarkers($birthEvent);

        return $this->success('Sukses: Data kelahiran berhasil diperbarui.', $this->loadSummary($birthEvent));
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
        $dam = Animal::query()->find($damId);
        if (! $dam || $dam->sex !== 'female') {
            return $this->error('Peringatan: Tag induk harus mengarah ke kambing betina.');
        }

        if (! empty($sireId)) {
            if ((int) $sireId === (int) $damId) {
                return $this->error('Peringatan: Tag induk dan tag pejantan tidak boleh menggunakan kambing yang sama.');
            }

            $sire = Animal::query()->find($sireId);
            if (! $sire || $sire->sex !== 'male') {
                return $this->error('Peringatan: Tag pejantan harus mengarah ke kambing jantan.');
            }
        }

        return ['ok' => true, 'dam' => $dam];
    }

    private function latestPregnantCheck(int $damId): ?PregnancyCheck
    {
        return PregnancyCheck::query()
            ->with(['breedingPeriod', 'breedingFemale'])
            ->where('female_animal_id', $damId)
            ->where('is_pregnant', true)
            ->where(function ($query) {
                $query->whereNull('outcome_status')
                    ->orWhere('outcome_status', '!=', 'born');
            })
            ->latest('check_date')
            ->first();
    }

    private function validateBirthDateAgainstMating(?string $birthDate, PregnancyCheck $pregnancyCheck): ?string
    {
        $matingDate = $pregnancyCheck->breedingFemale?->mating_date?->toDateString();

        if ($birthDate && $matingDate && $birthDate < $matingDate) {
            return 'Peringatan: Tanggal lahir tidak boleh lebih awal dari tanggal kawin induk.';
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
