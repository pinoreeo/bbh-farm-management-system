<?php

namespace App\Services;

use App\Models\Certificate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class CertificatePrintService
{
    public function __construct(
        private readonly CertificatePdfIntegrityService $pdfIntegrity,
        private readonly CertificateViewDataService $viewData
    ) {}

    public function render(Certificate $certificate, ?string $expectedTypeCode = null): View|JsonResponse
    {
        $certificate = $this->pdfIntegrity->loadForPdf($certificate);
        $typeCode = $certificate->certificateType?->type_code;

        if (! $typeCode) {
            return response()->json(['message' => 'Peringatan: Jenis sertifikat tidak ditemukan.'], 422);
        }

        if ($expectedTypeCode !== null && $typeCode !== $expectedTypeCode) {
            return response()->json(['message' => 'Peringatan: Template cetak tidak sesuai dengan jenis sertifikat.'], 422);
        }

        $bladeView = $this->pdfIntegrity->bladeViewFor($certificate);
        if (! $bladeView) {
            return response()->json(['message' => 'Peringatan: Jenis sertifikat tidak valid.'], 400);
        }

        $data = $this->viewData->build($certificate);
        $qr = $typeCode === 'BIBIT_UNGGUL'
            ? $this->viewData->makeQrBase64($data['verification_url'])
            : null;
        $assets = $this->pdfIntegrity->templateAssets();

        return view($bladeView, compact('certificate', 'data', 'qr', 'assets'));
    }
}
