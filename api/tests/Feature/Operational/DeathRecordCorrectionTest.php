<?php

namespace Tests\Feature\Operational;

use App\Models\AnimalPenMovement;
use App\Models\HealthTreatment;
use App\Models\Vaccination;
use App\Models\WeightRecord;
use Tests\Feature\Support\ApiTestCase;

class DeathRecordCorrectionTest extends ApiTestCase
{
    public function test_death_date_correction_updates_generated_pen_and_breeding_history(): void
    {
        $this->actingAsAdmin();
        $period = $this->createBreedingPeriod();
        $female = $this->createAnimal(['current_pen_id' => $period->colony_pen_id]);
        $registration = $this->createBreedingFemale($period, $female);

        $this->putJson('/api/v1/animals/'.$female->id, [
            'life_status' => 'dead', 'status_date' => '2026-09-01',
        ])->assertOk();
        $departure = AnimalPenMovement::query()->where('animal_id', $female->id)->firstOrFail();

        $this->putJson('/api/v1/animals/'.$female->id, ['status_date' => '2026-09-02'])->assertOk();

        $this->assertSame('2026-09-02', $female->fresh()->status_date->toDateString());
        $this->assertSame('2026-09-02', $registration->fresh()->exit_date->toDateString());
        $this->assertSame('2026-09-02', $departure->fresh()->movement_date->toDateString());

        $this->putJson('/api/v1/animals/'.$female->id, ['status_date' => '2026-05-01'])->assertUnprocessable();
        $this->assertSame('2026-09-02', $female->fresh()->status_date->toDateString());
    }

    public function test_death_date_correction_updates_sire_period_and_exits(): void
    {
        $this->actingAsAdmin();
        $male = $this->createAnimal(['sex' => 'male']);
        $period = $this->createBreedingPeriod($male);
        $registration = $this->createBreedingFemale($period);

        $this->putJson('/api/v1/animals/'.$male->id, [
            'life_status' => 'dead', 'status_date' => '2026-09-01',
        ])->assertOk();
        $this->putJson('/api/v1/animals/'.$male->id, ['status_date' => '2026-09-02'])->assertOk();

        $this->assertSame('2026-09-02', $period->fresh()->end_date->toDateString());
        $this->assertSame('2026-09-02', $registration->fresh()->exit_date->toDateString());
        $this->assertTrue($period->fresh()->closed_by_male_death);
    }

    public function test_manual_period_end_is_not_moved_with_the_sires_death_date(): void
    {
        $this->actingAsAdmin();
        $male = $this->createAnimal(['sex' => 'male']);
        $period = $this->createBreedingPeriod($male, overrides: [
            'status' => 'closed', 'end_date' => '2026-09-01',
        ]);

        $this->putJson('/api/v1/animals/'.$male->id, [
            'life_status' => 'dead', 'status_date' => '2026-09-01',
        ])->assertOk();
        $this->putJson('/api/v1/animals/'.$male->id, ['status_date' => '2026-09-02'])->assertOk();

        $this->assertSame('2026-09-01', $period->fresh()->end_date->toDateString());
        $this->assertFalse($period->fresh()->closed_by_male_death);
    }

    public function test_dead_animal_cannot_be_changed_directly_back_to_alive(): void
    {
        $this->actingAsAdmin();
        $animal = $this->createAnimal();
        $this->putJson('/api/v1/animals/'.$animal->id, [
            'life_status' => 'dead', 'status_date' => '2026-09-01',
        ])->assertOk();

        $this->putJson('/api/v1/animals/'.$animal->id, ['life_status' => 'alive'])
            ->assertUnprocessable();
        $this->assertSame('dead', $animal->fresh()->life_status);
    }

