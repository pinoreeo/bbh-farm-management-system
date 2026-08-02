<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\CertificateVerificationLog;
use Illuminate\Support\Facades\DB;

class CertificateVerificationService
{
    public function verify(Certificate $certificate, string $verificationMethod = 'certificate_number'): array
    {
        $isAuthentic = true;
        $authReason = null;
        $usedFingerprint = null;

        $recomputedHash = hash('sha256', (string) $certificate->payload_snapshot);

        if (! hash_equals((string) $certificate->hash_sha256, $recomputedHash)) {
            $isAuthentic = false;
            $authReason = 'Payload hash mismatch.';
        }

        if ($isAuthentic) {
            $signature = $certificate->signature;

            if (! $signature || ! $signature->rsaKey) {
                $isAuthentic = false;
                $authReason = 'Signature or RSA key not found.';
            } else {
                $rsaKey = $signature->rsaKey;
                $usedFingerprint = $rsaKey->fingerprint_sha256;

                $publicKey = openssl_pkey_get_public($rsaKey->public_key_pem);

                if ($publicKey === false) {
                    $isAuthentic = false;
                    $authReason = 'Invalid public key.';
                } else {
                    $signatureBin = base64_decode((string) $signature->signature_base64, true);

                    if ($signatureBin === false) {
                        $isAuthentic = false;
                        $authReason = 'Invalid signature encoding.';
                    } else {
                        $verifyResult = openssl_verify(
                            (string) $certificate->hash_sha256,
                            $signatureBin,
                            $publicKey,
                            OPENSSL_ALGO_SHA256
                        );

                        if ($verifyResult !== 1) {
                            $isAuthentic = false;
                            $authReason = 'Signature verification failed.';
                        }
                    }
                }
            }
        }

        $isValid = true;
        $reason = null;

        if (! $isAuthentic) {
            $isValid = false;
            $reason = 'Certificate is not authentic.';
        }

        if ($isValid && $certificate->status !== 'active') {
            $isValid = false;
            $reason = 'Certificate status is not active.';
        }

        $signedValidUntil = $this->signedPayloadDate($certificate, 'valid_until')
            ?? $certificate->valid_until?->toDateString();

        if ($isValid && $signedValidUntil && \Carbon\Carbon::parse($signedValidUntil)->lt(now()->startOfDay())) {
            $isValid = false;
            $reason = 'Certificate validity period has ended.';
        }

        $this->logVerification($certificate, $isValid, $reason, $usedFingerprint, $authReason, $verificationMethod);

        return [
            'certificate_number' => $certificate->certificate_number,
            'verification_token' => $certificate->verification_token,
            'certificate_status' => $certificate->status,
            'issue_date' => $certificate->issue_date?->toDateString(),
            'issue_place' => $certificate->issue_place,
            'valid_from' => $certificate->valid_from?->toDateString(),
            'valid_until' => $certificate->valid_until?->toDateString(),
            'is_authentic' => $isAuthentic,
            'authenticity_reason' => $authReason,
            'is_valid' => $isValid,
            'reason' => $reason,
            'used_key_fingerprint' => $usedFingerprint,
            'verified_at' => now(),
            ...$this->publicCertificateData($certificate),
        ];
    }

    private function signedPayloadDate(Certificate $certificate, string $key): ?string
    {
        $payload = json_decode((string) $certificate->payload_snapshot, true);

        if (! is_array($payload) || empty($payload[$key]) || ! is_string($payload[$key])) {
            return null;
        }

        return $payload[$key];
    }

    public function publicCertificateData(Certificate $certificate): array
    {
        $animal = $certificate->animal;
        $latestWeight = $animal?->weightRecords()
            ->latest('record_date')
            ->latest('id')
            ->first();

        $healthTreatments = $animal?->healthTreatments()
            ->latest('treatment_date')
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn ($record) => [
                'type' => 'Perawatan',
                'date' => $record->treatment_date?->toDateString(),
                'title' => trim((string) $record->treatment_group) ?: 'Perawatan kesehatan',
                'description' => trim(implode(' - ', array_filter([
                    $record->product_name,
                    $record->dosage,
                    $record->administration_route,
                ]))) ?: null,
                'notes' => $record->notes,
            ]) ?? collect();

