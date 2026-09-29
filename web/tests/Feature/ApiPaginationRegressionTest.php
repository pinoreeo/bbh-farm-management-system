<?php

namespace Tests\Feature;

use App\Support\AdminResourceViewData;
use App\Support\AdminTableViewData;
use App\Support\BbhApiClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApiPaginationRegressionTest extends TestCase
{
    public function test_animal_lookup_uses_exact_tag_filter(): void
    {
        Http::fake(['*' => Http::response([
            'data' => [['id' => 6001, 'tag_number' => 'GOAT-6001']],
            'current_page' => 1,
            'last_page' => 1,
        ])]);

        $animal = app(AdminResourceViewData::class)->animalByTag('GOAT-6001', 'token');

        $this->assertSame(6001, $animal['id']);
        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'tag_number=GOAT-6001'));
    }

    public function test_batched_pagination_can_read_past_fifty_pages(): void
    {
        Http::fake(function (Request $request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $page = (int) ($query['page'] ?? 1);

            return Http::response([
                'data' => [['id' => $page]],
                'current_page' => $page,
                'last_page' => 51,
            ]);
        });

        $result = app(BbhApiClient::class)->paginatedBatchData(['animals' => ['path' => 'animals']], 'token', PHP_INT_MAX);

        $this->assertTrue($result['animals']['ok']);
        $this->assertFalse($result['animals']['truncated']);
        $this->assertCount(51, $result['animals']['data']);
        $this->assertSame(51, $result['animals']['data'][50]['id']);
    }

    public function test_table_data_reports_when_the_api_page_limit_is_reached(): void
    {
        Http::fake(function (Request $request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $page = (int) ($query['page'] ?? 1);

            return Http::response([
                'data' => [['id' => $page, 'tag_number' => 'GOAT-'.$page]],
                'current_page' => $page,
                'last_page' => 3,
            ]);
        });

        $table = app(AdminTableViewData::class);
        $records = $table->records('animals', [], 'token', 2);

        $this->assertSame([1, 2], array_column($records, 'id'));
        $this->assertTrue($table->isTruncated());
        $this->assertNull($table->failureMessage());
    }

    public function test_table_data_includes_records_after_page_fifty_by_default(): void
    {
        Http::fake(function (Request $request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $page = (int) ($query['page'] ?? 1);

            return Http::response([
                'data' => [['id' => $page, 'tag_number' => 'GOAT-'.$page]],
                'current_page' => $page,
                'last_page' => 51,
            ]);
        });

        $table = app(AdminTableViewData::class);
        $records = $table->records('animals', [], 'token');

        $this->assertCount(51, $records);
        $this->assertSame('GOAT-51', $records[50]['raw']['tag_number']);
        $this->assertFalse($table->isTruncated());
    }

    public function test_form_options_include_page_fifty_one_without_reloading_the_same_endpoint(): void
    {
        Http::fake(function (Request $request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $page = (int) ($query['page'] ?? 1);

            return Http::response([
                'data' => [['id' => $page, 'breed_name' => 'Ras '.$page]],
                'current_page' => $page,
                'last_page' => 51,
            ]);
        });

        $fields = app(AdminResourceViewData::class)->fields('animals', [
            ['name' => 'breed_id', 'label' => 'Ras', 'type' => 'select'],
            ['name' => 'breed_id', 'label' => 'Ras', 'type' => 'select'],
        ], 'token');

        $this->assertSame('Ras 51', $fields[0]['options']['51']);
        $this->assertSame($fields[0]['options'], $fields[1]['options']);
        Http::assertSentCount(51);
    }

    public function test_record_history_requests_only_the_opened_record(): void
    {
        Http::fake(['*' => Http::response(['data' => [[
            'subject_id' => 77,
            'action' => 'update',
            'description' => 'Identitas diperbarui',
            'admin_name' => 'Admin',
            'created_at' => '2026-09-01T09:00:00',
        ]]])]);

        $history = app(AdminResourceViewData::class)->activityHistory('animals', 77, 'token');

        $this->assertCount(1, $history);
        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'module=animals')
            && str_contains($request->url(), 'subject_id=77')
            && str_contains($request->url(), 'per_page=8'));
    }

    public function test_breeding_period_detail_includes_females_and_checks_after_page_fifty(): void
    {
        Http::fake(function (Request $request) {
            $path = (string) parse_url($request->url(), PHP_URL_PATH);
            if (str_ends_with($path, '/breeding-periods/7')) {
                return Http::response(['id' => 7, 'period_code' => 'PERIOD-7']);
            }

            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $page = (int) ($query['page'] ?? 1);
            $data = [];
            if ($page === 51 && str_ends_with($path, '/breeding-females')) {
                $data = [['id' => 51, 'female_animal_id' => 99, 'female_animal' => ['tag_number' => 'GOAT-99']]];
            } elseif ($page === 51 && str_ends_with($path, '/pregnancy-checks')) {
                $data = [['id' => 900, 'female_animal_id' => 99, 'check_date' => '2026-09-01', 'is_pregnant' => true]];
            }

            return Http::response(['data' => $data, 'current_page' => $page, 'last_page' => 51]);
        });

        $context = app(AdminResourceViewData::class)->breedingPeriodContext(7, 'token');

        $this->assertSame(1, $context['summary']['total']);
        $this->assertSame('GOAT-99', $context['females'][0]['tag']);
        $this->assertSame(900, $context['females'][0]['check_id']);
    }
}
