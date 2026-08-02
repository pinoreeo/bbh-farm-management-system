<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CertificateSignature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CertificateSignatureController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $this->perPage($request);
        $q = CertificateSignature::query()->with(['certificate', 'rsaKey']);

        if ($request->filled('certificate_id')) {
            $q->where('certificate_id', (int) $request->query('certificate_id'));
        }

        if ($request->filled('rsa_key_id')) {
            $q->where('rsa_key_id', (int) $request->query('rsa_key_id'));
        }

        return response()->json(
            $q->orderByDesc('signed_at')
                ->orderByDesc('id')
                ->paginate($perPage)
        );
    }

    public function show(CertificateSignature $certificateSignature): JsonResponse
    {
        return response()->json(
            $certificateSignature->load(['certificate', 'rsaKey'])
        );
    }
}
