# 04 · Model Data

Semua tabel memakai `id` (bigint) dan `timestamps`, kecuali disebut lain. Nama tabel mengikuti pluralisasi Laravel dari nama model Indonesia, dan diset eksplisit lewat `$table` bila perlu.

## Diagram relasi (ringkas)
```
kecamatans 1─* desas 1─* tanah_wakafs *─1 users (nazir)
kecamatans 1─* users (kua)
tanah_wakafs 1─* pengajuans 1─* pengajuan_dokumens *─1 persyaratans
pengajuans 1─* riwayat_pengajuans *─1 users
```

## Wilayah

### `kecamatans`
| Kolom | Tipe | Catatan |
|---|---|---|
| nama | string unique | |
| kode | string(10) nullable | Kode Kemendagri |

**Seed 8 kecamatan:** Bajubang, Batin XXIV, Maro Sebo Ilir, Maro Sebo Ulu, Mersam, Muara Bulian, Muara Tembesi, Pemayung.

### `desas`
| Kolom | Tipe | Catatan |
|---|---|---|
| kecamatan_id | foreignId | |
| nama | string | Unique bersama kecamatan_id |
| jenis | enum `desa`/`kelurahan` | |
| kode | string(13) nullable | |

## Pengguna

### `users` (ditambah dari bawaan Laravel)
| Kolom | Tipe | Catatan |
|---|---|---|
| name | string | |
| username | string unique nullable | Untuk login petugas |
| role | string(20) index, default `nazir` | Enum `Role`: `admin_bpn`, `bpn`, `kua`, `nazir`. Tidak fillable, diisi eksplisit |
| nik | char(16) unique nullable | Wajib untuk Nazir |
| email | string unique | |
| phone | string(20) nullable | |
| password | string | |
| kecamatan_id | foreignId nullable | Wajib untuk role `kua` |
| is_active | boolean default true | |
| email_verified_at, remember_token | | Bawaan |

Role cukup satu kolom karena setiap akun hanya punya satu role dan daftar hak tidak diubah lewat UI (lihat 02).

## Tanah wakaf

### `tanah_wakafs`
| Kolom | Tipe | Catatan |
|---|---|---|
| kode | string unique | `TW-{kode kec}-{urut}`, dibuat otomatis |
| nama | string | Mis. "Masjid Al-Ikhlas" |
| nazir_id | foreignId → users nullable | Null untuk data impor tanpa akun Nazir |
| nama_nazir | string nullable | Nama Nazir jika belum punya akun |
| wakif_nama | string | |
| wakif_nik | char(16) nullable | **Tidak pernah tampil publik** |
| desa_id | foreignId | |
| alamat | text nullable | |
| luas_m2 | decimal(12,2) | |
| jenis_penggunaan | string (enum) | |
| status_sertifikat | string (enum) default `belum` | |
| nomor_aiw, tanggal_aiw | string/date nullable | |
| nomor_sertifikat, tanggal_sertifikat | string/date nullable | |
| latitude, longitude | decimal(10,7) | Wajib |
| batas_geojson | json nullable | Polygon GeoJSON |
| foto | string nullable | Path di disk publik |
| is_published | boolean default false | Tampil di peta/halaman publik |
| sumber | string default `pengajuan` | `pengajuan` / `impor` / `manual` |

Index: `status_sertifikat`, `jenis_penggunaan`, `desa_id`, `is_published`.

## Pengajuan

### `pengajuans`
| Kolom | Tipe | Catatan |
|---|---|---|
| nomor | string unique nullable | Diisi saat pertama kali dikirim |
| jenis | string (enum `JenisPengajuan`) | |
| tanah_wakaf_id | foreignId | |
| nazir_id | foreignId → users | |
| status | string (enum `StatusPengajuan`) default `draf` | |
| diajukan_at | timestamp nullable | |
| selesai_at | timestamp nullable | Saat mencapai status akhir |
| pemeriksa_id | foreignId → users nullable | Pemeriksa terakhir |
| catatan_pemeriksa | text nullable | |

