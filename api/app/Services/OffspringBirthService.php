<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\BirthEvent;
use App\Models\OffspringBirth;
use App\Models\WeightRecord;
use App\Support\AnimalEartag;
use App\Support\PureBreedSireMarker;
use App\Support\TypeValue;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OffspringBirthService
{
    public function __construct(
        private readonly PureBreedSireMarker $sireMarker,
        private readonly AnimalEartag $eartag,
        private readonly AnimalService $animals,
    ) {}

    /**
     * @return LengthAwarePaginator<int, OffspringBirth>
     */
    public function paginate(Request $request, int $perPage): LengthAwarePaginator
    {
        $query = OffspringBirth::query()->with(['birthEvent', 'offspringAnimal']);

        if ($request->filled('birth_event_id')) {
            $query->where('birth_event_id', TypeValue::int($request->query('birth_event_id')));
        }

        if ($request->filled('offspring_animal_id')) {
            $query->where('offspring_animal_id', TypeValue::int($request->query('offspring_animal_id')));
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function store(array $data): array
    {
        return DB::transaction(fn () => $this->storeLocked($data), 3);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function storeLocked(array $data): array
    {
        $birthEventId = TypeValue::int($data['birth_event_id'] ?? null);
        $birthEvent = BirthEvent::query()->whereKey($birthEventId)->lockForUpdate()->first();
        if (! $birthEvent) {
            return $this->error('Peringatan: Data kelahiran yang dipilih tidak ditemukan. Muat ulang halaman lalu pilih data kelahiran yang tersedia.');
        }

        if ($this->birthEventIsFull($birthEvent)) {
            return $this->error('Peringatan: Jumlah cempe tidak boleh melebihi jumlah anak pada catatan kelahiran.');
        }

        $offspringAnimalId = isset($data['offspring_animal_id'])
            ? TypeValue::int($data['offspring_animal_id'])
            : null;
        $animal = $offspringAnimalId ? Animal::query()->whereKey($offspringAnimalId)->first() : null;
        $validation = $this->validateExistingAnimal($data, $animal, $birthEvent);
        if (! $validation['ok']) {
            return $validation;
        }

        $birthStatus = TypeValue::nullableString($data['birth_status'] ?? null) ?? 'alive';
        $data['birth_status'] = $birthStatus;

        $row = DB::transaction(function () use ($data, $animal, $birthEvent, $birthStatus) {
            if (! $animal) {
                $animal = $this->createOffspringAnimal(
                    $data,
                    $birthEvent,
                    $birthStatus,
                    $this->sireMarker->markerForSire($birthEvent->sire),
                );
                $data['offspring_animal_id'] = TypeValue::int($animal->id);
            }

            $this->animals->syncBirthLifeStatus(
                $animal, $birthStatus, $birthEvent->birth_date?->toDateString() ?? now()->toDateString(), false,
            );

            $offspringBirth = OffspringBirth::query()->create($data);
            $this->sireMarker->syncOffspring($animal, $birthEvent);

            $this->syncBirthWeight(
                TypeValue::int($data['offspring_animal_id'] ?? null),
                $birthEvent->birth_date?->toDateString() ?? now()->toDateString(),
                TypeValue::number($data['birth_weight_kg'] ?? null),
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
            $wasDeadAtBirth = $offspringBirth->birth_status === 'dead';
            $offspringBirth->fill($data)->save();

            $offspringAnimal = $offspringBirth->offspringAnimal;
            if (array_key_exists('birth_status', $data) && $offspringAnimal) {
                $this->animals->syncBirthLifeStatus(
                    $offspringAnimal,
                    TypeValue::string($data['birth_status']),
                    $offspringBirth->birthEvent?->birth_date?->toDateString() ?? now()->toDateString(),
                    $wasDeadAtBirth,
                );
            }

            if (array_key_exists('birth_weight_kg', $data)) {
                $this->syncBirthWeight(
                    TypeValue::int($offspringBirth->offspring_animal_id),
                    $offspringBirth->birthEvent?->birth_date?->toDateString() ?? now()->toDateString(),
                    TypeValue::number($data['birth_weight_kg'] ?? null),
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

        $offspringAnimalId = TypeValue::int($data['offspring_animal_id'] ?? null);
        $birthEventId = TypeValue::int($data['birth_event_id'] ?? null);

        if (OffspringBirth::query()->where('offspring_animal_id', $offspringAnimalId)->exists()) {
            return $this->error("Peringatan: Kambing dengan tag {$animal->tag_number} sudah tercatat pada data kelahiran lain.");
        }

        if (OffspringBirth::query()
            ->where('birth_event_id', $birthEventId)
            ->where('offspring_animal_id', $offspringAnimalId)
            ->exists()) {
            return $this->error("Peringatan: Kambing dengan tag {$animal->tag_number} sudah tercatat sebagai anak pada data kelahiran ini.");
        }

        if ($animal->birth_date && $birthEvent->birth_date && $animal->birth_date->toDateString() !== $birthEvent->birth_date->toDateString()) {
            return $this->error("Peringatan: Tanggal lahir kambing {$animal->tag_number} harus sama dengan tanggal pada data kelahiran yang dipilih.");
        }

        return ['ok' => true];
    }

    private function birthEventIsFull(BirthEvent $birthEvent): bool
    {
        return OffspringBirth::query()
            ->where('birth_event_id', $birthEvent->id)
            ->count() >= (int) $birthEvent->offspring_count;
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
        $manualTag = TypeValue::nullableString($data['tag_number'] ?? null);
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $tag = $manualTag ?? $this->eartag->next($birthEvent->birth_date?->toDateString(), $jantanPemacek, $attempt);
            try {
                return DB::transaction(fn (): Animal => Animal::query()->create([
                    'tag_number' => $tag,
                    'breed_id' => TypeValue::int($data['breed_id'] ?? null),
                    'sex' => TypeValue::string($data['sex'] ?? ''),
                    'male_role' => $jantanPemacek,
                    'generation' => TypeValue::string($data['generation'] ?? ''),
                    'birth_date' => $birthEvent->birth_date?->toDateString(),
                    'birth_place' => $birthEvent->birth_place,
                    'life_status' => $birthStatus === 'dead' ? 'dead' : 'alive',
                    'status_date' => $birthStatus === 'dead' ? $birthEvent->birth_date?->toDateString() : null,
                    'notes' => TypeValue::nullableString($data['notes'] ?? null),
                    'is_impor' => false,
                    'origin_type' => 'internal_birth',
                    'origin_detail' => 'Tercatat melalui data kelahiran internal.',
                ]));
            } catch (QueryException $exception) {
                if ($manualTag !== null || ! $this->eartag->isDuplicateTag($exception) || $attempt === 4) {
                    throw $exception;
                }
            }
        }

        throw new \RuntimeException('Nomor eartag belum berhasil dibuat. Silakan coba lagi.');
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
