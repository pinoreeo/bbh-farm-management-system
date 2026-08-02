<?php

namespace Tests\Feature\BranchCoverage;

use App\Models\Certificate;
use App\Models\PostnatalCareRecord;
use App\Models\RsaKey;
use App\Services\CertificatePdfIntegrityService;
use App\Services\CertificateSigningService;
use App\Services\CertificateViewDataService;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Support\ApiTestCase;

class CertificateExportBranchCoverageTest extends ApiTestCase
{
    public function test_certificate_export_covers_qr_preview_print_pdf_and_lifecycle_guard_branches(): void
    {
        $this->actingAsAdmin();

        $certificate = $this->issueCertificate('BIBIT_UNGGUL');

        $this->getJson('/api/v1/certificates?status=active&certificate_type_id='.$certificate->certificate_type_id.'&animal_id='.$certificate->animal_id.'&certificate_number='.$certificate->certificate_number)
            ->assertOk()
            ->assertJsonFragment(['certificate_number' => $certificate->certificate_number]);

        $this->getJson('/api/v1/certificates/'.$certificate->id)
            ->assertOk()
            ->assertJsonPath('certificate_number', $certificate->certificate_number);

        $this->putJson('/api/v1/certificates/'.$certificate->id, [
            'issue_place' => 'Purwokerto',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Peringatan: Sertifikat yang sudah diterbitkan tidak dapat diedit. Cabut sertifikat lama dan terbitkan sertifikat baru jika data perlu diperbaiki.');

        $this->postJson('/api/v1/certificates/'.$certificate->id.'/sign')
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->getJson('/api/v1/certificates/'.$certificate->id.'/qr')
            ->assertOk()
            ->assertJsonStructure(['certificate_id', 'qr_base64', 'url']);

        $this->get('/api/v1/certificates/'.$certificate->id.'/print')
            ->assertOk()
            ->assertSee('Sertifikat Bibit Unggul');

        $pdfBytes = "%PDF-1.4\nOfficial certificate\n%%EOF";
        $pdfHash = hash('sha256', $pdfBytes);
        $signedHash = app(CertificateSigningService::class)->signHash($pdfHash);
        $officialPdfPath = 'certificates/official/testing/export-certificate.pdf';

        Storage::disk('local')->put($officialPdfPath, $pdfBytes);

        $certificate->forceFill([
            'official_pdf_path' => $officialPdfPath,
            'official_pdf_hash_sha256' => $pdfHash,
            'official_pdf_signature_base64' => $signedHash['signature_base64'],
            'official_pdf_signature_scheme' => config('bbh_signing.signature_scheme', 'RSA-SHA256'),
            'official_pdf_rsa_key_id' => $signedHash['rsa_key']->id,
            'official_pdf_signed_at' => now(),
            'official_pdf_generated_at' => now()->addDay(),
        ])->save();

        $this->get('/api/v1/certificates/'.$certificate->id.'/pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $certificate->forceFill(['barcode_value' => null])->save();

        $this->getJson('/api/v1/certificates/'.$certificate->id.'/qr')
            ->assertOk()
            ->assertJsonPath('url', $certificate->fresh()->public_verification_url);

        $birth = $this->issueCertificate('KELAHIRAN');

        $this->getJson('/api/v1/certificates/'.$birth->id.'/qr')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Peringatan: QR code hanya tersedia untuk Sertifikat Bibit Unggul.');

        $this->getJson('/api/v1/certificates/'.$birth->id.'/preview')
            ->assertOk();

        $this->get('/api/v1/certificates/'.$birth->id.'/print')
            ->assertOk()
            ->assertSee('Akta Kelahiran Ternak');

        $this->get('/api/v1/certificates/'.$certificate->id.'/print/authenticity')
            ->assertOk();

        $this->get('/api/v1/certificates/'.$birth->id.'/print/birth')
            ->assertOk();

        $this->getJson('/api/v1/certificates/'.$birth->id.'/print/death')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Peringatan: Template cetak tidak sesuai dengan jenis sertifikat.');

        $death = $this->issueCertificate('KEMATIAN');

        $this->get('/api/v1/certificates/'.$death->id.'/print')
            ->assertOk()
            ->assertSee('Akta Kematian Ternak');

        $this->get('/api/v1/certificates/'.$death->id.'/print/death')
            ->assertOk();

        $birth->forceFill(['birth_event_id' => null])->save();

        $this->getJson('/api/v1/certificates/'.$birth->id.'/pdf')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Gagal: Gagal membuat file PDF sertifikat. Periksa kelengkapan data dan konfigurasi dokumen.');

        $this->postJson('/api/v1/certificates/'.$certificate->id.'/revoke', [
            'reason' => 'Data tidak valid',
        ])->assertOk()
            ->assertJsonPath('data.status', 'revoked');

        $this->postJson('/api/v1/certificates/'.$certificate->id.'/revoke', [
            'reason' => 'Sudah revoked',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Peringatan: Sertifikat ini sudah dicabut.');

        $this->deleteJson('/api/v1/certificates/'.$certificate->id)
            ->assertMethodNotAllowed();

        $this->postJson('/api/v1/certificates/'.$certificate->id.'/unrevoke')
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->postJson('/api/v1/certificates/'.$certificate->id.'/unrevoke')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Peringatan: Sertifikat ini belum dalam status dicabut.');

        $expired = $this->issueCertificate('BIBIT_UNGGUL');
        $expired->forceFill(['status' => 'expired'])->save();

        $this->postJson('/api/v1/certificates/'.$expired->id.'/revoke', [
            'reason' => 'Expired certificate',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Peringatan: Masa berlaku sertifikat telah habis (Kedaluwarsa).');

        $this->postJson('/api/v1/certificates/'.$expired->id.'/sign')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Peringatan: Hanya sertifikat aktif yang dapat ditandatangani.');

        $unsigned = $this->issueCertificate('BIBIT_UNGGUL', null, ['auto_sign' => false]);
        RsaKey::query()->update(['is_active' => 0]);

        $this->postJson('/api/v1/certificates/'.$unsigned->id.'/sign')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Gagal: Sertifikat gagal ditandatangani. Pastikan RSA Key aktif telah dikonfigurasi dengan benar.');

        Certificate::query()->whereKey($expired->id)->update(['status' => 'expired']);

        $this->getJson('/api/v1/certificates?include_inactive=1&status=expired&certificate_number='.$expired->certificate_number)
            ->assertOk()
            ->assertJsonFragment(['certificate_number' => $expired->certificate_number]);
    }

    public function test_certificate_view_data_covers_formatting_postnatal_and_fallback_branches(): void
    {
        $this->actingAsAdmin();

        $viewData = app(CertificateViewDataService::class);

        $workflow = $this->createBirthWorkflow();

        PostnatalCareRecord::query()->create([
            'offspring_birth_id' => $workflow['offspringBirth']->id,
            'birth_event_id' => $workflow['birthEvent']->id,
            'target_animal_id' => $workflow['offspring']->id,
            'care_date' => '2026-05-17',
            'administration_method' => 'Oral',
            'volume_ml' => 15.50,
            'navel_iodine_status' => 'Sudah',
            'vitamin_ade_ml' => 1.00,
            'vitamin_b_complex_ml' => 0.75,
            'intracin_ml' => null,
        ]);

        $birthCertificate = $this->issueCertificate('KELAHIRAN', $workflow['offspring'], [
            'auto_sign' => false,
        ]);

        $birthData = $viewData->build($birthCertificate->fresh()->load([
            'animal.breed',
            'certificateType',
            'birthEvent.dam.breed',
            'birthEvent.sire.breed',
            'birthEvent.offspringBirths',
            'birthEvent.postnatalCareRecords',
        ]));

        $this->assertSame('Betina', $birthData['animal_sex']);
        $this->assertSame('Hidup', $birthData['animal_life_status']);
        $this->assertSame('17-05-2026', $birthData['birth_event_date']);
        $this->assertSame('08.30 WIB', $birthData['birth_event_time']);
        $this->assertSame('3.2', $birthData['birth_weight_kg']);
        $this->assertSame('A', $birthData['offspring_grade']);
        $this->assertCount(6, $birthData['postnatal_cares']);
        $this->assertSame('15.5 ml', $birthData['postnatal_cares'][1]['dose']);
        $this->assertSame('-', $birthData['postnatal_cares'][5]['dose']);

        $deathPen = $this->createPen([
            'pen_code' => 'KDG-DEATH',
            'colony_code' => 'D1',
            'colony_name' => 'Koloni Afkir D1',
        ]);
        $deadMale = $this->createAnimal([
            'sex' => 'male',
            'male_role' => 'APB',
            'life_status' => 'dead',
            'generation' => 'Pure Breed',
            'birth_place' => 'Kandang BBH',
            'current_pen_id' => $deathPen->id,
            'reproductive_status' => 'afkir',
        ]);

        $deathCertificate = $this->issueCertificate('KEMATIAN', $deadMale, [
            'auto_sign' => false,
            'death_date' => '2026-05-17',
            'death_time' => '10:00:00',
            'cause_of_death' => 'Sakit',
        ]);

        $deathData = $viewData->build($deathCertificate->fresh()->load([
            'animal.breed',
            'certificateType',
        ]));

        $this->assertSame('Jantan', $deathData['animal_sex']);
        $this->assertSame('Mati', $deathData['animal_life_status']);
        $this->assertStringEndsWith(' PB', $deathData['animal_generation_breed']);
        $this->assertSame('KDG-DEATH - D1 - Koloni Afkir D1', $deathData['animal_current_pen']);
        $this->assertSame('Afkir', $deathData['animal_reproductive_status']);
        $this->assertSame('APB', $deathData['animal_male_role']);
        $this->assertSame('Kandang BBH', $deathData['animal_birth_place']);
        $this->assertSame('17-05-2026', $deathData['death_date']);
        $this->assertSame('10.00 WIB', $deathData['death_time']);
        $this->assertSame('Sakit', $deathData['cause_of_death']);

        $fallbackCertificate = new Certificate([
            'certificate_number' => null,
            'issue_date' => null,
            'issue_place' => null,
            'valid_from' => null,
            'valid_until' => null,
            'barcode_value' => null,
            'status' => null,
        ]);
        $fallbackCertificate->setRelation('animal', null);
        $fallbackCertificate->setRelation('certificateType', null);
        $fallbackCertificate->setRelation('birthEvent', null);

        $fallbackData = $viewData->build($fallbackCertificate);

        $this->assertSame('-', $fallbackData['animal_sex']);
        $this->assertSame('-', $fallbackData['animal_life_status']);
        $this->assertSame('-', $fallbackData['animal_birth_date']);
        $this->assertSame('-', $fallbackData['issue_day_date']);
        $this->assertSame('-', $fallbackData['valid_until']);
        $this->assertSame('-', $fallbackData['birth_event_time']);
        $this->assertSame('-', $fallbackData['verification_url']);
        $this->assertCount(0, $fallbackData['postnatal_cares']);
    }

    public function test_pdf_integrity_service_covers_default_type_metadata_and_filename_fallback_branches(): void
    {
        $this->actingAsAdmin();

        $pdfIntegrity = app(CertificatePdfIntegrityService::class);
        $certificate = new Certificate([
            'certificate_number' => '',
            'issue_date' => null,
            'official_pdf_path' => null,
            'official_pdf_hash_sha256' => null,
            'official_pdf_signature_base64' => null,
            'official_pdf_rsa_key_id' => null,
        ]);
        $certificate->setRelation('certificateType', null);
        $certificate->setRelation('officialPdfRsaKey', null);

        $this->assertNull($pdfIntegrity->bladeViewFor($certificate));
        $this->assertSame('a4', $pdfIntegrity->paperFor($certificate));
        $this->assertSame(
            'Sertifikat_Tanpa-Nomor_Tidak-Tersedia.pdf',
            $pdfIntegrity->downloadFilenameFor($certificate, ['issue_date' => ''])
        );
        $this->assertFalse($pdfIntegrity->verifyOfficialPdfSignature($certificate));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Official PDF has not been generated.');

        $pdfIntegrity->officialPdfAbsolutePath($certificate);
    }
}
