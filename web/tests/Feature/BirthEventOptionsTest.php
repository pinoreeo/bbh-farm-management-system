<?php

namespace Tests\Feature;

use App\Support\AdminResourceViewData;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BirthEventOptionsTest extends TestCase
{
    public function test_birth_form_uses_latest_check_and_keeps_shared_sire_available(): void
    {
        $checks = [
            $this->check(1, 101, 11, '2026-08-01', true),
            $this->check(2, 101, 11, '2026-08-10', false),
            $this->check(3, 102, 12, '2026-08-10', true),
            $this->check(4, 103, 13, '2026-08-10', true),
            $this->check(5, 104, 14, '2026-08-10', true),
            $this->check(6, 105, 15, '2026-08-01', true, 'born'),
            $this->check(7, 105, 15, '2026-08-10', true),
        ];
        Http::fake(function (Request $request) use ($checks) {
            $items = match (true) {
                str_contains($request->url(), '/pregnancy-checks') => $checks,
                str_contains($request->url(), '/birth-events') => [['breeding_female_id' => 14]],
                str_contains($request->url(), '/animals') => [['id' => 7, 'sex' => 'male', 'tag_number' => 'SIRE-7']],
                default => [],
            };

            return Http::response(['data' => $items, 'current_page' => 1, 'last_page' => 1]);
        });

        $resources = app(AdminResourceViewData::class);
        $dam = $resources->fields('birth-events', [['name' => 'dam_id']], 'token')[0];
        $sire = $resources->fields('birth-events', [['name' => 'sire_id']], 'token')[0];

        $this->assertEqualsCanonicalizing(['102' => 'DAM-102', '103' => 'DAM-103'], $dam['options']);
        $this->assertSame('103,102', $sire['option_meta']['7']);
    }

    private function check(int $id, int $femaleId, int $registrationId, string $date, bool $pregnant, ?string $outcome = null): array
    {
        return [
            'id' => $id,
            'female_animal_id' => $femaleId,
            'breeding_female_id' => $registrationId,
            'female_animal' => ['tag_number' => 'DAM-'.$femaleId],
            'breeding_period' => ['male_animal_id' => 7],
            'check_date' => $date,
            'is_pregnant' => $pregnant,
            'outcome_status' => $outcome,
        ];
    }
}
