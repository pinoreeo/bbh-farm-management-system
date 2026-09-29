<?php

namespace App\Http\Controllers;

use App\Support\BbhApiClient;
use Illuminate\Http\Client\ConnectionException;
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
            'email.required' => 'Peringatan: Email wajib diisi.',
            'email.email' => 'Peringatan: Masukkan alamat email yang benar.',
        ]);

        try {
            $response = $api->post('auth/forgot-password', [
                'email' => $data['email'],
            ]);
        } catch (ConnectionException) {
            throw ValidationException::withMessages([
                'email' => 'Gagal: Layanan belum dapat diakses. Silakan coba lagi.',
            ]);
        }

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'email' => 'Gagal: Tautan reset password belum dapat dikirim. Silakan coba lagi.',
            ]);
        }

        return back()->with('status', 'Info: Jika email terdaftar, tautan reset password akan dikirim ke email tersebut.');
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
            'email.required' => 'Peringatan: Email wajib diisi.',
            'email.email' => 'Peringatan: Masukkan alamat email yang benar.',
            'token.required' => 'Gagal: Tautan reset password tidak valid atau sudah kedaluwarsa. Ajukan reset password kembali.',
            'password.required' => 'Peringatan: Password baru wajib diisi.',
            'password.min' => 'Peringatan: Password minimal 8 karakter.',
            'password.confirmed' => 'Peringatan: Konfirmasi password harus sama dengan password baru.',
        ]);

        try {
            $response = $api->post('auth/reset-password', [
                ...$data,
                'password_confirmation' => $request->input('password_confirmation'),
            ]);
        } catch (ConnectionException) {
            throw ValidationException::withMessages([
                'email' => 'Gagal: Layanan belum dapat diakses. Silakan coba lagi.',
            ]);
        }

        if (! $response->successful()) {
            if ($response->serverError()) {
                throw ValidationException::withMessages([
                    'email' => 'Gagal: Layanan belum dapat diakses. Silakan coba lagi.',
                ]);
            }

            $message = $response->json('message') ?: 'Gagal: Password belum berhasil diperbarui. Silakan coba lagi.';
            $errors = $response->json('errors');

            if (is_array($errors)) {
                $first = collect($errors)->flatten()->first();
                $message = is_string($first) ? $first : $message;
            }

            throw ValidationException::withMessages([
                'email' => $message,
            ]);
        }

        return redirect()->route('login')->with('status', 'Sukses: Password berhasil diperbarui. Silakan login dengan password baru.');
    }

    public function store(Request $request, BbhApiClient $api)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Peringatan: Email wajib diisi.',
            'email.email' => 'Peringatan: Masukkan alamat email yang benar.',
            'password.required' => 'Peringatan: Password wajib diisi.',
        ]);

        try {
            $response = $api->post('auth/login', [
                ...$credentials,
                'device_name' => 'bbh-laravel-frontend',
                'revoke_existing_tokens' => true,
            ]);
        } catch (ConnectionException) {
            throw ValidationException::withMessages([
                'email' => 'Gagal: Layanan belum dapat diakses. Silakan coba lagi.',
            ]);
        }

        if (! $response->successful()) {
            if ($response->notFound() || $response->serverError()) {
                throw ValidationException::withMessages([
                    'email' => 'Gagal: Layanan belum dapat diakses. Silakan coba lagi.',
                ]);
            }

            $message = $response->json('message');
            $errors = $response->json('errors');

            if (is_array($errors)) {
                $first = collect($errors)->flatten()->first();
                $message = is_string($first) ? $first : $message;
            }

            throw ValidationException::withMessages([
                'email' => is_string($message) && $message !== '' ? $message : 'Gagal: Login belum berhasil. Silakan coba lagi.',
            ]);
        }

        $request->session()->regenerate();
        $request->session()->put([
            'bbh_api_token' => $response->json('access_token'),
            'bbh_admin_user' => $response->json('user'),
        ]);

        return redirect()->route('admin.dashboard');
    }

    public function destroy(Request $request, BbhApiClient $api)
    {
        $token = $request->session()->get('bbh_api_token');

        try {
            if (is_string($token) && $token !== '') {
                $api->post('auth/logout', [], $token);
            }
        } catch (ConnectionException) {
            // Session lokal tetap diakhiri walaupun API sedang tidak dapat dihubungi.
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
