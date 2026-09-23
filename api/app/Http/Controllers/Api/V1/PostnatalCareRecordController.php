<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\OffspringBirth;
use App\Models\PostnatalCareRecord;
use App\Support\TypeValue;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PostnatalCareRecordController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $this->perPage($request);
        $q = PostnatalCareRecord::query()->with(['offspringBirth.birthEvent', 'offspringBirth.offspringAnimal', 'birthEvent', 'targetAnimal']);

        if ($request->filled('birth_event_id')) {
            $q->where('birth_event_id', (int) $request->query('birth_event_id'));
        }

        if ($request->filled('offspring_birth_id')) {
            $q->where('offspring_birth_id', (int) $request->query('offspring_birth_id'));
        }

        if ($request->filled('target_animal_id')) {
            $q->where('target_animal_id', (int) $request->query('target_animal_id'));
        }

        if ($request->filled('care_date')) {
            $q->where('care_date', $request->query('care_date'));
        }

        return response()->json($q->orderByDesc('care_date')->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request, [
            'offspring_birth_id' => ['nullable', 'integer', 'exists:breed_offsprings,id'],
            'birth_event_id' => ['required_without:offspring_birth_id', 'integer', 'exists:breed_births,id'],
            'target_animal_id' => ['required_without:offspring_birth_id', 'integer', 'exists:animals,id'],
            'care_date' => ['nullable', 'date', 'before_or_equal:today'],
            'administration_method' => ['nullable', 'string', 'max:100'],
            'volume_ml' => ['nullable', 'numeric', 'min:0'],
            'navel_iodine_status' => ['nullable', 'string', 'max:50'],
            'vitamin_ade_ml' => ['nullable', 'numeric', 'min:0'],
            'vitamin_b_complex_ml' => ['nullable', 'numeric', 'min:0'],
            'intracin_ml' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $offspringBirth = $this->resolveOffspringBirth($data);
        if ($offspringBirth instanceof JsonResponse) {
            return $offspringBirth;
        }

        try {
            return DB::transaction(function () use ($offspringBirth, $data): JsonResponse {
                $animal = Animal::query()->whereKey($offspringBirth->offspring_animal_id)->lockForUpdate()->firstOrFail();
                $offspringBirth = OffspringBirth::query()->with('birthEvent')->findOrFail($offspringBirth->id);
                $data['offspring_birth_id'] = $offspringBirth->id;
                $data['birth_event_id'] = $offspringBirth->birth_event_id;
                $data['target_animal_id'] = $animal->id;
                $data['care_date'] = $data['care_date'] ?? $offspringBirth->birthEvent?->birth_date?->toDateString() ?? now()->toDateString();

                if ($error = $this->careDateError(TypeValue::nullableString($data['care_date']), $offspringBirth->birthEvent?->birth_date?->toDateString(), $animal)) {
                    return response()->json(['message' => $error], 422);
                }

                if (PostnatalCareRecord::query()->where('offspring_birth_id', $offspringBirth->id)->exists()) {
                    return response()->json(['message' => 'Peringatan: Data perawatan pascalahir untuk cempe tersebut sudah pernah dicatat.'], 422);
                }

                $row = PostnatalCareRecord::query()->create($data);

                return response()->json([
                    'message' => 'Sukses: Data perawatan pascalahir berhasil disimpan.',
                    'data' => $this->loadRelations($row),
                ], 201);
            }, 3);
        } catch (QueryException $exception) {
            if (! str_contains($exception->getMessage(), 'uq_postnatal_care_offspring_birth')
                && ! str_contains($exception->getMessage(), 'med_postnatal_cares.offspring_birth_id')) {
                throw $exception;
            }

            return response()->json(['message' => 'Peringatan: Data perawatan pascalahir untuk cempe tersebut sudah pernah dicatat.'], 422);
        }
    }

    public function show(PostnatalCareRecord $postnatalCareRecord): JsonResponse
    {
        return response()->json($this->loadRelations($postnatalCareRecord));
    }

    public function update(Request $request, PostnatalCareRecord $postnatalCareRecord): JsonResponse
    {
        $data = $this->validated($request, [
            'care_date' => ['sometimes', 'date', 'before_or_equal:today'],
            'administration_method' => ['nullable', 'string', 'max:100'],
            'volume_ml' => ['nullable', 'numeric', 'min:0'],
            'navel_iodine_status' => ['nullable', 'string', 'max:50'],
            'vitamin_ade_ml' => ['nullable', 'numeric', 'min:0'],
            'vitamin_b_complex_ml' => ['nullable', 'numeric', 'min:0'],
            'intracin_ml' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($postnatalCareRecord, $data): JsonResponse {
            $animal = Animal::query()->whereKey($postnatalCareRecord->target_animal_id)->lockForUpdate()->firstOrFail();
            $record = PostnatalCareRecord::query()->with('birthEvent')->findOrFail($postnatalCareRecord->id);
            $newCareDate = TypeValue::nullableString($data['care_date'] ?? null) ?? $record->care_date?->toDateString();

            if ($error = $this->careDateError($newCareDate, $record->birthEvent?->birth_date?->toDateString(), $animal)) {
                return response()->json(['message' => $error], 422);
            }

            $record->fill($data)->save();

            return response()->json([
                'message' => 'Sukses: Data perawatan pascalahir berhasil diperbarui.',
                'data' => $this->loadRelations($record),
            ]);
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveOffspringBirth(array $data): OffspringBirth|JsonResponse
    {
        $offspringBirth = isset($data['offspring_birth_id'])
            ? OffspringBirth::query()->with(['birthEvent', 'offspringAnimal'])->whereKey($this->intValue($data['offspring_birth_id']))->first()
            : OffspringBirth::query()
                ->with(['birthEvent', 'offspringAnimal'])
                ->where('birth_event_id', $this->intValue($data['birth_event_id']))
                ->where('offspring_animal_id', $this->intValue($data['target_animal_id']))
                ->first();

        if (! $offspringBirth) {
            return response()->json([
                'message' => 'Peringatan: Tag cempe yang dipilih belum tercatat pada data cempe lahir.',
            ], 422);
        }

        return $offspringBirth;
    }

    private function loadRelations(PostnatalCareRecord $postnatalCareRecord): PostnatalCareRecord
    {
        return $postnatalCareRecord->load([
            'offspringBirth.birthEvent',
            'offspringBirth.offspringAnimal',
            'birthEvent',
            'targetAnimal',
        ]);
    }

    private function careDateError(?string $date, ?string $birthDate, Animal $animal): ?string
    {
        if ($birthDate === null) {
            return 'Peringatan: Data kelahiran untuk cempe yang dipilih tidak ditemukan.';
        }
        if ($date === null || $date < $birthDate) {
            return 'Peringatan: Tanggal perawatan tidak boleh lebih awal dari tanggal kelahiran cempe.';
        }
        if ($date > now()->toDateString()) {
            return 'Peringatan: Tanggal perawatan tidak boleh setelah hari ini.';
        }
        if ($animal->life_status === 'dead' && $animal->status_date && $date > $animal->status_date->toDateString()) {
            return 'Peringatan: Tanggal perawatan tidak boleh setelah tanggal kematian cempe.';
        }

        return null;
    }
}
