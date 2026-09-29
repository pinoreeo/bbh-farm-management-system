<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\BbhApiClient;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FarmProfileController extends Controller
{
    public function show()
    {
        return view('pages.admin.profile', [
            'user' => session('bbh_admin_user', []),
            'userProfileMessage' => session('userProfileMessage'),
            'passwordMessage' => session('passwordMessage'),
        ]);
    }

    public function showFarm(BbhApiClient $api)
    {
        $this->ensureSuperAdmin();

        $token = session('bbh_api_token');
        $response = is_string($token) ? $api->get('farm', [], $token) : null;

        return view('pages.admin.farm-profile', [
            'farm' => $response?->successful() ? $response->json() : [],
            'farmProfileMessage' => session('farmProfileMessage'),
        ]);
    }

    public function updateUser(Request $request, BbhApiClient $api)
    {
        $data = $request->validate([
            'user_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
        ], [
            'user_name.required' => 'Peringatan: Nama pengguna wajib diisi.',
            'user_name.max' => 'Peringatan: Nama pengguna maksimal 255 karakter.',
            'phone.max' => 'Peringatan: Nomor telepon maksimal 100 karakter.',
        ]);

        $token = session('bbh_api_token');
        abort_unless(is_string($token) && $token !== '', 401);

        $response = $api->put('auth/profile', [
            'name' => $data['user_name'],
            'phone' => $data['phone'] ?? null,
        ], $token);

        if (! $response->successful()) {
            $message = $response->json('message') ?? 'Gagal: Profil pengguna tidak dapat diperbarui.';
            $errors = $response->json('errors');
            if (is_array($errors)) {
                $first = collect($errors)->flatten()->first();
                $message = is_string($first) ? $first : $message;
            }

            throw ValidationException::withMessages(['user_name' => $message]);
        }

        $user = $response->json('user');
        if (is_array($user)) {
            session(['bbh_admin_user' => $user]);
        }

        return redirect()->route('admin.profile')->with('userProfileMessage', 'Sukses: Profil pengguna berhasil diperbarui.');
    }

    public function updateFarm(Request $request, BbhApiClient $api)
    {
        $this->ensureSuperAdmin();

        $data = $request->validate([
            'farm_name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:100'],
        ], [
            'farm_name.required' => 'Peringatan: Nama peternakan wajib diisi.',
            'farm_name.max' => 'Peringatan: Nama peternakan maksimal 255 karakter.',
            'phone.max' => 'Peringatan: Nomor telepon maksimal 100 karakter.',
        ]);

        $token = session('bbh_api_token');
        abort_unless(is_string($token) && $token !== '', 401);

        $response = $api->put('farm', $data, $token);

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'farm_name' => $response->json('message') ?? 'Gagal: Profil peternakan belum berhasil diperbarui. Silakan coba lagi.',
            ]);
        }

        return redirect()->route('admin.farm-profile')->with('farmProfileMessage', 'Sukses: Profil peternakan berhasil diperbarui.');
    }

    public function updatePassword(Request $request, BbhApiClient $api)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'Peringatan: Password saat ini wajib diisi.',
            'password.required' => 'Peringatan: Password baru wajib diisi.',
            'password.min' => 'Peringatan: Password minimal 8 karakter.',
            'password.confirmed' => 'Peringatan: Konfirmasi password harus sama dengan password baru.',
        ]);

        $token = session('bbh_api_token');
        abort_unless(is_string($token) && $token !== '', 401);

        $response = $api->put('auth/password', [
            ...$data,
            'password_confirmation' => $request->input('password_confirmation'),
        ], $token);

        if (! $response->successful()) {
            $errors = $response->json('errors');
            $message = $response->json('message') ?? 'Gagal: Password belum berhasil diperbarui. Silakan coba lagi.';
            if (is_array($errors)) {
                $first = collect($errors)->flatten()->first();
                $message = is_string($first) ? $first : $message;
            }

            throw ValidationException::withMessages([
                'current_password' => $message,
            ]);
        }

        return redirect()->route('admin.profile')->with('passwordMessage', 'Sukses: Password berhasil diperbarui.');
    }

    private function ensureSuperAdmin(): void
    {
        abort_unless(session('bbh_admin_user.role') === 'super_admin', 403);
    }
}
