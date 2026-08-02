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

    public function test_birth_event_requires_living_dam_and_sire(): void
    {
        $this->actingAsAdmin();

        $deadDam = $this->createAnimal([
            'sex' => 'female',
            'life_status' => 'dead',
            'tag_number' => 'DAM-DEAD-'.uniqid(),
        ]);
        $liveDam = $this->createAnimal(['sex' => 'female', 'tag_number' => 'DAM-LIVE-'.uniqid()]);
        $deadSire = $this->createAnimal([
            'sex' => 'male',
            'life_status' => 'dead',
            'tag_number' => 'SIRE-DEAD-'.uniqid(),
        ]);
        $liveSire = $this->createAnimal(['sex' => 'male', 'tag_number' => 'SIRE-LIVE-'.uniqid()]);

        $this->postJson('/api/v1/birth-events', [
            'dam_id' => $deadDam->id,
            'sire_id' => $liveSire->id,
            'birth_date' => '2026-07-20',
            'offspring_count' => 1,
            'birth_process' => 'Normal',
        ])->assertUnprocessable()
            ->assertJsonPath('message', "Peringatan: Kelahiran hanya dapat dicatat untuk induk {$deadDam->tag_number} yang masih hidup.");

        $this->postJson('/api/v1/birth-events', [
            'dam_id' => $liveDam->id,
            'sire_id' => $deadSire->id,
            'birth_date' => '2026-07-20',
            'offspring_count' => 1,
            'birth_process' => 'Normal',
        ])->assertUnprocessable()
            ->assertJsonPath('message', "Peringatan: Tag pejantan {$deadSire->tag_number} sudah tidak berstatus hidup.");
    }
}
