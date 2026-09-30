<?php

namespace Tests\Feature\Operational;

use App\Models\BirthEvent;
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
}
