<?php

namespace Tests\Feature\Operational;

use Tests\Feature\Support\ApiTestCase;

class BreedingProcessGuardTest extends ApiTestCase
{
    public function test_breeding_period_core_fields_are_locked_after_females_enter(): void
    {
        $this->actingAsAdmin();

        $period = $this->createBreedingPeriod();
        $this->createBreedingFemale($period);
        $newMale = $this->createAnimal(['sex' => 'male', 'tag_number' => 'NEW-MALE-'.uniqid()]);

        $this->putJson("/api/v1/breeding-periods/{$period->id}", [
            'male_animal_id' => $newMale->id,
        ])->assertUnprocessable();
    }

    public function test_manual_pen_movement_cannot_enter_breeding_colony(): void
    {
        $this->actingAsAdmin();

        $animal = $this->createAnimal(['sex' => 'female', 'tag_number' => 'MOVE-GUARD-'.uniqid()]);
        $breedingPen = $this->createPen([
            'pen_code' => 'KAWIN-GUARD-'.uniqid(),
            'colony_type' => 'koloni_kawin',
            'colony_phase' => 'koloni_kawin',
        ]);

        $this->postJson('/api/v1/animal-pen-movements', [
            'animal_id' => $animal->id,
            'to_pen_id' => $breedingPen->id,
            'movement_date' => '2026-06-01',
            'reason' => 'Pindah manual',
        ])->assertUnprocessable();
    }
}
