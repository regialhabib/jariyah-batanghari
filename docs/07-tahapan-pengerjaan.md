# 07 · Tahapan Pengerjaan & Progres

**Cara pakai:** centang `[x]` saat sebuah butir selesai **dan** sudah diverifikasi (test lulus atau dicek di browser). Catat keputusan baru di [CHANGELOG.md](CHANGELOG.md).

## Strategi eksekusi paralel

Fase 0 dan 1 adalah **fondasi** dan dikerjakan berurutan oleh satu pelaksana (sudah selesai). Setelah itu pekerjaan dibagi ke beberapa agen yang berjalan **paralel**. Setiap agen punya wilayah file sendiri, dan semua halaman sudah tersedia sebagai placeholder.

| Gelombang | Agen | Wilayah file (milik eksklusif) |
|---|---|---|
| **G1** (paralel) | A · Halaman publik | `resources/views/pages/publik/*` (kecuali `peta-wakaf`, `tanah-detail`), `UnduhanController`, `tests/Feature/Publik/*` |
| | B · Auth | `resources/views/pages/auth/*`, `resources/views/layouts/auth/*`, `app/Actions/Fortify/*`, `app/Providers/FortifyServiceProvider.php`, `app/Concerns/*`, `config/fortify.php`, `tests/Feature/Auth/*` |
| | C · Peta | `resources/js/peta.js`, `pages/publik/peta-wakaf`, `pages/publik/tanah-detail`, `PetaDataController`, komponen `resources/views/components/peta*.blade.php`, `tests/Feature/Peta/*` |
| **G2** (paralel) | D · Area Nazir | `resources/views/pages/nazir/*`, `tests/Feature/Nazir/*` |
| | E · Admin: data & pengajuan | `pages/admin/{dashboard, tanah/*, pengajuan/*, sertifikat, peta}`, `app/Imports/*`, `tests/Feature/Admin/{Tanah,Pengajuan}*` |
| | F · Admin: konten, pengguna, laporan | `pages/admin/{konten/*, pengguna/*, laporan, pesan, aktivitas}`, `app/Exports/*`, `LaporanController`, `resources/views/pdf/*`, `resources/js/editor.js`, `tests/Feature/Admin/{Konten,Pengguna,Laporan}*` |
| **G3** | Integrasi (satu pelaksana) | Test end-to-end, perbaikan lintas area, pemolesan |

**Aturan untuk setiap agen:**
1. Baca `docs/` lebih dulu (terutama 01, 02, 03, 05). Dokumen ini adalah spesifikasi.
2. **Jangan mengubah file fondasi**:
   - migrasi, model, enum, policy, `PengajuanWorkflow`, `StatistikWakaf`
   - layout publik/app, komponen bersama, `app.css`, `app.js`
   - file `routes/*`, seeder
   
   Jika perlu perubahan di file fondasi, laporkan di hasil akhir agar pelaksana integrasi yang menerapkannya.
3. Tulis test PHPUnit untuk wilayahnya sendiri di `tests/Feature/{Area}/`.
4. Pastikan `php artisan test` (seluruh suite) lulus sebelum menyatakan selesai.
5. Jangan menjalankan `migrate:fresh` pada database MySQL saat agen lain bekerja, karena test memakai SQLite in-memory.
6. Jangan commit. Pelaksana integrasi yang melakukan commit.
7. Laporkan: file yang dibuat/diubah, test yang dijalankan, dan hal yang belum selesai.

---

## Fase 0 · Setup
- [x] Composer Laragon diupdate ke 2.10.3 (Composer global butuh admin, jadi dipakai `php C:/laragon/bin/composer/composer.phar`)
- [x] Proyek Laravel 13 + starter kit Livewire 4 (Fortify, Flux) di root proyek
- [x] Database `jariyah_batanghari` di MySQL Laragon, `.env` (locale `id`, timezone `Asia/Jakarta`)
- [x] `git init` + commit awal
- [x] Paket composer: permission, activitylog, excel, dompdf, purifier, laravel-lang
- [x] Paket npm: leaflet, geoman, chart.js, trix
- [x] Bahasa Indonesia (`lang/id`, `lang/id.json`)
- [x] Token tema di `app.css` + font Poppins, dark mode dimatikan
- [x] `php artisan test` dan `npm run build` berhasil

## Fase 1 · Fondasi
- [x] Migrasi semua tabel (lihat 04)
- [x] Enum di `app/Enums`
- [x] Model + relasi + cast + scope `forUser()` + `dataPublik()`
- [x] Policy + role (kolom `users.role`) + hak akses Gate (`HakAkses`) + middleware `role:` + `PastikanAkunAktif`
- [x] `PengajuanWorkflow` + `StatistikWakaf` + notifikasi `StatusPengajuanBerubah` & `PengajuanMasuk` (11 test)
- [x] Helper `setting()`, `setting_list()`, `format_luas()`
- [x] Factory semua model utama
- [x] Seeder: Wilayah (8 kecamatan + desa contoh), Role, UserDemo, Persyaratan, Konten, TanahWakafDemo (125 bidang)
- [x] Layout publik + dashboard (sidebar per role, lonceng notifikasi) + komponen bersama
- [x] Rute per area + placeholder untuk semua halaman + smoke test akses (6 test)
- [x] `migrate:fresh --seed` dan `php artisan test` lulus (48 test)

