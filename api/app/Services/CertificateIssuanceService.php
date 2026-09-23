<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\BirthEvent;
use App\Models\Certificate;
use App\Models\CertificateType;
use App\Support\TypeValue;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CertificateIssuanceService
{
    public function __construct(
        private readonly CertificateNumberService $numbers,
        private readonly CertificatePayloadSnapshotService $snapshots,
        private readonly CertificateSigningService $signing,
        private readonly CertificateViewDataService $viewData,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array<array-key, mixed>
     */
    public function issue(array $data, bool $autoSign = true): array
    {
        try {
            return DB::transaction(function () use ($data, $autoSign) {
                $animal = Animal::query()->with(['breed'])->whereKey(TypeValue::int($data['animal_id']))->lockForUpdate()->firstOrFail();
                $type = CertificateType::query()
                    ->whereKey(TypeValue::int($data['certificate_type_id']))
                    ->lockForUpdate()
                    ->firstOrFail();

                $guard = $this->validateIssueContext($animal, $type, $data);
                if ($guard !== null) {
                    return $guard;
                }

                $replacedCertificate = $this->replacementCandidate($animal, $type);

                $issueDate = now()->toDateString();
                $certificateData = $this->baseCertificateData($animal, $type, $data, $issueDate);
                $certificateData['replaces_certificate_id'] = $replacedCertificate?->id;

                $typeResult = $this->applyTypeSpecificData($certificateData, $animal, $type, $data, $issueDate);
                if (($typeResult['ok'] ?? true) === false) {
                    return $typeResult;
                }

                $certificateData = TypeValue::stringKeyArray($typeResult['data'] ?? []);
                $payloadSnapshot = $this->snapshots->build($certificateData, $animal);
                $certificateData['payload_snapshot'] = $payloadSnapshot;
                $certificateData['hash_sha256'] = hash('sha256', $payloadSnapshot);

                $certificate = Certificate::create($certificateData);

                $viewSnapshot = $this->viewData->build($certificate->load([
                    'animal.breed', 'animal.currentPen', 'certificateType',
                    'birthEvent.dam.breed', 'birthEvent.sire.breed',
                    'birthEvent.offspringBirths', 'birthEvent.postnatalCareRecords',
                ]));
                $payloadSnapshot = $this->snapshots->build($certificateData, $animal, $viewSnapshot);
                $certificate->forceFill([
                    'payload_snapshot' => $payloadSnapshot,
                    'hash_sha256' => hash('sha256', $payloadSnapshot),
                ])->save();

                if ($autoSign && $certificate->status === 'active') {
                    try {
                        $this->signing->sign($certificate);
                    } catch (\Throwable $e) {
                        report($e);

                        throw new \RuntimeException('Signing failed.', 0, $e);
                    }
                }

                return $this->success('Sukses: Sertifikat berhasil diterbitkan.', $certificate->load([
                    'animal.breed',
                    'certificateType',
                    'replacedCertificate',
                    'birthEvent',
                    'signature.rsaKey',
                ]), 201);
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'Signing failed.') {
                return $this->error('Gagal: Sertifikat gagal ditandatangani. Pastikan RSA Key aktif telah dikonfigurasi dengan benar.');
            }

            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<array-key, mixed>|null
     */
    private function validateIssueContext(Animal $animal, CertificateType $type, array $data): ?array
    {
        if (! $type->is_active) {
            return $this->error('Peringatan: Jenis sertifikat ini sedang tidak aktif.');
        }

        $existingValidCertificate = Certificate::query()
            ->where('animal_id', $animal->id)
            ->where('certificate_type_id', $type->id)
            ->where('status', '!=', 'revoked')
            ->lockForUpdate()
            ->exists();

        if (in_array($type->type_code, ['KELAHIRAN', 'KEMATIAN'], true) && $existingValidCertificate) {
            return $this->error('Peringatan: Sertifikat jenis ini sudah pernah diterbitkan untuk hewan tersebut.');
        }

        return null;
    }

    private function replacementCandidate(Animal $animal, CertificateType $type): ?Certificate
    {
        if (! in_array($type->type_code, ['KELAHIRAN', 'KEMATIAN'], true)) {
            return null;
        }

        return Certificate::query()
            ->where('animal_id', $animal->id)
            ->where('certificate_type_id', $type->id)
            ->where('status', 'revoked')
            ->whereDoesntHave('replacementCertificate')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function baseCertificateData(Animal $animal, CertificateType $type, array $data, string $issueDate): array
    {
        return [
            'animal_id' => $animal->id,
            'certificate_type_id' => $type->id,
            'certificate_number' => $this->numbers->generate($type->type_code, $issueDate),
            'verification_token' => (string) Str::uuid(),
            'issue_date' => $issueDate,
            'issue_place' => TypeValue::nullableString($data['issue_place'] ?? null) ?? 'Ajibarang',
            'birth_event_id' => null,
            'valid_from' => $issueDate,
            'valid_until' => null,
            'death_date' => null,
            'death_time' => null,
            'cause_of_death' => null,
            'canonical_method' => 'canonical-json',
            'status' => 'active',
        ];
    }

    /**
     * @param  array<string, mixed>  $certificateData
     * @param  array<string, mixed>  $data
     * @return array<array-key, mixed>
     */
    private function applyTypeSpecificData(array $certificateData, Animal $animal, CertificateType $type, array $data, string $issueDate): array
    {
        switch ($type->type_code) {
            case 'KELAHIRAN':
                if ((bool) $animal->is_impor) {
                    return $this->error('Peringatan: Akta kelahiran hanya dapat diterbitkan untuk hewan yang lahir di kandang.');
                }

                $birthEvent = BirthEvent::query()
                    ->whereHas('offspringBirths', fn ($query) => $query->where('offspring_animal_id', $animal->id))
                    ->latest('birth_date')
                    ->first();

                if (! $birthEvent) {
                    return $this->error("Hewan dengan tag {$animal->tag_number} tidak memiliki kejadian kelahiran di peternakan.");
                }

                $certificateData['birth_event_id'] = $birthEvent->id;
                $certificateData['valid_from'] = $animal->birth_date?->toDateString() ?? $issueDate;
                break;

            case 'KEMATIAN':
                if ($animal->life_status !== 'dead') {
                    return $this->error('Peringatan: Akta kematian hanya dapat diterbitkan untuk kambing yang berstatus mati.');
                }

                foreach (['death_date' => 'Tanggal kematian', 'death_time' => 'Jam kematian', 'cause_of_death' => 'Penyebab kematian'] as $field => $label) {
                    if (empty($data[$field])) {
                        return $this->error("Peringatan: {$label} wajib diisi untuk sertifikat kematian.");
                    }
                }

                $deathDate = TypeValue::string($data['death_date']);
                if ($deathDate > now()->toDateString() || ($animal->birth_date && $deathDate < $animal->birth_date->toDateString())) {
                    return $this->error('Peringatan: Tanggal kematian harus berada antara tanggal lahir kambing dan hari ini.');
                }
                if ($animal->status_date && $deathDate !== $animal->status_date->toDateString()) {
                    return $this->error('Peringatan: Tanggal kematian pada akta harus sama dengan data kambing.');
                }

                $certificateData['death_date'] = $deathDate;
                $certificateData['death_time'] = TypeValue::nullableString($data['death_time'] ?? null);
                $certificateData['cause_of_death'] = TypeValue::string($data['cause_of_death']);
                break;

            case 'BIBIT_UNGGUL':
                $certificateData['valid_from'] = $issueDate;
                $certificateData['valid_until'] = Carbon::parse($issueDate)
                    ->addYears(TypeValue::int(config('bbh_certificates.superior_seed_validity_years', 9)))
                    ->toDateString();
                $certificateData['barcode_value'] = $this->publicVerificationUrl(TypeValue::string($certificateData['verification_token']));
                $certificateData['barcode_format'] = 'qrcode';
                break;

            default:
                return $this->error('Peringatan: Jenis sertifikat tidak dikenali.');
        }

        return ['ok' => true, 'data' => $certificateData];
    }

    /**
     * @return array<array-key, mixed>
     */
    private function success(string $message, mixed $data, int $status = 200): array
    {
        return ['ok' => true, 'status' => $status, 'message' => $message, 'data' => $data];
    }

    /**
     * @return array<array-key, mixed>
     */
    private function error(string $message, int $status = 422): array
    {
        return ['ok' => false, 'status' => $status, 'message' => $message];
    }

    private function publicVerificationUrl(string $token): string
    {
        $configuredBaseUrl = config('bbh.public_web_url');
        $baseUrl = rtrim(is_string($configuredBaseUrl) && $configuredBaseUrl !== '' ? $configuredBaseUrl : TypeValue::string(config('app.url', '')), '/');
        $locale = trim(TypeValue::string(config('bbh.public_default_locale', 'id-id')), '/');

        return "{$baseUrl}/{$locale}/verifikasi/".rawurlencode($token);
    }
}
