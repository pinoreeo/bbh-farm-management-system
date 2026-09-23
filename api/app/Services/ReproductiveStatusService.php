<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\BirthEvent;
use App\Models\BreedingFemale;
use App\Models\PregnancyCheck;

class ReproductiveStatusService
{
    // Call inside a transaction after locking the animal shared by reproduction records.
    public function sync(Animal $animal, ?string $previousDate = null): void
    {
        if ($animal->life_status !== 'alive') {
            return;
        }

        $check = PregnancyCheck::query()
            ->where('female_animal_id', $animal->id)
            ->whereNotIn('breeding_female_id', BirthEvent::query()->whereNotNull('breeding_female_id')->select('breeding_female_id'))
            ->whereNotIn('breeding_female_id', PregnancyCheck::query()->where('outcome_status', 'born')->select('breeding_female_id'))
            ->orderByDesc('check_date')->orderByDesc('id')->first();
        $birth = BirthEvent::query()->where('dam_id', $animal->id)->orderByDesc('birth_date')->orderByDesc('id')->first();
        $mating = BreedingFemale::query()->where('female_animal_id', $animal->id)
            ->whereNotNull('mating_date')->orderByDesc('mating_date')->first();

        $events = collect([
            $mating?->mating_date ? ['date' => $mating->mating_date->toDateString(), 'status' => 'kawin', 'priority' => 0] : null,
            $check ? ['date' => $check->check_date->toDateString(), 'status' => $check->is_pregnant ? 'bunting' : 'kosong', 'priority' => 1] : null,
            $birth ? ['date' => $birth->birth_date->toDateString(), 'status' => 'melahirkan', 'priority' => 2] : null,
        ])->filter()->sortBy([['date', 'desc'], ['priority', 'desc']]);
        $latest = $events->first();
        if (! $latest || $animal->reproductive_status === 'afkir') {
            return;
        }

        $currentDate = $animal->status_date?->toDateString();
        if ($currentDate && $currentDate > $latest['date'] && $currentDate !== $previousDate) {
            return;
        }

        $animal->forceFill(['reproductive_status' => $latest['status'], 'status_date' => $latest['date']])->save();
    }
}
