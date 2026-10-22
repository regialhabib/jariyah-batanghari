# 03 · Alur Pengajuan

Sistem punya tiga jenis pengajuan, sesuai tiga kolom di halaman Persyaratan.

| Kode (`JenisPengajuan`) | Nama | Pemeriksa | Prasyarat |
|---|---|---|---|
| `aiw` | Pembuatan Akta Ikrar Wakaf | **KUA** kecamatan lokasi tanah | – |
| `sertifikat_bersertifikat` | Sertifikat Wakaf, tanah sudah bersertifikat | **BPN** | Tanah sudah punya AIW yang disahkan |
| `sertifikat_belum` | Sertifikat Wakaf, tanah belum bersertifikat | **BPN** | Tanah sudah punya AIW yang disahkan |

## Status (`StatusPengajuan`)

| Status | Label | Warna badge | Arti |
|---|---|---|---|
| `draf` | Draf | abu | Disimpan Nazir, belum dikirim |
| `diajukan` | Diajukan | biru | Menunggu pemeriksaan |
| `perlu_perbaikan` | Perlu Perbaikan | kuning | Dikembalikan ke Nazir dengan catatan |
| `aiw_disahkan` | AIW Disahkan | hijau | Status akhir jenis `aiw` |
| `terverifikasi` | Terverifikasi | teal | Berkas sertifikat dinyatakan valid oleh BPN |
| `sertifikat_terbit` | Sertifikat Terbit | hijau | Status akhir jenis sertifikat |
| `ditolak` | Ditolak | merah | Status akhir, dengan alasan |

## Transisi yang diizinkan

### Jenis `aiw`
```
draf ──(Nazir: kirim)──────────────> diajukan
diajukan ──(KUA: minta perbaikan)──> perlu_perbaikan
perlu_perbaikan ──(Nazir: kirim ulang)──> diajukan
diajukan ──(KUA: sahkan + isi nomor/tanggal AIW + scan)──> aiw_disahkan
diajukan ──(KUA: tolak + alasan)───> ditolak
```

### Jenis sertifikat (`sertifikat_bersertifikat`, `sertifikat_belum`)
```
draf ──(Nazir: kirim)──────────────> diajukan
diajukan ──(BPN: minta perbaikan)──> perlu_perbaikan
perlu_perbaikan ──(Nazir: kirim ulang)──> diajukan
diajukan ──(BPN: verifikasi)───────> terverifikasi
terverifikasi ──(BPN: catat sertifikat terbit: nomor + tanggal)──> sertifikat_terbit
diajukan | terverifikasi ──(BPN: tolak + alasan)──> ditolak
```

Selain transisi di atas, semuanya **ditolak** oleh service.

## Efek samping pada `tanah_wakafs`

| Kejadian | Perubahan pada tanah wakaf |
|---|---|
| Pengajuan `aiw` dibuat | Record tanah dibuat: `status_sertifikat = belum`, `is_published = false` |
| `aiw` → `aiw_disahkan` | `nomor_aiw` dan `tanggal_aiw` diisi, file scan AIW disimpan, `is_published = true` |
| Pengajuan sertifikat → `diajukan` | `status_sertifikat = proses` |
| → `perlu_perbaikan` (jenis sertifikat) | Tetap `proses`, karena pengajuan masih berjalan |
| → `ditolak` (jenis sertifikat) | `status_sertifikat` kembali ke `belum` |
| → `sertifikat_terbit` | `status_sertifikat = sudah`, `nomor_sertifikat` dan `tanggal_sertifikat` diisi |

**Aturan tambahan:**
- Satu tanah hanya boleh punya **satu pengajuan aktif** (status selain draf dan status akhir) per jenis.
- Data tanah hasil impor BPN langsung `is_published = true` dengan status sesuai data impor.

## Aturan umum
- **Satu pintu:** semua transisi dijalankan lewat `App\Services\PengajuanWorkflow`. Service ini:
  1. memeriksa transisi sah (`StatusPengajuan::bisaKe()`)
  2. memeriksa hak akses lewat Policy
  3. menjalankan efek samping di dalam transaksi DB
  4. mencatat ke `riwayat_pengajuans`
  5. mengirim notifikasi database (dan email) ke Nazir
- **Operasi Nazir di luar transisi** juga lewat service yang sama: `buatDraf`, `unggahDokumen`, `ubahDataTanah` (hanya pengajuan AIW yang masih bisa diubah; kode tanah menyesuaikan bila kecamatan berganti), dan `hapusDraf` (hanya draf; dokumen dihapus dari disk, dan tanah yang lahir dari draf AIW itu ikut dihapus bila belum disahkan, belum dipublikasikan, dan tidak dipakai pengajuan lain).
- **Syarat sebelum kirim:** semua dokumen persyaratan `wajib` untuk jenis tersebut sudah diunggah.
- **Validasi per dokumen:** pemeriksa bisa menandai tiap dokumen `valid` atau `tidak_valid` + catatan. Jika ada dokumen `tidak_valid`, pemeriksa hanya bisa memilih "minta perbaikan" atau "tolak", bukan "sahkan/verifikasi".
- **Perbaikan:** Nazir hanya bisa mengganti dokumen yang ditandai `tidak_valid`. Saat diganti, status dokumen kembali ke `menunggu`.
- **Nomor pengajuan:** `JRY-{tahun}-{urut 4 digit}`, contoh `JRY-2026-0001`. Nomor dibuat saat pertama kali dikirim, bukan saat masih draf.
- **Pemeriksa yang berhak:** pengajuan `aiw` hanya bisa diproses KUA yang `kecamatan_id`-nya sama dengan kecamatan lokasi tanah.

## Wizard pengajuan (sisi Nazir)
1. **Jenis pengajuan.** Untuk jenis sertifikat, pilih tanah wakaf milik Nazir yang AIW-nya sudah disahkan.
2. **Data tanah** (khusus `aiw`): nama tanah/peruntukan, jenis penggunaan, data wakif (nama, NIK), desa (kecamatan → desa), alamat, luas m².
3. **Lokasi:** klik peta atau isi lat/lng (wajib), lalu gambar polygon batas (opsional).
4. **Unggah dokumen:** daftar diambil dari `persyaratans` sesuai jenis. File berformat pdf/jpg/png, maksimal 5 MB per file.
5. **Ringkasan dan kirim** (atau simpan sebagai draf).
