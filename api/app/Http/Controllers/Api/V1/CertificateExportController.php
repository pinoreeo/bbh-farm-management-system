<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Services\CertificatePdfIntegrityService;
use App\Services\CertificateViewDataService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CertificateExportController extends Controller
{
    public function qr(Certificate $certificate, CertificateViewDataService $viewData): JsonResponse
    {
        if ($certificate->certificateType?->type_code !== 'BIBIT_UNGGUL') {
            return response()->json([
                'message' => 'Peringatan: QR code hanya tersedia untuk Sertifikat Bibit Unggul.',
            ], 422);
        }

        $verificationUrl = $certificate->public_verification_url ?? $certificate->barcode_value;

        if (! $verificationUrl) {
            return response()->json([
                'message' => 'Peringatan: QR code belum tersedia untuk sertifikat ini.',
            ], 422);
        }

        return response()->json([
            'certificate_id' => $certificate->id,
            'qr_base64' => 'data:image/svg+xml;base64,'.$viewData->makeQrBase64($verificationUrl),
            'url' => $verificationUrl,
        ]);
    }

    public function preview(
        Certificate $certificate,
        CertificateViewDataService $viewData,
        CertificatePdfIntegrityService $pdfIntegrity
    ): JsonResponse|View {
        $certificate = $pdfIntegrity->loadForPdf($certificate);

        if (! $certificate->certificateType) {
            return response()->json([
                'message' => 'Peringatan: Jenis sertifikat tidak ditemukan.',
            ], 422);
        }

        $bladeView = $pdfIntegrity->bladeViewFor($certificate);

        if (! $bladeView) {
            return response()->json([
                'message' => 'Peringatan: Jenis sertifikat tidak valid.',
            ], 400);
        }

        $data = $viewData->build($certificate);
        $qr = $certificate->certificateType->type_code === 'BIBIT_UNGGUL'
            ? $viewData->makeQrBase64($data['verification_url'])
            : null;

        return view($bladeView, [
            'certificate' => $certificate,
            'data' => $data,
            'qr' => $qr,
            'assets' => $pdfIntegrity->templateAssets(),
        ]);
    }

    public function pdf(Certificate $certificate, CertificatePdfIntegrityService $pdfIntegrity): JsonResponse|BinaryFileResponse
    {
        try {
            $certificate = $pdfIntegrity->ensureOfficialPdf($certificate);
        } catch (\RuntimeException $e) {
            report($e);

            return response()->json([
                'message' => 'Gagal: Gagal membuat file PDF sertifikat. Periksa kelengkapan data dan konfigurasi dokumen.',
            ], 422);
        }

        $filename = basename((string) $certificate->official_pdf_path);

        return response()->download($pdfIntegrity->officialPdfAbsolutePath($certificate), $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