        $vaccinations = $animal?->vaccinations()
            ->latest('vaccination_date')
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn ($record) => [
                'type' => 'Vaksinasi',
                'date' => $record->vaccination_date?->toDateString(),
                'title' => $record->category_name ?: 'Vaksinasi',
                'description' => trim(implode(' - ', array_filter([
                    $record->product_name,
                    $record->dosage ? $record->dosage.' ml' : null,
                    $record->administration_route,
                ]))) ?: null,
                'notes' => $record->notes,
            ]) ?? collect();

        $healthHistory = $healthTreatments
            ->concat($vaccinations)
            ->sortByDesc(fn ($record) => $record['date'] ?? '')
            ->values()
            ->take(5)
            ->all();

        return [
            'certificate_type' => $certificate->certificateType?->type_code,
            'certificate_type_name' => $certificate->certificateType?->type_name,
            'certificate_type_template_version' => $certificate->certificateType?->template_version,
            'signature_info' => $certificate->signature ? [
                'signed_by' => $certificate->signature->signedByUser?->name ?? 'Pengelola Bumiku Bumimu Hijau Farm',
                'signed_at' => $certificate->signature->signed_at?->toIso8601String(),
                'signature_scheme' => $certificate->signature->signature_scheme,
                'key_identifier' => $certificate->signature->rsaKey?->key_identifier,
                'key_algorithm' => $certificate->signature->rsaKey?->algorithm,
                'key_length' => $certificate->signature->rsaKey?->key_length,
                'fingerprint_sha256' => $certificate->signature->rsaKey?->fingerprint_sha256,
                'key_status' => $certificate->signature->rsaKey?->key_status,
                'compromised_at' => $certificate->signature->rsaKey?->compromised_at?->toIso8601String(),
            ] : null,
            'official_pdf_signature_info' => $certificate->official_pdf_signature_base64 ? [
                'signed_by' => 'Bumiku Bumimu Hijau Farm',
                'signed_at' => $certificate->official_pdf_signed_at?->toIso8601String(),
                'signature_scheme' => $certificate->official_pdf_signature_scheme,
                'key_identifier' => $certificate->officialPdfRsaKey?->key_identifier,
                'key_algorithm' => $certificate->officialPdfRsaKey?->algorithm,
                'key_length' => $certificate->officialPdfRsaKey?->key_length,
                'fingerprint_sha256' => $certificate->officialPdfRsaKey?->fingerprint_sha256,
                'key_status' => $certificate->officialPdfRsaKey?->key_status,
                'compromised_at' => $certificate->officialPdfRsaKey?->compromised_at?->toIso8601String(),
            ] : null,
            'animal' => $animal ? [
                'id' => $animal->id,
                'tag_number' => $animal->tag_number,
                'photo_url' => $animal->photo_url,
                'breed_name' => $animal->breed?->breed_name,
                'sex' => $animal->sex,
                'male_role' => $animal->male_role,
                'generation' => $animal->generation,
                'birth_date' => $animal->birth_date?->toDateString(),
                'birth_place' => $animal->birth_place,
                'life_status' => $animal->life_status,
                'reproductive_status' => $animal->reproductive_status,
                'current_pen' => $animal->currentPen ? [
                    'pen_code' => $animal->currentPen->pen_code,
                    'colony_code' => $animal->currentPen->colony_code,
                    'colony_name' => $animal->currentPen->colony_name,
                    'colony_type' => $animal->currentPen->colony_type,
                    'colony_phase' => $animal->currentPen->colony_phase,
                    'location' => $animal->currentPen->location,
                ] : null,
                'umur' => $animal->umur,
                'kategori_umur' => $animal->kategori_umur,
                'latest_weight' => $latestWeight ? [
                    'weight_kg' => $latestWeight->weight_kg,
                    'record_date' => $latestWeight->record_date?->toDateString(),
                ] : null,
                'health_history' => $healthHistory,
            ] : null,
        ];
    }

    private function logVerification(
        Certificate $certificate,
        bool $isValid,
        ?string $reason,
        ?string $usedFingerprint,
        ?string $authReason,
        string $verificationMethod
    ): void {
        DB::transaction(function () use ($certificate, $isValid, $reason, $usedFingerprint, $authReason, $verificationMethod) {
            $failureReason = $reason;

            if ($authReason !== null) {
                $failureReason = $failureReason
                    ? $failureReason.' | authenticity: '.$authReason
                    : 'authenticity: '.$authReason;
            }

            CertificateVerificationLog::query()->create([
                'certificate_id' => $certificate->id,
                'verification_method' => $verificationMethod,
                'verification_time' => now(),
                'is_valid' => $isValid ? 1 : 0,
                'certificate_status_at_verification' => $certificate->status,
                'failure_reason' => $failureReason,
                'used_key_fingerprint' => $usedFingerprint,
                'used_barcode_value' => $certificate->barcode_value,
                'created_at' => now(),
            ]);
        });
    }
}
