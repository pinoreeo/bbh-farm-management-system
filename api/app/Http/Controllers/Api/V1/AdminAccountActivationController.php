<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AdminPhoneVerification;
use App\Models\User;
use App\Support\TypeValue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminAccountActivationController extends Controller
{
    public function activate(Request $request): JsonResponse
    {
        $data = $this->validated($request, [
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = User::query()->where('email', TypeValue::string($data['email']))->first();
        $verification = $user instanceof User
            ? AdminPhoneVerification::query()->where('user_id', $user->id)->first()
            : null;

        if (! $user instanceof User || ! $verification || $verification->expires_at->isPast() || ! Hash::check(TypeValue::string($data['code']), $verification->code_hash)) {
            throw ValidationException::withMessages([
                'code' => ['Peringatan: Kode SMS tidak valid atau telah kedaluwarsa.'],
            ]);
        }

        $user->forceFill([
            'phone_verified_at' => now(),
            'is_active' => true,
        ])->save();
        $verification->delete();

        return response()->json([
            'message' => 'Sukses: Nomor telepon terverifikasi. Akun Anda sudah aktif.',
        ]);
    }
}
