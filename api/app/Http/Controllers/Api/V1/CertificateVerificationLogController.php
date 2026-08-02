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
        $q = CertificateVerificationLog::query()->with(['certificate']);

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
