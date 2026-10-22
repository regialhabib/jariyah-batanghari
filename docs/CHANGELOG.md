# Catatan Keputusan & Perubahan

Format: tanggal, apa yang diputuskan atau diubah, dan alasannya. Entri terbaru di atas.

## 2026-10-22 (Fase 2–4 selesai)
- **Login menerima email atau username** (tanpa beda huruf besar/kecil). Pesan "akun dinonaktifkan" baru muncul setelah password benar, supaya status akun tidak bisa ditebak. Teks pesan disatukan di `FortifyServiceProvider::PESAN_AKUN_NONAKTIF` dan dipakai juga oleh middleware `PastikanAkunAktif`.
- **Registrasi selalu memberi role `nazir`**, tidak bisa disuntik dari form.
- **Unduhan publik dibatasi `throttle:30,1`**, peta API `throttle:60,1`. Unduh semua dokumen dikemas ZIP.
- **Statistik "Kecamatan Terdaftar" di beranda** menghitung kecamatan yang punya tanah wakaf terpublikasi.
- **Detail tanah publik tidak menampilkan nomor sertifikat**, hanya tanggal sertifikat (sesuai spesifikasi H5). NIK wakif tidak pernah tampil.
- **Komponen peta** `<x-peta>` dan `<x-peta-pilih>` didokumentasikan di [komponen-peta.md](komponen-peta.md). Geoman JS dimuat terpisah (lazy chunk). Batas kabupaten tampil otomatis bila file `public/geo/batang-hari.geojson` tersedia.
- **Pint tidak memformat `resources/views`** (`pint.json` → `notPath`), karena Pint merusak gaya kelas anonim di SFC Blade.

## 2026-10-22 (Fase 0–1 selesai)
- **Stack nyata mengikuti starter kit Livewire terbaru:** Laravel 13, Livewire 4 single-file components di `resources/views/pages/*`, Fortify untuk auth, dan Flux UI. Rencana awal (komponen class di `app/Livewire`) disesuaikan, lihat 05.
- **Test memakai PHPUnit** (bawaan starter kit), bukan Pest. *Alasan:* mengikuti kode yang sudah ada, tanpa konversi.
- **2FA dan passkey tidak diaktifkan.** Fitur auth yang aktif: registrasi, verifikasi email, konfirmasi password. Bisa diaktifkan lagi nanti untuk akun petugas.
- **Mode gelap dihapus.** Situs memakai desain hijau terang sesuai mockup, dan menu "Appearance" di pengaturan dihapus.
- **Rute Daftar Nazir publik menjadi `/daftar-nazir`**, karena prefix `/nazir` dipakai area Nazir.
- **Menu "Profil" memakai halaman settings starter kit** (`/settings/profile`, `/settings/security`).
- **Notifikasi `PengajuanMasuk`** dikirim ke KUA kecamatan terkait (AIW) atau ke semua petugas BPN (sertifikat) saat pengajuan dikirim.
- **CSS Geoman disajikan dari `public/vendor/leaflet-geoman`**, karena data URI di CSS-nya merusak build Vite.
- **Composer global (2.4.4) tidak bisa diupdate tanpa admin.** Proyek memakai Composer Laragon 2.10.3.
- **Data desa dan koordinat di seeder adalah contoh.** Data resmi menyusul (08).

## 2026-10-22
- **Dokumentasi awal dibuat** di folder `docs/` dari rancangan PDF dan sesi perencanaan.
- **Teknologi:** Laravel + Blade + Livewire + Tailwind, database MySQL (Laragon).
- **Registrasi hanya untuk Nazir.** Akun BPN dan KUA dibuat Admin BPN. *Alasan:* dropdown Role di mockup memungkinkan siapa pun mendaftar sebagai admin.
- **KUA memvalidasi AIW, BPN memverifikasi sertifikat.** *Alasan:* menurut PDF hanya BPN yang boleh memverifikasi. KUA diberi peran sesuai kewenangannya atas Akta Ikrar Wakaf.
- **Lokasi:** titik koordinat wajib, polygon batas opsional.
- **Konten publik bisa diedit admin.** Laporan bisa diekspor ke Excel dan PDF.
- **Koreksi mockup:** form registrasi (Email ganda, Role dihapus), navigasi diseragamkan, typo "Wakap" dan "Datatt", menu Status Pengajuan Nazir digabung ke Pengajuan.
- **PDF rancangan dipindah** dari root proyek ke `docs/`.
- **Rute dipecah per area** (`publik.php`, `nazir.php`, `admin.php`) supaya pengerjaan paralel oleh beberapa agen tidak bentrok.
