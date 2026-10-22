# 08 · Kebutuhan Data & Aset

Pengembangan memakai **data contoh/placeholder** lebih dulu. Daftar di bawah perlu disiapkan pemilik proyek sebelum aplikasi dipakai sungguhan. Kolom Status diperbarui saat data diterima.

| # | Kebutuhan | Format | Dipakai di | Status |
|---|---|---|---|---|
| 1 | Logo resmi Jariyah Batang Hari | SVG/PNG transparan | Header, footer, login, PDF | Belum ada, pakai placeholder |
| 2 | Foto masjid/tanah wakaf untuk hero & banner | JPG/WebP, min. 1600 px | Beranda, Tentang, Informasi | Belum ada |
| 3 | Daftar desa/kelurahan per kecamatan (+ kode Kemendagri) | Excel/CSV | Seeder wilayah | Belum ada, sementara pakai daftar contoh |
| 4 | Batas wilayah Kabupaten Batang Hari | GeoJSON (disederhanakan), simpan di `public/geo/batang-hari.geojson` | Garis batas di peta (otomatis tampil bila file ada) | Belum ada |
| 5 | Data tanah wakaf yang sudah ada (±125 bidang) | Excel sesuai template impor | Impor data awal | Belum ada |
| 6 | Kontak resmi: alamat, telepon, email, jam operasional | Teks | Footer, Kontak | Belum ada (di mockup: Jl. Jend. Sudirman, Muara Bulian) |
| 7 | Isi artikel Informasi Wakaf (7 topik) | Teks | Informasi Wakaf | Pakai draf awal, perlu ditinjau |
| 8 | Daftar FAQ | Teks | FAQ | Pakai draf awal, perlu ditinjau |
| 9 | Template formulir: Formulir Permohonan, Contoh AIW, Contoh Surat Kuasa, Contoh Surat Pernyataan, Brosur | PDF/DOCX | Persyaratan, Panduan | Belum ada |
| 10 | Kop surat Kantor Pertanahan | Gambar/teks | Laporan PDF | Belum ada |
| 11 | Daftar petugas BPN dan KUA per kecamatan (nama, email) | Excel | Pembuatan akun | Belum ada |
| 12 | Teks Syarat & Ketentuan | Teks | Registrasi | Belum ada |

## Template impor tanah wakaf (Excel)
Kolom: `nama`, `kecamatan`, `desa`, `alamat`, `luas_m2`, `jenis_penggunaan`, `wakif_nama`, `nama_nazir`, `status_sertifikat`, `nomor_aiw`, `tanggal_aiw`, `nomor_sertifikat`, `tanggal_sertifikat`, `latitude`, `longitude`.

File template bisa diunduh dari halaman Admin → Data Wakaf → Impor.
