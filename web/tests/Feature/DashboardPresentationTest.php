<?php

namespace Tests\Feature;

use App\Support\DashboardViewData;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DashboardPresentationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_missing_token_never_displays_sample_farm_data(): void
    {
        $dashboard = app(DashboardViewData::class)->data(null);
        $html = view('pages.admin.dashboard', compact('dashboard'))->render();

        $this->assertSame([], $dashboard['stats']);
        $this->assertSame([], $dashboard['latestAnimals']);
        $this->assertSame([], $dashboard['activities']);
        $this->assertStringContainsString('Sesi login Anda telah berakhir', $html);
        $this->assertStringNotContainsString('Ringkasan Kelahiran', $html);
        $this->assertStringNotContainsString('BBH-001', $html);
    }

    public function test_dashboard_shows_status_priority_and_empty_states_consistently(): void
    {
        $dashboard = $this->dashboard([
            'priorityTasks' => [[
                'tone' => 'danger',
                'status' => 'Lewat tenggat',
                'title' => 'Tanggal kontrol terlewat',
                'note' => 'Perbarui catatan kontrol.',
                'date' => '2026-09-28',
                'action_label' => 'Buka catatan',
                'action_url' => '/admin/dashboard',
            ]],
            'latestAnimals' => [
                ['BBH-001', 'Saanen', 'Betina', 'Hidup', '2026-09-27'],
                ['BBH-002', 'Saanen', 'Jantan', 'Mati', '2026-09-28'],
            ],
        ]);

        $html = view('pages.admin.dashboard', compact('dashboard'))->render();

        $this->assertStringContainsString('Ringkasan Kelahiran', $html);
        $this->assertStringContainsString('dashboard-status-pill is-danger', $html);
        $this->assertStringContainsString('28 Sep 2026', $html);
        $this->assertStringContainsString('data-tone="positive">Hidup', $html);
        $this->assertStringContainsString('data-tone="negative">Mati', $html);
        $this->assertStringNotContainsString('Aktivitas Terbaru', $html);
        $this->assertStringNotContainsString('Terakhir Update', $html);
    }

    public function test_super_admin_activity_panel_and_empty_lists_have_messages(): void
    {
        $dashboard = $this->dashboard(['showActivities' => true]);
        $html = view('pages.admin.dashboard', compact('dashboard'))->render();

        $this->assertStringContainsString('Aktivitas Terbaru', $html);
        $this->assertStringContainsString('Belum ada aktivitas yang dicatat.', $html);
        $this->assertStringContainsString('Belum ada data kambing yang dicatat.', $html);
    }

    public function test_dashboard_reads_one_bounded_summary_response(): void
    {
        Http::fake(['*' => Http::response([
            'total_animals' => 2,
            'counts' => ['all' => 2, 'adultFemales' => 2, 'pregnant' => 1],
            'trends' => ['all' => array_fill(0, 12, 0)],
            'birth_years' => [2026],
            'selected_birth_year' => 2026,
            'birth_chart' => array_fill(0, 12, 0),
            'offspring_chart' => array_fill(0, 12, 0),
            'current_year_birth_chart' => array_fill(0, 12, 0),
            'breeding_females' => [],
            'health_treatments' => [],
            'activities' => [],
            'latest_animals' => [],
        ])]);

        $dashboard = app(DashboardViewData::class)->data('token', 2026);

        $this->assertSame('2', $dashboard['stats'][0]['value']);
        $this->assertSame('1', $dashboard['stats'][6]['value']);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/admin/dashboard-summary'));
    }

    public function test_today_reminder_is_not_hidden_by_old_overdue_tasks(): void
    {
        $this->travelTo(Carbon::parse('2026-09-30'));
        $females = array_map(fn (int $day) => [
            'id' => $day,
            'entry_date' => '2026-05-17',
            'mating_date' => '2026-05-18',
            'expected_birth_date' => sprintf('2026-09-%02d', $day),
            'exit_date' => null,
            'female_animal' => ['tag_number' => 'GOAT-'.$day, 'reproductive_status' => 'bunting'],
            'breeding_period' => ['period_code' => 'PERIODE-1'],
        ], range(1, 13));
        $females[] = array_replace($females[0], [
            'id' => 14,
            'expected_birth_date' => '2026-09-30',
            'female_animal' => ['tag_number' => 'GOAT-TODAY', 'reproductive_status' => 'bunting'],
        ]);

        Http::fake(['*' => Http::response([
            'total_animals' => 14,
            'counts' => ['all' => 14],
            'trends' => ['all' => array_fill(0, 12, 0)],
            'birth_years' => [2026],
            'selected_birth_year' => 2026,
            'birth_chart' => array_fill(0, 12, 0),
            'offspring_chart' => array_fill(0, 12, 0),
            'current_year_birth_chart' => array_fill(0, 12, 0),
            'breeding_females' => $females,
            'health_treatments' => [],
            'activities' => [],
            'latest_animals' => [],
        ])]);

        $tasks = app(DashboardViewData::class)->data('token')['priorityTasks'];

        $this->assertCount(12, $tasks);
        $this->assertSame('Hari ini', $tasks[0]['status']);
        $this->assertStringContainsString('GOAT-TODAY', $tasks[0]['note']);
        $this->assertStringContainsString('GOAT-13', $tasks[1]['note']);
    }

    public function test_scheduled_control_note_uses_indonesian_month(): void
    {
        $this->travelTo(Carbon::parse('2026-09-30'));
        Http::fake(['*' => Http::response([
            'counts' => ['all' => 1],
            'birth_chart' => array_fill(0, 12, 0),
            'offspring_chart' => array_fill(0, 12, 0),
            'current_year_birth_chart' => array_fill(0, 12, 0),
            'breeding_females' => [],
            'health_treatments' => [[
                'id' => 1,
                'next_control_date' => '2026-10-01',
                'treatment_group' => 'Pemeriksaan',
                'animal' => ['tag_number' => 'GOAT-1'],
            ]],
            'activities' => [],
            'latest_animals' => [],
        ])]);

        $tasks = app(DashboardViewData::class)->data('token')['priorityTasks'];

        $this->assertStringContainsString('01 Oktober 2026', $tasks[0]['note']);
    }

    private function dashboard(array $overrides = []): array
    {
        return array_replace(app(DashboardViewData::class)->data(null), [
            'stats' => [[
                'label' => 'Total Kambing',
                'value' => '2',
                'note' => '1 kambing tercatat hidup.',
                'tone' => 'green',
                'trend' => array_fill(0, 12, 0),
            ]],
            'apiFailureMessage' => null,
        ], $overrides);
    }
}
