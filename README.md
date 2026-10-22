# Jariyah Batang Hari

Sistem informasi pengelolaan dan pemantauan tanah wakaf untuk Kantor Pertanahan Kabupaten Batang Hari.

- **Publik:** informasi wakaf, persyaratan, panduan, FAQ, kontak, peta sebaran tanah wakaf, daftar Nazir.
- **Nazir:** registrasi, pengajuan AIW dan sertifikat, unggah dokumen, perbaikan, pantau status.
- **KUA:** validasi dan pengesahan AIW di kecamatannya.
- **BPN:** verifikasi pengajuan sertifikat, pencatatan sertifikat terbit, kelola data wakaf dan impor Excel, laporan.
- **Admin BPN:** semua hak BPN, ditambah kelola pengguna petugas dan konten publik.

Dokumentasi lengkap (rancangan, alur, model data, keamanan, pengujian) ada di [`docs/`](docs/README.md).

## Teknologi
Laravel 13 · Livewire 4 (single-file components) · Flux UI · Tailwind CSS v4 · MySQL · Leaflet + Geoman · Chart.js · Trix · spatie/laravel-activitylog · maatwebsite/excel · barryvdh/laravel-dompdf

## Kebutuhan
- PHP 8.3+ dengan ekstensi `pdo_mysql`, `gd`, `zip`, `intl`, `fileinfo`
- Composer 2.x
- Node.js 20+ dan npm
- MySQL 8 (mis. Laragon)

## Instalasi (lokal)
```bash
composer install
cp .env.example .env
php artisan key:generate

# buat database kosong `jariyah_batanghari` di MySQL, sesuaikan DB_* di .env
php artisan migrate --seed
php artisan storage:link

npm install
npm run build
```

Jalankan aplikasi:
```bash
composer run dev      # server + Vite (pengembangan)
# atau
php artisan serve     # setelah npm run build
```
Buka http://localhost:8000.

## Akun demo
Seeder demo hanya berjalan di luar `production`. Semua akun memakai password `password`.

| Peran | Email |
|---|---|
| Admin BPN | `admin@jariyah.test` |
| Petugas BPN | `bpn@jariyah.test` |
| Petugas KUA | `kua.{kecamatan}@jariyah.test`, mis. `kua.muarabulian@jariyah.test` |
| Nazir | `nazir@jariyah.test`, `nazir2@jariyah.test` |

Daftar lengkap (username dan semua akun KUA): [docs/akun-demo.md](docs/akun-demo.md).

Untuk mengisi ulang data demo dari awal: `php artisan migrate:fresh --seed` (menghapus semua data).

## Pengujian
```bash
php artisan test
vendor/bin/pint      # format kode PHP (folder resources/views dikecualikan)
```
Test memakai SQLite in-memory, jadi tidak menyentuh database MySQL. Lihat [docs/09-pengujian.md](docs/09-pengujian.md).

## Deploy
Lihat [docs/10-deploy.md](docs/10-deploy.md).
