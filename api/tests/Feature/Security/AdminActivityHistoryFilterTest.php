<?php

namespace Tests\Feature\Security;

use App\Models\AdminActivityLog;
use Tests\Feature\Support\ApiTestCase;

class AdminActivityHistoryFilterTest extends ApiTestCase
{
    public function test_subject_filter_finds_old_history_before_pagination(): void
    {
        $this->actingAsAdmin();

        AdminActivityLog::query()->create([
            'admin_id' => $this->admin->id,
            'action' => 'update',
            'module' => 'animals',
            'subject_id' => 77,
            'method' => 'PUT',
            'path' => 'animals/77',
            'created_at' => now()->subDays(2),
        ]);

        for ($id = 1; $id <= 10; $id++) {
            AdminActivityLog::query()->create([
                'admin_id' => $this->admin->id,
                'action' => 'update',
                'module' => 'animals',
                'subject_id' => $id,
                'method' => 'PUT',
                'path' => 'animals/'.$id,
                'created_at' => now()->subDay(),
            ]);
        }

        $this->getJson('/api/v1/admin-activity-logs?module=animals&subject_id=77&per_page=8')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.subject_id', 77);

        $this->getJson('/api/v1/admin-activity-logs?subject_id=invalid')
            ->assertUnprocessable();
    }
}
