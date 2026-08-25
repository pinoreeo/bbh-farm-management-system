<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\OffspringBirth;
use App\Models\PostnatalCareRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            'care_date' => ['nullable', 'date'],
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

        $data['offspring_birth_id'] = $offspringBirth->id;
        $data['birth_event_id'] = $offspringBirth->birth_event_id;
        $data['target_animal_id'] = $offspringBirth->offspring_animal_id;

        $birthEvent = $offspringBirth->birthEvent;
        if (! $birthEvent) {
            return response()->json([
                'message' => 'Peringatan: Data kelahiran untuk cempe yang dipilih tidak ditemukan.',
            ], 422);
        }

        $data['care_date'] = $data['care_date'] ?? $birthEvent->birth_date?->toDateString() ?? now()->toDateString();

        if ($birthEvent->birth_date && $data['care_date'] < $birthEvent->birth_date->toDateString()) {
            return response()->json([
                'message' => 'Peringatan: Tanggal perawatan tidak boleh lebih awal dari tanggal kelahiran cempe.',
            ], 422);
        }

        $exists = PostnatalCareRecord::query()
            ->where('offspring_birth_id', $data['offspring_birth_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Peringatan: Data perawatan pascalahir untuk cempe tersebut sudah pernah dicatat.',
            ], 422);
        }

        $row = PostnatalCareRecord::create($data);

        return response()->json([
            'message' => 'Sukses: Data perawatan pascalahir berhasil disimpan.',
            'data' => $this->loadRelations($row),
        ], 201);
    }

    public function show(PostnatalCareRecord $postnatalCareRecord): JsonResponse
    {
        return response()->json($this->loadRelations($postnatalCareRecord));
    }

    public function update(Request $request, PostnatalCareRecord $postnatalCareRecord): JsonResponse
    {
        $data = $this->validated($request, [
            'care_date' => ['sometimes', 'date'],
            'administration_method' => ['nullable', 'string', 'max:100'],
            'volume_ml' => ['nullable', 'numeric', 'min:0'],
            'navel_iodine_status' => ['nullable', 'string', 'max:50'],
            'vitamin_ade_ml' => ['nullable', 'numeric', 'min:0'],
            'vitamin_b_complex_ml' => ['nullable', 'numeric', 'min:0'],
            'intracin_ml' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $newCareDate = $data['care_date'] ?? $postnatalCareRecord->care_date?->toDateString();

        if ($postnatalCareRecord->birthEvent && $newCareDate < $postnatalCareRecord->birthEvent->birth_date->toDateString()) {
            return response()->json([
                'message' => 'Peringatan: Tanggal perawatan tidak boleh lebih awal dari tanggal kelahiran cempe.',
            ], 422);
        }

        $postnatalCareRecord->fill($data)->save();

        return response()->json([
            'message' => 'Sukses: Data perawatan pascalahir berhasil diperbarui.',
            'data' => $this->loadRelations($postnatalCareRecord),
        ]);
    }

    private function resolveOffspringBirth(array $data): OffspringBirth|JsonResponse
    {
        $offspringBirth = isset($data['offspring_birth_id'])
            ? OffspringBirth::query()->with(['birthEvent', 'offspringAnimal'])->find($data['offspring_birth_id'])
            : OffspringBirth::query()
                ->with(['birthEvent', 'offspringAnimal'])
                ->where('birth_event_id', $data['birth_event_id'])
                ->where('offspring_animal_id', $data['target_animal_id'])
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
}
