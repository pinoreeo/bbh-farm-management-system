<?php

namespace App\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Throwable;

class AdminNotificationViewData
{
    public function __construct(private readonly BbhApiClient $api) {}

    /**
     * @return array<int, array<string, string>>
     */
    public function items(?string $token): array
    {
        if (! is_string($token) || $token === '') {
            return [];
        }

        return Cache::remember('bbh_admin_notifications:'.hash('sha256', $token), now()->addSeconds(30), function () use ($token): array {
            $apiItems = $this->apiItemsBatch([
                'breedingFemales' => ['path' => 'breeding-females'],
                'healthTreatments' => ['path' => 'health-treatments'],
                'rsaKeys' => ['path' => 'rsa-keys', 'query' => ['include_inactive' => 1]],
            ], $token);

            $items = [
                ...$this->breedingNotifications($apiItems['breedingFemales']),
                ...$this->healthNotifications($apiItems['healthTreatments']),
                ...$this->rsaNotifications($apiItems['rsaKeys']),
            ];

            usort($items, function (array $a, array $b): int {
                return [$a['priority'] ?? 99, $a['date'] ?? '9999-12-31'] <=> [$b['priority'] ?? 99, $b['date'] ?? '9999-12-31'];
            });

            return array_slice(array_map(fn (array $item) => [
                'title' => $item['title'],
                'body' => $item['body'],
                'time' => $this->timeLabel($item['date'] ?? null),
                'url' => $item['url'],
            ], $items), 0, 8);
        });
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<int, array<string, mixed>>
     */
    private function apiItems(string $endpoint, string $token, array $query = []): array
    {
        try {
            $response = $this->api->get($endpoint, ['per_page' => 100, ...$query], $token);
        } catch (Throwable) {
            return [];
        }

        return $response->successful() && is_array($response->json('data'))
            ? $response->json('data')
            : [];
    }

    /**
     * @param  array<string, array{path:string,query?:array<string, mixed>}>  $requests
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function apiItemsBatch(array $requests, string $token): array
    {
        $items = array_fill_keys(array_keys($requests), []);
        $batchRequests = [];

        foreach ($requests as $key => $request) {
            $batchRequests[$key] = [
                'path' => $request['path'],
                'query' => ['per_page' => 100, ...($request['query'] ?? [])],
            ];
        }

        try {
            $responses = $this->api->getMany($batchRequests, $token);
        } catch (Throwable) {
            return $items;
        }

        foreach ($responses as $key => $response) {
            $data = $response->successful() ? $response->json('data', []) : [];
            $items[$key] = is_array($data) ? $data : [];
        }

        return $items;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function breedingNotifications(array $rows): array
    {
        $today = now()->startOfDay();
        $items = [];

        foreach ($rows as $row) {
            if ($this->value($row, 'exit_date') !== '-') {
                continue;
            }

            $tag = $this->value($row, 'female_animal.tag_number');
            $period = $this->value($row, 'breeding_period.period_code');
            $id = $this->value($row, 'id');

            if ($this->value($row, 'mating_date') === '-') {
                $items[] = [
                    'priority' => 2,
                    'date' => $this->value($row, 'entry_date'),
                    'title' => 'Tanggal kawin belum dicatat',
                    'body' => "{$tag} masih aktif pada periode {$period}.",
                    'url' => route('admin.breeding-females.mating', ['id' => $id]),
                ];
            }

            $expectedDate = $this->value($row, 'expected_birth_date');
            if ($expectedDate === '-') {
                continue;
            }

            $due = Carbon::parse($expectedDate)->startOfDay();
            $hasDelivered = in_array($this->value($row, 'female_animal.reproductive_status'), ['melahirkan', 'laktasi', 'laktasi_kosong'], true);
            if ($hasDelivered) {
                continue;
            }

            if ($due->lessThan($today)) {
                $items[] = [
                    'priority' => 0,
                    'date' => $due->toDateString(),
                    'title' => 'Perkiraan lahir terlewat',
                    'body' => "{$tag} melewati perkiraan lahir. Periksa kondisi induk dan catat kelahiran bila sudah terjadi.",
                    'url' => route('admin.resource.create', ['resource' => 'birth-events']),
                ];
            } elseif ($due->betweenIncluded($today, now()->addDays(14)->endOfDay())) {
                $items[] = [
                    'priority' => 1,
                    'date' => $due->toDateString(),
                    'title' => 'Persiapan kelahiran',
                    'body' => "{$tag} diperkirakan lahir pada {$due->translatedFormat('d F Y')}.",
                    'url' => route('admin.resource.show', ['resource' => 'breeding-females', 'id' => $id]),
                ];
            }
        }

        return $items;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function healthNotifications(array $rows): array
    {
        $today = now()->startOfDay();
        $items = [];

        foreach ($rows as $row) {
            $controlDate = $this->value($row, 'next_control_date');
            if ($controlDate === '-') {
                continue;
            }

            $due = Carbon::parse($controlDate)->startOfDay();
            if ($due->greaterThan(now()->addDays(7)->endOfDay())) {
                continue;
            }

            $tag = $this->value($row, 'animal.tag_number');
            $group = $this->value($row, 'treatment_group');

            $items[] = [
                'priority' => $due->lessThan($today) ? 0 : 1,
                'date' => $due->toDateString(),
                'title' => $due->lessThan($today) ? 'Kontrol kesehatan terlewat' : 'Kontrol kesehatan perlu ditinjau',
                'body' => "{$tag} - {$group}.",
                'url' => route('admin.resource.edit', ['resource' => 'health-treatments', 'id' => $this->value($row, 'id')]),
            ];
        }

        return $items;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function rsaNotifications(array $rows): array
    {
        $hasActiveKey = collect($rows)->contains(fn (array $row) => $this->value($row, 'key_status') === 'active');

        if ($hasActiveKey) {
            return [];
        }

        return [[
            'priority' => 0,
            'date' => now()->toDateString(),
            'title' => 'RSA Key aktif belum tersedia',
            'body' => 'Buat atau aktifkan RSA Key sebelum menerbitkan sertifikat elektronik.',
            'url' => route('admin.rsa-keys'),
        ]];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function value(array $item, string $key): string
    {
        $value = Arr::get($item, $key);

        return ($value === null || $value === '') ? '-' : (string) $value;
    }

    private function timeLabel(?string $date): string
    {
        if (! is_string($date) || $date === '-' || $date === '') {
            return 'Terbaru';
        }

        $day = Carbon::parse($date)->startOfDay();

        if ($day->isToday()) {
            return 'Hari ini';
        }

        if ($day->isPast()) {
            return 'Lewat tenggat';
        }

        return $day->translatedFormat('d F Y');
    }
}
