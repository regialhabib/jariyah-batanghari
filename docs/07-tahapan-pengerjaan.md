# 07 · Tahapan Pengerjaan & Progres

**Cara pakai:** centang `[x]` saat sebuah butir selesai **dan** sudah diverifikasi (test lulus atau dicek di browser). Catat keputusan baru di [CHANGELOG.md](CHANGELOG.md).

## Strategi eksekusi paralel

Fase 0 dan 1 adalah **fondasi** dan dikerjakan berurutan oleh satu pelaksana. Hampir semua file lain bergantung padanya: layout, tema, model, enum, seeder, dan file rute per area.

Setelah fondasi selesai, pekerjaan dibagi ke beberapa agen yang berjalan **paralel**. Masing-masing punya wilayah file sendiri supaya tidak bentrok.

| Gelombang | Agen | Wilayah file (milik eksklusif) |
|---|---|---|
| **G1** (paralel) | A · Halaman publik | `app/Livewire/Publik/*` (kecuali PetaWakaf & TanahDetail), `resources/views/livewire/publik/*`, `routes/publik.php` |
| | B · Auth | Komponen/view auth dari starter kit, `x-layouts.auth`, `routes/auth.php`, logika login/registrasi |
| | C · Peta | `app/Livewire/Shared/PetaWakaf.php`, `Publik/PetaWakaf`, `Publik/TanahDetail`, `PetaDataController`, `resources/js/peta.js` |
| **G2** (paralel) | D · Area Nazir | `app/Livewire/Nazir/*`, view-nya, `routes/nazir.php` |
| | E · Area Admin: pengajuan & data | `Admin/{Dashboard, TanahIndex, TanahForm, TanahImport, PengajuanIndex, PengajuanShow, Sertifikat, Peta}`, `Imports/*` |
| | F · Admin: konten, pengguna, laporan | `Admin/Konten/*`, `Admin/{PenggunaIndex, PenggunaForm, PesanIndex, Laporan}`, `Exports/*`, `LaporanPdfController`, `views/pdf/*` |
| **G3** | Integrasi (satu pelaksana) | Test end-to-end, perbaikan lintas area, pemolesan |

**Aturan untuk setiap agen:**
1. Baca `docs/` lebih dulu. Dokumen ini adalah spesifikasi.
2. **Jangan mengubah file fondasi** (model, enum, migrasi, layout, `PengajuanWorkflow`, `app.css`). Jika butuh perubahan, laporkan di hasil akhir agar pelaksana integrasi yang menerapkannya.
3. Tulis test Pest untuk wilayahnya sendiri di `tests/Feature/{Area}/`.
4. Pastikan `php artisan test --filter={Area}` lulus sebelum menyatakan selesai.
5. Laporkan: file yang dibuat, test yang dijalankan, dan hal yang belum selesai.

Pada G2, agen D dan E sama-sama memakai `PengajuanWorkflow`. Karena itu service tersebut dibuat lengkap di **Fase 1** (fondasi), beserta test-nya.

---

## Fase 0 · Setup
- [ ] `composer self-update` (2.4.4 → terbaru)
- [ ] Buat proyek Laravel dengan starter kit Livewire + Pest di root proyek (folder `docs/` dipertahankan)
- [ ] Buat database `jariyah_batanghari` di MySQL Laragon dan atur `.env` (`DB_*`, `APP_NAME="Jariyah Batang Hari"`, `APP_LOCALE=id`, `APP_TIMEZONE=Asia/Jakarta`)
- [ ] `git init` + commit awal
- [ ] Pasang paket composer: permission, activitylog, excel, dompdf
- [ ] Pasang paket npm: leaflet, @geoman-io/leaflet-geoman-free, chart.js, trix
- [ ] Atur file bahasa Indonesia (`lang/id`)
- [ ] Token tema di `app.css` + font Poppins
- [ ] `php artisan test` dan `npm run build` berhasil

