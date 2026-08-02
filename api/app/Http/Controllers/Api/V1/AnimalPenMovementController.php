<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\AnimalPenMovement;
use App\Models\ColonyPen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnimalPenMovementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $this->perPage($request);
        $q = AnimalPenMovement::query()->with([
            'animal:id,tag_number,sex,life_status,current_pen_id',
            'fromPen:id,pen_code,colony_phase,colony_type,is_active',
            'toPen:id,pen_code,colony_phase,colony_type,is_active',
        ]);

        if ($request->filled('animal_id')) {
            $q->where('animal_id', (int) $request->query('animal_id'));
        }

        if ($request->filled('to_pen_id')) {
            $q->where('to_pen_id', (int) $request->query('to_pen_id'));
        }

        return response()->json(
            $q->orderByDesc('movement_date')->orderByDesc('id')->paginate($perPage)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request, [
            'animal_id' => ['required', 'integer', 'exists:animals,id'],
            'to_pen_id' => ['required', 'integer', 'exists:animal_pens,id'],
            'movement_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $animal = Animal::find($data['animal_id']);
        if (! $animal || $animal->life_status !== 'alive') {
            return response()->json([
                'message' => 'Peringatan: Pindah koloni hanya dapat dicatat untuk kambing yang masih hidup.',
            ], 422);
        }

        $destinationPen = ColonyPen::find($data['to_pen_id']);
        $destinationError = $this->validateDestinationPen($animal, $destinationPen);
        if ($destinationError) {
            return $destinationError;
        }

        if ($animal->birth_date && $data['movement_date'] < $animal->birth_date->toDateString()) {
            return response()->json([
                'message' => 'Peringatan: Tanggal pindah koloni tidak boleh lebih awal dari tanggal lahir kambing.',
            ], 422);
        }

        if ((int) $animal->current_pen_id === (int) $data['to_pen_id']) {
            return response()->json([
                'message' => 'Peringatan: Kambing sudah berada di koloni tujuan yang dipilih.',
            ], 422);
        }

        $data['from_pen_id'] = $animal->current_pen_id;

        $row = AnimalPenMovement::query()->create($data);
        $animal->forceFill([
            'current_pen_id' => $data['to_pen_id'],
            'status_date' => $data['movement_date'],
        ])->save();

        return response()->json([
            'message' => 'Sukses: Riwayat pindah koloni berhasil disimpan.',
            'data' => $row->load(['animal', 'fromPen', 'toPen']),
        ], 201);
    }

    public function show(AnimalPenMovement $animalPenMovement): JsonResponse
    {
        return response()->json($animalPenMovement->load(['animal', 'fromPen', 'toPen']));
    }

    public function update(Request $request, AnimalPenMovement $animalPenMovement): JsonResponse
    {
        $data = $this->validated($request, [
            'movement_date' => ['sometimes', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $animal = $animalPenMovement->animal;
        if (isset($data['movement_date']) && $animal?->birth_date && $data['movement_date'] < $animal->birth_date->toDateString()) {
            return response()->json([
                'message' => 'Peringatan: Tanggal pindah koloni tidak boleh lebih awal dari tanggal lahir kambing.',
            ], 422);
        }

        $animalPenMovement->fill($data)->save();
        $this->syncCurrentAnimalStatusDate($animalPenMovement);

        return response()->json([
            'message' => 'Sukses: Riwayat pindah koloni berhasil diperbarui.',
            'data' => $animalPenMovement->load(['animal', 'fromPen', 'toPen']),
        ]);
    }

    private function syncCurrentAnimalStatusDate(AnimalPenMovement $movement): void
    {
        $animal = $movement->animal;
        if (! $animal || (int) $animal->current_pen_id !== (int) $movement->to_pen_id) {
            return;
        }

        $latestMovement = AnimalPenMovement::query()
            ->where('animal_id', $movement->animal_id)
            ->orderByDesc('movement_date')
            ->orderByDesc('id')
            ->first();

        if ($latestMovement?->is($movement)) {
            $animal->forceFill(['status_date' => $movement->movement_date?->toDateString()])->save();
        }
    }

    private function validateDestinationPen(Animal $animal, ?ColonyPen $destinationPen): ?JsonResponse
    {
        $phase = $destinationPen?->colony_phase ?? $destinationPen?->colony_type;

        if (! $destinationPen || ! $destinationPen->is_active) {
            return response()->json([
                'message' => 'Peringatan: Koloni tujuan tidak aktif atau tidak ditemukan.',
            ], 422);
        }

        if ($phase === 'koloni_kawin') {
            return response()->json([
                'message' => 'Peringatan: Pindah ke koloni kawin harus diproses melalui menu Periode Kawin agar pengecekan hubungan darah tetap berjalan.',
            ], 422);
        }

        if ($phase === 'koloni_anak' && $animal->kategori_umur !== 'cempe') {
            return response()->json([
                'message' => 'Peringatan: Koloni anak hanya dapat diisi oleh cempe berdasarkan kategori umur ternak.',
            ], 422);
        }

        if (in_array($phase, ['koloni_bunting', 'koloni_kering', 'koloni_laktasi'], true) && $animal->sex !== 'female') {
            return response()->json([
                'message' => 'Peringatan: Koloni bunting, kering, dan laktasi hanya dapat diisi oleh kambing betina.',
            ], 422);
        }

        return null;
    }
}
