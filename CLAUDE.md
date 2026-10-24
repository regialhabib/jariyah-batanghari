@AGENTS.md

# Jariyah Batang Hari

- Spesifikasi, keputusan, dan progres ada di `docs/` (mulai dari `docs/README.md`; progres fase di `docs/07-tahapan-pengerjaan.md`). Catat setiap keputusan/penyimpangan di `docs/CHANGELOG.md`.
- UI dan teks berbahasa Indonesia.
- Dua branch: `main` (kode saja) dan `with-docs` (kode + `docs/` + file AI). Merge hanya `main` → `with-docs`, jangan sebaliknya. Lihat bagian "Branch Git" di `AGENTS.md`.
- Role disimpan di kolom `users.role` (enum `App\Enums\Role`), hak akses lewat Gate di `App\Support\HakAkses`. Jangan tambahkan paket role/permission.
- Semua perubahan status pengajuan wajib lewat `App\Services\PengajuanWorkflow`.
- Composer: `php C:/laragon/bin/composer/composer.phar` (Composer global terlalu lama).
- Test: `php artisan test` (SQLite in-memory). Pint: `vendor/bin/pint` (folder `resources/views` dikecualikan).

## Context Mode
- Untuk perintah yang outputnya besar (test, build, log, install, git log/diff panjang), jalankan lewat ctx_execute / ctx_batch_execute, dan tampilkan ringkasan atau bagian yang relevan saja.
- Untuk menganalisis banyak file sekaligus, tulis skrip lewat ctx_execute daripada membaca file satu per satu.
- Untuk dokumentasi dari web, pakai ctx_fetch_and_index lalu ctx_search.
