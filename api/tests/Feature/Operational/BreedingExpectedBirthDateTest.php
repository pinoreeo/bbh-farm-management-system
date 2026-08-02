<?php

namespace Tests\Feature\Operational;

use App\Models\BreedingFemale;
use Tests\Feature\Support\ApiTestCase;

class BreedingExpectedBirthDateTest extends ApiTestCase
{
    public function test_breeding_female_expected_birth_date_is_calculated_from_its_mating_date(): void
    {
        $this->actingAsAdmin();

        $period = $this->createBreedingPeriod(null, null, [
            'start_date' => '2026-03-01',
        ]);
        $female = $this->createAnimal(['sex' => 'female', 'tag_number' => 'DAM-EST-'.uniqid()]);

        $response = $this->postJson('/api/v1/breeding-females', [
            'breeding_period_id' => $period->id,
            'female_animal_id' => $female->id,
            'entry_date' => '2026-03-01',
            'mating_date' => '2026-03-05',
            'expected_birth_date' => '2026-01-01',
        ])->assertCreated();

        $breedingFemale = BreedingFemale::query()->findOrFail($response->json('data.id'));

        $this->assertSame('2026-03-05', $breedingFemale->mating_date->toDateString());
        $this->assertSame('2026-08-15', $breedingFemale->expected_birth_date->toDateString());
    }

    public function test_breeding_female_can_enter_colony_without_mating_date_or_expected_birth_date(): void
    {
        $this->actingAsAdmin();

        $period = $this->createBreedingPeriod(null, null, [
            'start_date' => '2026-03-01',
        ]);
        $female = $this->createAnimal(['sex' => 'female', 'tag_number' => 'DAM-ENTRY-'.uniqid()]);

        $response = $this->postJson('/api/v1/breeding-females', [
            'breeding_period_id' => $period->id,
            'female_animal_id' => $female->id,
            'entry_date' => '2026-03-01',
        ])->assertCreated();

        $breedingFemale = BreedingFemale::query()->findOrFail($response->json('data.id'));

        $this->assertNull($breedingFemale->mating_date);
        $this->assertNull($breedingFemale->expected_birth_date);
    }

    public function test_breeding_female_expected_birth_date_is_recalculated_when_mating_date_is_recorded_later(): void
    {
        $this->actingAsAdmin();

        $period = $this->createBreedingPeriod(null, null, [
            'start_date' => '2026-03-01',
        ]);
        $female = $this->createAnimal(['sex' => 'female', 'tag_number' => 'DAM-LATER-'.uniqid()]);
        $breedingFemale = BreedingFemale::query()->create([
            'breeding_period_id' => $period->id,
            'female_animal_id' => $female->id,
            'entry_date' => '2026-03-01',
            'cycle_stage' => 'kawin',
        ]);

        $this->postJson("/api/v1/breeding-females/{$breedingFemale->id}/mating", [
            'mating_date' => '2026-03-05',
        ])->assertOk();

        $breedingFemale->refresh();

        $this->assertSame('2026-03-05', $breedingFemale->mating_date->toDateString());
        $this->assertSame('2026-08-15', $breedingFemale->expected_birth_date->toDateString());
    }

    public function test_mating_date_cannot_be_updated_from_general_edit_endpoint(): void
    {
        $this->actingAsAdmin();

        $breedingFemale = $this->createBreedingFemale();

        $this->putJson("/api/v1/breeding-females/{$breedingFemale->id}", [
            'mating_date' => '2026-03-05',
        ])->assertUnprocessable();
    }

    public function test_exit_date_cannot_be_updated_from_general_edit_endpoint(): void
    {
        $this->actingAsAdmin();

        $period = $this->createBreedingPeriod();
        $female = $this->createAnimal(['sex' => 'female', 'tag_number' => 'DAM-EXIT-'.uniqid()]);
        $breedingFemale = BreedingFemale::query()->create([
            'breeding_period_id' => $period->id,
            'female_animal_id' => $female->id,
            'entry_date' => '2026-03-01',
            'cycle_stage' => 'kawin',
        ]);

        $this->putJson("/api/v1/breeding-females/{$breedingFemale->id}", [
            'exit_date' => '2026-04-01',
        ])->assertUnprocessable();
    }

