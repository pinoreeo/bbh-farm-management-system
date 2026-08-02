<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\BirthEvent;
use App\Models\Certificate;
use App\Models\CertificateType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CertificateIssuanceService
{
    public function __construct(
        private readonly CertificateNumberService $numbers,
        private readonly CertificatePayloadSnapshotService $snapshots,
        private readonly CertificateSigningService $signing
    ) {}

    public function issue(array $data, bool $autoSign = true): array
    {
        try {
            return DB::transaction(function () use ($data, $autoSign) {
                $animal = Animal::query()->with(['breed'])->findOrFail($data['animal_id']);
                $type = CertificateType::query()
                    ->whereKey($data['certificate_type_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $guard = $this->validateIssueContext($animal, $type, $data);
                if ($guard !== null) {
                    return $guard;
                }

                $issueDate = now()->toDateString();
                $certificateData = $this->baseCertificateData($animal, $type, $data, $issueDate);

                $typeResult = $this->applyTypeSpecificData($certificateData, $animal, $type, $data, $issueDate);
                if (($typeResult['ok'] ?? true) === false) {
                    return $typeResult;
                }

                $certificateData = $typeResult['data'];
                $payloadSnapshot = $this->snapshots->build($certificateData, $animal);
                $certificateData['payload_snapshot'] = $payloadSnapshot;
                $certificateData['hash_sha256'] = hash('sha256', $payloadSnapshot);

                $certificate = Certificate::create($certificateData);

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

    private function validateIssueContext(Animal $animal, CertificateType $type, array $data): ?array
    {
        if (! $type->is_active) {
            return $this->error('Peringatan: Jenis sertifikat ini sedang tidak aktif.');
        }

        $existingTypeCertificate = Certificate::query()
            ->where('animal_id', $animal->id)
            ->where('certificate_type_id', $type->id)
            ->exists();

        if (in_array($type->type_code, ['KELAHIRAN', 'KEMATIAN'], true) && $existingTypeCertificate) {
            return $this->error('Peringatan: Sertifikat jenis ini sudah pernah diterbitkan untuk hewan tersebut.');
        }

        return null;
    }

    private function baseCertificateData(Animal $animal, CertificateType $type, array $data, string $issueDate): array
    {
        return [
            'animal_id' => $animal->id,
            'certificate_type_id' => $type->id,
            'certificate_number' => $this->numbers->generate($type->type_code, $issueDate),
            'verification_token' => (string) Str::uuid(),
            'issue_date' => $issueDate,
            'issue_place' => $data['issue_place'] ?? 'Ajibarang',
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
                $certificateData['valid_from'] = optional($animal->birth_date)->toDateString() ?? $issueDate;
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

                $certificateData['death_date'] = $data['death_date'];
                $certificateData['death_time'] = $data['death_time'] ?? null;
                $certificateData['cause_of_death'] = $data['cause_of_death'];
                break;

            case 'BIBIT_UNGGUL':
                $certificateData['valid_from'] = $issueDate;
                $certificateData['valid_until'] = Carbon::parse($issueDate)
                    ->addYears((int) config('bbh_certificates.superior_seed_validity_years', 9))
                    ->toDateString();
                $certificateData['barcode_value'] = $this->publicVerificationUrl($certificateData['verification_token']);
                $certificateData['barcode_format'] = 'qrcode';
                break;

            default:
                return $this->error('Peringatan: Jenis sertifikat tidak dikenali.');
        }

        return ['ok' => true, 'data' => $certificateData];
    }

    private function success(string $message, mixed $data, int $status = 200): array
    {
        return ['ok' => true, 'status' => $status, 'message' => $message, 'data' => $data];
    }

    private function error(string $message, int $status = 422): array
    {
        return ['ok' => false, 'status' => $status, 'message' => $message];
    }

    private function publicVerificationUrl(string $token): string
    {
        $baseUrl = rtrim((string) (config('bbh.public_web_url') ?: config('app.url')), '/');
        $locale = trim((string) config('bbh.public_default_locale', 'id-id'), '/');

        return "{$baseUrl}/{$locale}/verifikasi/{$token}";
    }
}
