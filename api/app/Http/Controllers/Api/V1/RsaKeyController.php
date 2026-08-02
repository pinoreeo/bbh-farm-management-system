<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RsaKey\CompromiseRsaKeyRequest;
use App\Http\Requests\Api\V1\RsaKey\GenerateRsaKeyRequest;
use App\Http\Requests\Api\V1\RsaKey\StoreRsaKeyRequest;
use App\Http\Requests\Api\V1\RsaKey\UpdateRsaKeyRequest;
use App\Models\RsaKey;
use App\Services\RsaKeyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RsaKeyController extends Controller
{
    public function __construct(private readonly RsaKeyService $rsaKeys) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->rsaKeys
                ->queryFor($request)
                ->paginate($this->perPage($request))
        );
    }

    public function store(StoreRsaKeyRequest $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Peringatan: User tidak terautentikasi.'], 401);
        }

        return $this->serviceResponse($this->rsaKeys->storePublicKey($request->validated(), $user));
    }

    public function show(Request $request, RsaKey $rsaKey): JsonResponse
    {
        $this->rsaKeys->authorizeKeyAccess($rsaKey, $request->user());

        return response()->json($rsaKey);
    }

    public function generate(GenerateRsaKeyRequest $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Peringatan: User tidak terautentikasi.'], 401);
        }

        return $this->serviceResponse($this->rsaKeys->generate($request->validated(), $user));
    }

    public function update(UpdateRsaKeyRequest $request, RsaKey $rsaKey): JsonResponse
    {
        $this->rsaKeys->authorizeKeyAccess($rsaKey, $request->user());

        if ($request->hasAny(['public_key_pem', 'key_length'])) {
            return response()->json([
                'message' => 'Peringatan: Public key dan panjang RSA key tidak dapat diubah setelah dibuat. Buat key baru jika diperlukan.',
            ], 422);
        }

        return $this->serviceResponse($this->rsaKeys->update($rsaKey, $request->validated()));
    }

    public function activate(Request $request, RsaKey $rsaKey): JsonResponse
    {
        $this->rsaKeys->authorizeKeyAccess($rsaKey, $request->user());

        return $this->serviceResponse($this->rsaKeys->activate($rsaKey));
    }

    public function deactivate(Request $request, RsaKey $rsaKey): JsonResponse
    {
        $this->rsaKeys->authorizeKeyAccess($rsaKey, $request->user());

        return $this->serviceResponse($this->rsaKeys->deactivate($rsaKey));
    }

    public function compromise(CompromiseRsaKeyRequest $request, RsaKey $rsaKey): JsonResponse
    {
        $this->rsaKeys->authorizeKeyAccess($rsaKey, $request->user());

        return $this->serviceResponse($this->rsaKeys->markCompromised($rsaKey, $request->validated('status_reason')));
    }

    private function serviceResponse(array $result): JsonResponse
    {
        if (($result['ok'] ?? false) !== true) {
            return response()->json(['message' => $result['message']], $result['status'] ?? 422);
        }

        return response()->json([
            'message' => $result['message'],
            'data' => $result['data'],
        ], $result['status'] ?? 200);
    }
}
