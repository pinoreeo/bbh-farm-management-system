<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminResourceListControlsTest extends TestCase
{
    private $apiResponder = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->withSession(['bbh_api_token' => 'test-token']);

        $users = array_map(fn (int $id) => [
            'id' => $id,
            'name' => sprintf('Admin %02d', $id),
            'email' => $id === 12 ? 'needle@example.test' : sprintf('admin%02d@example.test', $id),
            'role' => 'admin',
            'is_active' => $id <= 11,
        ], range(1, 15));

        Http::fake(function (Request $request) use ($users) {
            if (str_contains($request->url(), '/auth/me')) {
                return Http::response(['id' => 1, 'role' => 'super_admin']);
            }

            if ($this->apiResponder !== null) {
                return ($this->apiResponder)($request);
            }

            return Http::response(['data' => $users, 'current_page' => 1, 'last_page' => 1]);
        });
    }

    public function test_search_finds_a_user_beyond_the_first_page(): void
    {
        $response = $this->get(route('admin.users', ['q' => 'needle']));

        $response->assertOk()->assertSee('Admin 12')->assertDontSee('Admin 01');
        $records = $response->viewData('records');
        $this->assertInstanceOf(LengthAwarePaginator::class, $records);
        $this->assertSame(1, $records->total());
        $this->assertSame(12, $records->items()[0]['id']);
    }

    public function test_account_status_filters_all_records_before_pagination(): void
    {
        $response = $this->get(route('admin.users', ['account_status' => 'inactive']));

        $response->assertOk()->assertSee('Admin 15')->assertDontSee('Admin 01');
        $records = $response->viewData('records');
        $this->assertSame(4, $records->total());
        $this->assertSame([12, 13, 14, 15], array_column($records->items(), 'id'));
    }

    public function test_sort_orders_all_records_before_pagination(): void
    {
        $response = $this->get(route('admin.users', ['sort' => 0, 'direction' => 'desc']));

        $response->assertOk();
        $records = $response->viewData('records');
        $this->assertSame(15, $records->total());
        $this->assertSame(range(15, 6), array_column($records->items(), 'id'));
        $this->assertStringContainsString('sort=0', $records->nextPageUrl());
        $this->assertStringContainsString('direction=desc', $records->nextPageUrl());
        $response->assertSee('data-server-sort', false);
    }

    public function test_api_failure_does_not_look_like_an_empty_list(): void
    {
        $this->apiResponder = fn (Request $request) => Http::response(['message' => 'Tidak tersedia'], 503);

        $this->get(route('admin.users'))
            ->assertOk()
            ->assertSee('Data Tidak Dapat Dimuat')
            ->assertDontSee('Belum ada data yang dicatat.');
    }

    public function test_global_search_reads_page_fifty_one_and_links_to_local_list_search(): void
    {
        $this->apiResponder = function (Request $request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $page = (int) ($query['page'] ?? 1);
            $isAnimals = str_contains((string) parse_url($request->url(), PHP_URL_PATH), '/animals');

            return Http::response([
                'data' => $isAnimals && $page === 51 ? [['id' => 33, 'tag_number' => 'GOAT-NEEDLE']] : [],
                'current_page' => $page,
                'last_page' => $isAnimals ? 51 : 1,
            ]);
        };

        $response = $this->get(route('admin.search', ['q' => 'NEEDLE']));

        $response->assertOk()
            ->assertSee('GOAT-NEEDLE')
            ->assertSee(route('admin.animals', ['q' => 'NEEDLE']), false);
        $this->assertSame(1, $response->viewData('results')->total());
        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/animals')
            && str_contains($request->url(), 'page=51')
            && ! str_contains($request->url(), 'search=NEEDLE'));
    }

    public function test_global_search_paginates_more_than_thirty_matches(): void
    {
        $this->apiResponder = function (Request $request) {
            $isAnimals = str_contains((string) parse_url($request->url(), PHP_URL_PATH), '/animals');

            return Http::response([
                'data' => $isAnimals ? array_map(fn (int $id) => [
                    'id' => $id,
                    'tag_number' => sprintf('GOAT-NEEDLE-%02d', $id),
                ], range(1, 35)) : [],
                'current_page' => 1,
                'last_page' => 1,
            ]);
        };

        $response = $this->get(route('admin.search', ['q' => 'NEEDLE', 'page' => 4]));

        $response->assertOk()->assertSee('GOAT-NEEDLE-35')->assertDontSee('GOAT-NEEDLE-01');
        $this->assertSame(35, $response->viewData('results')->total());
        $this->assertSame(4, $response->viewData('results')->currentPage());
    }

    public function test_certificate_download_action_points_to_pdf(): void
    {
        $html = view('components.admin.resource-row-actions', [
            'slug' => 'certificates',
            'id' => 7,
            'row' => [4 => 'Aktif'],
            'record' => ['raw' => []],
        ])->render();

        $this->assertStringContainsString('href="'.route('admin.certificates.pdf', ['id' => 7]).'" data-no-skeleton', $html);
        $this->assertStringContainsString('Unduh sertifikat', $html);
    }
}
