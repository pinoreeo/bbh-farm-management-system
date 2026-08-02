<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Services\CertificatePdfIntegrityService;
use App\Services\CertificateVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CertificateVerificationController extends Controller
{
    private const PUBLIC_RELATIONS = ['certificateType', 'animal.breed', 'animal.currentPen'];

    private const VERIFICATION_RELATIONS = ['signature.rsaKey', 'signature.signedByUser', 'certificateType', 'animal.breed', 'animal.currentPen', 'revocation'];

    private const PDF_VERIFICATION_RELATIONS = [
        'signature.rsaKey',
        'signature.signedByUser',
        'officialPdfRsaKey',
        'certificateType',
        'animal.breed',
        'animal.currentPen',
        'revocation',
    ];

    public function __construct(
        private readonly CertificateVerificationService $verificationService
    ) {}

    public function showPublic(string $certificate_number): JsonResponse
    {
        $cert = Certificate::query()
            ->with(self::PUBLIC_RELATIONS)
            ->where('certificate_number', $certificate_number)
            ->first();

        if (! $cert) {
            return response()->json([
                'message' => 'Peringatan: Sertifikat tidak ditemukan pada basis data resmi Bumiku Bumimu Hijau Farm.',
            ], 404);
        }

        return response()->json([
            'certificate_number' => $cert->certificate_number,
            'status' => $cert->status,
            'issue_date' => $cert->issue_date,
            'issue_place' => $cert->issue_place,
            ...$this->verificationService->publicCertificateData($cert),
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $data = $this->validated($request, [
            'certificate_number' => ['required', 'string', 'max:150'],
        ]);

        $cert = Certificate::query()
            ->with(self::VERIFICATION_RELATIONS)
            ->where('certificate_number', $data['certificate_number'])
            ->first();

        if (! $cert) {
            return response()->json([
                'message' => 'Peringatan: Nomor sertifikat tidak ditemukan pada basis data resmi Bumiku Bumimu Hijau Farm.',
                'is_valid' => false,
            ], 404);
        }

        return response()->json($this->verificationService->verify($cert, 'certificate_number', $request));
    }

    public function verifyByToken(Request $request, string $token): JsonResponse
    {
        $cert = Certificate::query()
            ->with(self::VERIFICATION_RELATIONS)
            ->where('verification_token', $token)
            ->first();

        if (! $cert) {
            return response()->json([
                'message' => 'Peringatan: Kode QR tidak terhubung dengan sertifikat yang terdaftar pada basis data resmi Bumiku Bumimu Hijau Farm.',
                'is_valid' => false,
            ], 404);
        }

        return response()->json($this->verificationService->verify($cert, 'qr_code', $request));
    }

    public function verifyPdf(Request $request, CertificatePdfIntegrityService $pdfIntegrity): JsonResponse
    {
        $data = $this->validated($request, [
            'pdf' => ['required', 'file', 'mimetypes:application/pdf', 'mimes:pdf', 'max:10240'],
            'certificate_number' => ['nullable', 'string', 'max:150'],
        ]);

        $startedAt = microtime(true);
        $originalName = $data['pdf']->getClientOriginalName();

        Log::info("PDF 1/1 diterima: {$originalName}");

        $uploadedPath = $data['pdf']->getRealPath();
        if (! $uploadedPath) {
            Log::warning('PDF 1/1 gagal dibaca dari temporary upload path.');

            return response()->json([
                'message' => 'Gagal: Dokumen PDF yang diunggah tidak dapat dibaca oleh sistem.',
                'is_valid' => false,
            ], 422);
        }

        Log::info("PDF 1/1 temporary path: {$uploadedPath}");

        if (! $this->looksLikePdfDocument($uploadedPath)) {
            $durationMs = number_format((microtime(true) - $startedAt) * 1000, 1);
            Log::warning("PDF 1/1 hasil akhir: TIDAK VALID, struktur PDF tidak valid, {$durationMs}ms");
            $invalidUploadedHash = is_file($uploadedPath) ? hash_file('sha256', $uploadedPath) : null;

            return response()->json([
                'certificate_number' => $data['certificate_number'] ?? null,
                'uploaded_pdf_hash_sha256' => is_string($invalidUploadedHash) ? $invalidUploadedHash : null,
                'official_pdf_hash_sha256' => null,
                'pdf_file_type_valid' => false,
                'pdf_hash_matches' => null,
                'pdf_signature_valid' => null,
                'is_pdf_integrity_valid' => false,
                'is_certificate_data_valid' => false,
                'is_valid' => false,
                'pdf_mismatch_type' => 'invalid_pdf_structure',
                'reason' => 'Gagal: Dokumen yang diunggah tidak menggunakan format PDF yang valid.',
            ], 422);
        }

        Log::info('PDF 1/1 menghitung SHA-256 dokumen...');

        $uploadedHash = hash_file('sha256', $uploadedPath);
        if (! is_string($uploadedHash)) {
            Log::warning('PDF 1/1 SHA-256 gagal dihitung.');

            return response()->json([
                'message' => 'Gagal: Dokumen PDF gagal diverifikasi karena nilai hash dokumen tidak dapat dihitung.',
                'is_valid' => false,
            ], 422);
        }

        Log::info('PDF 1/1 SHA-256: '.substr($uploadedHash, 0, 16).'...');

        $matchedByHash = Certificate::query()
            ->with(self::PDF_VERIFICATION_RELATIONS)
            ->where('official_pdf_hash_sha256', $uploadedHash)
            ->first();

        if (! empty($data['certificate_number'])) {
            Log::info('PDF 1/1 mencari sertifikat resmi: '.$data['certificate_number']);
            $cert = Certificate::query()
                ->with(self::PDF_VERIFICATION_RELATIONS)
                ->where('certificate_number', $data['certificate_number'])
                ->first();
        } else {
            Log::info('PDF 1/1 mencari sertifikat resmi berdasarkan hash PDF...');
            $cert = $matchedByHash;
        }

        if (! $cert) {
            $durationMs = number_format((microtime(true) - $startedAt) * 1000, 1);
            Log::info("PDF 1/1 hasil akhir: TIDAK VALID, sertifikat tidak ditemukan, {$durationMs}ms");

            $certificateNumberSupplied = ! empty($data['certificate_number']);

            return response()->json([
                'certificate_number' => $data['certificate_number'] ?? null,
                'uploaded_pdf_hash_sha256' => $uploadedHash,
                'official_pdf_hash_sha256' => null,
                'pdf_file_type_valid' => true,
                'pdf_hash_matches' => null,
                'pdf_signature_valid' => null,
                'is_pdf_integrity_valid' => false,
                'is_certificate_data_valid' => false,
                'is_valid' => false,
                'pdf_mismatch_type' => $certificateNumberSupplied ? 'certificate_number_not_found' : 'non_bbh_pdf',
                'reason' => $certificateNumberSupplied
                    ? 'Nomor sertifikat tidak ditemukan pada basis data resmi Bumiku Bumimu Hijau Farm.'
                    : 'Dokumen PDF yang diunggah tidak tercatat sebagai sertifikat resmi Bumiku Bumimu Hijau Farm.',
            ]);
        }

        $pdfHashMatches = $cert->official_pdf_hash_sha256
            ? hash_equals((string) $cert->official_pdf_hash_sha256, $uploadedHash)
            : false;

        Log::info('PDF 1/1 membandingkan hash upload dengan hash resmi: '.($pdfHashMatches ? 'cocok' : 'tidak cocok'));
        Log::info('PDF 1/1 memverifikasi tanda tangan digital RSA-SHA256...');

        $pdfSignatureValid = $pdfIntegrity->verifyOfficialPdfSignature($cert);
        $pdfIntegrityValid = $pdfHashMatches && $pdfSignatureValid;
        $certificateVerification = $this->verificationService->verify($cert, 'upload_pdf', $request);

        Log::info('PDF 1/1 nomor sertifikat: '.$cert->certificate_number);
        Log::info('PDF 1/1 signature RSA-SHA256: '.($pdfSignatureValid ? 'valid' : 'tidak valid'));
        Log::info('PDF 1/1 hasil verifikasi tersimpan ke tabel cert_log');

        $reason = null;
        $pdfMismatchType = null;
        if (! $pdfHashMatches && ! empty($data['certificate_number']) && $matchedByHash && $matchedByHash->id !== $cert->id) {
            $reason = 'Dokumen PDF yang diunggah tercatat sebagai sertifikat resmi Bumiku Bumimu Hijau Farm, tetapi tidak sesuai dengan nomor sertifikat yang dimasukkan.';
            $pdfMismatchType = 'wrong_certificate_number';
        } elseif (! $cert->official_pdf_hash_sha256 || ! $cert->official_pdf_signature_base64 || ! $cert->officialPdfRsaKey) {
            $reason = 'Data pembanding keaslian PDF resmi belum tersedia pada sistem. Silakan unduh ulang sertifikat resmi dari sistem, lalu lakukan verifikasi kembali.';
            $pdfMismatchType = 'missing_official_integrity';
        } elseif (! $pdfHashMatches) {
            $reason = ! empty($data['certificate_number'])
                ? 'Dokumen PDF tidak dapat disahkan karena tidak sesuai dengan arsip resmi untuk nomor sertifikat tersebut.'
                : 'Dokumen PDF tidak sesuai dengan arsip resmi sertifikat yang diterbitkan sistem.';
            $pdfMismatchType = 'tampered_or_unregistered';
        } elseif (! $pdfSignatureValid) {
            $reason = 'Tanda tangan digital pada dokumen PDF tidak valid sehingga keaslian dokumen tidak dapat disahkan.';
            $pdfMismatchType = 'invalid_pdf_signature';
        } elseif (! $certificateVerification['is_valid']) {
            $reason = 'Status atau data sertifikat tidak memenuhi ketentuan validasi.';
            $pdfMismatchType = 'certificate_status_invalid';
        }

        $isValid = $pdfIntegrityValid && (bool) $certificateVerification['is_valid'];
        $durationMs = number_format((microtime(true) - $startedAt) * 1000, 1);
        Log::info('PDF 1/1 hasil akhir: '.($isValid ? 'VALID' : 'TIDAK VALID').($reason ? ' - '.$reason : '').", {$durationMs}ms");

        return response()->json([
            'certificate_number' => $cert->certificate_number,
            'verification_token' => $cert->verification_token,
            'uploaded_pdf_hash_sha256' => $uploadedHash,
            'official_pdf_hash_sha256' => $cert->official_pdf_hash_sha256,
            'pdf_file_type_valid' => true,
            'pdf_hash_matches' => $pdfHashMatches,
            'pdf_signature_valid' => $pdfSignatureValid,
            'is_pdf_integrity_valid' => $pdfIntegrityValid,
            'is_certificate_data_valid' => (bool) $certificateVerification['is_valid'],
            'is_valid' => $isValid,
            'pdf_mismatch_type' => $pdfMismatchType,
            'matched_certificate_number' => $pdfMismatchType === 'wrong_certificate_number' ? $matchedByHash?->certificate_number : null,
            'reason' => $reason,
            'certificate_verification' => $certificateVerification,
        ]);
    }

    private function looksLikePdfDocument(string $path): bool
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return false;
        }

        $start = fread($handle, 1024);
        if (! is_string($start)) {
            fclose($handle);

            return false;
        }

        if (! str_starts_with(ltrim($start), '%PDF-')) {
            fclose($handle);

            return false;
        }

        $size = filesize($path);
        if (! is_int($size) || $size <= 0) {
            fclose($handle);

            return false;
        }

        fseek($handle, max(0, $size - 2048));
        $end = fread($handle, 2048);
        fclose($handle);

        return is_string($end) && str_contains($end, '%%EOF');
    }
}
