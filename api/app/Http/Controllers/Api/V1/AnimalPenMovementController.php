<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\AnimalPenMovement;
use App\Models\BreedingFemale;
use App\Services\AnimalPenMovementService;
use App\Support\TypeValue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnimalPenMovementController extends Controller
{
    public function __construct(private readonly AnimalPenMovementService $movements) {}

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
            'movement_date' => ['required', 'date', 'before_or_equal:today'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $animalId = $this->intValue($data['animal_id']);
        $destinationPenId = $this->intValue($data['to_pen_id']);
        $movementDate = $this->stringValue($data['movement_date']);

        return DB::transaction(function () use ($animalId, $destinationPenId, $movementDate, $data) {
            $animal = Animal::query()->whereKey($animalId)->lockForUpdate()->first();
            if (! $animal || $animal->life_status !== 'alive') {
                return response()->json([
                    'message' => 'Peringatan: Pindah koloni hanya dapat dicatat untuk kambing yang tercatat hidup.',
                ], 422);
            }

            $result = $this->movements->record($animal, $destinationPenId, $movementDate, TypeValue::nullableString($data['reason'] ?? null), TypeValue::nullableString($data['notes'] ?? null));
            if (isset($result['error'])) {
                return response()->json(['message' => $result['error']], 422);
            }
            $row = $result['row'] ?? null;
            if (! $row instanceof AnimalPenMovement) {
                return response()->json(['message' => 'Gagal: Riwayat pindah koloni belum berhasil disimpan.'], 422);
            }

            return response()->json([
                'message' => 'Sukses: Riwayat pindah koloni berhasil disimpan.',
                'data' => $row->refresh()->load(['animal', 'fromPen', 'toPen']),
            ], 201);
        }, 3);
    }

    public function show(AnimalPenMovement $animalPenMovement): JsonResponse
    {
        return response()->json($animalPenMovement->load(['animal', 'fromPen', 'toPen']));
    }

    public function update(Request $request, AnimalPenMovement $animalPenMovement): JsonResponse
    {
        $data = $this->validated($request, [
            'movement_date' => ['sometimes', 'date', 'before_or_equal:today'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($animalPenMovement, $data) {
            $animal = Animal::query()->whereKey($animalPenMovement->animal_id)->lockForUpdate()->firstOrFail();
            if ($animal->life_status !== 'alive') {
                return response()->json(['message' => 'Peringatan: Riwayat pindah koloni tidak dapat diubah untuk kambing yang berstatus mati.'], 422);
            }
            $movements = $this->movements->lockedMovements($animal->id);
            $initialPenId = $movements->first()?->from_pen_id;
            $movement = $movements->firstWhere('id', $animalPenMovement->id);
            if (! $movement instanceof AnimalPenMovement) {
                abort(404);
            }
            $managedExit = BreedingFemale::query()->where('female_animal_id', $animal->id)
                ->whereDate('exit_date', $movement->movement_date?->toDateString())
                ->where('exit_reason', $movement->reason)->exists();
            if ($movement->to_pen_id === null || $movement->reason === 'Masuk periode kawin' || $managedExit) {
                return response()->json(['message' => 'Peringatan: Perpindahan ini mengikuti catatan perkawinan atau status hidup kambing dan tidak dapat diedit langsung.'], 422);
            }
            $movementDate = isset($data['movement_date']) ? $this->stringValue($data['movement_date']) : $movement->movement_date->toDateString();

            if ($animal->birth_date && $movementDate < $animal->birth_date->toDateString()) {
                return response()->json([
                    'message' => 'Peringatan: Tanggal pindah koloni tidak boleh lebih awal dari tanggal lahir kambing.',
                ], 422);
            }

            if ($movement->toPen !== null) {
                $destinationError = $this->movements->destinationError(
                    $animal,
                    $movement->toPen,
                    $movementDate,
                    true,
                );
                if ($destinationError) {
                    return response()->json(['message' => $destinationError], 422);
                }
            }

            $movement->fill($data);
            $ordered = $movements->sortBy(fn (AnimalPenMovement $item) => sprintf(
                '%s-%020d',
                $item->movement_date?->toDateString(),
                $item->id
            ))->values();
            if ($this->hasRedundantMovement($ordered, $initialPenId)) {
                return response()->json([
                    'message' => 'Peringatan: Perubahan tanggal membuat urutan perpindahan menuju koloni yang sama.',
                ], 422);
            }

            $movement->save();
            $this->movements->rebuildMovementChain($animal, $initialPenId);

            return response()->json([
                'message' => 'Sukses: Riwayat pindah koloni berhasil diperbarui.',
                'data' => $movement->refresh()->load(['animal', 'fromPen', 'toPen']),
            ]);
        }, 3);
    }

    /** @param Collection<int, AnimalPenMovement> $movements */
    private function hasRedundantMovement(Collection $movements, ?int $initialPenId): bool
    {
        $sourcePenId = $initialPenId;
        foreach ($movements as $movement) {
            if ((string) $sourcePenId === (string) $movement->to_pen_id) {
                return true;
            }
            $sourcePenId = $movement->to_pen_id;
        }

        return false;
    }
}