    public function test_breeding_female_can_be_exited_from_period_with_reason(): void
    {
        $this->actingAsAdmin();

        $period = $this->createBreedingPeriod(null, null, [
            'end_date' => '2026-05-31',
        ]);
        $female = $this->createAnimal([
            'sex' => 'female',
            'tag_number' => 'DAM-NO-PEN-'.uniqid(),
            'current_pen_id' => $period->colony_pen_id,
        ]);
        $breedingFemale = $this->createBreedingFemale($period, $female);

        $this->postJson("/api/v1/breeding-females/{$breedingFemale->id}/exit", [
            'exit_date' => '2026-06-01',
            'exit_reason_code' => 'tidak_bunting',
        ])->assertOk();

        $breedingFemale->refresh();

        $this->assertSame('2026-06-01', $breedingFemale->exit_date->toDateString());
        $this->assertSame('Tidak bunting / gagal kawin', $breedingFemale->exit_reason);
        $this->assertSame('tidak_bunting', $breedingFemale->exit_reason_code);
        $this->assertSame('kosong', $breedingFemale->femaleAnimal->refresh()->reproductive_status);
        $this->assertNull($breedingFemale->femaleAnimal->current_pen_id);
    }

    public function test_breeding_female_exit_can_move_animal_to_destination_colony(): void
    {
        $this->actingAsAdmin();

        $period = $this->createBreedingPeriod();
        $female = $this->createAnimal([
            'sex' => 'female',
            'tag_number' => 'DAM-MOVE-'.uniqid(),
            'current_pen_id' => $period->colony_pen_id,
        ]);
        $breedingFemale = $this->createBreedingFemale($period, $female);
        $destinationPen = $this->createPen([
            'pen_code' => 'BUNTING-'.uniqid(),
            'colony_type' => 'koloni_bunting',
            'colony_phase' => 'koloni_bunting',
        ]);

        $this->postJson("/api/v1/breeding-females/{$breedingFemale->id}/exit", [
            'exit_date' => '2026-06-10',
            'exit_reason_code' => 'bunting_pindah_koloni_bunting',
            'to_pen_id' => $destinationPen->id,
            'exit_notes' => 'Hasil pengamatan petugas: betina siap dipindahkan.',
        ])->assertOk();

        $female->refresh();

        $this->assertSame($destinationPen->id, $female->current_pen_id);
        $this->assertSame('bunting', $female->reproductive_status);
        $this->assertDatabaseHas('animal_pen_movements', [
            'animal_id' => $female->id,
            'from_pen_id' => $period->colony_pen_id,
            'to_pen_id' => $destinationPen->id,
            'movement_date' => '2026-06-10 00:00:00',
            'reason' => 'Bunting, pindah ke koloni bunting',
        ]);
    }

    public function test_custom_exit_reason_requires_detail(): void
    {
        $this->actingAsAdmin();

        $breedingFemale = $this->createBreedingFemale();

        $this->postJson("/api/v1/breeding-females/{$breedingFemale->id}/exit", [
            'exit_date' => '2026-06-01',
            'exit_reason_code' => 'lainnya',
        ])->assertUnprocessable();
    }

    public function test_exit_cannot_move_directly_to_breeding_colony(): void
    {
        $this->actingAsAdmin();

        $breedingFemale = $this->createBreedingFemale();
        $breedingPen = $this->createPen([
            'pen_code' => 'KAWIN-TUJUAN-'.uniqid(),
            'colony_type' => 'koloni_kawin',
            'colony_phase' => 'koloni_kawin',
        ]);

        $this->postJson("/api/v1/breeding-females/{$breedingFemale->id}/exit", [
            'exit_date' => '2026-06-01',
            'exit_reason_code' => 'lainnya',
            'exit_reason' => 'Pindah ke periode lain',
            'to_pen_id' => $breedingPen->id,
        ])->assertUnprocessable();
    }
}
