# 06 · Keamanan & Privasi

## Autentikasi
- Login memakai email **atau** username + password. Login dibatasi 5 percobaan per menit per kombinasi email+IP (bawaan starter kit).
- Akun dengan `is_active = false` ditolak saat login, dengan pesan "Akun Anda dinonaktifkan. Hubungi Admin BPN."
- Nazir wajib verifikasi email sebelum bisa mengakses `/nazir/*`.
- Registrasi **tidak menerima input role**. Role `nazir` ditetapkan di server, dan ada test untuk memastikannya.
- Password minimal 8 karakter (`Password::defaults()`).

## Otorisasi
- Middleware role per area dan Policy per model (lihat 02).
- KUA dibatasi ke kecamatannya lewat query scope **dan** Policy (dua lapis).
- Transisi status hanya bisa dilakukan lewat `PengajuanWorkflow`, yang memanggil `Gate::authorize` sendiri.
- Aksi Livewire selalu memanggil `$this->authorize(...)`. Properti publik Livewire dianggap input tak tepercaya, jadi ID yang dikirim klien selalu divalidasi ulang.

## Berkas
- **Dokumen pengajuan:**
  - disimpan di disk `local` (privat, `storage/app/private/pengajuan/{id}/`) dengan nama acak
  - diunduh lewat `DokumenController` + Policy
  - yang berhak: pemilik (Nazir), BPN/Admin, dan KUA di kecamatan terkait
- **Validasi unggahan:** `mimes:pdf,jpg,jpeg,png`, `max:5120` KB. Pengecekan MIME dilakukan dari isi file, bukan ekstensi.
- **Unggahan konten publik** (foto, formulir) disimpan di disk `public`. Formulir: pdf/doc/docx/xls/xlsx. Gambar: jpg/png/webp.

## Privasi data
- **Tidak pernah tampil di halaman publik/API publik:** NIK (Nazir dan Wakif), No. HP, email Nazir, dokumen.
- `/api/peta` hanya mengirim field yang tercantum di `TanahWakaf::kolomPublik()`.
- Daftar Nazir publik hanya menampilkan nama, kecamatan, dan jumlah tanah yang dikelola.

## Form publik
- Form kontak dilindungi rate limit (3 kiriman per 10 menit per IP), field honeypot tersembunyi, dan validasi panjang pesan.
- Konten HTML dari Trix dibersihkan sebelum ditampilkan, dengan whitelist tag (sanitasi di server saat disimpan).

## Audit
Aktivitas berikut dicatat di activity log (siapa, apa, kapan, nilai lama → baru):
- perubahan data tanah
- pengguna dibuat/diubah/dinonaktifkan
- perubahan konten
- semua transisi pengajuan

Admin BPN bisa melihat log ini.

## Produksi (catatan deploy)
- `APP_DEBUG=false`, HTTPS wajib, `SESSION_SECURE_COOKIE=true`
- Backup database dan `storage/app/private` secara berkala
- Jalankan `php artisan storage:link`
- Queue worker untuk notifikasi email (atau `QUEUE_CONNECTION=sync` jika hosting terbatas)
