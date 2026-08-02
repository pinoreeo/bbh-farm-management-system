# BBH Farm V3

Monorepo versi operasional peternakan.

## Struktur

- `api/` - Laravel API, database, autentikasi, laporan XLSX, dan aturan operasional peternakan.
- `web/` - Laravel web admin yang mengonsumsi API.

## Setup Singkat

1. Jalankan migration dari folder `api/`.
2. Jalankan seeder untuk membuat akun awal `super_admin`.
3. Pastikan `web/.env` mengarah ke base URL API `v3`.
4. Jalankan `php artisan storage:link` di `api/` agar foto kambing bisa ditampilkan.

Tidak ada registrasi publik. Akun staf dibuat dari menu `Manajemen Pengguna` oleh `super_admin`.
