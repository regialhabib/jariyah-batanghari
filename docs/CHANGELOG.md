# Catatan Keputusan & Perubahan

Format: tanggal, apa yang diputuskan atau diubah, dan alasannya. Entri terbaru di atas.

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
