<?php

namespace App\Services;

use App\Models\AdminActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AdminActivityLogger
{
    private const MODULE_LABELS = [
        'users' => 'manajemen pengguna',
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
        'certificate-types' => 'jenis sertifikat',
        'certificates' => 'akte dan sertifikat',
        'rsa-keys' => 'RSA Key',
        'auth' => 'akun admin',
    ];

    private const ACTION_LABELS = [
        'login' => 'Login admin',
        'login_failed' => 'Gagal Masuk',
        'logout' => 'Logout admin',
        'create' => 'Menambahkan data',
        'update' => 'Memperbarui data',
        'delete' => 'Menghapus atau menonaktifkan data',
        'sign' => 'Menandatangani sertifikat',
        'revoke' => 'Mencabut sertifikat',
        'unrevoke' => 'Membatalkan pencabutan sertifikat',
        'generate' => 'Membuat kunci RSA',
        'activate' => 'Mengaktifkan data',
        'deactivate' => 'Menonaktifkan data',
        'compromise' => 'Menonaktifkan RSA Key',
        'mating' => 'Mencatat tanggal kawin',
        'exit' => 'Mengeluarkan dari periode',
        'unknown' => 'Melakukan aktivitas admin',
    ];

    public function log(
        Request $request,
        ?int $statusCode = null,
        ?string $action = null,
        ?string $module = null,
        array $metadata = [],
        ?Response $response = null
    ): void {
        $user = $request->user();
        $action ??= $this->resolveAction($request);
        $module ??= $this->resolveModule($request);
        $subject = $this->resolveSubject($request, $response, $module);
        $logMetadata = $metadata ?: $this->metadata($request);
        $detailData = $this->responseData($response) ?? $request->all();

        if ($subject['label'] !== null) {
            $logMetadata['subject_label'] = $subject['label'];
        }

        try {
            AdminActivityLog::query()->create([
                'admin_id' => $user?->id,
                'admin_name' => $user?->name,
                'admin_email' => $user?->email,
                'action' => $action,
                'module' => $module,
                'description' => $this->description(
                    $user?->name,
                    $action,
                    $module,
                    $subject['label'],
                    $statusCode,
                    $this->detailPhrase($module, $detailData)
                ),
                'subject_type' => $subject['type'],
                'subject_id' => $subject['id'],
                'method' => $request->method(),
                'path' => $request->path(),
                'status_code' => $statusCode,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => $logMetadata,
            ]);
        } catch (Throwable) {
            report(new \RuntimeException('Admin activity log could not be written.'));
        }
    }

    public function shouldLog(Request $request, int $statusCode): bool
    {
        if ($request->isMethod('GET') || $request->isMethod('HEAD') || $request->isMethod('OPTIONS')) {
            return false;
        }

        if (str_contains($request->path(), 'admin-activity-logs')) {
            return false;
        }

        return $statusCode < 500;
    }

    private function resolveAction(Request $request): string
    {
        $segments = $request->segments();
        $last = end($segments) ?: '';

        if (in_array($last, ['sign', 'revoke', 'unrevoke', 'generate', 'activate', 'deactivate', 'compromise', 'mating', 'exit'], true)) {
            return $last;
        }

        return match ($request->method()) {
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'unknown',
        };
    }

    private function resolveModule(Request $request): string
    {
        $segments = $request->segments();
        $module = $segments[2] ?? 'unknown';

        return (string) $module;
    }

    private function resolveSubject(Request $request, ?Response $response, string $module): array
    {
        foreach ($request->route()?->parameters() ?? [] as $parameter) {
            if ($parameter instanceof Model) {
                return [
                    'type' => $parameter::class,
                    'id' => $parameter->getKey(),
                    'label' => $this->subjectLabel($module, $parameter->toArray()),
                ];
            }
        }

        $responseData = $this->responseData($response);
        if (is_array($responseData)) {
            return [
                'type' => null,
                'id' => isset($responseData['id']) ? (int) $responseData['id'] : null,
                'label' => $this->subjectLabel($module, $responseData),
            ];
        }

        return [
            'type' => null,
            'id' => null,
            'label' => $this->subjectLabel($module, $request->all()),
        ];
    }

    private function description(
        ?string $adminName,
        string $action,
        string $module,
        ?string $subjectLabel,
        ?int $statusCode,
        string $detailPhrase = ''
    ): string {
        $moduleLabel = self::MODULE_LABELS[$module] ?? str($module)->replace('-', ' ')->title()->toString();
        $adminLabel = 'Admin '.($adminName ?: 'sistem');
        $target = $this->targetPhrase($module, $subjectLabel);
        $failed = $statusCode !== null && $statusCode >= 400;

        if ($action === 'login') {
            return "Log: {$adminLabel} berhasil masuk ke sistem.";
        }

        if ($action === 'login_failed') {
            return 'Log: Autentikasi Admin ditolak oleh sistem.';
        }

        if ($action === 'logout') {
            return "Log: {$adminLabel} telah keluar dari sistem.";
        }

        $verb = match ($action) {
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

        if ($failed) {
            return "Log: Permintaan {$adminLabel} untuk {$verb} pada modul {$moduleLabel}{$target}{$detailPhrase} ditolak karena melanggar kebijakan sistem.";
        }

        return "{$adminLabel} {$verb} {$moduleLabel}{$target}{$detailPhrase}.";
    }

    private function targetPhrase(string $module, ?string $subjectLabel): string
    {
        if ($subjectLabel === null || $subjectLabel === '') {
            return '';
        }

        return match ($module) {
            'users' => " {$subjectLabel}",
            'animals' => " dengan tag {$subjectLabel}",
            'colony-pens' => " dengan kode kandang {$subjectLabel}",
            'breeding-periods' => " dengan kode periode {$subjectLabel}",
            'breeding-females' => " {$subjectLabel}",
            'pregnancy-checks' => " {$subjectLabel}",
            'birth-events' => " {$subjectLabel}",
            'offspring-births' => " dengan tag {$subjectLabel}",
            'postnatal-care-records' => " untuk tag {$subjectLabel}",
            'weight-records', 'health-treatments', 'vaccinations' => " untuk tag {$subjectLabel}",
            'certificates' => " dengan nomor sertifikat {$subjectLabel}",
            'rsa-keys' => " dengan key identifier {$subjectLabel}",
            'breeds' => " {$subjectLabel}",
            default => " dengan ID {$subjectLabel}",
        };
    }

    private function subjectLabel(string $module, array $data): ?string
    {
        $label = match ($module) {
            'users' => $data['email'] ?? $data['name'] ?? null,
            'animals' => $data['tag_number'] ?? null,
            'colony-pens' => $data['pen_code'] ?? null,
            'breeding-periods' => $data['period_code'] ?? null,
            'breeding-females' => $this->breedingFemaleLabel($data),
            'pregnancy-checks' => $this->pregnancyCheckLabel($data),
            'birth-events' => $this->birthEventLabel($data),
            'offspring-births' => $this->relatedAnimalLabel($data, 'offspring_animal'),
            'postnatal-care-records' => $this->relatedAnimalLabel($data, 'target_animal'),
            'weight-records', 'health-treatments', 'vaccinations' => $this->relatedAnimalLabel($data, 'animal'),
            'certificates' => $data['certificate_number'] ?? null,
            'rsa-keys' => $data['key_identifier'] ?? null,
            'breeds' => $data['breed_name'] ?? null,
            'certificate-types' => $data['type_name'] ?? null,
            default => null,
        };

        if ($label !== null && $label !== '') {
            return (string) $label;
        }

        return isset($data['id']) ? '#'.$data['id'] : null;
    }

    private function breedingFemaleLabel(array $data): ?string
    {
        $tag = $data['female_animal']['tag_number'] ?? null;
        $period = $data['breeding_period']['period_code'] ?? null;

        if ($tag && $period) {
            return "{$tag} pada periode {$period}";
        }

        return $tag ?? $period ?? null;
    }

    private function pregnancyCheckLabel(array $data): ?string
    {
        $tag = $data['female_animal']['tag_number']
            ?? $data['breeding_female']['female_animal']['tag_number']
            ?? null;
        $period = $data['breeding_period']['period_code']
            ?? $data['breeding_female']['breeding_period']['period_code']
            ?? null;
        $date = isset($data['check_date']) ? substr((string) $data['check_date'], 0, 10) : null;

        $parts = array_filter([$tag, $period ? "periode {$period}" : null, $date ? "tanggal {$date}" : null]);

        return $parts !== [] ? implode(' ', $parts) : null;
    }

    private function birthEventLabel(array $data): ?string
    {
        $dam = $data['dam']['tag_number'] ?? null;
        $date = isset($data['birth_date']) ? substr((string) $data['birth_date'], 0, 10) : null;

        if ($dam && $date) {
            return "induk {$dam} tanggal {$date}";
        }

        return $dam ?? $date ?? null;
    }

    private function relatedAnimalLabel(array $data, string $relation): ?string
    {
        return $data[$relation]['tag_number']
            ?? $data['animal']['tag_number']
            ?? $data['target_animal']['tag_number']
            ?? $data['offspring_animal']['tag_number']
            ?? null;
    }

    private function detailPhrase(string $module, array $data): string
    {
        $details = match ($module) {
            'users' => $this->details($data, [
                'nama' => 'name',
                'email' => 'email',
                'role' => fn (array $row) => $this->roleLabel($row['role'] ?? null),
                'status' => fn (array $row) => $this->activeLabel($row['is_active'] ?? null),
            ]),
            'animals' => $this->details($data, [
                'tag' => 'tag_number',
                'ras' => 'breed.breed_name',
                'jenis kelamin' => fn (array $row) => $this->sexLabel($row['sex'] ?? null),
                'status' => fn (array $row) => $this->animalStatusLabel($row),
            ]),
            'colony-pens' => $this->details($data, [
                'kode koloni' => 'colony_code',
                'fase' => fn (array $row) => $this->colonyPhaseLabel($row['colony_phase'] ?? $row['colony_type'] ?? null),
                'kapasitas' => 'capacity',
            ]),
            'breeding-periods' => $this->details($data, [
                'kandang' => 'colony_pen.pen_code',
                'pejantan' => 'male_animal.tag_number',
                'mulai' => fn (array $row) => $this->dateValue($row['start_date'] ?? null),
                'selesai' => fn (array $row) => $this->dateValue($row['end_date'] ?? null),
                'status' => fn (array $row) => $this->periodStatusLabel($row['status'] ?? null),
            ]),
            'breeding-females' => $this->details($data, [
                'periode' => 'breeding_period.period_code',
                'betina' => 'female_animal.tag_number',
                'masuk' => fn (array $row) => $this->dateValue($row['entry_date'] ?? null),
                'kawin' => fn (array $row) => $this->dateValue($row['mating_date'] ?? null),
                'perkiraan lahir' => fn (array $row) => $this->dateValue($row['expected_birth_date'] ?? null),
                'keluar' => fn (array $row) => $this->exitDetail($row),
            ]),
            'pregnancy-checks' => $this->details($data, [
                'periode' => 'breeding_period.period_code',
                'betina' => 'female_animal.tag_number',
                'tanggal periksa' => fn (array $row) => $this->dateValue($row['check_date'] ?? null),
                'hasil' => fn (array $row) => $this->pregnancyLabel($row['is_pregnant'] ?? null),
            ]),
            'birth-events' => $this->details($data, [
                'induk' => 'dam.tag_number',
                'pejantan' => 'sire.tag_number',
                'tanggal lahir' => fn (array $row) => $this->dateValue($row['birth_date'] ?? null),
                'jumlah anak' => 'offspring_count',
            ]),
            'offspring-births' => $this->details($data, [
                'cempe' => 'offspring_animal.tag_number',
                'grade anak' => 'offspring_grade',
                'berat lahir' => 'birth_weight_kg',
                'status' => fn (array $row) => $this->lifeStatusLabel($row['birth_status'] ?? null),
            ]),
            'weight-records' => $this->details($data, [
                'tag' => 'animal.tag_number',
                'tanggal timbang' => fn (array $row) => $this->dateValue($row['record_date'] ?? null),
                'bobot' => 'weight_kg',
            ]),
            'health-treatments' => $this->details($data, [
                'tag' => 'animal.tag_number',
                'tanggal' => fn (array $row) => $this->dateValue($row['treatment_date'] ?? null),
                'perawatan' => 'treatment_group',
                'diagnosis' => 'diagnosis',
            ]),
            'vaccinations' => $this->details($data, [
                'tag' => 'animal.tag_number',
                'tanggal vaksin' => fn (array $row) => $this->dateValue($row['vaccination_date'] ?? null),
                'vaksin' => 'product_name',
            ]),
            'postnatal-care-records' => $this->details($data, [
                'tag' => 'target_animal.tag_number',
                'tanggal' => fn (array $row) => $this->dateValue($row['care_date'] ?? null),
                'metode kolostrum' => 'administration_method',
            ]),
            'certificates' => $this->details($data, [
                'nomor sertifikat' => 'certificate_number',
                'tag' => 'animal.tag_number',
                'jenis' => 'certificate_type.type_name',
                'status' => fn (array $row) => $this->certificateStatusLabel($row['status'] ?? null),
            ]),
            'rsa-keys' => $this->details($data, [
                'key identifier' => 'key_identifier',
                'algoritma' => 'algorithm',
                'panjang kunci' => 'key_length',
                'status' => fn (array $row) => $this->rsaKeyStatusLabel($row),
                'alasan status' => 'status_reason',
            ]),
            default => '',
        };

        return $details === '' ? '' : " ({$details})";
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, string|callable>  $fields
     */
    private function details(array $data, array $fields): string
    {
        $parts = [];

        foreach ($fields as $label => $resolver) {
            $value = is_callable($resolver)
                ? $resolver($data)
                : data_get($data, $resolver);

            if ($value === null || $value === '' || $value === '-') {
                continue;
            }

            if (is_bool($value)) {
                $value = $value ? 'Ya' : 'Tidak';
            }

            $parts[] = "{$label}: {$value}";
        }

        return implode(', ', array_slice($parts, 0, 5));
    }

    private function dateValue(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? substr($value, 0, 10) : null;
    }

    private function roleLabel(mixed $value): ?string
    {
        return match ($value) {
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            default => is_string($value) && $value !== '' ? $value : null,
        };
    }

    private function activeLabel(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL) ? 'Aktif' : 'Nonaktif';
    }

    private function rsaKeyStatusLabel(array $row): ?string
    {
        return match ($row['key_status'] ?? null) {
            'active' => 'Aktif',
            'retired' => 'Tidak Aktif',
            'compromised' => 'Dinonaktifkan',
            default => $this->activeLabel($row['is_active'] ?? null),
        };
    }

    private function sexLabel(mixed $value): ?string
    {
        return match ($value) {
            'male' => 'Jantan',
            'female' => 'Betina',
            default => is_string($value) && $value !== '' ? $value : null,
        };
    }

    private function lifeStatusLabel(mixed $value): ?string
    {
        return match ($value) {
            'alive' => 'Hidup',
            'dead' => 'Mati',
            default => is_string($value) && $value !== '' ? $value : null,
        };
    }

    private function periodStatusLabel(mixed $value): ?string
    {
        return match ($value) {
            'active' => 'Aktif',
            'closed' => 'Ditutup',
            default => is_string($value) && $value !== '' ? $value : null,
        };
    }

    private function certificateStatusLabel(mixed $value): ?string
    {
        return match ($value) {
            'active' => 'Aktif',
            'revoked' => 'Dicabut',
            'expired' => 'Kedaluwarsa',
            default => is_string($value) && $value !== '' ? $value : null,
        };
    }

    private function colonyPhaseLabel(mixed $value): ?string
    {
        return match ($value) {
            'koloni_kawin' => 'Kawin',
            'koloni_bunting' => 'Bunting',
            'koloni_kering' => 'Kering',
            'koloni_laktasi', 'koloni_laktasi_kosong' => 'Laktasi',
            'koloni_anak' => 'Anak/Cempe',
            default => is_string($value) && $value !== '' ? $value : null,
        };
    }

    private function animalStatusLabel(array $data): ?string
    {
        $exitStatus = $data['exit_status'] ?? null;

        if ($exitStatus !== null && $exitStatus !== '') {
            return match ($exitStatus) {
                'sold' => 'Dijual',
                'culled' => 'Afkir / Tidak Produktif',
                'lost' => 'Hilang',
                default => is_string($exitStatus) ? $exitStatus : null,
            };
        }

        return $this->lifeStatusLabel($data['life_status'] ?? null);
    }

    private function pregnancyLabel(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL) ? 'Bunting' : 'Tidak Bunting';
    }

    private function exitDetail(array $data): ?string
    {
        $date = $this->dateValue($data['exit_date'] ?? null);
        $reason = $data['exit_reason'] ?? null;

        if ($date && $reason) {
            return "{$date} - {$reason}";
        }

        return $date ?: (is_string($reason) && $reason !== '' ? $reason : null);
    }

    private function responseData(?Response $response): ?array
    {
        if ($response === null || $response->getStatusCode() >= 400) {
            return null;
        }

        $content = $response->getContent();
        if (! is_string($content) || $content === '') {
            return null;
        }

        $json = json_decode($content, true);
        if (! is_array($json)) {
            return null;
        }

        return is_array($json['data'] ?? null) ? $json['data'] : $json;
    }

    private function metadata(Request $request): array
    {
        $payload = collect($request->except(['password', 'password_confirmation', 'token']))
            ->map(fn ($value) => is_string($value) && strlen($value) > 500 ? substr($value, 0, 500).'...' : $value)
            ->all();

        return [
            'route' => $request->route()?->getName(),
            'payload' => $payload,
        ];
    }
}
