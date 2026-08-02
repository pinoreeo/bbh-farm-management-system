<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\WeightRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WeightRecordController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $this->perPage($request);
        $q = WeightRecord::query()->with('animal:id,tag_number,sex,life_status,birth_date');

        if ($request->filled('animal_id')) {
            $q->where('animal_id', (int) $request->query('animal_id'));
        }

        if ($request->filled('record_date')) {
            $q->where('record_date', $request->query('record_date'));
        }

        return response()->json(
            $q->orderByDesc('record_date')
                ->orderByDesc('id')
                ->paginate($perPage)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request, [
            'animal_id' => ['required', 'integer', 'exists:animals,id'],
            'record_date' => ['required', 'date', 'before_or_equal:today'],
            'weight_kg' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $animal = Animal::find($data['animal_id']);

        if (! $animal || $animal->life_status !== 'alive') {
            return response()->json([
                'message' => 'Peringatan: Catatan bobot hanya dapat dibuat untuk kambing yang masih hidup.',
            ], 422);
        }

        if ($animal->birth_date && $data['record_date'] < $animal->birth_date->toDateString()) {
            return response()->json([
                'message' => 'Peringatan: Tanggal timbang tidak boleh lebih awal dari tanggal lahir kambing.',
            ], 422);
        }

        $exists = WeightRecord::query()
            ->where('animal_id', $data['animal_id'])
            ->whereDate('record_date', $data['record_date'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Peringatan: Catatan bobot untuk kambing dan tanggal tersebut sudah ada.',
            ], 422);
        }


        $row = WeightRecord::create($data);

        return response()->json([
            'message' => 'Sukses: Catatan bobot berhasil disimpan.',
            'data' => $row->load('animal'),
        ], 201);
    }

    public function show(WeightRecord $weightRecord): JsonResponse
    {
        return response()->json($weightRecord->load('animal'));
    }

    public function update(Request $request, WeightRecord $weightRecord): JsonResponse
    {
        $data = $this->validated($request, [
            'record_date' => ['sometimes', 'date', 'before_or_equal:today'],
            'weight_kg' => ['sometimes', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $animal = $weightRecord->animal;

        $newRecordDate = $data['record_date'] ?? $weightRecord->record_date?->toDateString();

        if ($animal && $animal->life_status !== 'alive') {
            return response()->json([
                'message' => 'Peringatan: Catatan bobot hanya dapat dibuat untuk kambing yang masih hidup.',
            ], 422);
        }

        if ($animal && $animal->birth_date && $newRecordDate < $animal->birth_date->toDateString()) {
            return response()->json([
                'message' => 'Peringatan: Tanggal timbang tidak boleh lebih awal dari tanggal lahir kambing.',
            ], 422);
        }

        if (array_key_exists('record_date', $data)) {
            $exists = WeightRecord::query()
                ->where('id', '!=', $weightRecord->id)
                ->where('animal_id', $weightRecord->animal_id)
                ->whereDate('record_date', $newRecordDate)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'Peringatan: Catatan bobot lain untuk kambing dan tanggal tersebut sudah ada.',
                ], 422);
            }
        }

        $weightRecord->fill($data)->save();

        return response()->json([
            'message' => 'Sukses: Catatan bobot berhasil diperbarui.',
            'data' => $weightRecord->load('animal'),
        ]);
    }

}
