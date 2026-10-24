# 02 · Peran & Hak Akses

## Daftar role

| Role | Nama tampilan | Cara mendapat akun | Cakupan data |
|---|---|---|---|
| `admin_bpn` | Admin BPN | Dibuat oleh seeder (akun awal) | Semua data |
| `bpn` | Petugas BPN | Dibuat oleh Admin BPN | Semua kecamatan |
| `kua` | Petugas KUA | Dibuat oleh Admin BPN, **wajib terikat 1 kecamatan** | Hanya kecamatannya |
| `nazir` | Nazir | Registrasi publik + verifikasi email | Hanya data miliknya |

Ketentuan:
- Registrasi publik **selalu** membuat role `nazir`. Role tidak pernah diambil dari input form.
- Akun yang `is_active = false` tidak bisa login. Admin bisa menonaktifkan akun petugas maupun Nazir.

## Implementasi
- **Tanpa paket tambahan.** Role disimpan di kolom `users.role` (cast ke enum `App\Enums\Role`). Setiap akun hanya punya satu role.
- **Hak akses** di tabel bawah didaftarkan sebagai Gate oleh `App\Support\HakAkses` (dipanggil dari `AppServiceProvider`). Cara pakainya: `$user->can('laporan.ekspor')`, `@can('konten.kelola')`, atau middleware `can:pengguna.kelola`.
- **Middleware rute:**
  - `/nazir/*` memakai `auth`, `verified`, `role:nazir`
  - `/admin/*` memakai `auth`, `role:admin_bpn|bpn|kua`
  - `role:` adalah middleware `App\Http\Middleware\PastikanRole`. Rute admin tertentu ditambah `can:<hak>`.
- **Policy per model** untuk aturan berbasis data: `TanahWakafPolicy`, `PengajuanPolicy`, `PengajuanDokumenPolicy`, `UserPolicy`.
- **Cakupan KUA:** query scope `forUser($user)` di model `TanahWakaf` dan `Pengajuan`. Untuk KUA, scope ini memfilter `desa.kecamatan_id = user.kecamatan_id`. Untuk Nazir, scope memfilter `nazir_id = user.id`.
- **Redirect setelah login:** `nazir` diarahkan ke `/nazir/dashboard`, role lainnya ke `/admin/dashboard`.

## Daftar hak akses (`App\Support\HakAkses::DAFTAR`)

| Hak | admin_bpn | bpn | kua | nazir |
|---|:-:|:-:|:-:|:-:|
| `dashboard.admin` | ✔ | ✔ | ✔ | |
| `tanah.lihat-semua` | ✔ | ✔ | (kecamatannya) | |
| `tanah.kelola` (tambah/ubah/hapus) | ✔ | ✔ | | |
| `tanah.impor` | ✔ | ✔ | | |
| `pengajuan.buat` | | | | ✔ |
| `pengajuan.lihat-semua` | ✔ | ✔ | (kecamatannya) | |
| `pengajuan.validasi-aiw` | | | ✔ | |
| `pengajuan.verifikasi-sertifikat` | ✔ | ✔ | | |
| `sertifikat.catat-terbit` | ✔ | ✔ | | |
| `laporan.lihat` / `laporan.ekspor` | ✔ | ✔ | (kecamatannya) | |
| `pengguna.kelola` | ✔ | | | |
| `konten.kelola` | ✔ | | | |
| `pesan.lihat` | ✔ | ✔ | | |

**Catatan:** Admin BPN tidak ikut memvalidasi AIW. Itu wewenang KUA kecamatan. Jika suatu kecamatan belum punya akun KUA, Admin BPN perlu membuatkannya dulu.

## Menu sidebar per role

| Menu | admin_bpn | bpn | kua | nazir |
|---|:-:|:-:|:-:|:-:|
| Dashboard | ✔ | ✔ | ✔ | ✔ |
| Data Wakaf / Data Wakaf Saya | ✔ | ✔ | ✔ (lihat saja) | ✔ |
| Pengajuan | ✔ | ✔ | ✔ | ✔ |
| Verifikasi (sertifikat) | ✔ | ✔ | | |
| Validasi AIW | | | ✔ | |
| Sertifikat | ✔ | ✔ | | |
| Dokumen | | | | ✔ |
| Peta Wakaf | ✔ | ✔ | ✔ | ✔ |
| Laporan | ✔ | ✔ | ✔ | |
| Pengguna | ✔ | | | |
| Kelola Konten | ✔ | | | |
| Pesan Masuk | ✔ | ✔ | | |
| Profil | ✔ | ✔ | ✔ | ✔ |
