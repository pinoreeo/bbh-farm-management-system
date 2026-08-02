<?php

namespace App\Http\Controllers;

use App\Support\BbhApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;

class PublicVerificationController extends Controller
{
    public function index()
    {
        return view('pages.public.verification', [
            'verificationError' => null,
        ]);
    }

    public function result()
    {
        $verificationResult = session('verificationResult');

        if (! is_array($verificationResult)) {
            return redirect()->route('verification');
        }

        return view('pages.public.verification-result', [
            'verificationResult' => $verificationResult,
            'uploadedFilename' => session('uploadedFilename'),
        ]);
    }

    public function verify(Request $request, BbhApiClient $api)
    {
        $request->validate([
            'certificate_number' => ['nullable', 'string', 'max:255', 'required_without:pdf'],
            'pdf' => ['nullable', 'file', 'mimetypes:application/pdf', 'mimes:pdf', 'max:10240', 'required_without:certificate_number'],
        ], [
            'pdf.mimetypes' => 'File verifikasi harus berupa PDF.',
            'pdf.mimes' => 'File verifikasi harus berekstensi PDF.',
            'certificate_number.required_without' => 'Masukkan nomor sertifikat atau upload file PDF.',
            'pdf.required_without' => 'Masukkan nomor sertifikat atau upload file PDF.',
        ]);

        $certificateNumber = $request->string('certificate_number')->trim()->toString();

        try {
            $response = $request->hasFile('pdf')
                ? $api->postPdf(
                    'public/certificates/verify-pdf',
                    $request->file('pdf'),
                    'pdf',
                    $certificateNumber !== '' ? ['certificate_number' => $certificateNumber] : []
                )
                : $api->post('public/certificates/verify', [
                    'certificate_number' => $certificateNumber,
                ]);
        } catch (ConnectionException) {
            return back()
                ->withInput()
                ->with('verificationError', 'Gagal: Verifikasi gagal. Layanan API Bumiku Bumimu Hijau Farm tidak merespons.');
        }

        $result = $response->json();
        $canShowVerificationResult = is_array($result) && array_key_exists('is_valid', $result);

        if ((! $response->successful() && ! $canShowVerificationResult) || ! is_array($result)) {
            return back()
                ->withInput()
                ->with('verificationError', 'Gagal: Verifikasi gagal. Pastikan nomor sertifikat atau dokumen PDF sudah sesuai.');
        }

        return redirect()
            ->route('verification.result')
            ->with('verificationResult', $this->formatVerificationResult($result, $request->hasFile('pdf')))
            ->with('uploadedFilename', $request->hasFile('pdf') ? $request->file('pdf')->getClientOriginalName() : null);
    }

