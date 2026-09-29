<?php

namespace App\Http\Controllers;

use App\Support\BbhApiClient;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminInvitationController extends Controller
{
    public function create(string $token, Request $request)
    {
        return view('pages.auth.accept-invitation', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function store(Request $request, BbhApiClient $api)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.required' => 'Peringatan: Email wajib diisi.',
            'email.email' => 'Peringatan: Masukkan alamat email yang benar.',
            'token.required' => 'Peringatan: Tautan undangan tidak valid atau sudah kedaluwarsa.',
            'password.required' => 'Peringatan: Password wajib diisi.',
            'password.min' => 'Peringatan: Password minimal 8 karakter.',
            'password.confirmed' => 'Peringatan: Konfirmasi password harus sama dengan password baru.',
        ]);

        $response = $api->post('public/admin-invitations/accept', [
            ...$data,
            'password_confirmation' => $request->input('password_confirmation'),
        ]);

        if (! $response->successful()) {
            $message = $response->json('message') ?? 'Gagal: Tautan undangan tidak dapat digunakan.';
            $errors = $response->json('errors');

            if (is_array($errors)) {
                $first = collect($errors)->flatten()->first();
                $message = is_string($first) ? $first : $message;
            }

            throw ValidationException::withMessages(['email' => $message]);
        }

        return redirect()->route('login')->with('status', 'Sukses: Password berhasil dibuat. Akun Anda sudah aktif. Silakan login.');
    }
}
