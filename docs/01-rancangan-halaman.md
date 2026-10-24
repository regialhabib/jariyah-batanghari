# 01 · Rancangan Halaman

Dokumen ini adalah spesifikasi tiap halaman. Sumbernya mockup di PDF rancangan awal, ditambah koreksi yang sudah disepakati. Nomor halaman (H1–H11) mengikuti urutan di PDF.

## Gaya visual umum
- **Warna:** hijau tua sebagai warna utama (±`#1F6B3A`), hijau muda sebagai aksen, latar putih/krem lembut, dan ornamen daun di sudut.
- **Ilustrasi:** masjid berkubah hijau dengan pohon kelapa/sawit dan latar alam.
- **Komponen:** kartu putih bersudut membulat dengan bayangan lembut dan ikon hijau tua di dalam kotak/bulatan.
- **Header publik:** logo "JARIYAH / BATANG HARI" di kiri, menu di tengah, lalu ikon cari, tombol **Login** (outline) dan **Daftar** (solid) di kanan.
- **Judul halaman dalam:** banner tipis berisi judul besar dan breadcrumb (`Beranda > Nama Halaman`).
- **Footer** (dari H3): logo + tagline, kolom Navigasi, kolom Kontak Kami, ikon sosial media, dan copyright.

## Navigasi publik (seragam di semua halaman)
`Beranda · Tentang · Informasi Wakaf · Persyaratan · Peta Wakaf · Panduan · FAQ · Kontak`

Di layar HP, menu berubah jadi hamburger dan tombol Login/Daftar masuk ke dalam menu.

---

## H1 · Beranda (`/`)
- **Hero kiri:**
  - teks "Selamat Datang di", judul **JARIYAH BATANG HARI**
  - subjudul "Sistem Informasi Pengelolaan dan Monitoring Tanah Wakaf Kabupaten Batang Hari" dan paragraf deskripsi
  - tombol **Daftar Sekarang** (solid) dan **Login** (outline)
- **Hero kanan:** ilustrasi masjid dan teks latin "Wakaf untuk Masa Depan Umat"
- **4 kartu statistik** (dihitung live dari database, hanya data publik):
  1. Total Tanah Wakaf (bidang)
  2. Bersertifikat (bidang)
  3. Belum Bersertifikat (bidang)
  4. Kecamatan Terdaftar
- **Banner "Persyaratan Pengajuan Tanah Wakaf"** dengan tombol **Lihat Semua Persyaratan →**
- *Tambahan:* footer standar

## H2 · Tentang Jariyah (`/tentang`)
- Banner judul "Tentang Jariyah" + breadcrumb
- Foto di kiri. Di kanan: "Apa itu Jariyah Batang Hari?" dan deskripsi.
- **3 kartu:**
  - **Tujuan**
  - **Manfaat** (daftar poin)
  - **Instansi Terkait**: Kantor Pertanahan (BPN), Kemenag/KUA, Pemerintah Kecamatan, Nazir/Pengguna
- Banner penutup: "Bersama Jariyah Batang Hari, Wakaf Lebih Mudah, Aman dan Terdata."
- Semua teks bisa diedit lewat Kelola Konten → Pengaturan.

## H3 · Informasi Wakaf (`/informasi-wakaf`)
- Banner bergambar masjid, judul "Informasi Wakaf", dan subjudul "Kenali wakaf dan pahami pengelolaannya sebelum melakukan pengajuan."
- **Grid 7 kartu artikel**, masing-masing berisi ikon, judul, ringkasan, dan tautan **Selengkapnya →**:
  Apa Itu Wakaf? · Wakif · Nazir · Akta Ikrar Wakaf (AIW) · Tanah Wakaf · Sertifikat Tanah Wakaf · Kepastian Hukum Wakaf
- **Kartu ke-8:** kutipan "Wakaf bukan hanya tentang melepaskan harta, tetapi tentang menanam kebaikan yang terus tumbuh untuk generasi mendatang."
- **Banner CTA:** "Mari bersama wujudkan pengelolaan wakaf yang amanah dan bermanfaat bagi umat." dengan tombol **Unduh Brosur Wakaf** dan **Lihat Panduan Wakaf**
- *Halaman tambahan:* **Detail artikel** (`/informasi-wakaf/{slug}`) berisi isi lengkap dan daftar artikel lain.

