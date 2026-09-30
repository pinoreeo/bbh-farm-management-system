# Struktur Resources Web

Folder ini dipisah berdasarkan tanggung jawab supaya perubahan UI lebih mudah dicari.

## CSS

- `css/app.css`: entrypoint Vite. Jangan taruh style panjang di sini.
- `css/settings.css`: token warna, font, dan elemen dasar.
- `css/components/public.css`: dasar tampilan halaman publik dan verifikasi.
- `css/components/company-profile.css`: tata letak dan detail company profile.
- `css/components/shared.css`: komponen umum seperti tombol, input, tabel, badge, alert, dan skeleton.
- `css/components/admin.css`: layout admin, sidebar, topbar, toolbar, form, filter, dan notifikasi.
- `css/components/dashboard.css`: card, chart, list, dan table khusus dashboard.
- `css/components/admin-type-scale.css`: skala tipografi halaman admin.
- `css/utilities.css`: helper kecil lintas halaman.

## JavaScript

- `js/app.js`: entrypoint Vite. Hanya import dan menjalankan modul.
- `js/modules/mobile-sidebar.js`: sidebar admin versi mobile.
- `js/modules/sortable-tables.js`: sorting tabel.
- `js/modules/resource-columns.js`: pilih kolom yang ditampilkan pada tabel data.
- `js/modules/row-actions.js`: menu aksi pada setiap baris data.
- `js/modules/page-skeleton.js`: skeleton loader saat pindah halaman.
- `js/modules/notifications.js`: dropdown notifikasi admin.
- `js/modules/flash-toast.js`: notifikasi singkat dari respons form.
- `js/modules/admin-forms.js`: interaksi form admin seperti multi-select dan preview tanggal.
- `js/modules/settings-tabs.js`: tab pengaturan akun.
- `js/modules/dashboard-chart.js`: interaksi grafik dashboard.
- `js/modules/public-gallery.js`: carousel halaman company profile.
- `js/modules/public-navigation.js`: menu mobile dan smooth scroll halaman publik.
- `js/modules/public-pdf-inputs.js`: validasi input PDF halaman verifikasi publik.

## Blade

- `views/components/layouts`: kerangka halaman.
- `views/components/admin`: komponen admin yang dipakai lintas halaman.
- `views/components/admin/fields`: renderer input form admin per tipe field.
- `views/components/public`: komponen halaman publik.
- `views/pages/public/company`: section company profile yang dipisah per bagian.
- `views/pages/admin`: halaman admin.
- `views/pages/public`: halaman publik.
- `views/pages/auth`: halaman login dan reset sandi.
