<?php

namespace Tests\Feature\BranchCoverage;

use App\Services\CertificateSigningService;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Support\ApiTestCase;

class OfficialPdfVerificationBranchCoverageTest extends ApiTestCase
{
    public function test_pdf_verification_covers_lookup_integrity_signature_and_status_branches(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->actingAsAdmin();

        $this->postJson('/api/v1/public/certificates/verify-pdf', [])
            ->assertUnprocessable();

        $this->postJson('/api/v1/public/certificates/verify-pdf', [
            'pdf' => UploadedFile::fake()->create('not-pdf.txt', 1, 'text/plain'),
        ])->assertUnprocessable();

        $this->postJson('/api/v1/public/certificates/verify-pdf', [
            'pdf' => UploadedFile::fake()->createWithContent('unknown.pdf', "%PDF-1.4\nunknown\n%%EOF"),
        ])->assertOk()
            ->assertJsonPath('is_valid', false)
            ->assertJsonPath('pdf_file_type_valid', true)
            ->assertJsonPath('pdf_mismatch_type', 'non_bbh_pdf')
            ->assertJsonPath('reason', 'Dokumen PDF yang diunggah tidak tercatat sebagai sertifikat resmi Bumiku Bumimu Hijau Farm.');

        $this->postJson('/api/v1/public/certificates/verify-pdf', [
            'pdf' => UploadedFile::fake()->createWithContent('broken.pdf', "%PDF-1.4\nmissing eof"),
        ])->assertUnprocessable()
            ->assertJsonPath('pdf_file_type_valid', false)
            ->assertJsonPath('pdf_mismatch_type', 'invalid_pdf_structure')
            ->assertJsonPath('reason', 'Gagal: Dokumen yang diunggah tidak menggunakan format PDF yang valid.')
            ->assertJsonPath('is_valid', false);

        $certificate = $this->issueCertificate('BIBIT_UNGGUL');
        $pdfBytes = "%PDF-1.4\nOfficial certificate\n%%EOF";
        $pdfHash = hash('sha256', $pdfBytes);
        $signedHash = app(CertificateSigningService::class)->signHash($pdfHash);
        $officialPdfPath = 'certificates/official/testing/certificate.pdf';

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

        $this->postJson('/api/v1/public/certificates/verify-pdf', [
            'pdf' => UploadedFile::fake()->createWithContent('certificate.pdf', $pdfBytes),
            'certificate_number' => $certificate->certificate_number,
        ])->assertOk()
            ->assertJsonPath('pdf_hash_matches', true)
            ->assertJsonPath('pdf_signature_valid', true)
            ->assertJsonPath('is_pdf_integrity_valid', true)
            ->assertJsonPath('is_certificate_data_valid', true)
            ->assertJsonPath('is_valid', true);

        $this->postJson('/api/v1/public/certificates/verify-pdf', [
            'pdf' => UploadedFile::fake()->createWithContent('certificate.pdf', $pdfBytes),
        ])->assertOk()
            ->assertJsonPath('certificate_number', $certificate->certificate_number)
            ->assertJsonPath('is_valid', true);

        $otherCertificate = $this->issueCertificate('BIBIT_UNGGUL');

        $this->postJson('/api/v1/public/certificates/verify-pdf', [
            'pdf' => UploadedFile::fake()->createWithContent('certificate.pdf', $pdfBytes),
            'certificate_number' => $otherCertificate->certificate_number,
        ])->assertOk()
            ->assertJsonPath('pdf_hash_matches', false)
            ->assertJsonPath('pdf_mismatch_type', 'wrong_certificate_number')
            ->assertJsonPath('matched_certificate_number', $certificate->certificate_number)
            ->assertJsonPath('reason', 'Dokumen PDF yang diunggah tercatat sebagai sertifikat resmi Bumiku Bumimu Hijau Farm, tetapi tidak sesuai dengan nomor sertifikat yang dimasukkan.')
            ->assertJsonPath('is_valid', false);

        $this->postJson('/api/v1/public/certificates/verify-pdf', [
            'pdf' => UploadedFile::fake()->createWithContent('certificate.pdf', $pdfBytes),
            'certificate_number' => 'CERT-NOT-FOUND',
        ])->assertOk()
            ->assertJsonPath('pdf_hash_matches', null)
            ->assertJsonPath('pdf_mismatch_type', 'certificate_number_not_found')
            ->assertJsonPath('reason', 'Nomor sertifikat tidak ditemukan pada basis data resmi Bumiku Bumimu Hijau Farm.')
            ->assertJsonPath('is_valid', false);

        $this->postJson('/api/v1/public/certificates/verify-pdf', [
            'pdf' => UploadedFile::fake()->createWithContent('tampered.pdf', "%PDF-1.4\ntampered\n%%EOF"),
            'certificate_number' => $certificate->certificate_number,
        ])->assertOk()
            ->assertJsonPath('pdf_hash_matches', false)
            ->assertJsonPath('pdf_mismatch_type', 'tampered_or_unregistered')
            ->assertJsonPath('reason', 'Dokumen PDF tidak dapat disahkan karena tidak sesuai dengan arsip resmi untuk nomor sertifikat tersebut.')
            ->assertJsonPath('is_valid', false);

        $certificate->forceFill(['official_pdf_signature_base64' => base64_encode('wrong-signature')])->save();

        $this->postJson('/api/v1/public/certificates/verify-pdf', [
            'pdf' => UploadedFile::fake()->createWithContent('certificate.pdf', $pdfBytes),
            'certificate_number' => $certificate->certificate_number,
        ])->assertOk()
            ->assertJsonPath('pdf_hash_matches', true)
            ->assertJsonPath('pdf_signature_valid', false)
            ->assertJsonPath('pdf_mismatch_type', 'invalid_pdf_signature')
            ->assertJsonPath('reason', 'Tanda tangan digital pada dokumen PDF tidak valid sehingga keaslian dokumen tidak dapat disahkan.')
            ->assertJsonPath('is_valid', false);

        $certificate->forceFill([
            'official_pdf_signature_base64' => $signedHash['signature_base64'],
            'official_pdf_hash_sha256' => null,
        ])->save();

        $this->postJson('/api/v1/public/certificates/verify-pdf', [
            'pdf' => UploadedFile::fake()->createWithContent('certificate.pdf', $pdfBytes),
            'certificate_number' => $certificate->certificate_number,
        ])->assertOk()
            ->assertJsonPath('pdf_mismatch_type', 'missing_official_integrity')
            ->assertJsonPath('reason', 'Data pembanding keaslian PDF resmi belum tersedia pada sistem. Silakan unduh ulang sertifikat resmi dari sistem, lalu lakukan verifikasi kembali.')
            ->assertJsonPath('is_valid', false);

        $certificate->forceFill([
            'official_pdf_hash_sha256' => $pdfHash,
            'status' => 'revoked',
        ])->save();

        $this->postJson('/api/v1/public/certificates/verify-pdf', [
            'pdf' => UploadedFile::fake()->createWithContent('certificate.pdf', $pdfBytes),
            'certificate_number' => $certificate->certificate_number,
        ])->assertOk()
            ->assertJsonPath('is_pdf_integrity_valid', true)
            ->assertJsonPath('is_certificate_data_valid', false)
            ->assertJsonPath('pdf_mismatch_type', 'certificate_status_invalid')
            ->assertJsonPath('reason', 'Status atau data sertifikat tidak memenuhi ketentuan validasi.')
            ->assertJsonPath('is_valid', false);
    }
}