## Fase 1 · Fondasi (berurutan)
- [ ] Migrasi semua tabel (lihat 04)
- [ ] Enum di `app/Enums`
- [ ] Model + relasi + cast + scope `forUser()` + `kolomPublik()`
- [ ] Policy + registrasi role/permission (`RoleSeeder`)
- [ ] `PengajuanWorkflow` + `StatistikWakaf` + notifikasi `StatusPengajuanBerubah`, beserta unit/feature test
- [ ] Helper `setting()`
- [ ] Factory semua model
- [ ] Seeder: Wilayah (8 kecamatan + desa), Role, UserDemo (1 per role, KUA per kecamatan), Persyaratan (sesuai PDF), Konten (artikel, panduan, FAQ, pengaturan), TanahWakafDemo (±125 bidang dengan koordinat di dalam Batang Hari)
- [ ] Layout `publik`, `dashboard`, `auth` + komponen bersama (`page-header`, `stat-card`, `badge-status`, ikon)
- [ ] Rute dipecah: `publik.php`, `nazir.php`, `admin.php`, `auth.php`, dengan halaman placeholder
- [ ] `php artisan migrate:fresh --seed` dan `php artisan test` lulus

## Fase 2 · Halaman publik (G1-A)
- [ ] Beranda
- [ ] Tentang
- [ ] Informasi Wakaf + detail artikel
- [ ] Persyaratan
- [ ] Panduan (+ unduh ZIP)
- [ ] FAQ
- [ ] Kontak (simpan pesan, rate limit, honeypot)
- [ ] Daftar Nazir
- [ ] Unduhan formulir

## Fase 3 · Autentikasi (G1-B)
- [ ] Login dua kolom sesuai mockup, email/username, cek `is_active`
- [ ] Registrasi Nazir (NIK, No. HP, S&K, tanpa role)
- [ ] Redirect per role setelah login
- [ ] Lupa/reset password + verifikasi email dengan gaya yang sama

## Fase 4 · Peta (G1-C)
- [ ] Endpoint GeoJSON `/api/peta` + filter
- [ ] Komponen `PetaWakaf`: toggle Peta/Satelit, marker berwarna, popup, polygon, legenda
- [ ] Halaman publik Peta Wakaf + panel filter & informasi
- [ ] Detail tanah publik
- [ ] Mode mini (dashboard) dan mode pilih lokasi + gambar polygon (wizard)

## Fase 5 · Area Nazir (G2-D)
- [ ] Dashboard (kartu, tabel terbaru, peta mini)
- [ ] Data Wakaf Saya
- [ ] Wizard pengajuan (simpan draf, unggah dokumen, kirim)
- [ ] Detail pengajuan + riwayat + perbaikan dokumen
- [ ] Halaman Dokumen
- [ ] Peta Saya
- [ ] Profil
- [ ] Notifikasi

## Fase 6 · Area Admin: data & pengajuan (G2-E)
- [ ] Dashboard (kartu, grafik per kecamatan, peta mini)
- [ ] Data Wakaf: CRUD + filter + impor Excel (template disediakan)
- [ ] Daftar pengajuan + detail + validasi per dokumen
- [ ] Aksi KUA: sahkan AIW / minta perbaikan / tolak
- [ ] Aksi BPN: verifikasi / minta perbaikan / tolak / catat sertifikat terbit
- [ ] Halaman Sertifikat
- [ ] Peta admin

## Fase 7 · Konten, pengguna, laporan (G2-F)
- [ ] Kelola Konten: artikel (Trix), persyaratan, panduan, FAQ, unduhan, pengaturan
- [ ] Kelola Pengguna: buat petugas BPN/KUA, nonaktifkan, reset password
- [ ] Pesan Masuk
- [ ] Laporan: filter kecamatan/status/periode, tabel + grafik, ekspor Excel, ekspor PDF ber-kop

## Fase 8 · Integrasi & pemolesan (G3)
- [ ] Skenario end-to-end (lihat 09) lulus
- [ ] Audit akses semua rute per role
- [ ] Tampilan HP semua halaman
- [ ] Halaman error 403/404/419/500
- [ ] Meta title/description, favicon
- [ ] README proyek (cara install & menjalankan)
- [ ] Catatan deploy