## Fase 2 · Halaman publik (G1-A)
- [x] Beranda
- [x] Tentang
- [x] Informasi Wakaf + detail artikel
- [x] Persyaratan
- [x] Panduan (+ unduh ZIP)
- [x] FAQ
- [x] Kontak (simpan pesan, rate limit, honeypot)
- [x] Daftar Nazir
- [x] Unduhan formulir

## Fase 3 · Autentikasi (G1-B)
- [x] Login dua kolom sesuai mockup, email/username, cek `is_active`
- [x] Registrasi Nazir (NIK, No. HP, S&K, tanpa role)
- [x] Redirect per role setelah login
- [x] Lupa/reset password + verifikasi email dengan gaya yang sama

## Fase 4 · Peta (G1-C)
- [x] Endpoint GeoJSON `/api/peta` + filter
- [x] Komponen `petaWakaf`: toggle Peta/Satelit, marker berwarna, popup, polygon, legenda
- [x] Halaman publik Peta Wakaf + panel filter & informasi
- [x] Detail tanah publik
- [x] Mode mini (dashboard) dan mode pilih lokasi + gambar polygon (wizard)

## Fase 5 · Area Nazir (G2-D)
- [x] Dashboard (kartu, tabel terbaru, peta mini)
- [x] Data Wakaf Saya
- [x] Wizard pengajuan (simpan draf, unggah dokumen, kirim)
- [x] Detail pengajuan + riwayat + perbaikan dokumen
- [x] Halaman Dokumen
- [x] Peta Saya

## Fase 6 · Area Admin: data & pengajuan (G2-E)
- [x] Dashboard (kartu, grafik per kecamatan, peta mini)
- [x] Data Wakaf: daftar + filter + tambah/ubah + impor Excel (template disediakan)
- [x] Daftar pengajuan + detail + validasi per dokumen
- [x] Aksi KUA: sahkan AIW / minta perbaikan / tolak
- [x] Aksi BPN: verifikasi / minta perbaikan / tolak / catat sertifikat terbit
- [x] Halaman Sertifikat
- [x] Peta admin

## Fase 7 · Konten, pengguna, laporan (G2-F)
- [x] Kelola Konten: artikel (Trix), persyaratan, panduan, FAQ, unduhan, pengaturan
- [x] Kelola Pengguna: buat petugas BPN/KUA, nonaktifkan, reset password
- [x] Pesan Masuk
- [x] Log Aktivitas
- [x] Laporan: filter kecamatan/status/periode, tabel + grafik, ekspor Excel, ekspor PDF ber-kop

## Fase 8 · Integrasi & pemolesan (G3)
- [x] Skenario end-to-end (lihat 09) lulus — otomatis di `tests/Feature/EndToEnd`
- [x] Audit akses semua rute per role — `tests/Feature/Akses/AuditRuteTest.php` + persistent middleware Livewire
- [x] Tampilan HP semua halaman (46 halaman di 375 px: tanpa scroll horizontal, tanpa error konsol)
- [x] Halaman error 403/404/419/429/500/503
- [x] Meta title/description, favicon, robots.txt
- [x] README proyek (cara install & menjalankan)
- [x] Catatan deploy ([10-deploy.md](10-deploy.md))

## Pemolesan lanjutan (setelah Fase 8)
- [x] UI/UX area admin: judul halaman seragam, unggah berkas berbahasa Indonesia, tombol hapus netral + tooltip, Log Aktivitas ringkas, bilah simpan menempel ([CHANGELOG](CHANGELOG.md))
- [x] Tabel di dalam kartu tidak menempel ke tepi
- [x] Data demo: pengajuan diberi dokumen contoh + pengajuan AIW untuk KUA ([akun-demo.md](akun-demo.md))
- [x] Pemeriksaan dokumen: tombol Valid/Tidak Valid berwarna + progres pemeriksaan
- [x] Nomor AIW dan nomor sertifikat tidak bisa ganda: validasi di modal Sahkan AIW, modal Catat Sertifikat Terbit, `PengajuanWorkflow`, form Data Wakaf, dan Impor Excel (termasuk ganda di dalam satu berkas), ditambah indeks unik di database. Nomor tetap diisi manual ([CHANGELOG](CHANGELOG.md))

## Berikutnya (belum dikerjakan)
Tidak ada tugas pengembangan terbuka. Yang tersisa adalah data dari kantor ([08-kebutuhan-data.md](08-kebutuhan-data.md)) dan daftar periksa go-live ([10-deploy.md](10-deploy.md)).
