<?php

namespace App\Services;

use App\Models\Certificate;
use Carbon\Carbon;

class CertificateNumberService
{
    public function generate(string $typeCode, string $issueDate): string
    {
        $documentCode = match ($typeCode) {
            'BIBIT_UNGGUL' => 'SBU',
            'KELAHIRAN' => 'AKL',
            'KEMATIAN' => 'AKM',
            default => 'CERT',
        };

        $year = Carbon::parse($issueDate)->format('Y');
        $prefix = "BBH-{$documentCode}-{$year}";

        $lastCertificate = Certificate::query()
            ->whereHas('certificateType', fn ($query) => $query->where('type_code', $typeCode))
            ->where('certificate_number', 'like', $prefix.'-%')
            ->orderByDesc('id')
            ->first();

        $lastNumber = 0;
        if ($lastCertificate && preg_match('/(\d+)$/', $lastCertificate->certificate_number, $matches)) {
            $lastNumber = (int) $matches[1];
        }

        return $prefix.'-'.str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
    }
}