## H4 · Persyaratan (`/persyaratan`)
- Judul "Daftar Persyaratan Pengajuan Tanah Wakaf" dan pengantar
- **3 kolom checklist**, diambil dari tabel `persyaratans` per jenis pengajuan:
  1. **Syarat Pembuatan AIW di KUA Kecamatan**
     - Fotokopi KTP/KK Wakif dan Nazir
     - Fotokopi KTP saksi
     - Surat pernyataan penguasaan fisik bidang tanah
     - Surat asal tanah (jual beli/hibah/waris/sertifikat, dll)
     - Surat pernyataan tidak dalam sengketa
     - Surat pernyataan bersedia menjadi Nazir
     - Surat pernyataan bersedia diaudit bagi Nazir
     - Foto koordinat/geotagging (GPS Camera)
  2. **Syarat Sertifikat Wakaf (tanah yang sudah bersertifikat)**
     - Formulir permohonan
     - Surat kuasa apabila dikuasakan
     - Fotokopi KTP/KK Wakif, Nazir, dan kuasa (jika dikuasakan)
     - Akta Ikrar Wakaf dari KUA Kecamatan
     - Sertifikat asli
     - Surat persetujuan pemasangan tanda batas
     - Surat pernyataan tidak dalam sengketa
     - Foto koordinat/geotagging
  3. **Syarat Sertifikat Wakaf (tanah yang belum bersertifikat)**
     - Formulir permohonan
     - Surat kuasa apabila dikuasakan
     - Fotokopi KTP/KK Wakif, Nazir, dan kuasa (jika dikuasakan)
     - Akta Ikrar Wakaf dari KUA Kecamatan
     - Bukti kepemilikan tanah/alas hak
     - Surat persetujuan pemasangan tanda batas
     - Surat pernyataan tidak dalam sengketa
     - Foto koordinat/geotagging
- **Tombol:** **Lihat Persyaratan Lengkap** (ke Panduan), **Unduh Formulir** (file dari `unduhans`), **Daftar Akun**

## H5 · Peta Wakaf (`/peta-wakaf`)
- **Panel kiri "Filter Pencarian":** cari lokasi/nama tanah wakaf, Kecamatan, Jenis Penggunaan, Status Sertifikat, lalu tombol **Terapkan**
- **Peta di tengah:**
  - toggle **Peta / Satelit**, tombol zoom
  - garis batas Kabupaten Batang Hari
  - marker berwarna sesuai status, dengan legenda
  - **popup marker:** foto, nama, Nazir, Desa, Kecamatan, Luas, Status, dan tombol **Lihat Detail**
- **Panel kanan "Informasi":** gambar, keterangan cara pakai, Total Tanah Wakaf (bidang), "Tersebar di: X Kecamatan, Y Desa/Kelurahan"
- **Warna marker:** hijau = Sudah Bersertifikat, oranye = Belum Bersertifikat, biru = Dalam Proses
- Jika data bidang punya polygon batas, polygon ikut digambar.
- **Privasi:** popup hanya menampilkan nama Nazir, tanpa NIK atau No. HP.

## H6 · Panduan (`/panduan`)
- Judul "Panduan Pengurusan Tanah Wakaf"
- **Kiri: 5 langkah bernomor** (dari tabel `panduan_langkahs`):
  1. Persiapan Dokumen
  2. Pendaftaran Akun
  3. Pengajuan Data Wakaf
  4. Verifikasi dan Validasi
  5. Penerbitan Sertifikat
- **Kanan: "Dokumen Penting"**, yaitu daftar file unduhan (Formulir Permohonan, Contoh Akta Ikrar Wakaf, Contoh Surat Kuasa, Contoh Surat Pernyataan) dan tombol **Unduh Semua Dokumen** (ZIP)

## H7 · Login (`/login`)
- **Panel kiri hijau:** logo, "Selamat Datang di Jariyah Batang Hari", dan "Silakan login untuk mengakses sistem sesuai dengan akun Anda."
- **Form kanan:**
  - Email / Username
  - Password (dengan tombol lihat)
  - Ingat saya, Lupa password?
  - tombol **Login**
  - tautan "Belum punya akun? Daftar di sini"
- Setelah login, pengguna diarahkan sesuai role (lihat 02).

## H8 · Registrasi (`/register`), khusus Nazir
- **Panel kiri hijau:** ilustrasi masjid, "Bergabung bersama Jariyah Batang Hari", dan "Kelola data wakaf, pantau status pengajuan, dan akses informasi dengan lebih mudah."
- **Form (sudah dikoreksi):**

| Kiri | Kanan |
|---|---|
| Nama Lengkap | NIK (16 digit) |
| Email | No. HP |
| Password | Konfirmasi Password |

