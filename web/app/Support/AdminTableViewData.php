<?php

namespace App\Support;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Throwable;

class AdminTableViewData
{
    private ?string $failureMessage = null;

    public function __construct(private readonly BbhApiClient $api) {}

    public function failureMessage(): ?string
    {
        return $this->failureMessage;
    }

    /**
     * @param  array<int, array<int, string>>  $fallbackRows
     * @return array<int, array<int, string>>
     */
    public function rows(string $slug, array $fallbackRows, ?string $token): array
    {
        return array_map(fn ($record) => $record['cells'], $this->records($slug, $fallbackRows, $token));
    }

    /**
     * @param  array<int, array<int, string>>  $fallbackRows
     * @return array<int, array{id:int, cells:array<int, string>, raw:array<string, mixed>}>
     */
    public function records(string $slug, array $fallbackRows, ?string $token): array
    {
        if (! is_string($token) || $token === '') {
            return $this->fallbackRecords($fallbackRows);
        }

        $endpoint = $this->endpoint($slug);
        if ($endpoint === null) {
            return [];
        }

        try {
            $response = $this->api->get($endpoint, $this->queryFor($slug), $token);
        } catch (Throwable) {
            $this->failureMessage = 'Gagal: Layanan API tidak merespons. Data tidak dapat dimuat saat ini.';

            return [];
        }

        if (! $response->successful()) {
            $this->failureMessage = $this->apiFailureMessage($response);

            return [];
        }

        $items = $response->json('data', []);
        if (! is_array($items)) {
            return [];
        }

        $rows = array_values(array_filter(array_map(
            fn ($item) => is_array($item) ? [
                'id' => (int) ($item['id'] ?? 0),
                'cells' => $this->mapRow($slug, $item),
                'raw' => $item,
            ] : null,
            $items
        )));

        return $rows;
    }

    private function apiFailureMessage(Response $response): string
    {
        $message = $response->json('message');
        $message = is_string($message) && $message !== '' ? $message : null;

        return match (true) {
            $response->status() === 401 => 'Sesi Berakhir: Silakan masuk kembali sebelum melihat data admin.',
            $response->status() === 403 => $message ?: 'Gagal: Akun Anda tidak memiliki izin untuk melihat data ini.',
            $response->serverError() => 'Gagal: Layanan API sedang bermasalah. Data tidak dapat dimuat saat ini.',
            default => $message ?: 'Gagal: Data tidak dapat dimuat dari API. Silakan coba muat ulang halaman.',
        };
    }

