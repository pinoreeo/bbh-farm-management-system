<?php

namespace Tests\Feature\BranchCoverage;

use App\Models\CertificateSignature;
use App\Services\CertificateSigningService;
use Tests\Feature\Support\ApiTestCase;

class PublicCertificateVerificationBranchCoverageTest extends ApiTestCase
{
    public function test_public_verification_covers_number_token_public_show_and_status_branches(): void
    {
        $this->actingAsAdmin();

        $certificate = $this->issueCertificate('BIBIT_UNGGUL');

        $this->postJson('/api/v1/public/certificates/verify', [
            'certificate_number' => $certificate->certificate_number,
        ])->assertOk()
            ->assertJsonPath('is_valid', true)
            ->assertJsonMissingPath('animal.photo_url')
            ->assertJsonMissingPath('animal.reproductive_status')
            ->assertJsonMissingPath('animal.current_pen')
            ->assertJsonMissingPath('animal.latest_weight')
            ->assertJsonMissingPath('animal.health_history');

        $this->postJson('/api/v1/public/certificates/verify', [
            'certificate_number' => 'BBH-NOT-FOUND',
        ])->assertNotFound()
            ->assertJsonPath('is_valid', false);

        $this->getJson('/api/v1/public/certificates/verify/'.$certificate->verification_token)
            ->assertOk()
            ->assertJsonPath('is_valid', true);

        $this->getJson('/api/v1/public/certificates/verify/token-tidak-ada')
            ->assertNotFound()
            ->assertJsonPath('is_valid', false);

        $this->getJson('/api/v1/public/certificates/'.$certificate->certificate_number)
            ->assertOk()
            ->assertJsonPath('certificate_number', $certificate->certificate_number);

        $this->getJson('/api/v1/public/certificates/BBH-NOT-FOUND')
            ->assertNotFound();

        $this->postJson('/api/v1/certificates/'.$certificate->id.'/revoke', [
            'reason' => 'Tidak berlaku',
        ])->assertOk();

        $this->postJson('/api/v1/public/certificates/verify', [
            'certificate_number' => $certificate->certificate_number,
        ])->assertOk()
            ->assertJsonPath('is_valid', false)
            ->assertJsonPath('reason', 'Certificate status is not active.');

        $expired = $this->issueCertificate('BIBIT_UNGGUL');
        $expiredPayload = json_decode((string) $expired->payload_snapshot, true);
        $expiredPayload['valid_until'] = now()->subDay()->toDateString();
        ksort($expiredPayload);

        $expiredSnapshot = json_encode($expiredPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $expiredHash = hash('sha256', (string) $expiredSnapshot);
        $expiredSignedHash = app(CertificateSigningService::class)->signHash($expiredHash);

        $expired->forceFill([
            'valid_until' => now()->subDay()->toDateString(),
            'payload_snapshot' => $expiredSnapshot,
            'hash_sha256' => $expiredHash,
        ])->save();
        $expired->signature->forceFill([
            'rsa_key_id' => $expiredSignedHash['rsa_key']->id,
            'signature_base64' => $expiredSignedHash['signature_base64'],
        ])->save();

        $this->postJson('/api/v1/public/certificates/verify', [
            'certificate_number' => $expired->certificate_number,
        ])->assertOk()
            ->assertJsonPath('is_authentic', true)
            ->assertJsonPath('is_valid', false)
            ->assertJsonPath('reason', 'Certificate validity period has ended.');
    }

    public function test_rsa_sha256_verification_covers_authenticity_failure_branches(): void
    {
        $this->actingAsAdmin();

        $hashMismatch = $this->issueCertificate('BIBIT_UNGGUL');
        $hashMismatch->forceFill(['payload_snapshot' => '{"tampered":true}'])->save();

        $this->postJson('/api/v1/public/certificates/verify', [
            'certificate_number' => $hashMismatch->certificate_number,
        ])->assertOk()
            ->assertJsonPath('is_authentic', false)
            ->assertJsonPath('authenticity_reason', 'Payload hash mismatch.');

        $missingSignature = $this->issueCertificate('BIBIT_UNGGUL');
        CertificateSignature::query()
            ->where('certificate_id', $missingSignature->id)
            ->update(['status' => 'inactive']);

        $this->postJson('/api/v1/public/certificates/verify', [
            'certificate_number' => $missingSignature->certificate_number,
        ])->assertOk()
            ->assertJsonPath('is_authentic', false)
            ->assertJsonPath('authenticity_reason', 'Signature or RSA key not found.');

        $invalidPublicKey = $this->issueCertificate('BIBIT_UNGGUL');
        $originalPublicKey = $invalidPublicKey->signature->rsaKey->public_key_pem;
        $invalidPublicKey->signature->rsaKey->forceFill(['public_key_pem' => 'not-a-public-key'])->save();

        $this->postJson('/api/v1/public/certificates/verify', [
            'certificate_number' => $invalidPublicKey->certificate_number,
        ])->assertOk()
            ->assertJsonPath('is_authentic', false)
            ->assertJsonPath('authenticity_reason', 'Invalid public key.');

        $invalidPublicKey->signature->rsaKey->forceFill(['public_key_pem' => $originalPublicKey])->save();

        $invalidBase64 = $this->issueCertificate('BIBIT_UNGGUL');
        $invalidBase64->signature->forceFill(['signature_base64' => 'not valid base64 %%'])->save();

        $this->postJson('/api/v1/public/certificates/verify', [
            'certificate_number' => $invalidBase64->certificate_number,
        ])->assertOk()
            ->assertJsonPath('is_authentic', false)
            ->assertJsonPath('authenticity_reason', 'Invalid signature encoding.');

        $signatureMismatch = $this->issueCertificate('BIBIT_UNGGUL');
        $signatureMismatch->signature->forceFill(['signature_base64' => base64_encode('wrong-signature')])->save();

        $this->postJson('/api/v1/public/certificates/verify', [
            'certificate_number' => $signatureMismatch->certificate_number,
        ])->assertOk()
            ->assertJsonPath('is_authentic', false)
            ->assertJsonPath('authenticity_reason', 'Signature verification failed.');
    }
}
