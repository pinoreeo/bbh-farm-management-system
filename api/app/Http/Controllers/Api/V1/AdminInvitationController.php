<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AdminInvitationService;
use App\Support\TypeValue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminInvitationController extends Controller
{
    public function accept(Request $request, AdminInvitationService $invitations): JsonResponse
    {
        $data = $this->validated($request, [
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $invitations->accept(
            TypeValue::string($data['email']),
            TypeValue::string($data['token']),
            TypeValue::string($data['password']),
        );

        return response()->json([
            'message' => 'Sukses: Kata sandi dibuat dan akun admin sudah aktif.',
        ]);
    }
}
