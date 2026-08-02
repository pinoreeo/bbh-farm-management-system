<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\BbhApiClient;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FarmProfileController extends Controller
{
    public function show(BbhApiClient $api)
    {
        $token = session('bbh_api_token');
        $response = is_string($token) ? $api->get('farm', [], $token) : null;

        return view('pages.admin.profile', [
            'farm' => $response?->successful() ? $response->json() : [],
            'profileMessage' => session('profileMessage'),
            'passwordMessage' => session('passwordMessage'),
        ]);
    }

    public function update(Request $request, BbhApiClient $api)
    {
        $data = $request->validate([
            'farm_name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
        ], [
            'farm_name.required' => 'Peringatan: Kolom nama farm wajib diisi.',
            'farm_name.max' => 'Peringatan: Isian kolom nama farm melebihi batas maksimum karakter.',
            'phone.max' => 'Peringatan: Isian kolom nomor telepon melebihi batas maksimum karakter.',
            'email.email' => 'Peringatan: Kolom email harus menggunakan format email yang valid.',
            'email.max' => 'Peringatan: Isian kolom email melebihi batas maksimum karakter.',
        ]);

        $token = session('bbh_api_token');
        abort_unless(is_string($token) && $token !== '', 401);

        $response = $api->put('farm', $data, $token);

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'farm_name' => $response->json('message') ?? 'Gagal: Gagal memperbarui profil peternakan. Periksa kembali data Anda.',
            ]);
        }

        return redirect()->route('admin.profile')->with('profileMessage', 'Sukses: Profil peternakan berhasil diperbarui.');
    }

    public function updatePassword(Request $request, BbhApiClient $api)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'Peringatan: Kolom kata sandi saat ini wajib diisi.',
            'password.required' => 'Peringatan: Kolom kata sandi baru wajib diisi.',
            'password.min' => 'Peringatan: Nilai kolom kata sandi baru di bawah batas minimum yang ditentukan.',
            'password.confirmed' => 'Peringatan: Konfirmasi kata sandi baru tidak cocok. Pastikan nilai sama dengan kolom sebelumnya.',
        ]);

        $token = session('bbh_api_token');
        abort_unless(is_string($token) && $token !== '', 401);

        $response = $api->put('auth/password', [
            ...$data,
            'password_confirmation' => $request->input('password_confirmation'),
        ], $token);

        if (! $response->successful()) {
            $errors = $response->json('errors');
            $message = $response->json('message') ?? 'Gagal: Gagal memperbarui kata sandi. Periksa kembali kata sandi saat ini.';
            if (is_array($errors)) {
                $first = collect($errors)->flatten()->first();
                $message = is_string($first) ? $first : $message;
            }

            throw ValidationException::withMessages([
                'current_password' => $message,
            ]);
        }

        return redirect()->route('admin.profile')->with('passwordMessage', 'Sukses: Kata sandi akun berhasil diperbarui.');
    }
}
