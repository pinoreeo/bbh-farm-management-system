<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth) {}

    public function login(Request $request): JsonResponse
    {
        $data = $this->validated($request, [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
            'revoke_existing_tokens' => ['nullable', 'boolean'],
        ]);

        return response()->json($this->auth->login($request, $data));
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $this->validated($request, [
            'email' => ['required', 'email'],
        ]);

        return response()->json($this->auth->forgotPassword($data));
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $this->validated($request, [
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        return response()->json($this->auth->resetPassword($data));
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json(['message' => 'Sesi Habis: Sesi masuk tidak valid. Silakan masuk kembali.'], 401);
        }

        return response()->json($this->auth->userPayload($user));
    }

    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return response()->json(['message' => 'Sesi Habis: Sesi masuk tidak valid. Silakan masuk kembali.'], 401);
        }

        $data = $this->validated($request, [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        return response()->json($this->auth->changePassword($user, $data));
    }

    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($request);

        return response()->json(['message' => 'Sukses: Anda berhasil keluar dari sistem.']);
    }
}
