<?php

namespace App\Http\Controllers;

use App\Support\BbhApiClient;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminAccountActivationController extends Controller
{
    public function create()
    {
        return view('pages.auth.activate-admin');
    }

    public function store(Request $request, BbhApiClient $api)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'size:6'],
        ], [
            'email.required' => 'Peringatan: Email akun wajib diisi.',
            'email.email' => 'Peringatan: Format email tidak valid.',
            'code.required' => 'Peringatan: Kode SMS wajib diisi.',
            'code.size' => 'Peringatan: Kode SMS harus terdiri dari 6 digit.',
        ]);

        $response = $api->post('public/admin-account/activate', $data);
        if (! $response->successful()) {
            $message = $response->json('message') ?? 'Gagal: Kode SMS tidak dapat diverifikasi.';
            $errors = $response->json('errors');

            if (is_array($errors)) {
                $first = collect($errors)->flatten()->first();
                $message = is_string($first) ? $first : $message;
            }

            throw ValidationException::withMessages(['code' => $message]);
        }

        return redirect()->route('login')->with('status', 'Sukses: Nomor telepon telah diverifikasi. Akun Anda sekarang dapat digunakan untuk masuk.');
    }
}
