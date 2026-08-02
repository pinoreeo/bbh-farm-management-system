<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FarmProfileController extends Controller
{
    private function farmProfile(): array
    {
        return [
            'farm_name' => config('bbh.farm.name', 'Bumiku Bumimu Hijau Farm'),
            'address' => config('bbh.farm.address', 'Ajibarang'),
            'phone' => config('bbh.farm.phone'),
            'email' => config('bbh.farm.email'),
            'is_active' => true,
        ];
    }

    public function show(): JsonResponse
    {
        return response()->json($this->farmProfile());
    }

    public function update(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Peringatan: Profil peternakan dikelola melalui konfigurasi aplikasi, bukan tabel database.',
            'data' => $this->farmProfile(),
        ], 422);
    }
}
