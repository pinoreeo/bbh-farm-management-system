<?php

namespace App\Support;

use App\Models\Animal;
use App\Models\BirthEvent;
use App\Models\OffspringBirth;

class PureBreedSireMarker
{
    public function markerForSire(?Animal $sire): ?string
    {
        if (! $sire) {
            return null;
        }

        $sire->loadMissing('breed');

        if ($sire->generation !== 'Pure Breed') {
            return null;
        }

        $breedName = strtolower((string) $sire->breed?->breed_name);

        return match (true) {
            str_contains($breedName, 'saanen') => 'SPB',
            str_contains($breedName, 'alpine') => 'APB',
            default => null,
        };
    }

    public function syncOffspring(Animal $offspring, BirthEvent $birthEvent): void
    {
        $marker = $offspring->is_impor
            ? null
            : $this->markerForSire($birthEvent->sire);

        if ($offspring->male_role !== $marker) {
            $offspring->forceFill(['male_role' => $marker])->save();
        }
    }

    public function syncFromRecordedBirth(Animal $offspring): void
    {
        $birth = OffspringBirth::query()
            ->with('birthEvent.sire.breed')
            ->where('offspring_animal_id', $offspring->id)
            ->first();

        if (! $birth?->birthEvent) {
            if ($offspring->is_impor && $offspring->male_role !== null) {
                $offspring->forceFill(['male_role' => null])->save();
            }

            return;
        }

        $this->syncOffspring($offspring, $birth->birthEvent);
    }
}
