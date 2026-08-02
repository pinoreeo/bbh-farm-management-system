<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\Vaccination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VaccinationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $this->perPage($request);
        $q = Vaccination::query()->with('animal:id,tag_number,sex,life_status,birth_date');

        if ($request->filled('animal_id')) {
            $q->where('animal_id', (int) $request->query('animal_id'));
        }

        if ($request->filled('category_name')) {
            $q->where('category_name', 'like', '%'.trim((string) $request->query('category_name')).'%');
        }

        if ($request->filled('vaccination_date')) {
            $q->where('vaccination_date', $request->query('vaccination_date'));
        }

        return response()->json(
            $q->orderByDesc('vaccination_date')
                ->orderByDesc('id')
                ->paginate($perPage)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request, [
            'animal_id' => ['required', 'integer', 'exists:animals,id'],
            'category_name' => ['required', 'string', 'max:100'],
            'vaccination_date' => ['required', 'date', 'before_or_equal:today'],
            'product_name' => ['required', 'string', 'max:255'],
            'dosage' => ['nullable', 'string', 'max:100'],
            'administration_route' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $animal = Animal::find($data['animal_id']);
        if (! $animal || $animal->life_status !== 'alive') {
            return response()->json([
                'message' => 'Peringatan: Data vaksinasi hanya dapat dibuat untuk kambing yang masih hidup.',
            ], 422);
        }

        if ($animal->birth_date && $data['vaccination_date'] < $animal->birth_date->toDateString()) {
            return response()->json([
                'message' => 'Peringatan: Tanggal vaksin tidak boleh lebih awal dari tanggal lahir kambing.',
            ], 422);
        }

        $exists = Vaccination::query()
            ->where('animal_id', $data['animal_id'])
            ->where('category_name', $data['category_name'])
            ->whereDate('vaccination_date', $data['vaccination_date'])
            ->where('product_name', $data['product_name'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Peringatan: Data vaksinasi untuk kambing, jenis vaksin, tanggal, dan produk tersebut sudah ada.',
            ], 422);
        }


        $row = Vaccination::create($data);

        return response()->json([
            'message' => 'Sukses: Data vaksinasi berhasil disimpan.',
            'data' => $row->load(['animal']),
        ], 201);
    }

    public function show(Vaccination $vaccination): JsonResponse
    {
        return response()->json($vaccination->load(['animal']));
    }

    public function update(Request $request, Vaccination $vaccination): JsonResponse
    {
        $data = $this->validated($request, [
            'category_name' => ['sometimes', 'string', 'max:100'],
            'vaccination_date' => ['sometimes', 'date', 'before_or_equal:today'],
            'product_name' => ['sometimes', 'string', 'max:255'],
            'dosage' => ['nullable', 'string', 'max:100'],
            'administration_route' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $animal = $vaccination->animal;
        if (! $animal || $animal->life_status !== 'alive') {
            return response()->json([
                'message' => 'Peringatan: Data vaksinasi hanya dapat dibuat untuk kambing yang masih hidup.',
            ], 422);
        }

        $newCategoryName = $data['category_name'] ?? $vaccination->category_name;
        $newVaccinationDate = $data['vaccination_date'] ?? $vaccination->vaccination_date?->toDateString();
        $newProductName = $data['product_name'] ?? $vaccination->product_name;

        if ($animal->birth_date && $newVaccinationDate < $animal->birth_date->toDateString()) {
            return response()->json([
                'message' => 'Peringatan: Tanggal vaksin tidak boleh lebih awal dari tanggal lahir kambing.',
            ], 422);
        }

        $exists = Vaccination::query()
            ->where('id', '!=', $vaccination->id)
            ->where('animal_id', $vaccination->animal_id)
            ->where('category_name', $newCategoryName)
            ->whereDate('vaccination_date', $newVaccinationDate)
            ->where('product_name', $newProductName)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Peringatan: Data vaksinasi lain untuk kambing, jenis vaksin, tanggal, dan produk tersebut sudah ada.',
            ], 422);
        }

        $vaccination->fill($data)->save();

        return response()->json([
            'message' => 'Sukses: Data vaksinasi berhasil diperbarui.',
            'data' => $vaccination->load(['animal']),
        ]);
    }

}