### `pengajuan_dokumens`
| Kolom | Tipe | Catatan |
|---|---|---|
| pengajuan_id | foreignId cascade | |
| persyaratan_id | foreignId nullable | Null untuk dokumen hasil (scan AIW/sertifikat) |
| kategori | string default `persyaratan` | `persyaratan` / `hasil_aiw` / `hasil_sertifikat` |
| path | string | Disk `local` (privat) |
| nama_asli | string | |
| mime, ukuran | string, unsignedInteger | |
| status_validasi | string default `menunggu` | `menunggu` / `valid` / `tidak_valid` |
| catatan | text nullable | |

### `riwayat_pengajuans`
| Kolom | Tipe | Catatan |
|---|---|---|
| pengajuan_id | foreignId cascade | |
| user_id | foreignId | Pelaku |
| dari_status | string nullable | |
| ke_status | string | |
| catatan | text nullable | |
| created_at | timestamp | Tanpa updated_at |

## Persyaratan & konten

### `persyaratans`
| Kolom | Tipe | Catatan |
|---|---|---|
| jenis_pengajuan | string (enum) | |
| nama | string | |
| keterangan | text nullable | |
| wajib | boolean default true | |
| urutan | unsignedSmallInteger | |

Tabel ini dipakai bersama oleh halaman Persyaratan publik **dan** daftar unggahan di wizard.

### `artikels` (kartu Informasi Wakaf)
Kolom: `judul`, `slug` unique, `ikon` (nama ikon), `ringkasan`, `isi` (HTML dari Trix), `urutan`, `is_published`.

### `panduan_langkahs`
Kolom: `judul`, `deskripsi`, `urutan`.

### `faqs`
Kolom: `pertanyaan`, `jawaban`, `urutan`, `is_published`.

### `unduhans` (Dokumen Penting / formulir)
Kolom: `judul`, `keterangan` nullable, `file` (path disk publik), `urutan`, `jumlah_unduh` default 0.

### `pengaturans` (key-value)
Kolom: `kunci` unique, `nilai` (text), `grup` (`kontak` / `beranda` / `tentang` / `sosial`).

Kunci awal:
- **kontak:** `alamat`, `telepon`, `email`, `jam_operasional`
- **beranda:** `hero_judul`, `hero_subjudul`, `hero_deskripsi`
- **tentang:** `tentang_deskripsi`, `tentang_tujuan`, `tentang_manfaat` (JSON list), `tentang_instansi` (JSON list)
- **sosial:** `sosial_facebook`, `sosial_instagram`, `sosial_youtube`

Diakses lewat helper `setting('kunci', default)` yang di-cache dan cache-nya dihapus saat disimpan.

### `pesans`
Kolom: `nama`, `kontak` (email/HP), `isi`, `ip`, `dibaca_at` nullable.

## Bawaan / paket
- `notifications`: notifikasi database Laravel
- `activity_log`: spatie/laravel-activitylog
- `sessions`, `cache`, `jobs`: bawaan Laravel

## Enum PHP (`app/Enums`)
Semua enum di-*backed* string, dengan method `label(): string` dan `warna(): string` (kelas warna badge).

| Enum | Nilai |
|---|---|
| `Role` | `admin_bpn`, `bpn`, `kua`, `nazir` |
| `JenisPengajuan` | `aiw`, `sertifikat_bersertifikat`, `sertifikat_belum`, ditambah `pemeriksa(): Role` |
| `StatusPengajuan` | `draf`, `diajukan`, `perlu_perbaikan`, `aiw_disahkan`, `terverifikasi`, `sertifikat_terbit`, `ditolak`, ditambah `bisaKe(self $tujuan, JenisPengajuan $jenis): bool` dan `isFinal(): bool` |
| `StatusSertifikat` | `belum`, `proses`, `sudah`, ditambah `warnaPeta(): string` (hex marker) |
| `JenisPenggunaan` | `masjid`, `mushola`, `pesantren`, `sekolah`, `makam`, `lainnya` |
| `StatusValidasiDokumen` | `menunggu`, `valid`, `tidak_valid` |
