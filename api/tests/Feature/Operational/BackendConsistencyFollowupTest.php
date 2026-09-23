<?php

namespace Tests\Feature\Operational;

use App\Models\AnimalPenMovement;
use App\Models\BreedingFemale;
use App\Models\HealthTreatment;
use App\Models\Vaccination;
use App\Services\CertificateViewDataService;
use Illuminate\Database\QueryException;
use Tests\Feature\Support\ApiTestCase;

class BackendConsistencyFollowupTest extends ApiTestCase
{
    public function test_certificate_keeps_the_identity_and_birth_details_present_at_issue_time(): void
    {
        $this->actingAsAdmin();
        $workflow = $this->createBirthWorkflow();
        $certificate = $this->issueCertificate('KELAHIRAN', $workflow['offspring']);
        $originalChildTag = $workflow['offspring']->tag_number;
        $originalDamTag = $workflow['dam']->tag_number;

        $workflow['offspring']->update(['tag_number' => 'CHANGED-CHILD']);
        $workflow['dam']->update(['tag_number' => 'CHANGED-DAM']);

        $this->getJson('/api/v1/public/certificates/'.$certificate->certificate_number)
            ->assertOk()->assertJsonPath('animal.tag_number', $originalChildTag);
        $this->postJson('/api/v1/public/certificates/verify', ['certificate_number' => $certificate->certificate_number])
            ->assertOk()->assertJsonPath('animal.tag_number', $originalChildTag);
        $data = app(CertificateViewDataService::class)->build($certificate->fresh());
        $this->assertSame($originalChildTag, $data['animal_tag']);
        $this->assertSame($originalDamTag, $data['dam_tag']);
    }

    public function test_death_closes_active_female_registration_and_pen_record_atomically(): void
    {
        $this->actingAsAdmin();
        $period = $this->createBreedingPeriod();
        $female = $this->createAnimal(['current_pen_id' => $period->colony_pen_id]);
        $registration = $this->createBreedingFemale($period, $female);

        $this->putJson('/api/v1/animals/'.$female->id, [
            'life_status' => 'dead', 'status_date' => '2026-09-01',
        ])->assertOk();

        $this->assertSame('dead', $female->fresh()->life_status);
        $this->assertNull($female->fresh()->current_pen_id);
        $this->assertSame('2026-09-01', $registration->fresh()->exit_date->toDateString());
        $this->assertDatabaseHas('animal_pen_movements', [
            'animal_id' => $female->id, 'from_pen_id' => $period->colony_pen_id, 'to_pen_id' => null,
        ]);

        $certificate = $this->issueCertificate('KEMATIAN', $female, [
            'death_date' => '2026-09-01', 'death_time' => '10:00:00', 'cause_of_death' => 'Sakit',
        ]);
        $certificateData = app(CertificateViewDataService::class)->build($certificate->fresh());
        $this->assertStringContainsString($period->colonyPen->pen_code, $certificateData['animal_current_pen']);
        $this->putJson('/api/v1/animals/'.$female->id, ['life_status' => 'alive'])->assertUnprocessable();
        $this->assertSame('dead', $female->fresh()->life_status);
    }

    public function test_backdated_breeding_exit_preserves_later_pen_and_reproductive_status(): void
    {
        $this->actingAsAdmin();
        $period = $this->createBreedingPeriod();
        $female = $this->createAnimal([
            'current_pen_id' => $period->colony_pen_id,
            'reproductive_status' => 'bunting', 'status_date' => '2026-09-10',
        ]);
        $registration = $this->createBreedingFemale($period, $female);
        $earlierPen = $this->createPen(['colony_type' => 'koloni_bunting', 'colony_phase' => 'koloni_bunting']);
        $latestPen = $this->createPen(['colony_type' => 'koloni_laktasi', 'colony_phase' => 'koloni_laktasi']);

        $laterId = $this->postJson('/api/v1/animal-pen-movements', [
            'animal_id' => $female->id, 'to_pen_id' => $latestPen->id, 'movement_date' => '2026-09-15',
        ])->assertCreated()->json('data.id');
        $this->postJson('/api/v1/breeding-females/'.$registration->id.'/exit', [
            'exit_date' => '2026-08-01', 'exit_reason_code' => 'tidak_bunting', 'to_pen_id' => $earlierPen->id,
        ])->assertOk();

        $this->assertSame($latestPen->id, $female->fresh()->current_pen_id);
        $this->assertSame('bunting', $female->fresh()->reproductive_status);
        $this->assertSame($earlierPen->id, AnimalPenMovement::query()->findOrFail($laterId)->from_pen_id);
    }

    public function test_death_of_a_sire_closes_his_active_period_and_registrations(): void
    {
        $this->actingAsAdmin();
        $male = $this->createAnimal(['sex' => 'male']);
        $period = $this->createBreedingPeriod($male);
        $registration = $this->createBreedingFemale($period);

        $this->putJson('/api/v1/animals/'.$male->id, [
            'life_status' => 'dead', 'status_date' => '2026-09-01',
        ])->assertOk();

        $this->assertSame('closed', $period->fresh()->status);
        $this->assertSame('2026-09-01', $period->fresh()->end_date->toDateString());
        $this->assertSame('2026-09-01', $registration->fresh()->exit_date->toDateString());
        $this->assertSame('pejantan_mati', $registration->fresh()->exit_reason_code);
    }