    public function test_death_certificate_must_match_recorded_date_and_locks_correction(): void
    {
        $this->actingAsAdmin();
        $animal = $this->createAnimal();
        $this->putJson('/api/v1/animals/'.$animal->id, [
            'life_status' => 'dead', 'status_date' => '2026-09-01',
        ])->assertOk();

        $payload = [
            'animal_id' => $animal->id,
            'certificate_type_id' => $this->certificateType('KEMATIAN')->id,
            'death_time' => '10:00:00',
            'cause_of_death' => 'Sakit',
        ];
        $this->postJson('/api/v1/certificates', $payload + ['death_date' => '2026-09-02'])
            ->assertUnprocessable();
        $this->postJson('/api/v1/certificates', $payload + ['death_date' => '2027-01-01'])
            ->assertUnprocessable();
        $this->postJson('/api/v1/certificates', $payload + ['death_date' => '2026-09-01'])
            ->assertCreated();
        $this->putJson('/api/v1/animals/'.$animal->id, ['status_date' => '2026-09-02'])
            ->assertUnprocessable();
    }

    public function test_correcting_pregnancy_check_does_not_replace_death_date(): void
    {
        $this->actingAsAdmin();
        $female = $this->createAnimal();
        $registration = $this->createBreedingFemale(female: $female, overrides: ['mating_date' => '2026-05-18']);
        $checkId = $this->postJson('/api/v1/pregnancy-checks', [
            'breeding_female_id' => $registration->id,
            'check_date' => '2026-09-01',
            'is_pregnant' => true,
        ])->assertCreated()->json('data.id');

        $this->putJson('/api/v1/animals/'.$female->id, [
            'life_status' => 'dead', 'status_date' => '2026-09-01',
        ])->assertOk();
        $this->putJson('/api/v1/pregnancy-checks/'.$checkId, ['check_date' => '2026-08-31'])->assertOk();

        $this->assertSame('dead', $female->fresh()->life_status);
        $this->assertSame('2026-09-01', $female->fresh()->status_date->toDateString());
    }

    public function test_revoked_death_certificate_cannot_be_reactivated_after_death_date_changes(): void
    {
        $this->actingAsAdmin();
        $animal = $this->createAnimal();
        $this->putJson('/api/v1/animals/'.$animal->id, [
            'life_status' => 'dead', 'status_date' => '2026-09-01',
        ])->assertOk();
        $certificateId = $this->postJson('/api/v1/certificates', [
            'animal_id' => $animal->id,
            'certificate_type_id' => $this->certificateType('KEMATIAN')->id,
            'death_date' => '2026-09-01',
            'death_time' => '10:00:00',
            'cause_of_death' => 'Sakit',
        ])->assertCreated()->json('data.id');
        $this->postJson('/api/v1/certificates/'.$certificateId.'/revoke', ['reason' => 'Perlu koreksi tanggal'])
            ->assertOk();
        $this->putJson('/api/v1/animals/'.$animal->id, ['status_date' => '2026-09-02'])->assertOk();

        $this->postJson('/api/v1/certificates/'.$certificateId.'/unrevoke')->assertUnprocessable();
    }

    public function test_revoked_death_certificate_cannot_be_reactivated_after_birth_status_correction(): void
    {
        $this->actingAsAdmin();
        $workflow = $this->createBirthWorkflow();
        $offspring = $workflow['offspring'];
        $record = $workflow['offspringBirth'];
        $this->putJson('/api/v1/offspring-births/'.$record->id, ['birth_status' => 'dead'])->assertOk();

        $certificateId = $this->postJson('/api/v1/certificates', [
            'animal_id' => $offspring->id,
            'certificate_type_id' => $this->certificateType('KEMATIAN')->id,
            'death_date' => '2026-05-17',
            'death_time' => '10:00:00',
            'cause_of_death' => 'Mati saat lahir',
        ])->assertCreated()->json('data.id');
        $this->postJson('/api/v1/certificates/'.$certificateId.'/revoke', ['reason' => 'Koreksi status lahir'])
            ->assertOk();
        $this->putJson('/api/v1/offspring-births/'.$record->id, ['birth_status' => 'alive'])->assertOk();

        $this->postJson('/api/v1/certificates/'.$certificateId.'/unrevoke')->assertUnprocessable();
    }

