<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReportExportAuthorizationTest extends TestCase
{
    public function test_regular_admin_cannot_open_activity_log_export_url(): void
    {
        Http::fake(fn (Request $request) => Http::response(['role' => 'admin']));

        $this->withSession([
            'bbh_api_token' => 'test-token',
            'bbh_admin_user' => ['role' => 'admin'],
        ])->get('/admin/reports/activity-logs/xlsx')->assertForbidden();

        Http::assertSentCount(1);
    }
}
