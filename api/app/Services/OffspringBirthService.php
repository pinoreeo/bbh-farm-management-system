<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\BirthEvent;
use App\Models\OffspringBirth;
use App\Models\WeightRecord;
use App\Support\AnimalEartag;
use App\Support\PureBreedSireMarker;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OffspringBirthService
{
    public function __construct(
        private readonly PureBreedSireMarker $sireMarker,
        private readonly AnimalEartag $eartag,
    ) {}

    public function paginate(Request $request, int $perPage): LengthAwarePaginator
    {
        $query = OffspringBirth::query()->with(['birthEvent', 'offspringAnimal']);

        if ($request->filled('birth_event_id')) {
            $query->where('birth_event_id', (int) $request->query('birth_event_id'));
        }

        if ($request->filled('offspring_animal_id')) {
            $query->where('offspring_animal_id', (int) $request->query('offspring_animal_id'));
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function store(array $data): array
    {
        $birthEvent = BirthEvent::query()->find($data['birth_event_id']);
        if (! $birthEvent) {
            return $this->error('Peringatan: Data kelahiran yang dipilih tidak ditemukan. Muat ulang halaman lalu pilih data kelahiran yang tersedia.');
        }

        $animal = isset($data['offspring_animal_id']) ? Animal::query()->find($data['offspring_animal_id']) : null;
        $validation = $this->validateExistingAnimal($data, $animal, $birthEvent);
        if (! $validation['ok']) {
            return $validation;
        }

        $birthStatus = $data['birth_status'] ?? 'alive';
        $data['birth_status'] = $birthStatus;

        $row = DB::transaction(function () use ($data, $animal, $birthEvent, $birthStatus) {
            if (! $animal) {
                $animal = $this->createOffspringAnimal(
                    $data,
                    $birthEvent,
                    $birthStatus,
                    $this->sireMarker->markerForSire($birthEvent->sire),
                );
                $data['offspring_animal_id'] = $animal->id;
            }

            if ($birthStatus === 'dead' && $animal->life_status !== 'dead') {
                $animal->forceFill(['life_status' => 'dead'])->save();
            }

            $offspringBirth = OffspringBirth::query()->create($data);
            $this->sireMarker->syncOffspring($animal, $birthEvent);

            $this->syncBirthWeight(
                (int) $data['offspring_animal_id'],
                $birthEvent->birth_date?->toDateString() ?? now()->toDateString(),
                $data['birth_weight_kg'],
            );

            return $offspringBirth;
        });

        return $this->success('Sukses: Data cempe lahir berhasil disimpan.', $this->loadSummary($row), 201);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(OffspringBirth $offspringBirth, array $data): array
    {
        DB::transaction(function () use ($data, $offspringBirth) {
            $offspringBirth->fill($data)->save();

            if (($data['birth_status'] ?? null) === 'dead' && $offspringBirth->offspringAnimal?->life_status !== 'dead') {
                $offspringBirth->offspringAnimal->forceFill(['life_status' => 'dead'])->save();
            }

            if (array_key_exists('birth_weight_kg', $data)) {
                $this->syncBirthWeight(
                    (int) $offspringBirth->offspring_animal_id,
                    $offspringBirth->birthEvent?->birth_date?->toDateString() ?? now()->toDateString(),
                    $data['birth_weight_kg'],
                );
            }
        });

        return $this->success('Sukses: Data cempe lahir berhasil diperbarui.', $this->loadSummary($offspringBirth));
    }

    public function loadSummary(OffspringBirth $offspringBirth): OffspringBirth
    {
        return $offspringBirth->load(['birthEvent', 'offspringAnimal']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validateExistingAnimal(array $data, ?Animal $animal, BirthEvent $birthEvent): array
    {
        if (isset($data['offspring_animal_id']) && ! $animal) {
            return $this->error('Peringatan: Tag kambing yang dipilih tidak valid atau datanya sudah tidak tersedia.');
        }

        if (! $animal) {
            return ['ok' => true];
        }

        if (OffspringBirth::query()->where('offspring_animal_id', $data['offspring_animal_id'])->exists()) {
            return $this->error("Peringatan: Kambing dengan tag {$animal->tag_number} sudah tercatat pada data kelahiran lain.");
        }

        if (OffspringBirth::query()
            ->where('birth_event_id', $data['birth_event_id'])
            ->where('offspring_animal_id', $data['offspring_animal_id'])
            ->exists()) {
            return $this->error("Peringatan: Kambing dengan tag {$animal->tag_number} sudah tercatat sebagai anak pada data kelahiran ini.");
        }

        if ($animal->birth_date && $birthEvent->birth_date && $animal->birth_date->toDateString() !== $birthEvent->birth_date->toDateString()) {
            return $this->error("Peringatan: Tanggal lahir kambing {$animal->tag_number} harus sama dengan tanggal pada data kelahiran yang dipilih.");
        }

        return ['ok' => true];
    }

    private function syncBirthWeight(int $animalId, string $recordDate, float|int|string $weightKg): void
    {
        $payload = [
            'animal_id' => $animalId,
            'record_date' => $recordDate,
            'weight_kg' => $weightKg,
            'notes' => 'Bobot lahir dari data kelahiran.',
        ];

        $record = WeightRecord::query()
            ->where('animal_id', $animalId)
            ->whereDate('record_date', $recordDate)
            ->first();

        $record ? $record->fill($payload)->save() : WeightRecord::query()->create($payload);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createOffspringAnimal(
        array $data,
        BirthEvent $birthEvent,
        string $birthStatus,
        ?string $jantanPemacek,
    ): Animal {
        return Animal::query()->create([
            'tag_number' => $data['tag_number'] ?? $this->eartag->next($birthEvent->birth_date?->toDateString(), $jantanPemacek),
            'breed_id' => $data['breed_id'],
            'sex' => $data['sex'],
            'male_role' => $jantanPemacek,
            'generation' => $data['generation'],
            'birth_date' => $birthEvent->birth_date?->toDateString(),
            'birth_place' => $birthEvent->birth_place,
            'life_status' => $birthStatus === 'dead' ? 'dead' : 'alive',
            'notes' => $data['notes'] ?? null,
            'is_impor' => false,
            'origin_type' => 'internal_birth',
            'origin_detail' => 'Tercatat melalui data kelahiran internal.',
        ]);
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
