# Catatan Keputusan & Perubahan

Format: tanggal, apa yang diputuskan atau diubah, dan alasannya. Entri terbaru di atas.

## 2026-10-23 (Docs & file AI environment tidak lagi di-commit)
- **Folder `docs/` dikeluarkan dari repo** (ditambahkan ke `.gitignore` dan dihapus dari index). Berkasnya tetap ada di disk dan tetap jadi sumber spesifikasi/progres lokal; riwayat lamanya masih ada di git.
- `.gitignore` juga ditambah `/.ai`, `/.gemini`, `/GEMINI.md`, `/.github/copilot-instructions.md`, melengkapi file AI environment yang sudah diabaikan (`.claude`, `.cursor`, `AGENTS.md`, `CLAUDE.md`, `boost.json`, dll.).

## 2026-10-23 (Nomor AIW & sertifikat tidak boleh ganda)
- **Nomor AIW dan nomor sertifikat kini unik.** *Alasan:* keduanya nomor resmi dari dokumen fisik dan diisi manual, sehingga salah ketik bisa membuat dua tanah memakai nomor yang sama tanpa ketahuan.
- Satu aturan `App\Rules\NomorBelumDipakai` (memakai `TanahWakaf::pemakaiNomor()`) dipakai di modal **Sahkan AIW**, modal **Catat Sertifikat Terbit**, form **Data Wakaf**, dan di `PengajuanWorkflow` sebagai sumber kebenaran alur. Pesan galat menyebut kode dan nama tanah pemilik nomor. Perbandingan tidak membedakan huruf besar-kecil dan spasi di tepi; nomor disimpan dalam bentuk yang sudah dirapikan.
- **Impor Excel** menandai baris yang nomornya sudah ada di database atau sama dengan baris sebelumnya di berkas yang sama.
- **Indeks unik di database** (`tanah_wakafs.nomor_aiw`, `nomor_sertifikat`) sebagai lapisan terakhir bila dua petugas menyimpan nomor sama bersamaan. Kolom tetap nullable. Data yang ada sudah diperiksa: tidak ada nomor ganda.
- Factory memakai nomor acak unik agar seeder dan test tidak bentrok dengan indeks baru.
- Nomor tetap **diisi manual**, tidak dibuat otomatis oleh sistem.

## 2026-10-22 (Data demo pengajuan)
- **Pengajuan demo kini punya dokumen persyaratan** (PDF contoh di disk privat). *Alasan:* sebelumnya seeder membuat pengajuan berstatus Diajukan tanpa dokumen, padahal tombol Verifikasi/Sahkan AIW baru aktif setelah semua dokumen persyaratan ditandai valid, sehingga tombol itu tidak pernah bisa dipakai di data demo. Pengajuan yang sudah terbit diberi dokumen berstatus Valid.
- **Ditambah 1 pengajuan AIW yang menunggu KUA** (Masjid Al-Falah, Kec. Maro Sebo Ulu) agar alur KUA bisa dicoba. Jumlah tanah acak dikurangi satu, jadi total tetap 125 bidang (90 bersertifikat).

## 2026-10-22 (Pemolesan UI/UX area admin)
- **Judul halaman admin diseragamkan** lewat `<x-admin.judul-halaman>`: tautan kembali selalu di atas judul (sebelumnya ada yang berupa tombol di kanan), tombol aksi selalu di kanan. Form pengguna tidak lagi di tengah, rata kiri seperti form lain.
- **Input berkas bawaan peramban diganti `<x-admin.unggah-berkas>`.** *Alasan:* teks "Choose file / No file chosen" mengikuti bahasa peramban (Inggris) dan tidak bisa diterjemahkan. Komponen baru menampilkan nama berkas, menerima seret-lepas, dan menunjukkan progres unggah.
- **Tombol hapus di tiap baris dibuat netral** (abu-abu, merah saat disorot) dan semua tombol ikon diberi tooltip. Tombol "Tolak" di halaman pemeriksaan tidak lagi merah penuh. *Alasan:* warna merah di setiap baris menarik perhatian ke aksi yang paling jarang dan paling berisiko; konfirmasi tetap ada.
- **Log Aktivitas lebih ringkas:** tabel perubahan dilipat bila lebih dari 4 kolom (mis. data baru), dan deskripsi bawaan `created/updated/deleted` diganti nama subjek (jenisnya sudah tampil di badge).
- **Form panjang** (Data Wakaf, Pengaturan Situs) memakai `<x-admin.bilah-simpan>` yang menempel di bawah layar.
- Paginasi: "Menampilkan 1 kepada 10" → "1 sampai 10" (`lang/id.json`).
- **Tabel di dalam kartu tidak lagi menempel ke tepi.** Bawaan Flux membuat sel pertama/terakhir tanpa padding (`first:ps-0 last:pe-0`), dan override per halaman (`[&_td]:px-3`) kalah oleh aturan itu. Kini satu aturan CSS di `app.css` (`.kartu [data-flux-cell]`) memberi jarak 1,25rem (sama dengan `p-5`), judul kolom berlatar tipis, dan paginasi ikut berjarak. Pembungkus yang tidak seragam (`p-5`, `px-4 pb-4`, `overflow-x-auto`) dihapus agar semua tabel memakai pola yang sama.

