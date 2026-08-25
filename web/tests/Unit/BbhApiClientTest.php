<?php

namespace Tests\Unit;

use App\Support\BbhApiClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BbhApiClientTest extends TestCase
{
    public function test_paginated_data_collects_all_pages(): void
    {
        config(['services.bbh_api.base_url' => 'http://api.test/api/v1']);

        Http::fake([
            'http://api.test/api/v1/animals*' => Http::sequence()
                ->push([
                    'current_page' => 1,
                    'last_page' => 2,
                    'data' => [
                        ['id' => 1, 'tag_number' => '26-001'],
                    ],
                ])
                ->push([
                    'current_page' => 2,
                    'last_page' => 2,
                    'data' => [
                        ['id' => 2, 'tag_number' => '26-002'],
                    ],
                ]),
        ]);

        $result = app(BbhApiClient::class)->paginatedData('animals', [], 'token');

        $this->assertTrue($result['ok']);
        $this->assertFalse($result['truncated']);
        $this->assertSame(['26-001', '26-002'], array_column($result['data'], 'tag_number'));
        Http::assertSentCount(2);
    }

    public function test_paginated_batch_data_collects_multiple_resources(): void
    {
        config(['services.bbh_api.base_url' => 'http://api.test/api/v1']);

        Http::fake([
            'http://api.test/api/v1/animals*' => Http::sequence()
                ->push([
                    'current_page' => 1,
                    'last_page' => 1,
                    'data' => [
                        ['id' => 1, 'tag_number' => '26-001'],
                    ],
                ]),
            'http://api.test/api/v1/birth-events*' => Http::sequence()
                ->push([
                    'current_page' => 1,
                    'last_page' => 2,
                    'data' => [
                        ['id' => 10, 'birth_date' => '2026-08-01'],
                    ],
                ])
                ->push([
                    'current_page' => 2,
                    'last_page' => 2,
                    'data' => [
                        ['id' => 11, 'birth_date' => '2026-08-02'],
                    ],
                ]),
        ]);

        $result = app(BbhApiClient::class)->paginatedBatchData([
            'animals' => ['path' => 'animals'],
            'birthEvents' => ['path' => 'birth-events'],
        ], 'token');

        $this->assertTrue($result['animals']['ok']);
        $this->assertTrue($result['birthEvents']['ok']);
        $this->assertSame(['26-001'], array_column($result['animals']['data'], 'tag_number'));
        $this->assertSame(['2026-08-01', '2026-08-02'], array_column($result['birthEvents']['data'], 'birth_date'));
        Http::assertSentCount(3);
    }
}
