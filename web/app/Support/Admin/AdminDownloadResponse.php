<?php

namespace App\Support\Admin;

use App\Support\AdminResourceViewData;
use Illuminate\Http\Client\Response;

class AdminDownloadResponse
{
    public function apiFailureMessage(Response $response, string $fallback): string
    {
        $message = app(AdminResourceViewData::class)->failureMessage($response, $fallback);

        if (str_contains(strtolower($message), 'konfigurasi pdf')) {
            return 'Dokumen sertifikat belum siap diunduh. Periksa kembali data sertifikat dan kunci digital, lalu coba lagi.';
        }

        return $message;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function certificateFilename(array $data): string
    {
        $typeCode = data_get($data, 'certificate_type.type_code');
        $title = match ($typeCode) {
            'BIBIT_UNGGUL' => 'Sertifikat-Bibit-Unggul',
            'KELAHIRAN' => 'Akta-Kelahiran',
            'KEMATIAN' => 'Akta-Kematian',
            default => 'Sertifikat',
        };
        $certificateNumber = $this->safeFilenamePart((string) data_get($data, 'certificate_number', 'Tanpa-Nomor'));
        $issueDate = $this->safeFilenamePart(substr((string) data_get($data, 'issue_date', now()->format('Y-m-d')), 0, 10));

        return trim($title.'_'.$certificateNumber.'_'.$issueDate, '_').'.pdf';
    }

    public function reportFilename(string $report): string
    {
        $label = [
            'animals' => 'data-kambing',
            'deaths' => 'data-kematian',
            'births' => 'data-kelahiran',
            'offsprings' => 'data-anak',
            'weights' => 'data-bobot',
            'pens' => 'data-kandang-koloni',
            'health' => 'data-kesehatan',
            'vaccinations' => 'data-vaksinasi',
            'breeding' => 'data-perkawinan',
            'breeding-females' => 'data-betina-kawin',
            'pregnancies' => 'data-kebuntingan',
            'pen-movements' => 'riwayat-pindah-koloni',
        ][$report] ?? 'laporan';

        return $label.'-'.now()->format('Ymd-His').'.xlsx';
    }

    private function safeFilenamePart(string $value): string
    {
        return preg_replace('/[^A-Za-z0-9\-]+/', '-', $value) ?: '-';
    }
}
