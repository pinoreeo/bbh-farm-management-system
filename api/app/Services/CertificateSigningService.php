<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\CertificateSignature;
use App\Models\RsaKey;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

class CertificateSigningService
{
    public function sign(Certificate $certificate, bool $replaceActive = false): CertificateSignature
    {
        if ($certificate->status !== 'active') {
            throw new \RuntimeException('Only active certificates can be signed.');
        }

        $recomputedHash = hash('sha256', (string) $certificate->payload_snapshot);
        if ($recomputedHash !== (string) $certificate->hash_sha256) {
            throw new \RuntimeException('Payload hash mismatch. Refuse to sign.');
        }

        return DB::transaction(function () use ($certificate, $replaceActive) {
            Certificate::query()
                ->whereKey($certificate->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $replaceActive) {
                $existingSignature = CertificateSignature::query()
                    ->where('certificate_id', $certificate->id)
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->first();

                if ($existingSignature) {
                    return $existingSignature;
                }
            }

            $signedHash = $this->signHash((string) $certificate->hash_sha256);
            $rsaKey = $signedHash['rsa_key'];
            $signedByUserId = $signedHash['signed_by_user_id'];
            $signatureB64 = $signedHash['signature_base64'];

            if ($replaceActive) {
                CertificateSignature::query()
                    ->where('certificate_id', $certificate->id)
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->update(['status' => 'superseded']);
            }

            return CertificateSignature::query()->create([
                'certificate_id' => $certificate->id,
                'rsa_key_id' => $rsaKey->id,
                'signed_by_user_id' => $signedByUserId,
                'signature_scheme' => config('bbh_signing.signature_scheme', 'RSA-SHA256'),
                'signature_base64' => $signatureB64,
                'signed_at' => now(),
                'status' => 'active',
            ]);
        });
    }

    public function signHash(string $hash): array
    {
        $hash = trim($hash);
        if ($hash === '') {
            throw new \RuntimeException('Hash to sign is empty.');
        }

        $user = auth()->user();
        if (! $user) {
            throw new \RuntimeException('Authenticated user is required to sign.');
        }

        [$rsaKey, $pkey] = $this->activePrivateKey((int) $user->id);

        $signatureBin = '';
        $ok = openssl_sign($hash, $signatureBin, $pkey, OPENSSL_ALGO_SHA256);

        if ($ok !== true || $signatureBin === '') {
            throw new \RuntimeException('openssl_sign failed.');
        }

        $rsaKey->forceFill(['last_used_at' => now()])->save();

        return [
            'rsa_key' => $rsaKey,
            'signed_by_user_id' => (int) $user->id,
            'signature_base64' => base64_encode($signatureBin),
        ];
    }

    public function verifyHash(string $hash, string $signatureBase64, string $publicKeyPem): bool
    {
        $publicKey = openssl_pkey_get_public($publicKeyPem);
        if ($publicKey === false) {
            return false;
        }

        $signatureBin = base64_decode($signatureBase64, true);
        if ($signatureBin === false) {
            return false;
        }

        $result = openssl_verify($hash, $signatureBin, $publicKey, OPENSSL_ALGO_SHA256);

        return $result === 1;
    }

    private function activePrivateKey(int $userId): array
    {
        $rsaKey = RsaKey::query()
            ->where('user_id', $userId)
            ->where('is_active', 1)
            ->where('key_status', 'active')
            ->whereNotNull('private_key_path')
            ->orderByDesc('id')
            ->first();

        if (! $rsaKey) {
            throw new \RuntimeException('No active RSA key found for authenticated user.');
        }

        if (empty($rsaKey->public_key_pem)) {
            throw new \RuntimeException('Active RSA key does not have a public key.');
        }

        $privateKeyPath = $rsaKey->private_key_path ?: config('bbh_signing.private_key_path');
        if (! $privateKeyPath || ! is_string($privateKeyPath)) {
            throw new \RuntimeException('Private key path is not configured.');
        }

        $privateKeyPath = $this->resolvePrivateKeyPath($privateKeyPath);

        if (! is_file($privateKeyPath)) {
            throw new \RuntimeException("Private key file not found: {$privateKeyPath}");
        }

        $privateKeyPem = file_get_contents($privateKeyPath);
        if ($privateKeyPem === false || trim($privateKeyPem) === '') {
            throw new \RuntimeException('Failed to read private key file.');
        }

        $privateKeyPem = $this->decryptPrivateKeyIfNeeded($privateKeyPem);

        $passphrase = config('bbh_signing.private_key_passphrase');
        $pkey = openssl_pkey_get_private($privateKeyPem, $passphrase ?? '');

        if ($pkey === false) {
            throw new \RuntimeException('Invalid private key or passphrase.');
        }

        $privateKeyDetails = openssl_pkey_get_details($pkey);
        if (! is_array($privateKeyDetails) || empty($privateKeyDetails['key'])) {
            throw new \RuntimeException('Unable to read public key from configured private key.');
        }

        if ($this->normalizePem($privateKeyDetails['key']) !== $this->normalizePem($rsaKey->public_key_pem)) {
            throw new \RuntimeException('Active RSA public key does not match the configured private key.');
        }

        return [$rsaKey, $pkey];
    }

    private function normalizePem(string $pem): string
    {
        return preg_replace('/\s+/', '', trim($pem)) ?? '';
    }

    private function resolvePrivateKeyPath(string $privateKeyPath): string
    {
        if (is_file($privateKeyPath)) {
            return $privateKeyPath;
        }

        $fallbackPath = storage_path('app/keys/rsa/'.basename($privateKeyPath));

        return is_file($fallbackPath) ? $fallbackPath : $privateKeyPath;
    }

    private function decryptPrivateKeyIfNeeded(string $privateKeyContents): string
    {
        $trimmed = trim($privateKeyContents);
        if (str_contains($trimmed, '-----BEGIN')) {
            return $privateKeyContents;
        }

        try {
            return Crypt::decryptString($trimmed);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Private key file is encrypted but cannot be decrypted with the application key.');
        }
    }
}
