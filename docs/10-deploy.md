# 10 · Catatan Deploy

Panduan memasang Jariyah Batang Hari di server produksi (VPS Linux atau hosting yang mendukung PHP 8.3+ dan akses SSH).

## Kebutuhan server
- PHP 8.3+ dengan ekstensi `pdo_mysql`, `mbstring`, `xml`, `curl`, `gd`, `zip`, `intl`, `fileinfo`, `bcmath`
- MySQL 8 / MariaDB 10.6+
- Nginx atau Apache, dengan **document root diarahkan ke folder `public/`**
- Composer 2. Node.js hanya perlu untuk build aset, dan build bisa dilakukan di mesin lain lalu folder `public/build` diunggah.
- HTTPS (Let's Encrypt), karena ada form login dan unggah dokumen pribadi

## Langkah pertama kali
```bash
git clone <repo> jariyah && cd jariyah
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

Atur `.env`:
```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda

DB_DATABASE=jariyah_batanghari
DB_USERNAME=...
DB_PASSWORD=...

MAIL_MAILER=smtp            # wajib untuk verifikasi email, reset password, dan notifikasi status
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=noreply@domain-anda
MAIL_FROM_NAME="Jariyah Batang Hari"

SESSION_SECURE_COOKIE=true
```

Lalu:
```bash
php artisan migrate --force
php artisan db:seed --force          # di production hanya mengisi data dasar: wilayah, role, persyaratan, konten awal
php artisan storage:link
npm ci && npm run build              # atau unggah public/build hasil build
php artisan optimize                 # cache config, route, view, event
```

## Akun Admin BPN pertama
Seeder akun demo tidak berjalan di production. Buat admin pertama lewat tinker:
```bash
php artisan tinker
>>> $u = App\Models\User::create(['name' => 'Admin BPN', 'username' => 'admin', 'email' => 'admin@domain-anda', 'password' => 'ganti-password-kuat', 'is_active' => true]);
>>> $u->markEmailAsVerified();
>>> $u->assignRole('admin_bpn');
```
Setelah itu akun petugas BPN dan KUA dibuat dari menu **Pengguna**.

## Izin folder
Web server harus bisa menulis ke `storage/` dan `bootstrap/cache/`:
```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

## Penyimpanan file
| Disk | Lokasi | Isi | Akses |
|---|---|---|---|
| `local` (privat) | `storage/app/private` | Dokumen pengajuan, scan AIW, scan sertifikat | Hanya lewat `/dokumen/{id}` dengan cek Policy |
| `public` | `storage/app/public` (lewat `public/storage`) | Foto tanah, file unduhan formulir | Publik |

**Cadangkan kedua folder itu bersama database.** Contoh cron harian:
```bash
mysqldump jariyah_batanghari | gzip > /backup/db-$(date +%F).sql.gz
tar czf /backup/files-$(date +%F).tgz storage/app
```

## Antrian & jadwal
- Notifikasi dan email dikirim langsung (`QUEUE_CONNECTION=sync`). Kalau pengiriman email terasa lambat, ganti ke `database` lalu jalankan worker dengan Supervisor: `php artisan queue:work --tries=3`.
- Belum ada tugas terjadwal. Kalau nanti ditambah, pasang cron `* * * * * php /path/artisan schedule:run`.

## Pembaruan versi
```bash
php artisan down
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm ci && npm run build
php artisan optimize
php artisan up
```

## Daftar periksa sebelum go-live
- [ ] `APP_DEBUG=false`, `APP_ENV=production`
- [ ] HTTPS aktif dan `SESSION_SECURE_COOKIE=true`
- [ ] Email SMTP sudah dites (registrasi Nazir → email verifikasi masuk)
- [ ] Password admin awal sudah diganti, dan tidak ada akun `@jariyah.test`
- [ ] Konten resmi diisi lewat **Kelola Konten**: alamat, telepon, artikel, FAQ, formulir unduhan (lihat [08](08-kebutuhan-data.md))
- [ ] Data desa resmi dan data tanah wakaf lama sudah diimpor
- [ ] Backup harian berjalan
