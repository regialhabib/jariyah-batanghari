# Akun Demo

Akun untuk mencoba aplikasi di komputer lokal (http://localhost:8000). Dibuat oleh `UserDemoSeeder` saat menjalankan `php artisan migrate:fresh --seed`.

> **Hanya untuk pengembangan lokal.** Seeder ini tidak berjalan di production, dan tidak boleh ada akun `@jariyah.test` di server produksi (lihat [10-deploy.md](10-deploy.md)).

**Password semua akun: `password`**

Petugas bisa login memakai **email atau username**. Nazir login memakai email.

## Petugas

| Role | Email | Username | Yang bisa dicoba |
|---|---|---|---|
| Admin BPN | `admin@jariyah.test` | `admin` | Semua menu, termasuk Pengguna, Kelola Konten, dan Log Aktivitas |
| Petugas BPN | `bpn@jariyah.test` | `bpn` | Memverifikasi pengajuan sertifikat, mencatat sertifikat terbit, data wakaf semua kecamatan |

## Petugas KUA (satu akun per kecamatan)

KUA mengesahkan pengajuan AIW dan hanya melihat data kecamatannya sendiri.

| Kecamatan | Email | Username |
|---|---|---|
| Bajubang | `kua.bajubang@jariyah.test` | `kuabajubang` |
| Batin XXIV | `kua.batinxxiv@jariyah.test` | `kuabatinxxiv` |
| Maro Sebo Ilir | `kua.maroseboilir@jariyah.test` | `kuamaroseboilir` |
| Maro Sebo Ulu | `kua.maroseboulu@jariyah.test` | `kuamaroseboulu` |
| Mersam | `kua.mersam@jariyah.test` | `kuamersam` |
| Muara Bulian | `kua.muarabulian@jariyah.test` | `kuamuarabulian` |
| Muara Tembesi | `kua.muaratembesi@jariyah.test` | `kuamuaratembesi` |
| Pemayung | `kua.pemayung@jariyah.test` | `kuapemayung` |

Saran: pakai **KUA Maro Sebo Ulu**. Pengajuan AIW demo yang menunggu pemeriksaan ada di kecamatan ini.

## Pengajuan demo yang bisa langsung diperiksa

Semua dokumen persyaratan sudah terlampir (PDF contoh) dan berstatus *Menunggu Pemeriksaan*.

| Nomor | Jenis | Pemeriksa | Login sebagai |
|---|---|---|---|
| JRY-2026-0002 | Sertifikat (tanah belum bersertifikat) | BPN | `bpn@jariyah.test` atau `admin@jariyah.test` |
| JRY-2026-0004 | Pembuatan AIW | KUA Maro Sebo Ulu | `kua.maroseboulu@jariyah.test` |

Buka **Pengajuan → Perlu Diperiksa**, tandai setiap dokumen **Valid**, lalu tombol **Verifikasi** (BPN) atau **Sahkan AIW** (KUA) aktif. JRY-2026-0001 dan 0003 sudah selesai (sertifikat terbit), jadi hanya bisa dilihat.

## Nazir

| Nama | Email | Keterangan |
|---|---|---|
| Ahmad | `nazir@jariyah.test` | Sudah punya beberapa data tanah dan pengajuan |
| Hasan Basri | `nazir2@jariyah.test` | Nazir kedua, untuk menguji bahwa Nazir tidak bisa melihat data Nazir lain |

Akun Nazir baru juga bisa dibuat sendiri lewat halaman **Daftar** (`/register`).

## Catatan

- Kalau database di-reset (`migrate:fresh --seed`), semua akun di atas dibuat ulang dengan password `password`.
- Kalau password salah satu akun diganti lewat halaman Profil, password itu berlaku sampai database di-reset.
- Untuk berganti role, keluar dulu lewat tombol **Keluar** di sidebar atau di menu profil kanan atas.
