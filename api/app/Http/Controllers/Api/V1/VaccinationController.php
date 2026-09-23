<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\Vaccination;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $animalId = $this->intValue($data['animal_id']);
        $categoryName = $this->stringValue($data['category_name']);
        $vaccinationDate = $this->stringValue($data['vaccination_date']);
        $productName = $this->stringValue($data['product_name']);

        return DB::transaction(function () use ($data, $animalId, $categoryName, $vaccinationDate, $productName): JsonResponse {
            $animal = Animal::query()->whereKey($animalId)->lockForUpdate()->first();
            if (! $animal || ($animal->life_status !== 'alive'
                && (! $animal->status_date || $vaccinationDate > $animal->status_date->toDateString()))) {
                return response()->json([
                    'message' => 'Peringatan: Tanggal vaksin tidak boleh setelah tanggal kematian kambing.',
                ], 422);
            }

            if ($animal->birth_date && $vaccinationDate < $animal->birth_date->toDateString()) {
                return response()->json([
                    'message' => 'Peringatan: Tanggal vaksin tidak boleh lebih awal dari tanggal lahir kambing.',
                ], 422);
            }

            $exists = Vaccination::query()
                ->where('animal_id', $animalId)
                ->where('category_name', $categoryName)
                ->whereDate('vaccination_date', $vaccinationDate)
                ->where('product_name', $productName)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'Peringatan: Data vaksinasi untuk kambing, jenis vaksin, tanggal, dan produk tersebut sudah ada.',
                ], 422);
            }

            try {
                $row = Vaccination::create($data);
            } catch (QueryException $e) {
                if (! $this->isDuplicateKey($e)) {
                    throw $e;
                }

                return response()->json(['message' => 'Peringatan: Data vaksinasi untuk kambing, jenis vaksin, tanggal, dan produk tersebut sudah ada.'], 422);
            }

            return response()->json([
                'message' => 'Sukses: Data vaksinasi berhasil disimpan.',
                'data' => $row->load(['animal']),
            ], 201);
        }, 3);
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

        return DB::transaction(function () use ($data, $vaccination): JsonResponse {
            $animal = Animal::query()->whereKey($vaccination->animal_id)->lockForUpdate()->first();
            $vaccination = Vaccination::query()->whereKey($vaccination->id)->lockForUpdate()->firstOrFail();
            $newVaccinationDate = $this->stringValue($data['vaccination_date'] ?? $vaccination->vaccination_date?->toDateString());
            if (! $animal || ($animal->life_status !== 'alive'
                && (! $animal->status_date || $newVaccinationDate > $animal->status_date->toDateString()))) {
                return response()->json([
                    'message' => 'Peringatan: Data vaksinasi hanya dapat diperbaiki untuk tanggal sebelum atau saat kambing mati.',
                ], 422);
            }

            $newCategoryName = $this->stringValue($data['category_name'] ?? $vaccination->category_name);
            $newProductName = $this->stringValue($data['product_name'] ?? $vaccination->product_name);

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

            try {
                $vaccination->fill($data)->save();
            } catch (QueryException $e) {
                if (! $this->isDuplicateKey($e)) {
                    throw $e;
                }

                return response()->json(['message' => 'Peringatan: Data vaksinasi lain untuk kambing, jenis vaksin, tanggal, dan produk tersebut sudah ada.'], 422);
            }

            return response()->json([
                'message' => 'Sukses: Data vaksinasi berhasil diperbarui.',
                'data' => $vaccination->load(['animal']),
            ]);
        }, 3);
    }

    private function isDuplicateKey(QueryException $exception): bool
    {
        return in_array((string) $exception->getCode(), ['23000', '23505', '19'], true);
    }
}
