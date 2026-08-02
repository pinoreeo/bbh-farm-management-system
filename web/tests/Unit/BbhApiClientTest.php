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
}
