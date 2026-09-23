<?php

namespace Tests\Feature\Operational;

use App\Models\BirthEvent;
use App\Models\BreedingFemale;
use App\Models\OffspringBirth;
use App\Models\PregnancyCheck;
use App\Services\PregnancyCheckService;
use App\Services\ReproductiveStatusService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Support\ApiTestCase;

class ReproductionConsistencyTest extends ApiTestCase
{
    private function pregnancy(): BreedingFemale
    {
        $this->actingAsAdmin();
        $registration = $this->createBreedingFemale(overrides: ['mating_date' => '2026-05-18']);
        $this->check($registration, '2026-06-01');
        $this->check($registration, '2026-07-01');

        return $registration;
    }

    private function check(BreedingFemale $registration, string $date, bool $pregnant = true): int
    {
        return $this->postJson('/api/v1/pregnancy-checks', [
            'breeding_female_id' => $registration->id,
            'check_date' => $date,
            'is_pregnant' => $pregnant,
        ])->assertCreated()->json('data.id');
    }

    private function birthPayload(BreedingFemale $registration): array
    {
        return ['dam_id' => $registration->female_animal_id, 'birth_date' => '2026-09-01', 'offspring_count' => 3, 'birth_process' => 'normal'];
    }

    public function test_repeated_positive_checks_only_allow_one_birth_per_cycle(): void
    {
        $registration = $this->pregnancy();
        $this->postJson('/api/v1/birth-events', $this->birthPayload($registration))->assertCreated()
            ->assertJsonPath('data.breeding_female_id', $registration->id);
        $this->postJson('/api/v1/birth-events', $this->birthPayload($registration))->assertUnprocessable();

        $this->assertSame(1, BirthEvent::query()->count());
        $this->assertSame(2, PregnancyCheck::query()->where('outcome_status', 'born')->count());
    }

    public function test_latest_negative_check_does_not_fall_back_to_an_old_positive(): void
    {
        $registration = $this->pregnancy();
        $this->check($registration, '2026-08-01', false);
        $this->postJson('/api/v1/birth-events', $this->birthPayload($registration))->assertUnprocessable();
    }

    public function test_legacy_completed_check_also_closes_older_checks_in_the_cycle(): void
    {
        $registration = $this->pregnancy();
        PregnancyCheck::query()->orderByDesc('check_date')->first()->update(['outcome_status' => 'born']);
        $this->postJson('/api/v1/birth-events', $this->birthPayload($registration))->assertUnprocessable();
    }

    public function test_old_check_corrections_do_not_replace_latest_status(): void
    {
        $registration = $this->pregnancy();
        $old = PregnancyCheck::query()->orderBy('check_date')->first();
        $this->putJson('/api/v1/pregnancy-checks/'.$old->id, ['is_pregnant' => false, 'check_date' => '2026-06-02'])->assertOk();
        $this->assertSame('bunting', $registration->femaleAnimal->fresh()->reproductive_status);
        $this->assertSame('2026-07-01', $registration->femaleAnimal->fresh()->status_date->toDateString());

        $this->postJson('/api/v1/birth-events', $this->birthPayload($registration))->assertCreated();
        $registration->update(['exit_date' => '2026-09-01']);
        $registration->breedingPeriod->update(['status' => 'closed', 'end_date' => '2026-09-01']);
        $this->putJson('/api/v1/pregnancy-checks/'.$old->id, ['check_date' => '2026-06-03'])->assertOk();
        $this->assertSame('melahirkan', $registration->femaleAnimal->fresh()->reproductive_status);
        $this->assertSame('2026-09-01', $registration->femaleAnimal->fresh()->status_date->toDateString());
        $this->postJson('/api/v1/birth-events', $this->birthPayload($registration))->assertUnprocessable();
    }

    public function test_moving_latest_check_back_recomputes_from_the_remaining_history(): void
    {
        $registration = $this->pregnancy();
        $latest = $this->check($registration, '2026-08-01', false);
        $this->putJson('/api/v1/pregnancy-checks/'.$latest, ['check_date' => '2026-06-15'])->assertOk();
        $this->assertSame('bunting', $registration->femaleAnimal->fresh()->reproductive_status);
        $this->assertSame('2026-07-01', $registration->femaleAnimal->fresh()->status_date->toDateString());
    }

    public function test_completed_cycle_cannot_be_reopened_through_request_payload(): void
    {
        $registration = $this->pregnancy();
        $this->postJson('/api/v1/birth-events', $this->birthPayload($registration))->assertCreated();
        $check = PregnancyCheck::query()->first();
        $this->putJson('/api/v1/pregnancy-checks/'.$check->id, ['outcome_status' => 'other'])->assertUnprocessable();
        $this->putJson('/api/v1/pregnancy-checks/'.$check->id, ['outcome_status' => null])->assertOk();
        $this->assertSame('born', $check->fresh()->outcome_status);
        $this->putJson('/api/v1/pregnancy-checks/'.$check->id, ['check_date' => '2026-09-02'])->assertUnprocessable();
    }

