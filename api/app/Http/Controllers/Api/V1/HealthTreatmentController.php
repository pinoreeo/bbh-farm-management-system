<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\HealthTreatment;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HealthTreatmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $this->perPage($request);
        $q = HealthTreatment::query()->with('animal:id,tag_number,sex,life_status,birth_date');

        if ($request->filled('animal_id')) {
            $q->where('animal_id', (int) $request->query('animal_id'));
        }

        if ($request->filled('treatment_date')) {
            $q->where('treatment_date', $request->query('treatment_date'));
        }

        if ($request->filled('treatment_group')) {
            $q->where('treatment_group', $request->query('treatment_group'));
        }

        return response()->json(
            $q->orderByDesc('treatment_date')
                ->orderByDesc('id')
                ->paginate($perPage)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request, [
            'animal_id' => ['required', 'integer', 'exists:animals,id'],
            'treatment_group' => ['required', 'string', 'max:100'],
            'product_name' => ['required', 'string', 'max:255'],
            'treatment_date' => ['required', 'date', 'before_or_equal:today'],
            'symptoms' => ['nullable', 'string'],
            'diagnosis' => ['nullable', 'string'],
            'dosage' => ['nullable', 'string', 'max:100'],
            'administration_route' => ['nullable', 'string', 'max:100'],
            'action_category' => ['nullable', 'string', 'max:100'],
            'handled_by' => ['nullable', 'string', 'max:255'],
            'next_control_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $animalId = $this->intValue($data['animal_id']);
        $treatmentGroup = $this->stringValue($data['treatment_group']);
        $productName = $this->stringValue($data['product_name']);
        $treatmentDate = $this->stringValue($data['treatment_date']);

        return DB::transaction(function () use ($data, $animalId, $treatmentGroup, $productName, $treatmentDate): JsonResponse {
            $animal = Animal::query()->whereKey($animalId)->lockForUpdate()->first();

            if (! $animal || ($animal->life_status !== 'alive'
                && (! $animal->status_date || $treatmentDate > $animal->status_date->toDateString()))) {
                return response()->json([
                    'message' => 'Peringatan: Tanggal perawatan tidak boleh setelah tanggal kematian kambing.',
                ], 422);
            }

            if ($animal->birth_date && $treatmentDate < $animal->birth_date->toDateString()) {
                return response()->json([
                    'message' => 'Peringatan: Tanggal perawatan tidak boleh lebih awal dari tanggal lahir kambing.',
                ], 422);
            }

            $exists = HealthTreatment::query()
                ->where('animal_id', $animalId)
                ->where('treatment_group', $treatmentGroup)
                ->where('product_name', $productName)
                ->whereDate('treatment_date', $treatmentDate)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'Peringatan: Catatan kesehatan untuk kambing, jenis perawatan, produk, dan tanggal tersebut sudah ada.',
                ], 422);
            }

            try {
                $row = HealthTreatment::create($data);
            } catch (QueryException $e) {
                if (! $this->isDuplicateKey($e)) {
                    throw $e;
                }

                return response()->json(['message' => 'Peringatan: Catatan kesehatan untuk kambing, jenis perawatan, produk, dan tanggal tersebut sudah ada.'], 422);
            }

            return response()->json([
                'message' => 'Sukses: Catatan kesehatan berhasil disimpan.',
                'data' => $row->load('animal'),
            ], 201);
        }, 3);
    }

    public function show(HealthTreatment $healthTreatment): JsonResponse
    {
        return response()->json($healthTreatment->load('animal'));
    }

    public function update(Request $request, HealthTreatment $healthTreatment): JsonResponse
    {
        $data = $this->validated($request, [
            'treatment_group' => ['sometimes', 'string', 'max:100'],
            'product_name' => ['sometimes', 'string', 'max:255'],
            'treatment_date' => ['sometimes', 'date', 'before_or_equal:today'],
            'symptoms' => ['nullable', 'string'],
            'diagnosis' => ['nullable', 'string'],
            'dosage' => ['nullable', 'string', 'max:100'],
            'administration_route' => ['nullable', 'string', 'max:100'],
            'action_category' => ['nullable', 'string', 'max:100'],
            'handled_by' => ['nullable', 'string', 'max:255'],
            'next_control_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($data, $healthTreatment): JsonResponse {
            $animal = Animal::query()->whereKey($healthTreatment->animal_id)->lockForUpdate()->first();
            $healthTreatment = HealthTreatment::query()->whereKey($healthTreatment->id)->lockForUpdate()->firstOrFail();

            $newTreatmentDate = $this->stringValue($data['treatment_date'] ?? $healthTreatment->treatment_date?->toDateString());
            if (! $animal || ($animal->life_status !== 'alive'
                && (! $animal->status_date || $newTreatmentDate > $animal->status_date->toDateString()))) {
                return response()->json([
                    'message' => 'Peringatan: Catatan kesehatan hanya dapat diperbaiki untuk tanggal sebelum atau saat kambing mati.',
                ], 422);
            }

            $newTreatmentGroup = $this->stringValue($data['treatment_group'] ?? $healthTreatment->treatment_group);
            $newProductName = $this->stringValue($data['product_name'] ?? $healthTreatment->product_name);

            if ($animal->birth_date && $newTreatmentDate < $animal->birth_date->toDateString()) {
                return response()->json([
                    'message' => 'Peringatan: Tanggal perawatan tidak boleh lebih awal dari tanggal lahir kambing.',
                ], 422);
            }

            $exists = HealthTreatment::query()
                ->where('id', '!=', $healthTreatment->id)
                ->where('animal_id', $healthTreatment->animal_id)
                ->where('treatment_group', $newTreatmentGroup)
                ->where('product_name', $newProductName)
                ->whereDate('treatment_date', $newTreatmentDate)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'Peringatan: Catatan kesehatan lain untuk kambing, jenis perawatan, produk, dan tanggal tersebut sudah ada.',
                ], 422);
            }

            try {
                $healthTreatment->fill($data)->save();
            } catch (QueryException $e) {
                if (! $this->isDuplicateKey($e)) {
                    throw $e;
                }

                return response()->json(['message' => 'Peringatan: Catatan kesehatan lain untuk kambing, jenis perawatan, produk, dan tanggal tersebut sudah ada.'], 422);
            }

            return response()->json([
                'message' => 'Sukses: Catatan kesehatan berhasil diperbarui.',
                'data' => $healthTreatment->load('animal'),
            ]);
        }, 3);
    }

    private function isDuplicateKey(QueryException $exception): bool
    {
        return in_array((string) $exception->getCode(), ['23000', '23505', '19'], true);
    }
}
