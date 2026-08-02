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
        $fallback = $this->fallback();

        if (! is_string($token) || $token === '') {
            return $fallback;
        }

        $animals = $this->items('animals', $token, 200);
        $birthEvents = $this->items('birth-events', $token, 200);
        $breedingFemales = $this->items('breeding-females', $token, 200);
        $healthTreatments = $this->items('health-treatments', $token, 200);
        $activityLogs = $includeActivityLogs ? $this->items('admin-activity-logs', $token, 12) : [];

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

        return [
            'stats' => [
                ['label' => 'Total Kambing', 'value' => (string) count($animals), 'note' => count($aliveAnimals).' kambing aktif', 'tone' => 'green', 'icon' => 'goat'],
                ['label' => 'Jantan Dewasa', 'value' => (string) count($adultMales), 'note' => 'Pejantan produktif', 'tone' => 'blue', 'icon' => 'goat'],
                ['label' => 'Betina Dewasa', 'value' => (string) count($adultFemales), 'note' => 'Induk produktif', 'tone' => 'green', 'icon' => 'female'],
                ['label' => 'Pejantan Muda', 'value' => (string) count($youngMales), 'note' => 'Calon pejantan', 'tone' => 'blue', 'icon' => 'goat'],
                ['label' => 'Dere', 'value' => (string) count($readyFemales), 'note' => 'Betina muda siap masuk program', 'tone' => 'yellow', 'icon' => 'female'],
                ['label' => 'Cempe', 'value' => (string) count($kids), 'note' => 'Usia sampai 6 bulan', 'tone' => 'orange', 'icon' => 'baby'],
                ['label' => 'Betina Bunting', 'value' => (string) count($pregnantAnimals), 'note' => $this->percent(count($pregnantAnimals), $totalAlive).' dari populasi aktif', 'tone' => 'green', 'icon' => 'pregnancy'],
                ['label' => 'Kelahiran Tahun Ini', 'value' => (string) count($birthEvents), 'note' => count($birthEvents).' data kelahiran tercatat', 'tone' => 'orange', 'icon' => 'birth'],
            ],
            'birthYears' => $birthYears,
            'selectedBirthYear' => $selectedBirthYear,
            'birthChart' => $this->birthChart($birthEvents, $selectedBirthYear),
            'activities' => $this->activities($activityLogs),
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
    private function items(string $endpoint, string $token, int $perPage): array
    {
        try {
            $response = $this->api->get($endpoint, ['per_page' => $perPage], $token);
        } catch (Throwable) {
            $this->failureMessage ??= 'Gagal: Layanan API tidak merespons. Sebagian data dashboard tidak dapat dimuat.';

            return [];
        }

        if (! $response->successful()) {
            $message = $response->json('message');
            $this->failureMessage ??= match (true) {
                $response->status() === 401 => 'Sesi Berakhir: Silakan masuk kembali sebelum melihat dashboard.',
                $response->status() === 403 => is_string($message) && $message !== '' ? $message : 'Gagal: Akun Anda tidak memiliki izin untuk melihat sebagian data dashboard.',
                $response->serverError() => 'Gagal: Layanan API sedang bermasalah. Sebagian data dashboard tidak dapat dimuat.',
                default => is_string($message) && $message !== '' ? $message : 'Gagal: Sebagian data dashboard tidak dapat dimuat dari API.',
            };

            return [];
        }

        return $response->successful() && is_array($response->json('data'))
            ? $response->json('data')
            : [];
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
                    'note' => "{$tag} aktif pada periode {$period}, tetapi tanggal kawin belum dicatat.",
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
                        'title' => 'Perkiraan lahir terlewat',
                        'note' => "{$tag} melewati perkiraan lahir. Periksa kondisi induk dan catat kelahiran jika sudah terjadi.",
                        'date' => $due->toDateString(),
                        'action_label' => 'Catat kelahiran',
                        'action_url' => route('admin.resource.create', ['resource' => 'birth-events']),
                    ];
                } elseif ($due->betweenIncluded($today, now()->addDays(14)->endOfDay())) {
                    $items[] = [
                        'tone' => $due->isSameDay($today) ? 'danger' : 'warning',
                        'status' => $due->isSameDay($today) ? 'Hari ini' : 'Segera',
                        'title' => 'Persiapan kelahiran',
                        'note' => "{$tag} diperkirakan lahir pada {$due->toDateString()}.",
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
                    'title' => $overdue ? 'Kontrol kesehatan terlewat' : 'Kontrol kesehatan',
                    'note' => $this->value($row, 'animal.tag_number').' - '.$this->value($row, 'treatment_group'),
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

        $max = max($counts) ?: 1;

        return array_map(fn ($count) => max(8, (int) round(($count / $max) * 88)), array_values($counts));
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
    private function fallback(): array
    {
        return [
            'stats' => [
                ['label' => 'Total Kambing', 'value' => '128', 'note' => '118 kambing aktif', 'tone' => 'green', 'icon' => 'goat'],
                ['label' => 'Jantan Dewasa', 'value' => '22', 'note' => 'Pejantan produktif', 'tone' => 'blue', 'icon' => 'goat'],
                ['label' => 'Betina Dewasa', 'value' => '48', 'note' => 'Induk produktif', 'tone' => 'green', 'icon' => 'female'],
                ['label' => 'Pejantan Muda', 'value' => '14', 'note' => 'Calon pejantan', 'tone' => 'blue', 'icon' => 'goat'],
                ['label' => 'Dere', 'value' => '19', 'note' => 'Betina muda siap masuk program', 'tone' => 'yellow', 'icon' => 'female'],
                ['label' => 'Cempe', 'value' => '36', 'note' => 'Usia sampai 6 bulan', 'tone' => 'orange', 'icon' => 'baby'],
                ['label' => 'Betina Bunting', 'value' => '12', 'note' => '10% dari populasi aktif', 'tone' => 'green', 'icon' => 'pregnancy'],
                ['label' => 'Kelahiran Tahun Ini', 'value' => '24', 'note' => '+6 kelahiran bulan ini', 'tone' => 'orange', 'icon' => 'birth'],
            ],
            'birthYears' => [2026, 2025],
            'birthChart' => [35, 55, 68, 82, 76, 64, 48, 58, 74, 88, 70, 52],
            'activities' => [
                ['text' => 'Admin Rio menambahkan data kambing dengan tag BBH-001', 'time' => '2 jam lalu'],
                ['text' => 'Admin Rio memperbarui kandang dengan kode kandang KP-001', 'time' => '2 jam lalu'],
                ['text' => 'Admin Rio menambahkan kebuntingan dengan ID #1', 'time' => '2 jam lalu'],
                ['text' => 'Admin Rio menerbitkan sertifikat dengan nomor sertifikat BBH-SBU-2026-0001', 'time' => '2 jam lalu'],
                ['text' => 'Admin Rio mengaktifkan RSA Key dengan key identifier BBH-RSA-2026-0001', 'time' => '2 jam lalu'],
                ['text' => 'Admin Rio memperbarui catatan bobot dengan ID #1', 'time' => '2 jam lalu'],
            ],
            'agenda' => [
                ['date' => '2026-10-11', 'title' => 'Perkiraan lahir', 'note' => 'BBH-26-014 dari periode PK-001'],
                ['date' => '2026-06-02', 'title' => 'Kontrol kesehatan', 'note' => 'BBH-26-001 - Pemeriksaan Rutin'],
            ],
            'priorityTasks' => [
                ['tone' => 'warning', 'status' => 'Perlu dicatat', 'title' => 'Tanggal kawin belum dicatat', 'note' => 'BBH-26-014 aktif pada periode PK-001, tetapi tanggal kawin belum dicatat.', 'date' => '2026-07-24', 'action_label' => 'Catat kawin', 'action_url' => '#'],
                ['tone' => 'danger', 'status' => 'Lewat tenggat', 'title' => 'Kontrol kesehatan terlewat', 'note' => 'BBH-26-001 - Pemeriksaan Rutin', 'date' => '2026-07-20', 'action_label' => 'Buka catatan', 'action_url' => '#'],
                ['tone' => 'info', 'status' => 'Terjadwal', 'title' => 'Kontrol kesehatan', 'note' => 'BBH-26-003 - Pemeriksaan Rutin', 'date' => '2026-07-27', 'action_label' => 'Buka catatan', 'action_url' => '#'],
            ],
            'todayAgenda' => [],
            'latestAnimals' => [
                ['BBH-001', 'Boer', 'Jantan', 'Hidup', '30 Mei 2026'],
                ['BBH-014', 'Saanen', 'Betina', 'Hidup', '30 Mei 2026'],
                ['BBH-022', 'Etawa', 'Betina', 'Hidup', '30 Mei 2026'],
            ],
            'apiFailureMessage' => null,
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