    public function test_new_cycle_can_have_its_own_birth(): void
    {
        $this->travelTo(Carbon::parse('2027-03-02'));
        $registration = $this->pregnancy();
        $this->postJson('/api/v1/birth-events', $this->birthPayload($registration))->assertCreated();
        $next = $this->createBreedingFemale(
            $this->createBreedingPeriod(overrides: ['start_date' => '2026-10-01']),
            $registration->femaleAnimal,
            ['entry_date' => '2026-10-01', 'mating_date' => '2026-10-02'],
        );
        $this->check($next, '2026-11-01');
        $this->postJson('/api/v1/birth-events', array_replace($this->birthPayload($next), ['birth_date' => '2027-03-01']))->assertCreated();
        $this->assertSame(2, BirthEvent::query()->count());
    }

    public function test_birth_edits_cannot_contradict_recorded_children(): void
    {
        $this->actingAsAdmin();
        $workflow = $this->createBirthWorkflow();
        $birth = $workflow['birthEvent'];
        $birth->update(['offspring_count' => 2]);
        OffspringBirth::query()->create([
            'birth_event_id' => $birth->id,
            'offspring_animal_id' => $this->createAnimal(['birth_date' => '2026-05-17'])->id,
            'birth_weight_kg' => 3, 'birth_status' => 'alive',
        ]);
        $this->putJson('/api/v1/birth-events/'.$birth->id, ['offspring_count' => 1])->assertUnprocessable();
        $this->putJson('/api/v1/birth-events/'.$birth->id, ['birth_date' => '2026-05-18'])->assertUnprocessable();
        $this->putJson('/api/v1/birth-events/'.$birth->id, ['dam_id' => $this->createAnimal()->id])->assertUnprocessable();
        $this->putJson('/api/v1/birth-events/'.$birth->id, ['notes' => 'Koreksi catatan', 'birth_date' => '2026-05-17'])->assertOk();
        $this->assertSame(2, $birth->fresh()->offspring_count);
        $this->assertSame('2026-05-17', $workflow['offspring']->fresh()->birth_date->toDateString());
    }

    public function test_unlinked_legacy_birth_is_not_silently_assigned_to_a_cycle(): void
    {
        $this->actingAsAdmin();
        $birth = $this->createBirthWorkflow()['birthEvent'];
        $this->assertNull($birth->breeding_female_id);
        $this->putJson('/api/v1/birth-events/'.$birth->id, ['notes' => 'Riwayat lama'])->assertOk();
        $this->assertNull($birth->fresh()->breeding_female_id);
    }

    public function test_correcting_birth_date_without_children_keeps_mating_constraints(): void
    {
        $registration = $this->pregnancy();
        $id = $this->postJson('/api/v1/birth-events', $this->birthPayload($registration))->assertCreated()->json('data.id');
        $this->putJson('/api/v1/birth-events/'.$id, ['birth_date' => '2026-05-01'])->assertUnprocessable();
        $this->putJson('/api/v1/birth-events/'.$id, ['birth_date' => '2026-08-31'])->assertOk();
        $this->assertSame('2026-08-31', $registration->femaleAnimal->fresh()->status_date->toDateString());
    }

    public function test_check_and_status_changes_roll_back_together(): void
    {
        $registration = $this->pregnancy();
        $this->mock(ReproductiveStatusService::class)->shouldReceive('sync')->andThrow(new \RuntimeException('Status update failed'));
        try {
            app(PregnancyCheckService::class)->store([
                'breeding_female_id' => $registration->id, 'check_date' => '2026-08-01', 'is_pregnant' => false,
            ]);
            $this->fail('A failed status update should abort the transaction.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Status update failed', $exception->getMessage());
        }
        $this->assertSame(2, PregnancyCheck::query()->count());
        $this->assertSame('bunting', $registration->femaleAnimal->fresh()->reproductive_status);
    }

    public function test_last_child_slot_is_checked_inside_the_parent_transaction(): void
    {
        $this->actingAsAdmin();
        $birth = $this->createBirthWorkflow()['birthEvent'];
        $birth->update(['offspring_count' => 2]);
        $baseline = DB::transactionLevel();
        $countLevels = [];
        DB::listen(function ($query) use (&$countLevels) {
            if (str_contains($query->sql, 'count(*)') && str_contains($query->sql, 'breed_offspring')) {
                $countLevels[] = DB::transactionLevel();
            }
        });
        $payload = [
            'birth_event_id' => $birth->id, 'breed_id' => $this->breed()->id,
            'sex' => 'female', 'generation' => 'F1', 'birth_weight_kg' => 3,
        ];
        $this->postJson('/api/v1/offspring-births', $payload + ['tag_number' => 'LAST-SLOT'])->assertCreated();
        $this->postJson('/api/v1/offspring-births', $payload + ['tag_number' => 'EXTRA-CHILD'])->assertUnprocessable();
        $this->assertNotEmpty($countLevels);
        foreach ($countLevels as $level) {
            $this->assertGreaterThan($baseline, $level);
        }
        $this->assertSame(2, $birth->offspringBirths()->count());
        $this->assertDatabaseMissing('animals', ['tag_number' => 'EXTRA-CHILD']);
    }
}
