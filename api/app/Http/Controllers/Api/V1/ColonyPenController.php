<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BreedingFemale;
use App\Models\BreedingPeriod;
use App\Models\ColonyPen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ColonyPenController extends Controller
{
    private const COLONY_PHASES = ['koloni_kawin', 'koloni_bunting', 'koloni_kering', 'koloni_laktasi', 'koloni_anak', 'koloni_laktasi_kosong'];

    public function index(Request $request): JsonResponse
    {
        $perPage = $this->perPage($request);
        $q = ColonyPen::query()->withCount('animals');

        if ($request->filled('colony_type')) {
            $q->where('colony_type', $request->query('colony_type'));
        }

        if ($request->filled('colony_phase')) {
            $q->where('colony_phase', $request->query('colony_phase'));
        }

        if ($request->filled('is_active')) {
            $q->where('is_active', (bool) $request->boolean('is_active'));
        }

        if ($request->filled('search')) {
            $s = trim($request->query('search'));
            $q->where(function ($query) use ($s) {
                $query->where('pen_code', 'like', "%{$s}%")
                    ->orWhere('colony_code', 'like', "%{$s}%")
                    ->orWhere('colony_name', 'like', "%{$s}%")
                    ->orWhere('location', 'like', "%{$s}%");
            });
        }

        return response()->json(
            $q->orderBy('pen_code')
                ->orderBy('id')
                ->paginate($perPage)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request, [
            'pen_code' => ['required', 'string', 'max:100', 'unique:animal_pens,pen_code'],
            'colony_code' => ['nullable', 'string', 'max:100'],
            'colony_name' => ['nullable', 'string', 'max:255'],
            'colony_type' => ['nullable', Rule::in(self::COLONY_PHASES)],
            'colony_phase' => ['required', Rule::in(self::COLONY_PHASES)],
            'location' => ['nullable', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['colony_phase'] = $data['colony_phase'] === 'koloni_laktasi_kosong' ? 'koloni_laktasi' : $data['colony_phase'];
        $data['colony_type'] = $data['colony_type'] ?? $data['colony_phase'];
        $data['is_active'] = $data['is_active'] ?? true;

        $row = ColonyPen::create($data);

        return response()->json([
            'message' => 'Sukses: Data kandang berhasil disimpan.',
            'data' => $row,
        ], 201);
    }

    public function show(ColonyPen $colonyPen): JsonResponse
    {
        return response()->json($colonyPen->load(['animals.breed'])->loadCount('animals'));
    }

    public function update(Request $request, ColonyPen $colonyPen): JsonResponse
    {
        $data = $this->validated($request, [
            'pen_code' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('animal_pens', 'pen_code')->ignore($colonyPen->id),
            ],
            'colony_code' => ['nullable', 'string', 'max:100'],
            'colony_name' => ['nullable', 'string', 'max:255'],
            'colony_type' => ['nullable', Rule::in(self::COLONY_PHASES)],
            'colony_phase' => ['sometimes', Rule::in(self::COLONY_PHASES)],
            'location' => ['nullable', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (isset($data['colony_phase']) && $data['colony_phase'] === 'koloni_laktasi_kosong') {
            $data['colony_phase'] = 'koloni_laktasi';
        }

        if (isset($data['colony_phase'])) {
            $data['colony_type'] = $data['colony_type'] ?? $data['colony_phase'];
        }

        return DB::transaction(function () use ($colonyPen, $data) {
            $colonyPen = ColonyPen::query()->whereKey($colonyPen->id)->lockForUpdate()->firstOrFail();
            $occupants = $colonyPen->animals()->count();
            $activeFemales = BreedingFemale::query()
                ->whereHas('breedingPeriod', fn ($query) => $query->where('colony_pen_id', $colonyPen->id))
                ->whereNull('exit_date')->count();
            $activePeriod = BreedingPeriod::query()->where('colony_pen_id', $colonyPen->id)->where('status', 'active')->exists();
            if (isset($data['capacity']) && $data['capacity'] > 0 && $data['capacity'] < max($occupants, $activeFemales)) {
                return response()->json(['message' => 'Peringatan: Kapasitas kandang tidak boleh lebih kecil dari jumlah kambing yang tercatat di dalamnya.'], 422);
            }
            if (array_key_exists('is_active', $data) && ! $data['is_active'] && ($occupants > 0 || $activePeriod)) {
                return response()->json(['message' => 'Peringatan: Kandang yang masih berisi kambing atau memiliki periode kawin aktif tidak dapat dinonaktifkan.'], 422);
            }
            if (isset($data['colony_phase']) && $data['colony_phase'] !== $colonyPen->colony_phase && ($occupants > 0 || $activePeriod)) {
                return response()->json(['message' => 'Peringatan: Fase koloni tidak dapat diubah selama kandang masih berisi kambing atau memiliki periode kawin aktif.'], 422);
            }
            if (isset($data['colony_type']) && $data['colony_type'] !== $colonyPen->colony_type && ($occupants > 0 || $activePeriod)) {
                return response()->json(['message' => 'Peringatan: Jenis koloni tidak dapat diubah selama kandang masih berisi kambing atau memiliki periode kawin aktif.'], 422);
            }

            $colonyPen->fill($data)->save();

            return response()->json([
                'message' => 'Sukses: Data kandang berhasil diperbarui.',
                'data' => $colonyPen,
            ]);
        }, 3);
    }
}
