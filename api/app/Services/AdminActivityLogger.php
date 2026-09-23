<?php

namespace App\Services;

use App\Models\AdminActivityLog;
use App\Support\TypeValue;
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

    /**
     * @param  array<string, mixed>  $metadata
     */
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
        $detailData = $this->responseData($response) ?? TypeValue::stringKeyArray($request->all());

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

        return is_string($module) ? $module : 'unknown';
    }

    /**
     * @return array{type: class-string<Model>|null, id: mixed, label: string|null}
     */
    private function resolveSubject(Request $request, ?Response $response, string $module): array
    {
        foreach ($request->route()?->parameters() ?? [] as $parameter) {
            if ($parameter instanceof Model) {
                return [
                    'type' => $parameter::class,
                    'id' => $parameter->getKey(),
                    'label' => $this->subjectLabel($module, TypeValue::stringKeyArray($parameter->toArray())),
                ];
            }
        }

        $responseData = $this->responseData($response);
        if (is_array($responseData)) {
            return [
                'type' => null,
                'id' => isset($responseData['id']) ? TypeValue::int($responseData['id']) : null,
                'label' => $this->subjectLabel($module, $responseData),
            ];
        }

        return [
            'type' => null,
            'id' => null,
            'label' => $this->subjectLabel($module, TypeValue::stringKeyArray($request->all())),
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

    /**
     * @param  array<array-key, mixed>  $data
     */
    private function subjectLabel(string $module, array $data): ?string
    {
        $label = match ($module) {
            'users' => data_get($data, 'email') ?? data_get($data, 'name'),
            'animals' => data_get($data, 'tag_number'),
            'colony-pens' => data_get($data, 'pen_code'),
            'breeding-periods' => data_get($data, 'period_code'),
            'breeding-females' => $this->breedingFemaleLabel($data),
            'pregnancy-checks' => $this->pregnancyCheckLabel(TypeValue::stringKeyArray($data)),
            'birth-events' => $this->birthEventLabel(TypeValue::stringKeyArray($data)),
            'offspring-births' => $this->relatedAnimalLabel(TypeValue::stringKeyArray($data), 'offspring_animal'),
            'postnatal-care-records' => $this->relatedAnimalLabel(TypeValue::stringKeyArray($data), 'target_animal'),
            'weight-records', 'health-treatments', 'vaccinations' => $this->relatedAnimalLabel(TypeValue::stringKeyArray($data), 'animal'),
            'certificates' => data_get($data, 'certificate_number'),
            'rsa-keys' => data_get($data, 'key_identifier'),
            'breeds' => data_get($data, 'breed_name'),
            'certificate-types' => data_get($data, 'type_name'),
            default => null,
        };

        $label = $this->stringLabel($label);
        if ($label !== null) {
            return $label;
        }

        $id = $this->stringLabel(data_get($data, 'id'));

        return $id !== null ? '#'.$id : null;
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private function breedingFemaleLabel(array $data): ?string
    {
        $tag = $this->stringLabel(data_get($data, 'female_animal.tag_number'));
        $period = $this->stringLabel(data_get($data, 'breeding_period.period_code'));

        if ($tag && $period) {
            return "{$tag} pada periode {$period}";
        }

        return $tag ?? $period;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function pregnancyCheckLabel(array $data): ?string
    {
        $tag = $this->stringLabel(data_get($data, 'female_animal.tag_number'))
            ?? $this->stringLabel(data_get($data, 'breeding_female.female_animal.tag_number'));
        $period = $this->stringLabel(data_get($data, 'breeding_period.period_code'))
            ?? $this->stringLabel(data_get($data, 'breeding_female.breeding_period.period_code'));
        $date = $this->dateValue(data_get($data, 'check_date'));

        $parts = array_filter([$tag, $period ? "periode {$period}" : null, $date ? "tanggal {$date}" : null]);

        return $parts !== [] ? implode(' ', $parts) : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function birthEventLabel(array $data): ?string
    {
        $dam = $this->stringLabel(data_get($data, 'dam.tag_number'));
        $date = $this->dateValue(data_get($data, 'birth_date'));

        if ($dam && $date) {
            return "induk {$dam} tanggal {$date}";
        }

        return $dam ?? $date ?? null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function relatedAnimalLabel(array $data, string $relation): ?string
    {
        return $this->stringLabel(data_get($data, "{$relation}.tag_number"))
            ?? $this->stringLabel(data_get($data, 'animal.tag_number'))
            ?? $this->stringLabel(data_get($data, 'target_animal.tag_number'))
            ?? $this->stringLabel(data_get($data, 'offspring_animal.tag_number'));
    }

    /**
     * @param  array<string, mixed>  $data
     */
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
                'status' => fn (array $row) => $this->animalStatusLabel(TypeValue::stringKeyArray($row)),
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
                'keluar' => fn (array $row) => $this->exitDetail(TypeValue::stringKeyArray($row)),
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
                'status' => fn (array $row) => $this->rsaKeyStatusLabel(TypeValue::stringKeyArray($row)),
                'alasan status' => 'status_reason',
            ]),
            default => '',
        };

        return $details === '' ? '' : " ({$details})";
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, string|callable(array<string, mixed>): mixed>  $fields
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

            $detail = $this->detailValue($value);
            if ($detail === null) {
                continue;
            }

            $parts[] = "{$label}: {$detail}";
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

    private function stringLabel(mixed $value): ?string
    {
        if (is_string($value) && $value !== '') {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return null;
    }

    private function detailValue(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === '-') {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'Ya' : 'Tidak';
        }

        return $this->stringLabel($value);
    }

    /**
     * @param  array<string, mixed>  $row
     */
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

    /**
     * @param  array<string, mixed>  $data
     */
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

    /**
     * @param  array<string, mixed>  $data
     */
    private function exitDetail(array $data): ?string
    {
        $date = $this->dateValue($data['exit_date'] ?? null);
        $reason = $this->stringLabel($data['exit_reason'] ?? null);

        if ($date && $reason) {
            return "{$date} - {$reason}";
        }

        return $date ?: $reason;
    }

    /**
     * @return array<string, mixed>|null
     */
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

        return TypeValue::stringKeyArray(is_array($json['data'] ?? null) ? $json['data'] : $json);
    }

    /**
     * @return array<string, mixed>
     */
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