    public function endpoint(string $slug): ?string
    {
        return [
            'users' => 'users',
            'animals' => 'animals',
            'weight-records' => 'weight-records',
            'pens' => 'colony-pens',
            'pen-movements' => 'animal-pen-movements',
            'breeding-periods' => 'breeding-periods',
            'breeding-females' => 'breeding-females',
            'pregnancy-checks' => 'breeding-periods',
            'birth-events' => 'birth-events',
            'offspring-births' => 'offspring-births',
            'health-treatments' => 'health-treatments',
            'vaccinations' => 'vaccinations',
            'postnatal-care' => 'postnatal-care-records',
            'certificates' => 'certificates',
            'certificate-logs' => 'certificate-verification-logs',
            'activity-logs' => 'admin-activity-logs',
            'rsa-keys' => 'rsa-keys',
        ][$slug] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    private function queryFor(string $slug): array
    {
        $query = match ($slug) {
            'breeding-periods', 'pregnancy-checks' => ['per_page' => 200, 'include_closed' => 1],
            'certificate-logs', 'activity-logs' => ['per_page' => 200],
            'certificates' => ['per_page' => 200, 'include_inactive' => 1],
            'rsa-keys' => ['per_page' => 200, 'include_inactive' => 1],
            default => ['per_page' => 200],
        };

        foreach (['search', 'sex', 'life_status', 'exit_status', 'reproductive_status', 'current_pen_id', 'breed_id', 'colony_phase', 'status'] as $key) {
            if (request()->filled($key)) {
                $query[$key] = request($key);
            }
        }

        return $query;
    }

    /**
     * @param  array<int, array<int, string>>  $fallbackRows
     * @return array<int, array{id:int, cells:array<int, string>, raw:array<string, mixed>}>
     */
    private function fallbackRecords(array $fallbackRows): array
    {
        return array_map(fn ($row, $index) => [
            'id' => $index + 1,
            'cells' => $row,
            'raw' => [],
        ], array_values($fallbackRows), array_keys(array_values($fallbackRows)));
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<int, string>
     */
    private function mapRow(string $slug, array $item): array
    {
        return match ($slug) {
            'users' => [
                $this->value($item, 'name'),
                $this->value($item, 'email'),
                $this->roleLabel($this->value($item, 'role')),
                ((bool) ($item['is_active'] ?? true)) ? 'Aktif' : 'Nonaktif',
                $this->date($this->value($item, 'last_login_at')),
            ],
            'animals' => [
                $this->value($item, 'tag_number'),
                $this->value($item, 'breed.breed_name'),
                $this->value($item, 'generation'),
                $this->sex($this->value($item, 'sex')),
                $this->originType($this->value($item, 'origin_type'), (bool) ($item['is_impor'] ?? false)),
                $this->animalOperationalStatus($item),
                $this->value($item, 'umur'),
            ],
            'weight-records' => [
                $this->value($item, 'animal.tag_number'),
                $this->date($this->value($item, 'record_date')),
                $this->value($item, 'weight_kg'),
                $this->ageBetween($this->value($item, 'animal.birth_date'), $this->value($item, 'record_date'), $this->value($item, 'age_months')),
                $this->value($item, 'notes'),
            ],
            'pens' => [
                $this->value($item, 'pen_code'),
                $this->value($item, 'colony_code'),
                $this->value($item, 'colony_name'),
                $this->penType($this->value($item, 'colony_phase')),
                $this->value($item, 'location'),
                $this->value($item, 'capacity'),
                ((bool) ($item['is_active'] ?? true)) ? 'Aktif' : 'Nonaktif',
            ],
            'pen-movements' => [
                $this->value($item, 'animal.tag_number'),
                $this->value($item, 'from_pen.pen_code'),
                $this->value($item, 'to_pen.pen_code'),
                $this->date($this->value($item, 'movement_date')),
                $this->value($item, 'reason'),
            ],
            'breeding-periods' => [
                $this->value($item, 'period_code'),
                $this->value($item, 'colony_pen.pen_code'),
                $this->date($this->value($item, 'start_date')),
                $this->date($this->value($item, 'end_date')),
                $this->value($item, 'male_animal.tag_number'),
                $this->status($this->value($item, 'status')),
            ],
            'breeding-females' => [
                $this->value($item, 'breeding_period.period_code'),
                $this->value($item, 'female_animal.tag_number'),
                $this->date($this->value($item, 'entry_date')),
                $this->date($this->value($item, 'mating_date')),
                $this->date($this->value($item, 'expected_birth_date')),
                $this->reproductiveStatus($this->value($item, 'cycle_stage')),
                $this->date($this->value($item, 'exit_date')),
                $this->value($item, 'exit_reason'),
            ],
            'pregnancy-checks' => [
                $this->value($item, 'period_code'),
                $this->value($item, 'colony_pen.pen_code'),
                $this->value($item, 'male_animal.tag_number'),
                $this->date($this->value($item, 'start_date')),
                $this->date($this->value($item, 'end_date')),
                $this->status($this->value($item, 'status')),
            ],
            'birth-events' => [
                $this->value($item, 'sire.tag_number'),
                $this->value($item, 'dam.tag_number'),
                $this->date($this->value($item, 'birth_date')),
                $this->value($item, 'birth_time'),
                $this->value($item, 'offspring_count'),
                $this->value($item, 'birth_process'),
                $this->value($item, 'birth_place'),
            ],
            'offspring-births' => [
                $this->date($this->value($item, 'birth_event.birth_date')),
                $this->value($item, 'offspring_animal.tag_number'),
                $this->value($item, 'birth_weight_kg'),
                $this->value($item, 'offspring_grade'),
                $this->lifeStatus($this->value($item, 'birth_status')),
                $this->value($item, 'notes'),
            ],
            'health-treatments' => [
                $this->value($item, 'animal.tag_number'),
                $this->date($this->value($item, 'treatment_date')),
                $this->value($item, 'treatment_group'),
                $this->value($item, 'symptoms'),
                $this->value($item, 'diagnosis'),
                $this->value($item, 'product_name'),
                $this->value($item, 'dosage'),
                $this->date($this->value($item, 'next_control_date')),
            ],
            'vaccinations' => [
                $this->value($item, 'animal.tag_number'),
                $this->value($item, 'category_name'),
                $this->date($this->value($item, 'vaccination_date')),
                $this->value($item, 'product_name'),
                $this->value($item, 'dosage'),
                $this->value($item, 'administration_route'),
            ],
            'postnatal-care' => [
                $this->value($item, 'birth_event.id'),
                $this->value($item, 'target_animal.tag_number'),
                $this->date($this->value($item, 'care_date')),
                $this->value($item, 'administration_method'),
                $this->volume($this->value($item, 'volume_ml')),
                $this->value($item, 'navel_iodine_status'),
                $this->volume($this->value($item, 'vitamin_ade_ml')),
                $this->volume($this->value($item, 'vitamin_b_complex_ml')),
                $this->volume($this->value($item, 'intracin_ml')),
            ],
            'certificates' => [
                $this->value($item, 'certificate_number'),
                $this->value($item, 'animal.tag_number'),
                $this->certificateTypeName($this->value($item, 'certificate_type.type_name')),
                $this->date($this->value($item, 'issue_date')),
                $this->status($this->value($item, 'status')),
            ],
            'certificate-logs' => [
                $this->value($item, 'certificate.certificate_number'),
                $this->date($this->value($item, 'verification_time')),
                $this->time($this->value($item, 'verification_time')),
                $this->verificationMethod($this->value($item, 'verification_method')),
                ((bool) ($item['is_valid'] ?? false)) ? 'Valid' : 'Tidak Valid',
                $this->value($item, 'failure_reason'),
                $this->value($item, 'ip_address'),
            ],
            'activity-logs' => [
                $this->date($this->value($item, 'created_at')),
                $this->time($this->value($item, 'created_at')),
                $this->adminIdentity($item),
                $this->moduleLabel($this->value($item, 'module')),
                $this->auditActionLabel($this->value($item, 'action')),
                $this->auditDetail($item),
                $this->auditResult($item),
                $this->value($item, 'ip_address'),
            ],
            'rsa-keys' => [
                $this->value($item, 'key_identifier'),
                $this->rsaKeyOwner($item),
                $this->value($item, 'algorithm'),
                $this->value($item, 'key_length'),
                $this->value($item, 'fingerprint_sha256'),
                $this->rsaKeyStatus($item),
            ],
            default => [],
        };
    }

    private function value(array $item, string $key): string
    {
        $value = Arr::get($item, $key);

        return ($value === null || $value === '') ? '-' : (string) $value;
    }

    private function date(string $value): string
    {
        return $value === '-' ? '-' : substr($value, 0, 10);
    }

    private function time(string $value): string
    {
        return $value === '-' ? '-' : substr($value, 11, 5);
    }

    private function volume(string $value): string
    {
        return $value === '-' ? '-' : rtrim(rtrim($value, '0'), '.').' ml';
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
            'active', 'alive' => 'Aktif',
            'inactive' => 'Nonaktif',
            'closed' => 'Ditutup',
            'dead' => 'Mati',
            'revoked' => 'Dicabut',
            'expired' => 'Kedaluwarsa',
            default => $value,
        };
    }

    private function rsaKeyStatus(array $item): string
    {
        return match ($item['key_status'] ?? null) {
            'active' => 'Aktif',
            'retired' => 'Tidak Aktif',
            'compromised' => 'Dinonaktifkan',
            default => ((bool) ($item['is_active'] ?? false)) ? 'Aktif' : 'Tidak Aktif',
        };
    }

    private function rsaKeyOwner(array $item): string
    {
        $user = Arr::get($item, 'user', []);
        if (! is_array($user)) {
            return '-';
        }

        $name = trim(implode(' ', array_filter([
            Arr::get($user, 'first_name'),
            Arr::get($user, 'last_name'),
        ])));

        $name = $name !== '' ? $name : (string) Arr::get($user, 'name', '-');
        $email = (string) Arr::get($user, 'email', '');

        return $email !== '' ? "{$name} ({$email})" : $name;
    }

    private function lifeStatus(string $value): string
    {
        return match ($value) {
            'alive' => 'Hidup',
            'dead' => 'Mati',
            default => $value,
        };
    }

    private function animalCategory(string $value): string
    {
        return match (strtolower($value)) {
            'cempe' => 'Cempe',
            'dere' => 'Dere',
            'pejantan muda' => 'Pejantan Muda',
            'pejantan dewasa' => 'Pejantan Dewasa',
            'betina dewasa' => 'Betina Dewasa',
            'unknown', '-' => '-',
            default => $value,
        };
    }

    private function penType(string $value): string
    {
        return match ($value) {
            'koloni_kawin' => 'Perkawinan',
            'koloni_bunting' => 'Kebuntingan',
            'koloni_kering' => 'Kering',
            'koloni_laktasi' => 'Laktasi',
            'koloni_laktasi_kosong' => 'Laktasi',
            'koloni_anak' => 'Anak/Cempe',
            default => $value,
        };
    }

    private function reproductiveStatus(string $value): string
    {
        return match ($value) {
            'kosong' => 'Kosong',
            'kawin' => 'Kawin',
            'bunting' => 'Bunting',
            'kering' => 'Kering',
            'laktasi_kosong' => 'Laktasi Kosong',
            'melahirkan' => 'Melahirkan',
            'afkir' => 'Afkir',
            default => $value,
        };
    }

    private function verificationMethod(string $value): string
    {
        return match ($value) {
            'certificate_number' => 'Nomor Sertifikat',
            'qr_code' => 'QR Code',
            'upload_pdf' => 'Upload PDF',
            default => $value === '-' ? 'Belum Tercatat' : $value,
        };
    }

    private function certificateTypeName(string $value): string
    {
        return str_contains(strtolower($value), 'keaslian') ? 'Sertifikat Bibit Unggul' : $value;
    }

    private function adminIdentity(array $item): string
    {
        $name = $this->value($item, 'admin_name');
        $email = $this->value($item, 'admin_email');

        if ($name !== '-' && $email !== '-') {
            return "{$name} ({$email})";
        }

        return $name !== '-' ? $name : ($email !== '-' ? $email : 'Admin Sistem');
    }

    private function auditActionLabel(string $value): string
    {
        return match ($value) {
            'login' => 'Login',
            'login_failed' => 'Login Gagal',
            'logout' => 'Logout',
            'create' => 'Tambah Data',
            'update' => 'Perbarui Data',
            'delete', 'deactivate' => 'Nonaktifkan Data',
            'activate' => 'Aktifkan Data',
            'sign' => 'Tanda Tangan Sertifikat',
            'revoke' => 'Cabut Sertifikat',
            'unrevoke' => 'Aktifkan Kembali Sertifikat',
            'generate' => 'Buat RSA Key',
            'mating' => 'Catat Tanggal Kawin',
            'exit' => 'Keluar dari Periode',
            default => $value === '-' ? 'Aktivitas Sistem' : str($value)->replace('_', ' ')->title()->toString(),
        };
    }

    private function auditResult(array $item): string
    {
        $statusCode = $this->value($item, 'status_code');

        if ($statusCode === '-') {
            return 'Tercatat';
        }

        $statusCode = (int) $statusCode;

        return match (true) {
            $statusCode >= 200 && $statusCode < 300 => 'Berhasil',
            $statusCode >= 300 && $statusCode < 400 => 'Dialihkan',
            $statusCode >= 400 && $statusCode < 500 => 'Ditolak',
            $statusCode >= 500 => 'Gagal',
            default => 'Tercatat',
        };
    }

    private function auditDetail(array $item): string
    {
        $subjectLabel = $this->value($item, 'metadata.subject_label');
        $description = $this->value($item, 'description');
        $detailParts = [];

        if ($subjectLabel !== '-') {
            $detailParts[] = 'Objek: '.$subjectLabel;
        }

        if ($description !== '-' && preg_match('/\((.+)\)\.?$/', $description, $matches) === 1) {
            $detailParts[] = $matches[1];
        }

        if ($detailParts !== []) {
            return implode('; ', array_unique($detailParts));
        }

        return match ($this->value($item, 'action')) {
            'login' => 'Sesi masuk admin berhasil dibuat',
            'logout' => 'Sesi admin telah diakhiri',
            'login_failed' => 'Permintaan autentikasi ditolak oleh sistem',
            default => $this->endpointDetail($item),
        };
    }

    private function endpointDetail(array $item): string
    {
        $method = $this->value($item, 'method');
        $path = $this->value($item, 'path');

        if ($method === '-' && $path === '-') {
            return 'Aktivitas tercatat oleh sistem';
        }

        return trim("Endpoint: {$method} /{$path}");
    }

    private function activityText(array $item): string
    {
        $description = $this->value($item, 'description');
        $admin = $this->value($item, 'admin_name');
        $adminLabel = $admin === '-' ? 'Admin' : 'Admin '.$admin;
        $module = $this->moduleLabel($this->value($item, 'module'));
        $action = $this->value($item, 'action');

        if ($description !== '-' && str_starts_with($description, 'Log:')) {
            return rtrim($description, '.');
        }

        if ($description !== '-' && str_starts_with($description, 'Admin ')) {
            return $this->normalizeStoredActivityDescription($description, $action, $adminLabel, $module, $this->isFailedActivity($item));
        }

        if ($this->isFailedActivity($item)) {
            return match ($action) {
                'login', 'login_failed' => 'Log: Autentikasi Admin ditolak oleh sistem',
                default => "Log: Permintaan {$adminLabel} untuk {$this->actionVerb($action)} pada modul {$module} gagal diproses",
            };
        }

        return match ($action) {
            'login' => "Log: {$adminLabel} berhasil masuk ke sistem",
            'logout' => "Log: {$adminLabel} telah keluar dari sistem",
            'create' => "Log: {$adminLabel} menyimpan data {$module}",
            'update' => "Log: {$adminLabel} memperbarui data {$module}",
            'delete' => "Log: {$adminLabel} menonaktifkan data {$module}",
            'sign' => "Log: {$adminLabel} menandatangani sertifikat",
            'revoke' => "Log: {$adminLabel} mencabut sertifikat",
            'unrevoke' => "Log: {$adminLabel} mengaktifkan kembali sertifikat",
            'generate' => "Log: {$adminLabel} membuat kunci RSA",
            'activate' => "Log: {$adminLabel} mengaktifkan data {$module}",
            'deactivate' => "Log: {$adminLabel} menonaktifkan data {$module}",
            default => "Log: {$adminLabel} melakukan aktivitas pada {$module}",
        };
    }

    private function isFailedActivity(array $item): bool
    {
        return (int) $this->value($item, 'status_code') >= 400;
    }

    private function normalizeStoredActivityDescription(string $description, string $action, string $adminLabel, string $module, bool $failed): string
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
                default => "Log: Permintaan {$adminLabel} untuk {$this->actionVerb($action)} pada modul {$module} gagal diproses",
            };
        }

        return rtrim($description, '.');
    }

    private function actionVerb(string $action): string
    {
        return match ($action) {
            'create' => 'menyimpan data',
            'update' => 'memperbarui data',
            'delete', 'deactivate' => 'menonaktifkan data',
            'sign' => 'menandatangani',
            'revoke' => 'mencabut',
            'unrevoke' => 'mengaktifkan kembali',
            'generate' => 'membuat',
            'activate' => 'mengaktifkan data',
            'compromise' => 'menonaktifkan data',
            'mating' => 'mencatat tanggal kawin untuk',
            'exit' => 'mengeluarkan',
            default => 'memproses',
        };
    }

    private function moduleLabel(string $value): string
    {
        return [
            'auth' => 'Autentikasi',
            'farm' => 'Profil Farm',
            'users' => 'Manajemen Pengguna',
            'breeds' => 'Ras Kambing',
            'animals' => 'Data Kambing',
            'colony-pens' => 'Kandang & Koloni',
            'breeding-periods' => 'Periode Kawin',
            'breeding-females' => 'Betina Kawin',
            'pregnancy-checks' => 'Kebuntingan',
            'birth-events' => 'Kelahiran',
            'offspring-births' => 'Cempe Lahir',
            'postnatal-care-records' => 'Pascalahir',
            'weight-records' => 'Catatan Bobot',
            'pen-movements' => 'Pindah Koloni',
            'health-treatments' => 'Kesehatan',
            'vaccinations' => 'Vaksinasi',
            'certificates' => 'Akte & Sertifikat',
            'rsa-keys' => 'RSA Key',
        ][$value] ?? str($value)->replace('-', ' ')->title()->toString();
    }

    private function roleLabel(string $value): string
    {
        return match ($value) {
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            default => $value,
        };
    }

    private function animalOperationalStatus(array $item): string
    {
        $exitStatus = $this->value($item, 'exit_status');

        if ($exitStatus !== '-') {
            return match ($exitStatus) {
                'sold' => 'Dijual',
                'culled' => 'Afkir / Tidak Produktif',
                'lost' => 'Hilang',
                default => $exitStatus,
            };
        }

        return $this->lifeStatus($this->value($item, 'life_status'));
    }

    private function originType(string $value, bool $isImport = false): string
    {
        if ($value === '-' && $isImport) {
            return 'Impor';
        }

        return match ($value) {
            'internal_birth' => 'Lahir Internal',
            'purchase' => 'Pembelian',
            'import' => 'Impor',
            'grant' => 'Hibah',
            'unknown', '-' => 'Tidak Diketahui',
            default => $value,
        };
    }

    private function ageBetween(string $birthDate, string $recordDate, string $fallbackMonths): string
    {
        if ($birthDate !== '-' && $recordDate !== '-') {
            $diff = Carbon::parse($birthDate)->diff(Carbon::parse($recordDate));
            $parts = [];

            if ($diff->y > 0) {
                $parts[] = $diff->y.' Tahun';
            }

            if ($diff->m > 0) {
                $parts[] = $diff->m.' Bulan';
            }

            if ($diff->d > 0 || $parts === []) {
                $parts[] = $diff->d.' Hari';
            }

            return implode(' ', $parts);
        }

        if (is_numeric($fallbackMonths)) {
            $months = (int) $fallbackMonths;
            $years = intdiv($months, 12);
            $remainingMonths = $months % 12;
            $parts = [];

            if ($years > 0) {
                $parts[] = $years.' Tahun';
            }

            if ($remainingMonths > 0 || $parts === []) {
                $parts[] = $remainingMonths.' Bulan';
            }

            return implode(' ', $parts);
        }

        return '-';
    }
}
