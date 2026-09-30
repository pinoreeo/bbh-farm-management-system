<?php

namespace Tests\Feature\Operational;

use App\Models\AdminActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Support\ApiTestCase;

class AdminBrowseTest extends ApiTestCase
{
    public function test_list_search_filter_and_sort_are_applied_before_pagination(): void
    {
        $this->actingAsAdmin();

        foreach (range(1, 15) as $id) {
            User::query()->create([
                'name' => sprintf('Admin %02d', $id),
                'email' => sprintf('browse%02d@example.test', $id),
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => $id <= 11,
            ]);
        }

        $this->getJson('/api/v1/admin/browse/users?account_status=inactive&sort=0&direction=desc')
            ->assertOk()
            ->assertJsonPath('total', 4)
            ->assertJsonPath('data.0.name', 'Admin 15');

        $this->getJson('/api/v1/admin/browse/users?q=browse12')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.name', 'Admin 12');

        $this->getJson('/api/v1/admin/browse/users?page=2')
            ->assertOk()
            ->assertJsonPath('current_page', 2)
            ->assertJsonCount(6, 'data');
    }

    public function test_large_user_list_keeps_each_response_bounded(): void
    {
        $this->actingAsAdmin();

        foreach (range(0, 9) as $batch) {
            $users = [];
            foreach (range(1, 100) as $offset) {
                $number = $batch * 100 + $offset;
                $users[] = [
                    'name' => sprintf('Load User %04d', $number),
                    'email' => sprintf('load%04d@example.test', $number),
                    'password' => 'unused-in-this-test',
                    'role' => 'admin',
                    'is_active' => true,
                ];
            }
            DB::table('sys_users')->insert($users);
        }

        $this->getJson('/api/v1/admin/browse/users?page=50')
            ->assertOk()
            ->assertJsonPath('total', 1001)
            ->assertJsonPath('current_page', 50)
            ->assertJsonCount(10, 'data');

        $this->getJson('/api/v1/admin/browse/users?q=load1000')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonCount(1, 'data');
    }

    public function test_related_search_and_global_search_return_bounded_results(): void
    {
        $this->actingAsAdmin();
        $animal = $this->createAnimal(['tag_number' => 'GOAT-NEEDLE']);
        $this->createAnimal(['tag_number' => 'GOAT-OTHER']);

        $this->getJson('/api/v1/admin/browse/animals?q=NEEDLE')
            ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $animal->id);

        $this->getJson('/api/v1/admin/search?q=NEEDLE')
            ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.slug', 'animals')
            ->assertJsonPath('data.0.item.id', $animal->id);
    }

    public function test_regular_admin_cannot_browse_users(): void
    {
        $this->admin->update(['role' => 'admin']);
        $this->actingAsAdmin();

        $this->getJson('/api/v1/admin/browse/users')->assertForbidden();
        $this->getJson('/api/v1/admin/browse/activity-logs')->assertForbidden();
    }

    public function test_each_browse_resource_accepts_search_and_sort(): void
    {
        $this->actingAsAdmin();

        foreach ([
            'users' => 4, 'animals' => 7, 'weight-records' => 5, 'pens' => 7,
            'pen-movements' => 5, 'breeding-periods' => 6, 'breeding-females' => 8,
            'pregnancy-checks' => 6, 'birth-events' => 7, 'offspring-births' => 6,
            'health-treatments' => 8, 'vaccinations' => 6, 'postnatal-care' => 9,
            'certificates' => 5, 'certificate-logs' => 7, 'activity-logs' => 8,
            'rsa-keys' => 6,
        ] as $resource => $columnCount) {
            foreach (range(0, $columnCount - 1) as $sort) {
                $this->getJson('/api/v1/admin/browse/'.$resource.'?q=nomatch&sort='.$sort.'&direction=desc')
                    ->assertOk()->assertJsonPath('total', 0);
            }
        }
    }

    public function test_log_list_defaults_to_the_latest_year(): void
    {
        $this->actingAsAdmin();
        $older = null;
        foreach ([2025, 2026] as $year) {
            $log = AdminActivityLog::query()->create([
                'action' => 'update',
                'module' => 'animals',
                'method' => 'PUT',
                'path' => '/api/v1/animals/1',
                'created_at' => "{$year}-03-01 08:00:00",
            ]);
            $older ??= $log;
        }

        $this->getJson('/api/v1/admin/browse/activity-logs')
            ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('filter_years', [2026, 2025]);
        $this->getJson('/api/v1/admin/browse/activity-logs?year=2025')
            ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $older->id);
    }
}
