<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CertificateType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CertificateTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $this->perPage($request);
        $q = CertificateType::query();

        if ($request->filled('is_active')) {
            $q->where('is_active', (bool) $request->query('is_active'));
        } elseif (! (int) $request->query('include_inactive', 0)) {
            $q->where('is_active', true);
        }

        if ($request->filled('type_code')) {
            $q->where('type_code', $request->query('type_code'));
        }

        return response()->json(
            $q->orderBy('type_code')
                ->orderBy('id')
                ->paginate($perPage)
        );
    }

    public function show(CertificateType $certificateType): JsonResponse
    {
        return response()->json($certificateType);
    }

    public function update(Request $request, CertificateType $certificateType): JsonResponse
    {
        $data = $this->validated($request, [
            'type_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'template_version' => ['nullable', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $certificateType->fill($data)->save();

        return response()->json([
            'message' => 'Sukses: Jenis sertifikat berhasil diperbarui.',
            'data' => $certificateType,
        ]);
    }

}
