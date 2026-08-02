<?php

namespace Tests\Feature\Operational;

use App\Models\BirthEvent;
use App\Models\OffspringBirth;
use Tests\Feature\Support\ApiTestCase;

class InbreedingIncestBlockTest extends ApiTestCase
{
    public function test_father_cannot_be_mated_with_his_daughter(): void
    {
        $this->actingAsAdmin();

        $sire = $this->createAnimal(['sex' => 'male', 'tag_number' => 'AYAH-'.uniqid()]);
        $dam = $this->createAnimal(['sex' => 'female', 'tag_number' => 'INDUK-'.uniqid()]);
        $daughter = $this->createAnimal(['sex' => 'female', 'tag_number' => 'ANAK-BETINA-'.uniqid()]);
        $period = $this->createBreedingPeriod($sire);

        $this->registerOffspring($dam->id, $sire->id, $daughter->id);

        $this->postJson('/api/v1/breeding-females', [
            'breeding_period_id' => $period->id,
            'female_animal_id' => $daughter->id,
            'entry_date' => '2026-03-01',
            'mating_date' => '2026-03-05',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('inbreeding.status', 'blocked')
            ->assertJsonPath('inbreeding.relationships.0', 'sire_daughter');
    }

    public function test_mother_cannot_be_mated_with_her_son(): void
    {
        $this->actingAsAdmin();

        $dam = $this->createAnimal(['sex' => 'female', 'tag_number' => 'IBU-'.uniqid()]);
        $originalSire = $this->createAnimal(['sex' => 'male', 'tag_number' => 'AYAH-ASAL-'.uniqid()]);
        $son = $this->createAnimal(['sex' => 'male', 'tag_number' => 'ANAK-JANTAN-'.uniqid()]);
        $period = $this->createBreedingPeriod($son);

        $this->registerOffspring($dam->id, $originalSire->id, $son->id);

        $this->postJson('/api/v1/breeding-females', [
            'breeding_period_id' => $period->id,
            'female_animal_id' => $dam->id,
            'entry_date' => '2026-03-01',
            'mating_date' => '2026-03-05',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('inbreeding.status', 'blocked')
            ->assertJsonPath('inbreeding.relationships.0', 'dam_son');
    }

    public function test_related_animals_are_blocked_when_entering_breeding_colony(): void
    {
        $this->actingAsAdmin();

        $sharedDam = $this->createAnimal(['sex' => 'female', 'tag_number' => 'INDUK-BERSAMA-'.uniqid()]);
        $firstSire = $this->createAnimal(['sex' => 'male', 'tag_number' => 'PEJANTAN-1-'.uniqid()]);
        $secondSire = $this->createAnimal(['sex' => 'male', 'tag_number' => 'PEJANTAN-2-'.uniqid()]);
        $male = $this->createAnimal(['sex' => 'male', 'tag_number' => 'ANAK-JANTAN-'.uniqid()]);
        $female = $this->createAnimal(['sex' => 'female', 'tag_number' => 'ANAK-BETINA-'.uniqid()]);
        $period = $this->createBreedingPeriod($male);

        $this->registerOffspring($sharedDam->id, $firstSire->id, $male->id);
        $this->registerOffspring($sharedDam->id, $secondSire->id, $female->id);

        $this->postJson('/api/v1/breeding-females', [
            'breeding_period_id' => $period->id,
            'female_animal_id' => $female->id,
            'entry_date' => '2026-03-01',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('inbreeding.status', 'blocked')
            ->assertJsonPath('inbreeding.relationships.0', 'half_sibling');
    }

    private function registerOffspring(int $damId, int $sireId, int $offspringId): void
    {
        $birthEvent = BirthEvent::query()->create([
            'dam_id' => $damId,
            'sire_id' => $sireId,
            'birth_date' => '2025-01-01',
            'offspring_count' => 1,
            'birth_process' => 'Normal',
            'birth_place' => 'BBH Farm',
        ]);

        OffspringBirth::query()->create([
            'birth_event_id' => $birthEvent->id,
            'offspring_animal_id' => $offspringId,
            'birth_weight_kg' => 3.2,
            'birth_status' => 'alive',
        ]);
    }
}