    public function test_last_active_super_admin_cannot_be_disabled(): void
    {
        $this->actingAsAdmin();

        $this->putJson('/api/v1/users/'.$this->admin->id, ['is_active' => false])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Peringatan: Minimal harus ada satu super admin aktif.');
        $this->assertTrue($this->admin->fresh()->is_active);
    }

    public function test_breeding_capacity_and_active_membership_are_enforced(): void
    {
        $this->actingAsAdmin();
        $period = $this->createBreedingPeriod(pen: $this->createPen(['capacity' => 1]));
        $female = $this->createAnimal();
        $secondFemale = $this->createAnimal();
        $payload = ['breeding_period_id' => $period->id, 'entry_date' => '2026-05-18'];

        $this->postJson('/api/v1/breeding-females', $payload + ['female_animal_id' => $female->id])->assertCreated();
        $this->postJson('/api/v1/breeding-females', $payload + ['female_animal_id' => $secondFemale->id])->assertUnprocessable();

        $otherPeriod = $this->createBreedingPeriod();
        $this->postJson('/api/v1/breeding-females', [
            'breeding_period_id' => $otherPeriod->id,
            'female_animal_id' => $female->id,
            'entry_date' => '2026-05-18',
        ])->assertUnprocessable();
        $this->assertSame(1, BreedingFemale::query()->where('breeding_period_id', $period->id)->count());
    }

    public function test_period_cannot_close_with_an_active_female(): void
    {
        $this->actingAsAdmin();
        $period = $this->createBreedingPeriod();
        $registration = $this->createBreedingFemale($period);

        $this->postJson('/api/v1/breeding-periods/'.$period->id.'/close')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Peringatan: Keluarkan seluruh betina dari periode kawin sebelum menutupnya.');
        $this->assertSame('active', $period->fresh()->status);

        $registration->update(['exit_date' => '2026-09-01']);
        $this->postJson('/api/v1/breeding-periods/'.$period->id.'/close')->assertOk();
        $this->assertSame('closed', $period->fresh()->status);
    }

    public function test_correcting_entry_date_updates_its_managed_pen_movement(): void
    {
        $this->actingAsAdmin();
        $period = $this->createBreedingPeriod();
        $female = $this->createAnimal();
        $registrationId = $this->postJson('/api/v1/breeding-females', [
            'breeding_period_id' => $period->id,
            'female_animal_id' => $female->id,
            'entry_date' => '2026-05-18',
        ])->assertCreated()->json('data.id');
        $movement = AnimalPenMovement::query()->where('animal_id', $female->id)->firstOrFail();

        $this->putJson('/api/v1/breeding-females/'.$registrationId, ['entry_date' => '2026-05-19'])->assertOk();
        $this->assertSame('2026-05-19', $movement->fresh()->movement_date->toDateString());
        $this->assertSame('2026-05-19', $female->fresh()->status_date->toDateString());
        $this->putJson('/api/v1/animal-pen-movements/'.$movement->id, ['movement_date' => '2026-05-20'])
            ->assertUnprocessable();
    }

    public function test_occupied_pen_cannot_be_shrunk_disabled_or_repurposed(): void
    {
        $this->actingAsAdmin();
        $pen = $this->createPen(['capacity' => 2]);
        $this->createAnimal(['current_pen_id' => $pen->id]);
        $this->createAnimal(['current_pen_id' => $pen->id]);

        $this->putJson('/api/v1/colony-pens/'.$pen->id, ['capacity' => 1])->assertUnprocessable();
        $this->putJson('/api/v1/colony-pens/'.$pen->id, ['is_active' => false])->assertUnprocessable();
        $this->putJson('/api/v1/colony-pens/'.$pen->id, ['colony_phase' => 'koloni_bunting'])->assertUnprocessable();
        $this->assertSame(2, $pen->fresh()->capacity);
        $this->assertTrue($pen->fresh()->is_active);
    }

    public function test_database_rejects_duplicate_health_and_vaccination_records(): void
    {
        $animal = $this->createAnimal();
        $treatment = [
            'animal_id' => $animal->id, 'treatment_group' => 'Pemeriksaan',
            'product_name' => 'Vitamin A', 'treatment_date' => '2026-09-01',
        ];
        $vaccination = [
            'animal_id' => $animal->id, 'category_name' => 'Vaksin A',
            'product_name' => 'Produk A', 'vaccination_date' => '2026-09-01',
        ];
        HealthTreatment::query()->create($treatment);
        Vaccination::query()->create($vaccination);

        try {
            HealthTreatment::query()->create($treatment);
            $this->fail('Identical treatment should be rejected by the database.');
        } catch (QueryException) {
            $this->assertSame(1, HealthTreatment::query()->count());
        }
        try {
            Vaccination::query()->create($vaccination);
            $this->fail('Identical vaccination should be rejected by the database.');
        } catch (QueryException) {
            $this->assertSame(1, Vaccination::query()->count());
        }
    }
}