    public function test_correcting_birth_status_restores_animal_only_when_death_was_at_birth(): void
    {
        $this->actingAsAdmin();
        $workflow = $this->createBirthWorkflow();
        $offspring = $workflow['offspring'];
        $record = $workflow['offspringBirth'];

        $this->putJson('/api/v1/offspring-births/'.$record->id, ['birth_status' => 'dead'])->assertOk();
        $this->assertSame('dead', $offspring->fresh()->life_status);
        $this->assertSame('2026-05-17', $offspring->fresh()->status_date->toDateString());

        $this->putJson('/api/v1/offspring-births/'.$record->id, ['birth_status' => 'alive'])->assertOk();
        $this->assertSame('alive', $offspring->fresh()->life_status);
        $this->assertNull($offspring->fresh()->status_date);

        $this->putJson('/api/v1/animals/'.$offspring->id, [
            'life_status' => 'dead', 'status_date' => '2026-09-01',
        ])->assertOk();
        $this->putJson('/api/v1/offspring-births/'.$record->id, ['birth_status' => 'alive'])->assertOk();
        $this->assertSame('dead', $offspring->fresh()->life_status);
    }

    public function test_historical_care_can_be_corrected_but_not_moved_after_death(): void
    {
        $this->actingAsAdmin();
        $animal = $this->createAnimal();
        $weight = WeightRecord::query()->create([
            'animal_id' => $animal->id, 'record_date' => '2026-08-01', 'weight_kg' => 30,
        ]);
        $treatment = HealthTreatment::query()->create([
            'animal_id' => $animal->id, 'treatment_date' => '2026-08-01',
            'treatment_group' => 'Pemeriksaan', 'product_name' => 'Vitamin',
        ]);
        $vaccination = Vaccination::query()->create([
            'animal_id' => $animal->id, 'vaccination_date' => '2026-08-01',
            'category_name' => 'Vaksin A', 'product_name' => 'Produk A',
        ]);
        $this->putJson('/api/v1/animals/'.$animal->id, [
            'life_status' => 'dead', 'status_date' => '2026-09-01',
        ])->assertOk();

        $this->putJson('/api/v1/weight-records/'.$weight->id, ['weight_kg' => 31])->assertOk();
        $this->putJson('/api/v1/health-treatments/'.$treatment->id, ['notes' => 'Dikoreksi'])->assertOk();
        $this->putJson('/api/v1/vaccinations/'.$vaccination->id, ['notes' => 'Dikoreksi'])->assertOk();
        $this->putJson('/api/v1/weight-records/'.$weight->id, ['record_date' => '2026-09-02'])->assertUnprocessable();
        $this->putJson('/api/v1/health-treatments/'.$treatment->id, ['treatment_date' => '2026-09-02'])->assertUnprocessable();
        $this->putJson('/api/v1/vaccinations/'.$vaccination->id, ['vaccination_date' => '2026-09-02'])->assertUnprocessable();
    }

    public function test_missing_historical_records_can_be_added_before_but_not_after_death(): void
    {
        $this->actingAsAdmin();
        $animal = $this->createAnimal();
        $this->putJson('/api/v1/animals/'.$animal->id, [
            'life_status' => 'dead', 'status_date' => '2026-09-01',
        ])->assertOk();

        $records = [
            'weight-records' => ['record_date' => '2026-08-01', 'weight_kg' => 30],
            'health-treatments' => ['treatment_date' => '2026-08-01', 'treatment_group' => 'Pemeriksaan', 'product_name' => 'Vitamin'],
            'vaccinations' => ['vaccination_date' => '2026-08-01', 'category_name' => 'Vaksin A', 'product_name' => 'Produk A'],
        ];
        foreach ($records as $path => $attributes) {
            $this->postJson('/api/v1/'.$path, ['animal_id' => $animal->id] + $attributes)->assertCreated();
            $dateField = array_key_first($attributes);
            $this->postJson('/api/v1/'.$path, ['animal_id' => $animal->id] + array_replace($attributes, [$dateField => '2026-09-02']))
                ->assertUnprocessable();
        }
    }

    public function test_birth_date_cannot_be_cleared_after_birth_is_recorded(): void
    {
        $this->actingAsAdmin();
        $workflow = $this->createBirthWorkflow();
        $this->putJson('/api/v1/animals/'.$workflow['offspring']->id, ['birth_date' => null])
            ->assertUnprocessable();
        $this->assertSame('2026-05-17', $workflow['offspring']->fresh()->birth_date->toDateString());
    }
}
