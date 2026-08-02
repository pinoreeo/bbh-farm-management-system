<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\CertificatePdfIntegrityService;
use App\Services\CertificateSigningService;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Support\ApiTestCase;

class CertificateRsaSha256ServiceTest extends ApiTestCase
{
    public function test_rsa_sha256_signing_service_covers_success_and_failure_branches(): void
    {
        $this->actingAsAdmin();

        $service = app(CertificateSigningService::class);

        $hash = hash('sha256', 'branch-unit-rsa-sha256');
        $signed = $service->signHash($hash);

        $this->assertTrue($service->verifyHash(
            $hash,
            $signed['signature_base64'],
            $signed['rsa_key']->public_key_pem
        ));

        $this->assertFalse($service->verifyHash(
            hash('sha256', 'different-hash'),
            $signed['signature_base64'],
            $signed['rsa_key']->public_key_pem
        ));

        $this->assertFalse($service->verifyHash($hash, 'not valid base64 %%', $signed['rsa_key']->public_key_pem));
        $this->assertFalse($service->verifyHash($hash, $signed['signature_base64'], 'not-a-public-key'));
        $this->assertNotNull($signed['rsa_key']->fresh()->last_used_at);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Hash to sign is empty.');
        $service->signHash('');
    }

    public function test_regular_admin_signs_with_own_active_rsa_key(): void
    {
        $regularAdmin = User::query()->create([
            'name' => 'Operator Sertifikat',
            'first_name' => 'Operator',
            'last_name' => 'Sertifikat',
            'email' => 'operator-sertifikat@example.test',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        Sanctum::actingAs($regularAdmin);

        $this->postJson('/api/v1/rsa-keys/generate', [
            'key_identifier' => 'REGULAR-SIGNING-RSA-'.uniqid(),
            'key_length' => 2048,
        ])->assertCreated();

        $service = app(CertificateSigningService::class);
        $signed = $service->signHash(hash('sha256', 'regular-admin-own-key'));

        $this->assertSame($regularAdmin->id, $signed['rsa_key']->user_id);
        $this->assertSame($regularAdmin->id, $signed['signed_by_user_id']);
    }

    public function test_pdf_integrity_service_unit_branches_do_not_require_browser_rendering(): void
    {
        $this->actingAsAdmin();

        $service = app(CertificatePdfIntegrityService::class);
        $bibit = $this->issueCertificate('BIBIT_UNGGUL');
        $birth = $this->issueCertificate('KELAHIRAN');
        $death = $this->issueCertificate('KEMATIAN');

        $this->assertSame('certificates.pdf_sertifikat_hewan', $service->bladeViewFor($bibit));
        $this->assertSame('certificates.pdf_akte_kelahiran', $service->bladeViewFor($birth));
        $this->assertSame('certificates.pdf_akte_kematian', $service->bladeViewFor($death));

        $this->assertSame([0, 0, 487.5, 675], $service->paperFor($bibit));
        $this->assertSame([0, 0, 525, 780], $service->paperFor($birth));
        $this->assertSame([0, 0, 525, 780], $service->paperFor($death));

        $this->assertStringStartsWith('Sertifikat-Bibit-Unggul_', $service->downloadFilenameFor($bibit, [
            'issue_date' => '17/05/2026',
        ]));
        $this->assertFalse($service->verifyOfficialPdfSignature($bibit));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Official PDF has not been generated.');
        $service->officialPdfAbsolutePath($bibit);
    }
}
