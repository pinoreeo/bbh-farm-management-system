<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\AnimalPenMovement;
use App\Models\ColonyPen;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AnimalPenMovementService
{
    /** @return array{row: AnimalPenMovement|null}|array{error: string} */
    public function record(Animal $animal, ?int $toPenId, string $date, ?string $reason = null, ?string $notes = null, bool $allowBreedingPen = false): array
    {
        if ($animal->birth_date && $date < $animal->birth_date->toDateString()) {
            return ['error' => 'Peringatan: Tanggal pindah koloni tidak boleh lebih awal dari tanggal lahir kambing.'];
        }

        $destinationPen = null;
        if ($toPenId !== null) {
            $destinationPen = ColonyPen::query()->whereKey($toPenId)->lockForUpdate()->first();
            $error = $this->destinationError($animal, $destinationPen, $date, $allowBreedingPen);
            if ($error !== null) {
                return ['error' => $error];
            }
        }

        $movements = $this->lockedMovements($animal->id);
        $initialPenId = $movements->first()?->from_pen_id ?? $animal->current_pen_id;
        $sourcePenId = $initialPenId;
        $nextMovement = null;
        foreach ($movements as $movement) {
            if ($movement->movement_date->toDateString() <= $date) {
                $sourcePenId = $movement->to_pen_id;
            } else {
                $nextMovement = $movement;
                break;
            }
        }

        if ($sourcePenId === null && $toPenId === null && $nextMovement === null) {
            return ['row' => null];
        }
        if ((string) $sourcePenId === (string) $toPenId || ($nextMovement && (string) $nextMovement->to_pen_id === (string) $toPenId)) {
            return ['error' => 'Peringatan: Pilih koloni tujuan yang berbeda dari koloni pada urutan riwayat tersebut.'];
        }
        if ($destinationPen && $destinationPen->capacity > 0 && $nextMovement === null
            && $destinationPen->animals()->whereKeyNot($animal->id)->count() >= $destinationPen->capacity) {
            return ['error' => 'Peringatan: Kapasitas koloni tujuan sudah penuh.'];
        }

        $row = AnimalPenMovement::query()->create([
            'animal_id' => $animal->id,
            'from_pen_id' => $sourcePenId,
            'to_pen_id' => $toPenId,
            'movement_date' => $date,
            'reason' => $reason,
            'notes' => $notes,
        ]);
        $this->rebuildMovementChain($animal, $initialPenId);

        return ['row' => $row];
    }

    /** @return Collection<int, AnimalPenMovement> */
    public function lockedMovements(int $animalId): Collection
    {
        return AnimalPenMovement::query()->where('animal_id', $animalId)
            ->orderBy('movement_date')->orderBy('id')->lockForUpdate()->get();
    }

    public function reschedule(Animal $animal, AnimalPenMovement $movement, string $date): ?string
    {
        if ($animal->birth_date && $date < $animal->birth_date->toDateString()) {
            return 'Peringatan: Tanggal pindah koloni tidak boleh lebih awal dari tanggal lahir kambing.';
        }

        $movements = $this->lockedMovements($animal->id);
        $initialPenId = $movements->first()?->from_pen_id;
        $record = $movements->firstWhere('id', $movement->id);
        if (! $record instanceof AnimalPenMovement) {
            return 'Peringatan: Riwayat pindah koloni tidak ditemukan.';
        }
        $record->movement_date = Carbon::parse($date);
        $sourcePenId = $initialPenId;
        foreach ($movements->sortBy(fn (AnimalPenMovement $item) => sprintf('%s-%020d', $item->movement_date?->toDateString(), $item->id)) as $item) {
            if ((string) $sourcePenId === (string) $item->to_pen_id) {
                return 'Peringatan: Perubahan tanggal membuat urutan perpindahan menuju koloni yang sama.';
            }
            $sourcePenId = $item->to_pen_id;
        }

        $record->save();
        $this->rebuildMovementChain($animal, $initialPenId);

        return null;
    }

    public function rebuildMovementChain(Animal $animal, ?int $initialPenId): void
    {
        $sourcePenId = $initialPenId;
        foreach ($this->lockedMovements($animal->id) as $movement) {
            if ((string) $movement->from_pen_id !== (string) $sourcePenId) {
                $movement->forceFill(['from_pen_id' => $sourcePenId])->save();
            }
            $sourcePenId = $movement->to_pen_id;
        }

        $animal->forceFill(['current_pen_id' => $sourcePenId])->save();
    }

    public function destinationError(Animal $animal, ?ColonyPen $pen, string $date, bool $allowBreedingPen = false): ?string
    {
        $phase = $pen?->colony_phase ?? $pen?->colony_type;
        if (! $pen || ! $pen->is_active) {
            return 'Peringatan: Koloni tujuan tidak aktif atau tidak ditemukan.';
        }
        if ($phase === 'koloni_kawin' && ! $allowBreedingPen) {
            return 'Peringatan: Pindah ke koloni kawin harus diproses melalui menu Periode Kawin agar pengecekan hubungan darah tetap berjalan.';
        }
        if ($phase === 'koloni_anak' && (! $animal->birth_date || $animal->birth_date->copy()->addMonths(6)->lt($date))) {
            return 'Peringatan: Koloni anak hanya dapat diisi oleh cempe berdasarkan kategori umur ternak.';
        }
        if (in_array($phase, ['koloni_bunting', 'koloni_kering', 'koloni_laktasi'], true) && $animal->sex !== 'female') {
            return 'Peringatan: Koloni bunting, kering, dan laktasi hanya dapat diisi oleh kambing betina.';
        }

        return null;
    }
}
