<?php

namespace Tests\Feature;

use App\Support\AdminNotificationViewData;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminNotificationLocaleTest extends TestCase
{
    public function test_scheduled_control_notification_uses_indonesian_month(): void
    {
        $this->travelTo(Carbon::parse('2026-09-30'));
        Http::fake(function (Request $request) {
            $items = str_contains($request->url(), '/health-treatments') ? [[
                'id' => 1,
                'next_control_date' => '2026-10-01',
                'treatment_group' => 'Pemeriksaan',
                'animal' => ['tag_number' => 'GOAT-1'],
            ]] : [];

            return Http::response(['data' => $items]);
        });

        $items = app(AdminNotificationViewData::class)->items('locale-test-token');
        $control = collect($items)->firstWhere('title', 'Jadwal kontrol');

        $this->assertNotNull($control);
        $this->assertStringContainsString('01 Oktober 2026', $control['body']);
        $this->assertSame('01 Oktober 2026', $control['time']);
    }
}
