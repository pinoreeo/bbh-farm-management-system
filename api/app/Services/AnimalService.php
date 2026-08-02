<?php

namespace App\Services;

use App\Models\Animal;
use App\Support\AnimalEartag;
use App\Support\PureBreedSireMarker;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AnimalService
{
    public function __construct(
        private readonly AnimalEartag $eartag,
        private readonly PureBreedSireMarker $sireMarker,
    ) {}

    public function paginate(Request $request, int $perPage): LengthAwarePaginator
    {
        $query = Animal::query()->with([
            'breed:id,breed_name',
            'currentPen:id,pen_code,colony_phase,colony_type,capacity,is_active',
        ]);

        foreach ([
            'life_status',
            'sex',
            'reproductive_status',
            'exit_status',
            'male_role',
            'is_impor',
            'origin_type',
        ] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->query($field));
            }
        }

        foreach (['breed_id', 'current_pen_id'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, (int) $request->query($field));
            }
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where('tag_number', 'like', "%{$search}%");
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function store(Request $request, array $data): array
    {
        unset($data['photo_path']);

        $data['life_status'] = $data['life_status'] ?? 'alive';
        $data['reproductive_status'] = $data['reproductive_status'] ?? 'kosong';
        $data = $this->syncOriginFields($data);
        $data = $this->fillStatusDate($data);
        $data['tag_number'] = $data['tag_number'] ?? $this->eartag->next($data['birth_date'] ?? null);
        $data = $this->storePhotoIfPresent($request, $data);

        $row = Animal::query()->create($data);

        return $this->success('Sukses: Data kambing berhasil disimpan.', $this->loadSummary($row), 201);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(Request $request, Animal $animal, array $data): array
    {
        unset($data['photo_path']);

        $data = $this->syncOriginFields($data, $animal);
        $data = $this->fillStatusDate($data);
        $data = $this->storePhotoIfPresent($request, $data, $animal);

        $animal->fill($data)->save();
        $this->sireMarker->syncFromRecordedBirth($animal);

        return $this->success('Sukses: Data kambing berhasil diperbarui.', $this->loadSummary($animal));
    }

    public function loadDetail(Animal $animal): Animal
    {
        return $animal->load([
            'breed',
            'currentPen',
            'penMovements.fromPen',
            'penMovements.toPen',
            'healthTreatments',
            'vaccinations',
            'weightRecords',
            'breedingFemales.breedingPeriod.maleAnimal',
            'pregnancyChecks',
            'birthEventsAsDam.offspringBirths.offspringAnimal',
            'birthEventsAsSire.offspringBirths.offspringAnimal',
            'offspringBirths.birthEvent.dam',
            'offspringBirths.birthEvent.sire',
        ]);
    }

    private function loadSummary(Animal $animal): Animal
    {
        return $animal->load(['breed', 'currentPen']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function syncOriginFields(array $data, ?Animal $animal = null): array
    {
        $originType = $data['origin_type'] ?? null;

        if (! $originType) {
            if (array_key_exists('is_impor', $data)) {
                $originType = (bool) $data['is_impor'] ? 'import' : 'unknown';
            } else {
                $originType = $animal?->origin_type ?? 'unknown';
            }
        }

        $data['origin_type'] = $originType;
        $data['is_impor'] = $originType === 'import';

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function fillStatusDate(array $data): array
    {
        if (! empty($data['exit_status']) && empty($data['status_date'])) {
            $data['status_date'] = now()->toDateString();
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function storePhotoIfPresent(Request $request, array $data, ?Animal $animal = null): array
    {
        if (! $request->hasFile('photo')) {
            return $data;
        }

        if ($animal?->photo_path) {
            Storage::disk('public')->delete($animal->photo_path);
        }

        $data['photo_path'] = $request->file('photo')->store('animals', 'public');

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function success(string $message, mixed $data, int $status = 200): array
    {
        return compact('message', 'data', 'status') + ['ok' => true];
    }
}
