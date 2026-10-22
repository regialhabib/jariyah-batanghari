# 09 · Pengujian

## Test otomatis (PHPUnit)
Jalankan dengan `php artisan test` (PHPUnit). Test memakai SQLite in-memory dan `withoutVite()`, jadi tidak butuh MySQL atau `npm run build`.

| Area | Folder | Yang diuji |
|---|---|---|
| Fondasi | `tests/Unit`, `tests/Feature/Workflow` | Setiap transisi `PengajuanWorkflow`, sah dan tidak sah, beserta efek samping pada tanah; `StatusPengajuan::bisaKe()`; `StatistikWakaf`; penomoran pengajuan |
| Akses | `tests/Feature/Akses` | Matriks rute × role (200/403/redirect); KUA tidak bisa memverifikasi sertifikat; KUA tidak bisa membuka data kecamatan lain; Nazir tidak bisa membuka pengajuan Nazir lain. `AuditRuteTest` mengambil daftar rute dari router: setiap rute `admin.*`/`nazir.*` wajib `auth`, `verified`, `role:`; tamu diarahkan ke login; Nazir 403 di semua rute admin dan petugas 403 di semua rute Nazir; dokumen privat; ekspor laporan per role |
| End-to-end | `tests/Feature/EndToEnd` | Skenario uji manual langkah 2–8 (di bawah) dijalankan otomatis lewat halaman: registrasi → AIW → perbaikan → pengesahan KUA → sertifikat → verifikasi & terbit BPN → status di peta publik, statistik beranda, notifikasi Nazir |
| Auth | `tests/Feature/Auth` | Login email & username; akun nonaktif ditolak; registrasi selalu `nazir` walau ada input `role`; NIK unik 16 digit; redirect per role |
| Publik | `tests/Feature/Publik` | Semua halaman publik 200; statistik beranda benar; `/api/peta` tidak membocorkan NIK/HP; form kontak tersimpan + rate limit |
| Nazir | `tests/Feature/Nazir` | Wizard membuat draf; tidak bisa kirim tanpa dokumen wajib; perbaikan dokumen |
| Admin | `tests/Feature/Admin` | Aksi pemeriksaan; impor Excel; CRUD konten; buat petugas |
| Laporan | `tests/Feature/Admin/LaporanTest.php` | Ekspor Excel & PDF menghasilkan file dengan isi yang sesuai filter |

## Cek tampilan HP
Semua halaman (publik, Nazir, admin, KUA) dibuka di lebar 375 px dengan Edge headless lewat `playwright-core`, lalu diperiksa: tidak ada scroll horizontal, tidak ada error konsol, dan status HTTP benar. Skrip ini alat bantu sekali jalan, bukan dependensi proyek. Ulangi setelah perubahan tampilan besar.

## Uji manual end-to-end
Siapkan data dengan `php artisan migrate:fresh --seed`, lalu jalankan `composer run dev`.

**Akun demo** (dibuat `UserDemoSeeder`, password `password`):
- `admin@jariyah.test`
- `bpn@jariyah.test`
- `kua.muarabulian@jariyah.test` (dan satu akun KUA per kecamatan)
- `nazir@jariyah.test`

**Skenario:**
1. Buka semua halaman publik di desktop dan lebar HP (375 px), lalu bandingkan dengan mockup PDF.
2. Daftar sebagai Nazir baru, verifikasi email (cek `storage/logs` atau Mailpit), lalu login. Hasilnya diarahkan ke dashboard Nazir.
3. Buat pengajuan **AIW** di Muara Bulian: isi data, titik di peta, polygon, dan unggah dokumen, lalu kirim.
4. Login sebagai KUA Muara Bulian. Tandai satu dokumen tidak valid, lalu minta perbaikan.
   - Login sebagai KUA kecamatan lain: pengajuan ini **tidak terlihat**.
5. Sebagai Nazir, ganti dokumen dan kirim ulang. KUA lalu mengesahkan AIW (nomor, tanggal, scan). Tanah muncul di peta publik berwarna **oranye**.
6. Sebagai Nazir, ajukan **sertifikat**. Marker berubah **biru** (proses).
7. Login sebagai BPN: verifikasi, lalu catat sertifikat terbit. Marker berubah **hijau** dan statistik beranda ikut berubah.
8. Pastikan Nazir menerima notifikasi di setiap perubahan status.
9. Sebagai Admin BPN:
   - buat akun KUA baru
   - ubah satu artikel dan FAQ, lalu cek perubahannya tampil di halaman publik
   - ekspor laporan Excel dan PDF
10. Coba akses `/admin/*` sebagai Nazir dan rute verifikasi sebagai KUA. Hasilnya harus **403**.
