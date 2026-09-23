<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FarmProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FarmProfileController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    private function farmProfile(): array
    {
        $profile = FarmProfile::query()->firstOrCreate(['singleton_key' => 1], [
            'farm_name' => config('bbh.farm.name', 'Bumiku Bumimu Hijau Farm'),
            'address' => config('bbh.farm.address', 'Ajibarang'),
            'phone' => config('bbh.farm.phone'),
            'email' => config('bbh.farm.email'),
        ]);

        return [
            'farm_name' => $profile->farm_name,
            'address' => $profile->address,
            'phone' => $profile->phone,
            'email' => $profile->email,
            'is_active' => true,
        ];
    }

    public function show(): JsonResponse
    {
        return response()->json($this->farmProfile());
    }

    public function update(Request $request): JsonResponse
    {
        $data = $this->validated($request, [
            'farm_name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $profile = FarmProfile::query()->firstOrCreate(['singleton_key' => 1], [
            'farm_name' => config('bbh.farm.name', 'Bumiku Bumimu Hijau Farm'),
            'address' => config('bbh.farm.address', 'Ajibarang'),
            'phone' => config('bbh.farm.phone'),
            'email' => config('bbh.farm.email'),
        ]);

        $profile->fill($data)->save();

        return response()->json([
            'message' => 'Sukses: Profil peternakan berhasil diperbarui.',
            'data' => $this->farmProfile(),
        ]);
    }
}
