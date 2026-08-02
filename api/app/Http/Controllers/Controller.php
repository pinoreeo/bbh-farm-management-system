<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function perPage(Request $request, int $default = 15, int $max = 100): int
    {
        return max(1, min((int) $request->query('per_page', $default), $max));
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    protected function validated(Request $request, array $rules): array
    {
        return $request->validate($rules, [
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
        ], $this->validationAttributes());
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
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
            'animal_id' => 'Kambing',
            'record_date' => 'Tanggal Timbang',
            'weight_kg' => 'Berat',
            'pen_code' => 'Kode Kandang',
            'colony_code' => 'Kode Koloni',
            'colony_name' => 'Nama Koloni',
            'colony_type' => 'Jenis Kandang',
            'colony_phase' => 'Fase Koloni',
            'location' => 'Lokasi Kandang',
            'capacity' => 'Kapasitas',
            'is_active' => 'Status Aktif',
            'period_code' => 'Kode Periode',
            'colony_pen_id' => 'Kode Kandang',
            'start_date' => 'Tanggal Mulai',
            'mating_date' => 'Tanggal Kawin',
            'expected_birth_date' => 'Perkiraan Lahir',
            'end_date' => 'Tanggal Selesai',
            'male_animal_id' => 'Tag Pejantan',
            'female_animal_id' => 'Tag Betina',
            'female_animal_ids' => 'Tag Betina',
            'breeding_period_id' => 'Kode Periode',
            'breeding_female_id' => 'Betina Dalam Periode',
            'entry_date' => 'Tanggal Masuk',
            'exit_date' => 'Tanggal Keluar',
            'exit_reason_code' => 'Kategori Alasan Keluar',
            'exit_reason' => 'Alasan Keluar',
            'exit_notes' => 'Catatan Keluar',
            'to_pen_id' => 'Koloni Tujuan',
            'check_date' => 'Tanggal Periksa',
            'is_pregnant' => 'Status Bunting',
            'method' => 'Metode Periksa',
            'estimated_gestation_days' => 'Estimasi Usia Kebuntingan',
            'dam_id' => 'Tag Induk',
            'sire_id' => 'Tag Pejantan',
            'birth_time' => 'Jam Lahir',
            'offspring_count' => 'Jumlah Anak',
            'birth_process' => 'Proses Kelahiran',
            'dam_grade' => 'Grade Anak',
            'offspring_grade' => 'Grade Anak',
            'birth_event_id' => 'Data Kelahiran',
            'offspring_birth_id' => 'Data Cempe Lahir',
            'offspring_animal_id' => 'Tag Kambing',
            'birth_weight_kg' => 'Berat Lahir',
            'birth_status' => 'Status Hidup Cempe',
            'target_animal_id' => 'Tag Cempe Lahir',
            'care_date' => 'Tanggal Perawatan',
            'administration_method' => 'Metode Pemberian',
            'volume_ml' => 'Volume',
            'navel_iodine_status' => 'Iodin Pusar',
            'vitamin_ade_ml' => 'Vitamin ADE',
            'vitamin_b_complex_ml' => 'Vitamin B-Complex',
            'intracin_ml' => 'Intracin',
            'treatment_date' => 'Tanggal Perawatan',
            'treatment_group' => 'Jenis Perawatan',
            'symptoms' => 'Gejala',
            'diagnosis' => 'Diagnosis',
            'product_name' => 'Nama Produk',
            'dosage' => 'Dosis',
            'administration_route' => 'Cara Pemberian',
            'action_category' => 'Kategori Tindakan',
            'handled_by' => 'Ditangani Oleh',
            'next_control_date' => 'Tanggal Kontrol Berikutnya',
            'category_name' => 'Jenis Vaksin',
            'vaccination_date' => 'Tanggal Vaksin',
            'certificate_type_id' => 'Jenis Sertifikat',
            'issue_place' => 'Tempat Terbit',
            'death_date' => 'Tanggal Kematian',
            'death_time' => 'Waktu Kematian',
            'cause_of_death' => 'Penyebab Kematian',
            'key_identifier' => 'Key Identifier',
            'public_key_pem' => 'Public Key',
            'key_length' => 'Panjang Kunci',
            'notes' => 'Catatan',
            'status' => 'Status',
        ];
    }
}
