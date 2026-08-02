<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

abstract class ApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'Peringatan: Kolom :attribute wajib diisi.',
            'required_if' => 'Peringatan: Kolom :attribute wajib diisi untuk pilihan tersebut.',
            'required_without' => 'Peringatan: Kolom :attribute wajib diisi jika :values belum diisi.',
            'integer' => 'Peringatan: Isian :attribute harus sesuai dengan pilihan yang tersedia.',
            'numeric' => 'Peringatan: Kolom :attribute hanya boleh diisi dengan angka.',
            'boolean' => "Peringatan: Kolom :attribute wajib memilih 'Ya' atau 'Tidak'.",
            'date' => 'Peringatan: Kolom :attribute harus menggunakan format tanggal yang valid.',
            'date_format' => 'Peringatan: Format :attribute tidak valid.',
            'exists' => 'Peringatan: Pilihan :attribute tidak valid atau sudah tidak aktif.',
            'unique' => 'Peringatan: :attribute sudah terdaftar. Gunakan nilai yang berbeda.',
            'in' => 'Peringatan: Pilihan :attribute tidak valid atau sudah tidak aktif.',
            'mimes' => 'Peringatan: Format :attribute harus berupa jpg, jpeg, png, atau webp.',
            'image' => 'Peringatan: :attribute harus berupa file gambar.',
            'min' => 'Peringatan: Nilai kolom :attribute di bawah batas minimum yang ditentukan.',
            'max' => 'Peringatan: Isian kolom :attribute melebihi batas maksimum karakter.',
            'confirmed' => 'Peringatan: Konfirmasi :attribute tidak cocok. Pastikan nilai sama dengan kolom sebelumnya.',
            'before_or_equal' => 'Peringatan: Tanggal :attribute tidak boleh melebihi hari ini.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'Nama',
            'first_name' => 'Nama Depan',
            'last_name' => 'Nama Belakang',
            'email' => 'Email',
            'password' => 'Password',
            'password_confirmation' => 'Konfirmasi Password',
            'current_password' => 'Password Saat Ini',
            'role' => 'Role',
            'is_active' => 'Status Aktif',
            'tag_number' => 'Nomor Eartag',
            'photo' => 'Foto Kambing',
            'photo_path' => 'Foto Kambing',
            'breed_id' => 'Ras Kambing',
            'sex' => 'Jenis Kelamin',
            'male_role' => 'Jantan Pemacek',
            'generation' => 'Generasi',
            'birth_date' => 'Tanggal Lahir',
            'birth_place' => 'Tempat Lahir',
            'current_pen_id' => 'Kandang/Koloni Saat Ini',
            'reproductive_status' => 'Status Reproduksi',
            'status_date' => 'Tanggal Status',
            'life_status' => 'Status Hidup',
            'exit_status' => 'Status Ternak',
            'is_impor' => 'Kambing Impor',
            'origin_type' => 'Asal Ternak',
            'origin_detail' => 'Detail Asal',
            'colony_pen_id' => 'Kode Kandang',
            'period_code' => 'Kode Periode',
            'start_date' => 'Tanggal Mulai',
            'end_date' => 'Tanggal Selesai',
            'male_animal_id' => 'Tag Pejantan',
            'female_animal_id' => 'Tag Betina',
            'female_animal_ids' => 'Tag Betina',
            'entry_date' => 'Tanggal Masuk',
            'mating_date' => 'Tanggal Kawin',
            'cycle_stage' => 'Tahap Siklus',
            'exit_date' => 'Tanggal Keluar',
            'exit_reason_code' => 'Kategori Alasan Keluar',
            'exit_reason' => 'Alasan Keluar',
            'to_pen_id' => 'Koloni Tujuan',
            'exit_notes' => 'Catatan Keluar',
            'key_identifier' => 'Key Identifier',
            'public_key_pem' => 'Public Key',
            'key_length' => 'Panjang Kunci',
            'status_reason' => 'Alasan Status',
        ];
    }
}