- Centang "Saya menyetujui Syarat dan Ketentuan yang berlaku", tombol **Daftar**, dan tautan "Sudah punya akun? Login di sini"
- **Koreksi dari PDF:** dropdown Role **dihapus** (akun otomatis ber-role `nazir`), dan kolom "Email" ganda diganti Password/No. HP.

## H9 · Dashboard Admin (BPN & KUA) (`/admin/dashboard`)
- **Sidebar:** avatar + nama instansi + role, lalu menu Dashboard, Data Wakaf, Pengajuan, Verifikasi, Sertifikat, Peta Wakaf, Laporan, Pengguna, Profil. Isi menu menyesuaikan role (lihat 02).
- **Topbar:** judul halaman, lonceng notifikasi, dan dropdown akun
- **Konten:**
  - salam "Selamat Datang, {nama}" dan tanggal hari ini
  - **4 kartu:** Total Tanah Wakaf, Bersertifikat, Belum Bersertifikat, Pengajuan Baru
  - **Grafik batang** "Sebaran Tanah Wakaf per Kecamatan"
  - **Peta mini** "Peta Sebaran Tanah Wakaf" dengan tombol **Lihat Peta Lengkap**
- **Perbedaan BPN dan KUA:**
  - Hanya BPN yang bisa memverifikasi pengajuan sertifikat.
  - KUA hanya melihat data di kecamatannya dan memvalidasi AIW.

## H10 · Dashboard Nazir (`/nazir/dashboard`)
- Header publik dengan avatar Nazir di kanan, ditambah **sidebar**: Dashboard, Data Wakaf Saya, Pengajuan, Dokumen, Peta Wakaf, Profil
- **Koreksi:** menu "Status Pengajuan" digabung ke "Pengajuan".
- **4 kartu:** Data Wakaf Saya (bidang), Pengajuan Diproses, Pengajuan Selesai, Dokumen Belum Lengkap
- **Tabel "Data Terbaru":** No, Nama Tanah Wakaf, Lokasi, Status (badge), Aksi (Detail)
- **Peta mini "Peta Wakaf Saya"** dengan tombol **Lihat Peta**

## H11 · Kontak (`/kontak`)
- **Kiri: info kontak** (dari `pengaturans`): Alamat, Telepon, Email, Jam Operasional
- **Tengah: form "Kirim Pesan"**: Nama Lengkap, Email/No. HP, Pesan, tombol **Kirim Pesan**. Pesan disimpan ke `pesans` dan dilindungi rate limit + honeypot.
- **Kanan: kartu "Kontak Nazir"**: "Butuh bantuan terkait pengajuan wakaf? Hubungi Nazir yang terdaftar di wilayah Anda." dan tombol **Lihat Daftar Nazir**

---

## Halaman tambahan (belum ada di PDF, mengikuti gaya yang sama)

| Halaman | Rute | Isi singkat |
|---|---|---|
| FAQ | `/faq` | Accordion tanya-jawab dari tabel `faqs` |
| Detail Informasi Wakaf | `/informasi-wakaf/{slug}` | Isi artikel lengkap |
| Daftar Nazir | `/daftar-nazir` | Nama Nazir + kecamatan + jumlah tanah yang dikelola, bisa difilter per kecamatan |
| Detail Tanah Wakaf publik | `/peta-wakaf/{kode}` | Info publik satu bidang + peta kecil (tujuan tombol "Lihat Detail") |
| Lupa / Reset Password | `/forgot-password`, `/reset-password/{token}` | Gaya sama dengan Login |
| Verifikasi Email | `/verify-email` | Untuk Nazir baru |
| Area Nazir | `/nazir/*` | Data Wakaf Saya, Pengajuan (daftar/wizard/detail), Dokumen, Peta, Profil |
| Area Admin | `/admin/*` | Data Wakaf, Pengajuan, Verifikasi/Validasi AIW, Sertifikat, Peta, Laporan, Pengguna, Kelola Konten, Pesan Masuk, Profil |
| Error | 403, 404, 419, 500 | Halaman ramah pengguna dengan gaya yang sama |

## Daftar koreksi terhadap PDF
1. Dropdown Role di registrasi dihapus (registrasi hanya untuk Nazir).
2. Kolom "Email" ganda di registrasi diganti Password.
3. Menu navigasi diseragamkan (FAQ dan Kontak selalu ada).
4. Typo: "Wakap" → "Wakaf", tombol "Datatt" → "Daftar".
5. Menu Nazir "Status Pengajuan" digabung ke "Pengajuan".