## 2026-10-22 (Fase 8: audit akses & end-to-end)
- **`PastikanRole` dan `verified` didaftarkan sebagai persistent middleware Livewire** (`AppServiceProvider`). *Alasan:* aksi Livewire dikirim ke `/livewire/update`, bukan ke rute halaman, sehingga middleware buatan sendiri tidak ikut dijalankan ulang. Sebelumnya pembatas role hanya berlaku saat halaman dibuka; aksi tetap aman karena dicek Policy/`can:`, tetapi kini dibatasi di dua lapis.
- **Audit akses otomatis** (`tests/Feature/Akses/AuditRuteTest.php`) membaca daftar rute dari router, jadi rute baru yang lupa diberi pengaman langsung gagal di test.
- **Skenario uji manual end-to-end kini juga berjalan sebagai test** (`tests/Feature/EndToEnd`).

## 2026-10-22 (spatie/laravel-permission dihapus)
- **Role cukup satu kolom `users.role`** (enum `Role`), menggantikan spatie/laravel-permission. *Alasan:* rolenya hanya 4 dan tetap, setiap akun satu role, dan daftar hak tidak pernah diubah lewat UI. Paket itu menambah 5 tabel dan cache tanpa manfaat. Aturan berbasis data tetap di Policy.
- **Hak akses** (`laporan.ekspor`, `konten.kelola`, dst.) didaftarkan sebagai Gate di `App\Support\HakAkses`. Semua pemakaian `can(...)`/`@can` tetap sama; middleware rute `permission:` diganti `can:`.
- **Middleware `role:`** kini `App\Http\Middleware\PastikanRole` (sintaks sama: `role:admin_bpn|bpn|kua`).
- **`role` tidak fillable**, jadi tidak bisa disuntik lewat form registrasi atau profil. Registrasi mengisinya eksplisit sebagai `nazir`.
- **`RoleSeeder` dan migrasi tabel permission dihapus.** Kolom `role` ditambahkan langsung di migrasi kolom Jariyah pada `users`, karena aplikasi belum pernah dideploy. Database lokal perlu `php artisan migrate:fresh --seed`.
- **Jumlah tabel** turun dari 28 menjadi 23 (ditambah tabel `migrations`).

## 2026-10-22 (Fase 5 & 7 selesai)
- **Area Nazir:** wizard AIW (jenis → data tanah → lokasi → ringkasan) dan wizard sertifikat (pilih tanah yang memenuhi syarat). Dokumen diunggah di halaman detail pengajuan dan langsung tersimpan saat dipilih. Saat `perlu_perbaikan`, hanya dokumen `tidak_valid` atau yang belum ada yang bisa diganti; aturan ini ditegakkan di UI dan server.
- **Hapus draf dan ubah data tanah AIW dipindah ke `PengajuanWorkflow`** (`hapusDraf`, `ubahDataTanah`), supaya aturan bisnis tidak tersebar di komponen.
- **Peta di area Nazir/admin** mengarahkan popup ke halaman internal, karena tanah yang belum dipublikasikan memberi 404 di halaman publik.
- **Laporan:** semua filter (halaman, Excel, PDF) melewati satu kelas `App\Support\FilterLaporan`, yang selalu menerapkan `forUser` dan mengunci KUA ke kecamatannya. Ekspor tidak pernah memuat NIK wakif.
- **Excel memakai maatwebsite/excel v4**: kelas ekspor wajib mengimplementasikan `Concerns\Export`.
- **Purifier mengizinkan tag keluaran Trix** (`h2`, `h3`, `blockquote`, `pre`, `del`, `s`). `<script>` dan atribut berbahaya tetap dibuang.
- **Pengguna:** form admin hanya bisa membuat role `admin_bpn`, `bpn`, `kua`. Akun Nazir yang diedit admin hanya bisa diubah nama, email, HP, dan status aktif; bila email Nazir diganti, verifikasi email direset. Admin tidak bisa menurunkan role atau menonaktifkan dirinya sendiri.
- **Persyaratan yang sudah dipakai dokumen pengajuan tidak bisa dihapus**; admin ditawari menjadikannya "tidak wajib".

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
