<?php

namespace Tests\Feature\Operational;

use App\Models\BirthEvent;
use App\Models\HealthTreatment;
use Illuminate\Support\Carbon;
use Tests\Feature\Support\ApiTestCase;

class AdminDashboardSummaryTest extends ApiTestCase
{
    public function test_summary_counts_all_records_but_limits_detail_payload(): void
    {
        $this->actingAsAdmin();
        foreach (range(1, 7) as $id) {
            $this->createAnimal(['tag_number' => 'GOAT-'.$id, 'birth_date' => '2026-01-01']);
        }
        BirthEvent::query()->create([
            'dam_id' => $this->createAnimal()->id,
            'sire_id' => $this->createAnimal(['sex' => 'male'])->id,
            'birth_date' => '2026-05-17',
            'offspring_count' => 2,
            'birth_process' => 'normal',
        ]);

        $this->getJson('/api/v1/admin/dashboard-summary?year=2026')
            ->assertOk()
            ->assertJsonPath('total_animals', 9)
            ->assertJsonPath('counts.all', 9)
            ->assertJsonPath('birth_chart.4', 1)
            ->assertJsonPath('offspring_chart.4', 2)
            ->assertJsonCount(5, 'latest_animals');

        $this->getJson('/api/v1/admin/dashboard-summary?year=9999')
            ->assertOk()->assertJsonPath('selected_birth_year', 2026);
    }

    public function test_recent_overdue_and_today_reminders_remain_in_the_summary(): void
    {
        $this->travelTo(Carbon::parse('2026-09-30'));
        $this->actingAsAdmin();
        $period = $this->createBreedingPeriod();
        $animal = $this->createAnimal();
        $oldestFemaleId = null;
        $oldestTreatmentId = null;

        foreach (range(1, 13) as $day) {
            $date = sprintf('2026-09-%02d', $day);
            $female = $this->createBreedingFemale($period, overrides: [
                'mating_date' => '2026-05-18',
                'expected_birth_date' => $date,
            ]);
            $treatment = HealthTreatment::query()->create([
                'animal_id' => $animal->id,
                'treatment_group' => 'Pemeriksaan',
                'product_name' => 'Produk-'.$day,
                'treatment_date' => '2026-08-01',
                'next_control_date' => $date,
            ]);
            $oldestFemaleId ??= $female->id;
            $oldestTreatmentId ??= $treatment->id;
        }

        $todayFemale = $this->createBreedingFemale($period, overrides: [
            'mating_date' => '2026-05-18',
            'expected_birth_date' => '2026-09-30',
        ]);
        $todayTreatment = HealthTreatment::query()->create([
            'animal_id' => $animal->id,
            'treatment_group' => 'Pemeriksaan',
            'product_name' => 'Produk-hari-ini',
            'treatment_date' => '2026-08-01',
            'next_control_date' => '2026-09-30',
        ]);

        $summary = $this->getJson('/api/v1/admin/dashboard-summary')->assertOk()->json();

        $femaleIds = array_column($summary['breeding_females'], 'id');
        $treatmentIds = array_column($summary['health_treatments'], 'id');
        $this->assertContains($todayFemale->id, $femaleIds);
        $this->assertContains($todayTreatment->id, $treatmentIds);
        $this->assertNotContains($oldestFemaleId, $femaleIds);
        $this->assertNotContains($oldestTreatmentId, $treatmentIds);
    }
}
