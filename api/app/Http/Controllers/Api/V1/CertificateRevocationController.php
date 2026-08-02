<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CertificateRevocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CertificateRevocationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $this->perPage($request);
        $q = CertificateRevocation::query()
            ->with(['certificate:id,certificate_number,status']);

        if ($request->filled('certificate_id')) {
            $q->where('certificate_id', (int) $request->query('certificate_id'));
        }

        return response()->json(
            $q->orderByDesc('revoked_at')
                ->orderByDesc('id')
                ->paginate($perPage)
        );
    }

    public function show(CertificateRevocation $certificateRevocation): JsonResponse
    {
        return response()->json(
            $certificateRevocation->load(['certificate'])
        );
    }
}