    public function verifyToken(string $locale, string $token, BbhApiClient $api)
    {
        try {
            $response = $api->get('public/certificates/verify/'.rawurlencode($token));
        } catch (ConnectionException) {
            return redirect()
                ->route('verification')
                ->with('verificationError', 'Gagal: Verifikasi gagal. Layanan API Bumiku Bumimu Hijau Farm tidak merespons.');
        }

        $result = $response->json();
        $canShowVerificationResult = is_array($result) && array_key_exists('is_valid', $result);

        if ((! $response->successful() && ! $canShowVerificationResult) || ! is_array($result)) {
            return redirect()
                ->route('verification')
                ->with('verificationError', 'Gagal: Kode QR tidak terhubung dengan sertifikat yang terdaftar pada sistem.');
        }

        return view('pages.public.verification-result', [
            'verificationResult' => $this->formatVerificationResult($result, false, 'QR Code'),
            'uploadedFilename' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function formatVerificationResult(array $result, bool $fromPdf, ?string $methodLabel = null): array
    {
        $certificateVerification = $result['certificate_verification'] ?? [];
        $certificateVerification = is_array($certificateVerification) ? $certificateVerification : [];
        $animal = $result['animal'] ?? $certificateVerification['animal'] ?? null;
        $animal = is_array($animal) ? $animal : null;

        $isValid = (bool) ($result['is_valid'] ?? false);
        $rawReason = $result['reason']
            ?? $certificateVerification['reason']
            ?? $result['authenticity_reason']
            ?? $certificateVerification['authenticity_reason']
            ?? $result['message']
            ?? null;

        return [
            ...$result,
            'method_label' => $methodLabel ?? ($fromPdf ? 'Upload PDF' : 'Nomor Sertifikat'),
            'status_label' => $isValid ? 'Valid' : 'Tidak Valid',
            'status_message' => $isValid
                ? ($fromPdf ? 'Dokumen PDF valid dan sesuai dengan data resmi Bumiku Bumimu Hijau Farm.' : 'Sertifikat valid dan sesuai dengan data resmi Bumiku Bumimu Hijau Farm.')
                : ($fromPdf ? 'Dokumen PDF tidak memenuhi ketentuan verifikasi.' : 'Sertifikat tidak memenuhi ketentuan verifikasi.'),
            'public_reason' => $isValid ? null : $this->humanReason((string) $rawReason, $result, $certificateVerification, $fromPdf),
            'certificate_number' => $result['certificate_number'] ?? $certificateVerification['certificate_number'] ?? null,
            'certificate_status' => $result['certificate_status'] ?? $certificateVerification['certificate_status'] ?? null,
            'certificate_type' => $result['certificate_type'] ?? $certificateVerification['certificate_type'] ?? null,
            'certificate_type_name' => $result['certificate_type_name'] ?? $certificateVerification['certificate_type_name'] ?? null,
            'certificate_type_template_version' => $result['certificate_type_template_version'] ?? $certificateVerification['certificate_type_template_version'] ?? null,
            'issue_date' => $result['issue_date'] ?? $certificateVerification['issue_date'] ?? null,
            'issue_place' => $result['issue_place'] ?? $certificateVerification['issue_place'] ?? null,
            'valid_from' => $result['valid_from'] ?? $certificateVerification['valid_from'] ?? null,
            'valid_until' => $result['valid_until'] ?? $certificateVerification['valid_until'] ?? null,
            'signature_info' => $result['signature_info'] ?? $certificateVerification['signature_info'] ?? null,
            'official_pdf_signature_info' => $result['official_pdf_signature_info'] ?? $certificateVerification['official_pdf_signature_info'] ?? null,
            'animal_tag' => $animal['tag_number'] ?? null,
            'pdf_file_type_label' => $this->pdfFileTypeLabel($result['pdf_file_type_valid'] ?? null),
            'pdf_integrity_label' => $this->pdfIntegrityLabel($result['is_pdf_integrity_valid'] ?? null, $result, $fromPdf, $isValid),
            'pdf_hash_label' => $this->pdfHashLabel($result['pdf_hash_matches'] ?? null, $result, $fromPdf, $isValid),
            'pdf_signature_label' => $this->pdfSignatureLabel($result['pdf_signature_valid'] ?? null),
            'certificate_data_label' => $this->statusLabel($result['is_certificate_data_valid'] ?? $certificateVerification['is_valid'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $result
     * @param  array<string, mixed>  $certificateVerification
     */
    private function humanReason(string $reason, array $result, array $certificateVerification, bool $fromPdf = false): string
    {
        $reason = trim($reason);
        $pdfMismatchType = (string) ($result['pdf_mismatch_type'] ?? '');
        $authReason = (string) ($certificateVerification['authenticity_reason'] ?? $result['authenticity_reason'] ?? '');
        $certificateReason = (string) ($certificateVerification['reason'] ?? $result['reason'] ?? '');
        $combined = strtolower($reason.' '.$certificateReason.' '.$authReason);

        if ($fromPdf && $pdfMismatchType !== '') {
            return match ($pdfMismatchType) {
                'invalid_pdf_structure' => 'Gagal: Dokumen yang diunggah tidak menggunakan format PDF yang valid.',
                'certificate_number_not_found' => 'Gagal: Nomor sertifikat tidak terdaftar pada basis data Bumiku Bumimu Hijau Farm.',
                'non_bbh_pdf' => 'Gagal: Dokumen PDF tidak teridentifikasi sebagai sertifikat resmi Bumiku Bumimu Hijau Farm.',
                'wrong_certificate_number' => 'Gagal: Dokumen PDF teridentifikasi sebagai sertifikat resmi Bumiku Bumimu Hijau Farm, tetapi tidak sesuai dengan nomor sertifikat yang dimasukkan.',
                'tampered_or_unregistered' => 'Gagal: Dokumen PDF tidak cocok dengan arsip sertifikat pada sistem.',
                'missing_official_integrity' => 'Data pembanding keaslian PDF resmi belum tersedia pada sistem. Silakan unduh ulang sertifikat resmi dari sistem, lalu lakukan verifikasi kembali.',
                'invalid_pdf_signature' => 'Gagal: Tanda tangan digital tidak valid. Keaslian dokumen tidak dapat diverifikasi.',
                'certificate_status_invalid' => 'Gagal: Data sertifikat tidak sinkron dengan data resmi Bumiku Bumimu Hijau Farm.',
                default => 'Gagal: Data sertifikat tidak sinkron dengan data resmi Bumiku Bumimu Hijau Farm.',
            };
        }

        if (
            str_contains($combined, 'bukan dokumen pdf yang valid') ||
            str_contains($combined, 'not a valid pdf')
        ) {
            return 'Gagal: Dokumen yang diunggah tidak menggunakan format PDF yang valid.';
        }

        if (
            str_contains($combined, 'uploaded pdf hash does not match') ||
            str_contains($combined, 'file pdf sudah berubah') ||
            str_contains($combined, 'pdf bukan dokumen resmi untuk nomor sertifikat') ||
            str_contains($combined, 'pdf tidak sesuai dengan dokumen resmi untuk nomor sertifikat') ||
            str_contains($combined, 'pdf sudah berubah') ||
            (($result['pdf_hash_matches'] ?? null) === false)
        ) {
            return 'Gagal: Dokumen PDF tidak cocok dengan arsip sertifikat pada sistem.';
        }

        if (
            str_contains($combined, 'no official certificate pdf matches') ||
            str_contains($combined, 'file pdf tidak cocok dengan dokumen resmi') ||
            str_contains($combined, 'file pdf tidak terdaftar sebagai dokumen resmi')
        ) {
            return 'Gagal: Dokumen PDF tidak teridentifikasi sebagai sertifikat resmi Bumiku Bumimu Hijau Farm.';
        }

        if (
            $fromPdf &&
            (
                str_contains($combined, 'file pdf tidak cocok dengan dokumen resmi') ||
                (($result['uploaded_pdf_hash_sha256'] ?? null) && empty($result['official_pdf_hash_sha256']))
            )
        ) {
            return 'Gagal: Dokumen PDF tidak cocok dengan arsip sertifikat pada sistem.';
        }

        if (
            str_contains($combined, 'official pdf integrity data is not available') ||
            str_contains($combined, 'data keaslian pdf resmi belum tersedia')
        ) {
            return 'Peringatan: Data pembanding keaslian PDF resmi belum tersedia pada sistem. Unduh ulang sertifikat resmi, lalu lakukan verifikasi kembali.';
        }

        if (
            str_contains($combined, 'official pdf signature verification failed') ||
            str_contains($combined, 'tanda tangan digital pdf tidak valid') ||
            (($result['pdf_signature_valid'] ?? null) === false && ($result['pdf_hash_matches'] ?? null) !== false)
        ) {
            return 'Gagal: Tanda tangan digital tidak valid. Keaslian dokumen tidak dapat diverifikasi.';
        }

        if (
            str_contains($combined, 'certificate not found') ||
            str_contains($combined, 'nomor sertifikat tidak ditemukan')
        ) {
            return 'Gagal: Nomor sertifikat tidak terdaftar pada basis data Bumiku Bumimu Hijau Farm.';
        }

        if (str_contains($combined, 'qr code tidak mengarah')) {
            return 'Gagal: Kode QR tidak terhubung dengan sertifikat yang terdaftar pada basis data Bumiku Bumimu Hijau Farm.';
        }

        if (str_contains($combined, 'payload hash mismatch')) {
            return 'Gagal: Data sertifikat tidak sinkron dengan data resmi Bumiku Bumimu Hijau Farm.';
        }

        if (str_contains($combined, 'signature verification failed')) {
            return 'Gagal: Tanda tangan digital tidak valid. Keaslian dokumen tidak dapat diverifikasi.';
        }

        if (str_contains($combined, 'signature or rsa key not found')) {
            return 'Gagal: Data tanda tangan digital atau kunci publik sertifikat tidak tersedia pada sistem.';
        }

        if (str_contains($combined, 'invalid public key')) {
            return 'Gagal: Kunci publik sertifikat tidak dapat dibaca oleh sistem.';
        }

        if (str_contains($combined, 'invalid signature encoding')) {
            return 'Gagal: Format tanda tangan digital sertifikat tidak dapat dibaca oleh sistem.';
        }

        if (str_contains($combined, 'certificate is not authentic')) {
            return 'Gagal: Data sertifikat tidak sinkron dengan data resmi Bumiku Bumimu Hijau Farm.';
        }

        if (str_contains($combined, 'certificate status is not active')) {
            $status = (string) ($result['certificate_status'] ?? $certificateVerification['certificate_status'] ?? '');

            return match ($status) {
                'revoked' => 'Peringatan: Sertifikat telah dicabut oleh Bumiku Bumimu Hijau Farm dan dinyatakan tidak berlaku.',
                'expired' => 'Peringatan: Masa berlaku sertifikat telah habis (Kedaluwarsa).',
                default => 'Peringatan: Sertifikat berstatus tidak aktif sehingga tidak dapat digunakan.',
            };
        }

        if (str_contains($combined, 'certificate validity period has ended')) {
            return 'Peringatan: Masa berlaku sertifikat telah habis (Kedaluwarsa).';
        }

        if (
            str_contains($combined, 'certificate data or status verification failed') ||
            str_contains($combined, 'data atau status sertifikat tidak valid')
        ) {
            return $fromPdf
                ? 'Gagal: Data sertifikat tidak sinkron dengan data resmi Bumiku Bumimu Hijau Farm.'
                : 'Gagal: Data sertifikat tidak sinkron dengan data resmi Bumiku Bumimu Hijau Farm.';
        }

        return $fromPdf
            ? 'Gagal: Data sertifikat tidak sinkron dengan data resmi Bumiku Bumimu Hijau Farm.'
            : 'Gagal: Data sertifikat tidak sinkron dengan data resmi Bumiku Bumimu Hijau Farm.';
    }

    private function statusLabel(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return (bool) $value ? 'Sesuai' : 'Tidak sesuai';
    }

    private function pdfFileTypeLabel(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return (bool) $value ? 'Format PDF valid' : 'Bukan PDF valid';
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function pdfIntegrityLabel(mixed $value, array $result, bool $fromPdf, bool $isValid): ?string
    {
        if ($value !== null) {
            return (bool) $value ? 'Sesuai' : 'Tidak sesuai';
        }

        if ($fromPdf && ! $isValid && ($result['uploaded_pdf_hash_sha256'] ?? null)) {
            return 'Tidak sesuai';
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function pdfHashLabel(mixed $value, array $result, bool $fromPdf, bool $isValid): ?string
    {
        $pdfMismatchType = (string) ($result['pdf_mismatch_type'] ?? '');

        if ($pdfMismatchType === 'wrong_certificate_number') {
            return 'Milik sertifikat lain';
        }

        if ($pdfMismatchType === 'tampered_or_unregistered') {
            return 'Tidak sesuai';
        }

        if ($value === null) {
            if ($fromPdf && ! $isValid && ($result['uploaded_pdf_hash_sha256'] ?? null)) {
                return 'Tidak ditemukan / berubah';
            }

            return null;
        }

        return (bool) $value ? 'Sesuai' : 'Berubah';
    }

    private function pdfSignatureLabel(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return (bool) $value ? 'Valid' : 'Tidak valid';
    }
}
