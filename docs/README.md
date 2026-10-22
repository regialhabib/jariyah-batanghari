# Dokumentasi Proyek Jariyah Batang Hari

**Jariyah Batang Hari** adalah sistem informasi berbasis web untuk pengelolaan dan monitoring tanah wakaf di Kabupaten Batang Hari, dikembangkan untuk Kantor Pertanahan Kabupaten Batang Hari.

Folder ini adalah sumber kebenaran untuk rancangan, keputusan, dan progres proyek. Setiap perubahan keputusan dicatat di sini **sebelum** kode diubah.

## Daftar dokumen

| File | Isi |
|---|---|
| [01-rancangan-halaman.md](01-rancangan-halaman.md) | Spesifikasi tiap halaman dari mockup PDF + koreksi dan halaman tambahan |
| [02-peran-hak-akses.md](02-peran-hak-akses.md) | Role, cakupan data, dan matriks hak akses |
| [03-alur-pengajuan.md](03-alur-pengajuan.md) | Jenis pengajuan, status, dan transisi (state machine) |
| [04-model-data.md](04-model-data.md) | Tabel database, kolom, relasi, dan enum |
| [05-arsitektur.md](05-arsitektur.md) | Teknologi, paket, struktur folder, rute, komponen, dan tema |
| [06-keamanan.md](06-keamanan.md) | Aturan keamanan dan privasi |
| [07-tahapan-pengerjaan.md](07-tahapan-pengerjaan.md) | Fase kerja, checklist progres, dan strategi eksekusi paralel |
| [08-kebutuhan-data.md](08-kebutuhan-data.md) | Data dan aset yang perlu disiapkan pemilik proyek |
| [09-pengujian.md](09-pengujian.md) | Strategi test otomatis dan skenario uji manual |
| [10-deploy.md](10-deploy.md) | Pemasangan di server produksi, backup, pembaruan versi, daftar periksa go-live |
| [komponen-peta.md](komponen-peta.md) | Kontrak komponen peta `<x-peta>` dan `<x-peta-pilih>` |
| [CHANGELOG.md](CHANGELOG.md) | Catatan keputusan dan perubahan dari waktu ke waktu |

**Rancangan asli:** [DESAIN_ATAU_GAMBARAN_AWAL_PEMBUATAN_WEBSITE_JARIYAH_BATANG_HARI.pdf](DESAIN_ATAU_GAMBARAN_AWAL_PEMBUATAN_WEBSITE_JARIYAH_BATANG_HARI.pdf)

## Keputusan utama

| Topik | Keputusan |
|---|---|
| Framework | Laravel 13 |
| Frontend | Livewire 4 (single-file components) + Flux UI + Tailwind CSS v4, auth via Fortify |
| Database | MySQL via Laragon, nama database `jariyah_batanghari` |
| Registrasi publik | Hanya untuk **Nazir**. Akun BPN dan KUA dibuat oleh Admin BPN. |
| Peran KUA | Memvalidasi dan mengesahkan **AIW** (tahap 1) di kecamatannya |
| Peran BPN | **Satu-satunya** yang memverifikasi pengajuan sertifikat dan mencatat sertifikat terbit |
| Data lokasi | Titik koordinat wajib + polygon batas bidang opsional |
| Konten publik | Bisa diedit Admin BPN dari dashboard (tidak di-hardcode) |
| Laporan | Tampil di layar + ekspor Excel (.xlsx) dan PDF |
| Bahasa | Antarmuka berbahasa Indonesia. Nama tabel/kolom domain memakai istilah Indonesia (`tanah_wakafs`, `pengajuans`), kode umum mengikuti konvensi Laravel. |

## Lingkungan pengembangan

| Komponen | Versi | Catatan |
|---|---|---|
| PHP | 8.4.26 | Ekstensi pdo_mysql, gd, zip, intl, fileinfo sudah tersedia |
| Composer | 2.10.3 (Laragon) | Pakai `php C:/laragon/bin/composer/composer.phar`, karena Composer global 2.4.4 butuh admin untuk update |
| Node / npm | 24.12 / 11.6 | |
| Server lokal | Laragon | MySQL dan virtual host |
