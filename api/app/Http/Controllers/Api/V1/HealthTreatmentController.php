<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\HealthTreatment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $animal = Animal::find($data['animal_id']);

        if (! $animal || $animal->life_status !== 'alive') {
            return response()->json([
                'message' => 'Peringatan: Catatan kesehatan hanya dapat dibuat untuk kambing yang masih hidup.',
            ], 422);
        }

        if ($animal->birth_date && $data['treatment_date'] < $animal->birth_date->toDateString()) {
            return response()->json([
                'message' => 'Peringatan: Tanggal perawatan tidak boleh lebih awal dari tanggal lahir kambing.',
            ], 422);
        }

        $exists = HealthTreatment::query()
            ->where('animal_id', $data['animal_id'])
            ->where('treatment_group', $data['treatment_group'])
            ->where('product_name', $data['product_name'])
            ->whereDate('treatment_date', $data['treatment_date'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Peringatan: Catatan kesehatan untuk kambing, jenis perawatan, produk, dan tanggal tersebut sudah ada.',
            ], 422);
        }

        $row = HealthTreatment::create($data);

        return response()->json([
            'message' => 'Sukses: Catatan kesehatan berhasil disimpan.',
            'data' => $row->load('animal'),
        ], 201);
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

        $animal = $healthTreatment->animal;

        if (! $animal || $animal->life_status !== 'alive') {
            return response()->json([
                'message' => 'Peringatan: Catatan kesehatan hanya dapat dibuat untuk kambing yang masih hidup.',
            ], 422);
        }

        $newTreatmentGroup = $data['treatment_group'] ?? $healthTreatment->treatment_group;
        $newProductName = $data['product_name'] ?? $healthTreatment->product_name;
        $newTreatmentDate = $data['treatment_date'] ?? $healthTreatment->treatment_date?->toDateString();

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

        $healthTreatment->fill($data)->save();

        return response()->json([
            'message' => 'Sukses: Catatan kesehatan berhasil diperbarui.',
            'data' => $healthTreatment->load('animal'),
        ]);
    }
}
