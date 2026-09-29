<?php

namespace App\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Throwable;

class DashboardViewData
{
    private ?string $failureMessage = null;

    public function __construct(private readonly BbhApiClient $api) {}

    /**
     * @return array<string, mixed>
     */
    public function data(?string $token, ?int $selectedBirthYear = null, bool $includeActivityLogs = false): array
    {
        if (! is_string($token) || $token === '') {
            return $this->fallback($includeActivityLogs);
        }

        $dashboardItems = $this->batchItems([
            'animals' => 'animals',
            'birthEvents' => 'birth-events',
            'breedingFemales' => 'breeding-females',
            'healthTreatments' => 'health-treatments',
            ...($includeActivityLogs ? ['activityLogs' => 'admin-activity-logs'] : []),
        ], $token);

        $animals = $dashboardItems['animals'];
        $birthEvents = $dashboardItems['birthEvents'];
        $breedingFemales = $dashboardItems['breedingFemales'];
        $healthTreatments = $dashboardItems['healthTreatments'];
        $activityLogs = $dashboardItems['activityLogs'] ?? [];

        $aliveAnimals = array_values(array_filter($animals, fn ($animal) => $this->value($animal, 'life_status') !== 'dead'));
        $kids = array_values(array_filter($aliveAnimals, fn ($animal) => $this->ageInMonths($animal) <= 6));
        $youngMales = array_values(array_filter($aliveAnimals, fn ($animal) => $this->category($animal) === 'pejantan muda'));
        $readyFemales = array_values(array_filter($aliveAnimals, fn ($animal) => $this->category($animal) === 'dere'));
        $adultMales = array_values(array_filter($aliveAnimals, fn ($animal) => $this->category($animal) === 'pejantan dewasa'));
        $adultFemales = array_values(array_filter($aliveAnimals, fn ($animal) => $this->category($animal) === 'betina dewasa'));
        $totalAlive = max(1, count($aliveAnimals));
        $pregnantAnimals = array_values(array_filter($aliveAnimals, fn ($animal) => $this->value($animal, 'reproductive_status') === 'bunting'));
        $agenda = $this->agenda($breedingFemales, $healthTreatments);
        $priorityTasks = $this->priorityTasks($breedingFemales, $healthTreatments);

        $birthYears = $this->birthYears($birthEvents);
        $selectedBirthYear = in_array($selectedBirthYear, $birthYears, true)
            ? $selectedBirthYear
            : ($birthYears[0] ?? (int) now()->format('Y'));
        $birthChart = $this->birthChart($birthEvents, $selectedBirthYear);
        $offspringChart = $this->offspringChart($birthEvents, $selectedBirthYear);
        $currentYearBirthChart = $this->birthChart($birthEvents, (int) now()->format('Y'));

        return [
            'stats' => [
                ['label' => 'Total Kambing', 'value' => (string) count($animals), 'note' => count($aliveAnimals).' kambing tercatat hidup.', 'tone' => 'green', 'icon' => 'goat', 'trend' => $this->animalTrend($animals, null, $selectedBirthYear)],
                ['label' => 'Jantan Dewasa', 'value' => (string) count($adultMales), 'note' => 'Jantan dewasa yang tercatat.', 'tone' => 'blue', 'icon' => 'goat', 'trend' => $this->animalTrend($adultMales, null, $selectedBirthYear)],
                ['label' => 'Betina Dewasa', 'value' => (string) count($adultFemales), 'note' => 'Betina dewasa yang tercatat.', 'tone' => 'green', 'icon' => 'female', 'trend' => $this->animalTrend($adultFemales, null, $selectedBirthYear)],
                ['label' => 'Pejantan Muda', 'value' => (string) count($youngMales), 'note' => 'Jantan muda yang tercatat.', 'tone' => 'blue', 'icon' => 'goat', 'trend' => $this->animalTrend($youngMales, null, $selectedBirthYear)],
                ['label' => 'Dere', 'value' => (string) count($readyFemales), 'note' => 'Betina muda yang tercatat.', 'tone' => 'yellow', 'icon' => 'female', 'trend' => $this->animalTrend($readyFemales, null, $selectedBirthYear)],
                ['label' => 'Cempe', 'value' => (string) count($kids), 'note' => 'Usia sampai 6 bulan berdasarkan tanggal lahir.', 'tone' => 'orange', 'icon' => 'baby', 'trend' => $this->animalTrend($kids, null, $selectedBirthYear)],
                ['label' => 'Betina Bunting', 'value' => (string) count($pregnantAnimals), 'note' => $this->percent(count($pregnantAnimals), $totalAlive).' dari kambing yang tercatat hidup.', 'tone' => 'green', 'icon' => 'pregnancy', 'trend' => $this->animalTrend($pregnantAnimals, 'status_date', $selectedBirthYear)],
                ['label' => 'Kelahiran Tahun Ini', 'value' => (string) array_sum($currentYearBirthChart), 'note' => 'Jumlah kelahiran yang dicatat tahun ini.', 'tone' => 'orange', 'icon' => 'birth', 'trend' => $currentYearBirthChart],
            ],
            'birthYears' => $birthYears,
            'selectedBirthYear' => $selectedBirthYear,
            'birthChart' => $birthChart,
            'offspringChart' => $offspringChart,
            'offspringChartSvg' => $this->chartPaths($offspringChart, 82, 850, 42, 304),
            'activities' => $this->activities($activityLogs),
            'showActivities' => $includeActivityLogs,
            'agenda' => $agenda,
            'priorityTasks' => $priorityTasks,
            'todayAgenda' => array_values(array_filter($agenda, fn ($item) => $item['date'] === now()->toDateString())),
            'latestAnimals' => array_slice(array_map(fn ($animal) => [
                $this->value($animal, 'tag_number'),
                $this->value($animal, 'breed.breed_name'),
                $this->sex($this->value($animal, 'sex')),
                $this->status($this->value($animal, 'life_status')),
                substr($this->value($animal, 'updated_at'), 0, 10),
            ], $animals), 0, 5),
            'apiFailureMessage' => $this->failureMessage,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function items(string $endpoint, string $token): array
    {
        try {
            $result = $this->api->paginatedData($endpoint, [], $token);
        } catch (Throwable) {
            $this->failureMessage ??= 'Sebagian data belum dapat ditampilkan. Silakan coba lagi.';

            return [];
        }

        $response = $result['response'];
        if (! $result['ok'] && $response !== null) {
            $this->setFailureMessageFromResponse($response);

            return [];
        }

        return $result['data'];
    }

    /**
     * @param  array<string, string>  $endpoints
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function batchItems(array $endpoints, string $token): array
    {
        $items = array_fill_keys(array_keys($endpoints), []);
        $requests = [];

        foreach ($endpoints as $key => $endpoint) {
            $requests[$key] = ['path' => $endpoint];
        }

        try {
            $results = $this->api->paginatedBatchData($requests, $token, PHP_INT_MAX);
        } catch (Throwable) {
            $this->failureMessage ??= 'Sebagian data belum dapat ditampilkan. Silakan coba lagi.';

            return $items;
        }

        foreach ($results as $key => $result) {
            $response = $result['response'];

            if (! $result['ok'] && $response !== null) {
                $this->setFailureMessageFromResponse($response);

                continue;
            }

            if ($result['truncated']) {
                $this->failureMessage ??= 'Sebagian data dashboard belum termuat. Silakan coba lagi.';
            }

            $items[$key] = $result['data'];
        }

        return $items;
    }

    private function setFailureMessageFromResponse($response): void
    {
        $message = $response->json('message');
        $this->failureMessage ??= match (true) {
            $response->status() === 401 => 'Sesi Berakhir: Silakan masuk kembali sebelum melihat dashboard.',
            $response->status() === 403 => is_string($message) && $message !== '' ? $message : 'Gagal: Akun Anda tidak memiliki izin untuk melihat sebagian data dashboard.',
            $response->serverError() => 'Sebagian data belum dapat ditampilkan. Silakan coba lagi.',
            default => is_string($message) && $message !== '' ? $message : 'Sebagian data belum dapat ditampilkan. Silakan coba lagi.',
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $breedingFemales
     * @param  array<int, array<string, mixed>>  $healthTreatments
     * @return array<int, array<string, string>>
     */
    private function agenda(array $breedingFemales, array $healthTreatments): array
    {
        $today = now()->startOfDay();
        $limit = now()->addDays(45)->endOfDay();
        $items = [];

        foreach ($breedingFemales as $row) {
            $date = $this->value($row, 'expected_birth_date');
            if ($date === '-') {
                continue;
            }

            $due = Carbon::parse($date);
            if ($due->betweenIncluded($today, $limit)) {
                $items[] = [
                    'date' => $due->toDateString(),
                    'title' => 'Perkiraan lahir',
                    'note' => $this->value($row, 'female_animal.tag_number').' dari periode '.$this->value($row, 'breeding_period.period_code'),
                ];
            }
        }

        foreach ($healthTreatments as $row) {
            $date = $this->value($row, 'next_control_date');
            if ($date === '-') {
                continue;
            }

            $due = Carbon::parse($date);
            if ($due->betweenIncluded($today, $limit)) {
                $items[] = [
                    'date' => $due->toDateString(),
                    'title' => 'Kontrol kesehatan',
                    'note' => $this->value($row, 'animal.tag_number').' - '.$this->value($row, 'treatment_group'),
                ];
            }
        }

        usort($items, fn ($a, $b) => strcmp($a['date'], $b['date']));

        return array_slice($items, 0, 12);
    }

    /**
     * @param  array<int, array<string, mixed>>  $breedingFemales
     * @param  array<int, array<string, mixed>>  $healthTreatments
     * @return array<int, array<string, string>>
     */
    private function priorityTasks(array $breedingFemales, array $healthTreatments): array
    {
        $today = now()->startOfDay();
        $items = [];

        foreach ($breedingFemales as $row) {
            if ($this->value($row, 'exit_date') !== '-') {
                continue;
            }

            $tag = $this->value($row, 'female_animal.tag_number');
            $period = $this->value($row, 'breeding_period.period_code');

            if ($this->value($row, 'mating_date') === '-') {
                $items[] = [
                    'tone' => 'warning',
                    'status' => 'Perlu dicatat',
                    'title' => 'Tanggal kawin belum dicatat',
                    'note' => "Tanggal kawin {$tag} pada periode {$period} belum dicatat.",
                    'date' => $this->value($row, 'entry_date'),
                    'action_label' => 'Catat kawin',
                    'action_url' => route('admin.breeding-females.mating', ['id' => $this->value($row, 'id')]),
                ];
            }

            $expectedDate = $this->value($row, 'expected_birth_date');
            if ($expectedDate !== '-') {
                $due = Carbon::parse($expectedDate)->startOfDay();
                $hasDelivered = in_array($this->value($row, 'female_animal.reproductive_status'), ['melahirkan', 'laktasi', 'laktasi_kosong'], true);

                if ($hasDelivered) {
                    continue;
                }

                if ($due->lessThan($today)) {
                    $items[] = [
                        'tone' => 'danger',
                        'status' => 'Lewat tenggat',
                        'title' => 'Perkiraan kelahiran terlewat',
                        'note' => "Perkiraan tanggal melahirkan {$tag} sudah lewat. Perbarui catatan sesuai kondisi terakhir.",
                        'date' => $due->toDateString(),
                        'action_label' => 'Catat kelahiran',
                        'action_url' => route('admin.resource.create', ['resource' => 'birth-events']),
                    ];
                } elseif ($due->betweenIncluded($today, now()->addDays(14)->endOfDay())) {
                    $items[] = [
                        'tone' => $due->isSameDay($today) ? 'danger' : 'warning',
                        'status' => $due->isSameDay($today) ? 'Hari ini' : 'Segera',
                        'title' => 'Persiapan kelahiran',
                        'note' => "Perkiraan tanggal melahirkan {$tag}: {$due->translatedFormat('d F Y')}.",
                        'date' => $due->toDateString(),
                        'action_label' => 'Lihat betina',
                        'action_url' => route('admin.resource.show', ['resource' => 'breeding-females', 'id' => $this->value($row, 'id')]),
                    ];
                }
            }
        }

        foreach ($healthTreatments as $row) {
            $controlDate = $this->value($row, 'next_control_date');
            if ($controlDate === '-') {
                continue;
            }

            $due = Carbon::parse($controlDate)->startOfDay();
            if ($due->lessThanOrEqualTo(now()->addDays(7)->endOfDay())) {
                $overdue = $due->lessThan($today);
                $items[] = [
                    'tone' => $overdue ? 'danger' : 'info',
                    'status' => $overdue ? 'Lewat tenggat' : ($due->isSameDay($today) ? 'Hari ini' : 'Terjadwal'),
                    'title' => $overdue ? 'Tanggal kontrol terlewat' : 'Jadwal kontrol',
                    'note' => $overdue
                        ? 'Tanggal kontrol '.$this->value($row, 'animal.tag_number').' sudah lewat. Perbarui catatan jika kontrol telah dilakukan.'
                        : 'Jadwal kontrol '.$this->value($row, 'animal.tag_number').' tercatat pada '.$due->translatedFormat('d F Y').'.',
                    'date' => $due->toDateString(),
                    'action_label' => 'Buka catatan',
                    'action_url' => route('admin.resource.edit', ['resource' => 'health-treatments', 'id' => $this->value($row, 'id')]),
                ];
            }
        }

        usort($items, fn ($a, $b) => strcmp($a['date'], $b['date']));

        return array_slice($items, 0, 12);
    }

    /**
     * @param  array<int, array<string, mixed>>  $birthEvents
     * @return array<int, int>
     */
    private function birthYears(array $birthEvents): array
    {
        $years = array_values(array_unique(array_filter(array_map(
            fn ($event) => (int) substr($this->value($event, 'birth_date'), 0, 4),
            $birthEvents
        ))));
        rsort($years);

        return $years !== [] ? $years : [(int) now()->format('Y')];
    }

    /**
     * @param  array<int, array<string, mixed>>  $birthEvents
     * @return array<int, int>
     */
    private function birthChart(array $birthEvents, ?int $year = null): array
    {
        $counts = array_fill(1, 12, 0);

        foreach ($birthEvents as $event) {
            if ($year !== null && (int) substr($this->value($event, 'birth_date'), 0, 4) !== $year) {
                continue;
            }

            $month = (int) substr($this->value($event, 'birth_date'), 5, 2);
            if ($month >= 1 && $month <= 12) {
                $counts[$month]++;
            }
        }

        return array_values($counts);
    }

    /**
     * @param  array<int, array<string, mixed>>  $birthEvents
     * @return array<int, int>
     */
    private function offspringChart(array $birthEvents, ?int $year = null): array
    {
        $counts = array_fill(1, 12, 0);

        foreach ($birthEvents as $event) {
            if ($year !== null && (int) substr($this->value($event, 'birth_date'), 0, 4) !== $year) {
                continue;
            }

            $month = (int) substr($this->value($event, 'birth_date'), 5, 2);
            if ($month >= 1 && $month <= 12) {
                $offspring = (int) $this->value($event, 'offspring_count');
                $counts[$month] += max(1, $offspring);
            }
        }

        return array_values($counts);
    }

    /**
     * @param  array<int, array<string, mixed>>  $animals
     * @return array<int, int>
     */
    private function animalTrend(array $animals, ?string $dateKey, int $year): array
    {
        $counts = array_fill(1, 12, 0);
        $dateKey ??= 'birth_date';

        foreach ($animals as $animal) {
            $date = $this->value($animal, $dateKey);
            if ($date === '-' || (int) substr($date, 0, 4) !== $year) {
                continue;
            }

            $month = (int) substr($date, 5, 2);
            if ($month >= 1 && $month <= 12) {
                $counts[$month]++;
            }
        }

        if (array_sum($counts) === 0) {
            return array_fill(0, 12, 0);
        }

        return array_values($counts);
    }

    /**
     * @param  array<int, int>  $values
     * @return array{line: string, area: string, points: array<int, array{x: float, y: float, value: int}>}
     */
    public function chartPaths(array $values, float $minX, float $maxX, float $minY, float $maxY): array
    {
        $values = array_values($values);
        if ($values === []) {
            $values = [0];
        }

        $maxValue = max($values);
        $minValue = min(0, min($values));
        $isZeroTrend = $maxValue === 0 && $minValue === 0;
        $range = max(1, $maxValue - $minValue);
        $step = count($values) > 1 ? ($maxX - $minX) / (count($values) - 1) : 0;

        $points = array_map(function (int $value, int $index) use ($isZeroTrend, $minX, $maxY, $minY, $minValue, $range, $step): array {
            return [
                'x' => round($minX + ($step * $index), 2),
                'y' => $isZeroTrend ? $maxY : round($maxY - ((($value - $minValue) / $range) * ($maxY - $minY)), 2),
                'value' => $value,
            ];
        }, $values, array_keys($values));

        $line = 'M'.$points[0]['x'].' '.$points[0]['y'];
        for ($index = 1, $total = count($points); $index < $total; $index++) {
            $previous = $points[$index - 1];
            $current = $points[$index];
            $controlOffset = round(($current['x'] - $previous['x']) / 2, 2);
            $line .= ' C'.($previous['x'] + $controlOffset).' '.$previous['y'].' '.($current['x'] - $controlOffset).' '.$current['y'].' '.$current['x'].' '.$current['y'];
        }

        $first = $points[0];
        $last = $points[array_key_last($points)];
        $area = $line.' L'.$last['x'].' '.$maxY.' L'.$first['x'].' '.$maxY.' Z';

        return compact('line', 'area', 'points');
    }

    /**
     * @param  array<int, array<string, mixed>>  $logs
     * @return array<int, array<string, string>>
     */
    private function activities(array $logs): array
    {
        $items = array_map(fn ($log) => [
            'text' => $this->activityText($log),
            'time' => substr($this->value($log, 'created_at'), 0, 16),
        ], $logs);

        return $items;
    }

    /**
     * @param  array<string, mixed>  $log
     */
    private function activityText(array $log): string
    {
        $description = $this->value($log, 'description');
        $admin = $this->value($log, 'admin_name');
        $adminLabel = $admin === '-' ? 'Admin' : 'Admin '.$admin;
        $moduleKey = $this->value($log, 'module');
        $module = $this->moduleLabel($moduleKey);
        $target = $this->targetPhrase($moduleKey, $this->subjectLabel($log));
        $action = $this->value($log, 'action');

        if ($description !== '-' && str_starts_with($description, 'Log:')) {
            return rtrim($description, '.');
        }

        if ($description !== '-' && str_starts_with($description, 'Admin ')) {
            return $this->normalizeStoredActivityDescription($description, $action, $adminLabel, $module, $target, $this->isFailed($log));
        }

        if ($this->isFailed($log)) {
            return match ($action) {
                'login', 'login_failed' => 'Log: Autentikasi Admin ditolak oleh sistem',
                default => "Log: Permintaan {$adminLabel} untuk {$this->actionVerb($action)} pada modul {$module}{$target} gagal diproses",
            };
        }

        return match ($action) {
            'login' => "Log: {$adminLabel} berhasil masuk ke sistem",
            'logout' => "Log: {$adminLabel} telah keluar dari sistem",
            'create' => "Log: {$adminLabel} menyimpan {$module}{$target}",
            'update' => "Log: {$adminLabel} memperbarui {$module}{$target}",
            'delete' => "Log: {$adminLabel} menonaktifkan {$module}{$target}",
            'sign' => "Log: {$adminLabel} menandatangani sertifikat{$target}",
            'revoke' => "Log: {$adminLabel} mencabut sertifikat{$target}",
            'unrevoke' => "Log: {$adminLabel} mengaktifkan kembali sertifikat{$target}",
            'generate' => "Log: {$adminLabel} membuat RSA Key{$target}",
            'activate' => "Log: {$adminLabel} mengaktifkan {$module}{$target}",
            'deactivate' => "Log: {$adminLabel} menonaktifkan {$module}{$target}",
            'compromise' => "Log: {$adminLabel} menonaktifkan RSA Key{$target}",
            default => "Log: {$adminLabel} melakukan aktivitas pada {$module}{$target}",
        };
    }

    /**
     * @param  array<string, mixed>  $log
     */
    private function isFailed(array $log): bool
    {
        $statusCode = (int) $this->value($log, 'status_code');

        return $statusCode >= 400;
    }

    private function normalizeStoredActivityDescription(string $description, string $action, string $adminLabel, string $module, string $target, bool $failed): string
    {
        $lower = strtolower($description);

        if (str_contains($lower, 'melakukan autentikasi masuk') || str_contains($lower, 'telah login')) {
            return "Log: {$adminLabel} berhasil masuk ke sistem";
        }

        if (str_contains($lower, 'mengakhiri sesi penggunaan') || str_contains($lower, 'telah logout')) {
            return "Log: {$adminLabel} telah keluar dari sistem";
        }

        if ($failed || str_contains($lower, 'belum berhasil') || str_contains($lower, 'mencoba menambahkan')) {
            return match ($action) {
                'login', 'login_failed' => 'Log: Autentikasi Admin ditolak oleh sistem',
                default => "Log: Permintaan {$adminLabel} untuk {$this->actionVerb($action)} pada modul {$module}{$target} gagal diproses",
            };
        }

        return rtrim($description, '.');
    }

    private function actionVerb(string $action): string
    {
        return match ($action) {
            'create' => 'menyimpan',
            'update' => 'memperbarui',
            'delete', 'deactivate' => 'menonaktifkan',
            'sign' => 'menandatangani',
            'revoke' => 'mencabut',
            'unrevoke' => 'mengaktifkan kembali',
            'generate' => 'membuat',
            'activate' => 'mengaktifkan',
            'compromise' => 'menonaktifkan',
            'mating' => 'mencatat tanggal kawin untuk',
            'exit' => 'mengeluarkan',
            default => 'memproses',
        };
    }

    /**
     * @param  array<string, mixed>  $log
     */
    private function subjectLabel(array $log): ?string
    {
        $metadataLabel = Arr::get($log, 'metadata.subject_label');
        if (is_string($metadataLabel) && $metadataLabel !== '') {
            return $metadataLabel;
        }

        $module = $this->value($log, 'module');
        $payload = Arr::get($log, 'metadata.payload', []);
        $payload = is_array($payload) ? $payload : [];

        $label = match ($module) {
            'animals' => $payload['tag_number'] ?? null,
            'colony-pens' => $payload['pen_code'] ?? null,
            'breeding-periods' => $payload['period_code'] ?? null,
            'certificates' => $payload['certificate_number'] ?? null,
            'rsa-keys' => $payload['key_identifier'] ?? null,
            'breeds' => $payload['breed_name'] ?? null,
            'certificate-types' => $payload['type_name'] ?? null,
            default => null,
        };

        if (is_string($label) && $label !== '') {
            return $label;
        }

        $subjectId = Arr::get($log, 'subject_id');

        return $subjectId === null || $subjectId === '' ? null : '#'.$subjectId;
    }

    private function targetPhrase(string $module, ?string $subjectLabel): string
    {
        if ($subjectLabel === null || $subjectLabel === '') {
            return '';
        }

        return match ($module) {
            'animals' => " dengan tag {$subjectLabel}",
            'colony-pens' => " dengan kode kandang {$subjectLabel}",
            'breeding-periods' => " dengan kode periode {$subjectLabel}",
            'certificates' => " dengan nomor sertifikat {$subjectLabel}",
            'rsa-keys' => " dengan key identifier {$subjectLabel}",
            default => " dengan ID {$subjectLabel}",
        };
    }

    private function moduleLabel(string $value): string
    {
        return [
            'auth' => 'akun admin',
            'farm' => 'profil farm',
            'breeds' => 'ras kambing',
            'animals' => 'data kambing',
            'colony-pens' => 'kandang',
            'breeding-periods' => 'periode kawin',
            'breeding-females' => 'betina kawin',
            'pregnancy-checks' => 'kebuntingan',
            'birth-events' => 'kelahiran',
            'offspring-births' => 'cempe lahir',
            'postnatal-care-records' => 'pascalahir',
            'weight-records' => 'catatan bobot',
            'health-treatments' => 'kesehatan',
            'vaccinations' => 'vaksinasi',
            'certificates' => 'akte dan sertifikat',
            'rsa-keys' => 'RSA Key',
        ][$value] ?? str_replace('-', ' ', $value);
    }

    /**
     * @return array<string, mixed>
     */
    private function fallback(bool $includeActivityLogs): array
    {
        $emptyChart = array_fill(0, 12, 0);

        return [
            'stats' => [],
            'birthYears' => [(int) now()->year],
            'selectedBirthYear' => (int) now()->year,
            'birthChart' => $emptyChart,
            'offspringChart' => $emptyChart,
            'offspringChartSvg' => $this->chartPaths($emptyChart, 82, 850, 42, 304),
            'activities' => [],
            'showActivities' => $includeActivityLogs,
            'agenda' => [],
            'priorityTasks' => [],
            'todayAgenda' => [],
            'latestAnimals' => [],
            'apiFailureMessage' => 'Sesi login Anda telah berakhir. Silakan masuk kembali.',
        ];
    }

    private function percent(int $value, int $total): string
    {
        return round(($value / max(1, $total)) * 100).'%';
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function category(array $item): string
    {
        return strtolower($this->value($item, 'kategori_umur'));
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function ageInMonths(array $item): int
    {
        $birthDate = $this->value($item, 'birth_date');

        if ($birthDate === '-') {
            return PHP_INT_MAX;
        }

        return (int) Carbon::parse($birthDate)->diffInMonths(now());
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function value(array $item, string $key): string
    {
        $value = Arr::get($item, $key);

        return ($value === null || $value === '') ? '-' : (string) $value;
    }

    private function sex(string $value): string
    {
        return match ($value) {
            'male' => 'Jantan',
            'female' => 'Betina',
            default => $value,
        };
    }

    private function status(string $value): string
    {
        return match ($value) {
            'alive' => 'Hidup',
            'dead' => 'Mati',
            default => $value,
        };
    }
}
