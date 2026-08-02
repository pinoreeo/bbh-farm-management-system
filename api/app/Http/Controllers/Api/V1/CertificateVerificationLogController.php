<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CertificateVerificationLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CertificateVerificationLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $this->perPage($request);
        $q = CertificateVerificationLog::query()
            ->select([
                'id',
                'certificate_id',
                'verification_method',
                'verification_time',
                'is_valid',
                'certificate_status_at_verification',
                'failure_reason',
                'used_key_fingerprint',
                'used_barcode_value',
                'ip_address',
                'created_at',
            ])
            ->with(['certificate:id,certificate_number,status']);

        if ($request->filled('certificate_id')) {
            $q->where('certificate_id', (int) $request->query('certificate_id'));
        }

        if ($request->filled('is_valid')) {
            $q->where('is_valid', (int) $request->query('is_valid') ? 1 : 0);
        }

        return response()->json($q->orderByDesc('verification_time')->paginate($perPage));
    }

    public function show(CertificateVerificationLog $certificateVerificationLog): JsonResponse
    {
        return response()->json($certificateVerificationLog->load(['certificate']));
    }
}
