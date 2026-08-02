<?php

namespace App\Http\Controllers;

use App\Support\BbhApiClient;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthSessionController extends Controller
{
    public function create()
    {
        return view('pages.auth.login');
    }

    public function forgotPassword()
    {
        return view('pages.auth.forgot-password');
    }

    public function sendResetLink(Request $request, BbhApiClient $api)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Peringatan: Kolom email wajib diisi.',
            'email.email' => 'Peringatan: Kolom email harus menggunakan format email yang valid.',
        ]);

        $response = $api->post('auth/forgot-password', [
            'email' => $data['email'],
            'reset_url_template' => url('/reset-kata-sandi/{token}'),
        ]);

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'email' => 'Gagal: Email tidak terdaftar. Periksa kembali email Anda dan coba lagi.',
            ]);
        }

        return back()->with('status', "Info: Jika email terdaftar di sistem, tautan reset kata sandi akan dikirim ke {$data['email']}.");
    }

    public function resetPassword(string $token, Request $request)
    {
        return view('pages.auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function updatePassword(Request $request, BbhApiClient $api)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.required' => 'Peringatan: Kolom email wajib diisi.',
            'email.email' => 'Peringatan: Kolom email harus menggunakan format email yang valid.',
            'token.required' => 'Gagal: Data pengajuan reset kata sandi tidak valid.',
            'password.required' => 'Peringatan: Kolom kata sandi baru wajib diisi.',
            'password.min' => 'Peringatan: Nilai kolom kata sandi baru di bawah batas minimum yang ditentukan.',
            'password.confirmed' => 'Peringatan: Konfirmasi kata sandi tidak cocok. Pastikan nilai sama dengan kolom sebelumnya.',
        ]);

        $response = $api->post('auth/reset-password', [
            ...$data,
            'password_confirmation' => $request->input('password_confirmation'),
        ]);

        if (! $response->successful()) {
            $message = $response->json('message') ?: 'Gagal: Data pengajuan reset kata sandi tidak valid.';
            $errors = $response->json('errors');

            if (is_array($errors)) {
                $first = collect($errors)->flatten()->first();
                $message = is_string($first) ? $first : $message;
            }

            throw ValidationException::withMessages([
                'email' => $message,
            ]);
        }

        return redirect()->route('login')->with('status', 'Sukses: Kata sandi berhasil diperbarui. Silakan masuk dengan kata sandi baru.');
    }

    public function store(Request $request, BbhApiClient $api)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Peringatan: Kolom email wajib diisi.',
            'email.email' => 'Peringatan: Kolom email harus menggunakan format email yang valid.',
            'password.required' => 'Peringatan: Kolom kata sandi wajib diisi.',
        ]);

        $response = $api->post('auth/login', [
            ...$credentials,
            'device_name' => 'bbh-laravel-frontend',
            'revoke_existing_tokens' => true,
        ]);

        if (! $response->successful()) {
            $message = $response->json('message');
            $errors = $response->json('errors');

            if (is_array($errors)) {
                $first = collect($errors)->flatten()->first();
                $message = is_string($first) ? $first : $message;
            }

            throw ValidationException::withMessages([
                'email' => is_string($message) && $message !== '' ? $message : 'Gagal Masuk: Email atau kata sandi tidak sesuai.',
            ]);
        }

        session([
            'bbh_api_token' => $response->json('access_token'),
            'bbh_admin_user' => $response->json('user'),
        ]);

        return redirect()->route('admin.dashboard');
    }

    public function destroy(BbhApiClient $api)
    {
        $token = session('bbh_api_token');

        if (is_string($token) && $token !== '') {
            $api->post('auth/logout', [], $token);
        }

        session()->forget(['bbh_api_token', 'bbh_admin_user']);

        return redirect()->route('login');
    }
}
