<?php

use App\Http\Controllers\LaporanController;
use Illuminate\Support\Facades\Route;

/*
 * Area petugas: Admin BPN, Petugas BPN, Petugas KUA (docs/02-peran-hak-akses.md).
 * Halaman di resources/views/pages/admin/. Pembatasan per role/kecamatan ada di Policy & permission.
 */

Route::middleware(['auth', 'verified', 'role:admin_bpn|bpn|kua'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::livewire('dashboard', 'pages::admin.dashboard')->name('dashboard');

        // Data tanah wakaf
        Route::livewire('tanah', 'pages::admin.tanah.index')->name('tanah.index');
        Route::livewire('tanah/buat', 'pages::admin.tanah.form')->middleware('permission:tanah.kelola')->name('tanah.create');
        Route::livewire('tanah/impor', 'pages::admin.tanah.impor')->middleware('permission:tanah.impor')->name('tanah.impor');
        Route::livewire('tanah/{tanah}/ubah', 'pages::admin.tanah.form')->middleware('permission:tanah.kelola')->name('tanah.edit');

        // Pengajuan & pemeriksaan
        Route::livewire('pengajuan', 'pages::admin.pengajuan.index')->name('pengajuan.index');
        Route::livewire('pengajuan/{pengajuan}', 'pages::admin.pengajuan.show')->name('pengajuan.show');
        Route::livewire('sertifikat', 'pages::admin.sertifikat')->middleware('permission:sertifikat.catat-terbit')->name('sertifikat');

        Route::livewire('peta', 'pages::admin.peta')->name('peta');

        // Laporan
        Route::livewire('laporan', 'pages::admin.laporan')->middleware('permission:laporan.lihat')->name('laporan');
        Route::get('laporan/excel', [LaporanController::class, 'excel'])->middleware('permission:laporan.ekspor')->name('laporan.excel');
        Route::get('laporan/pdf', [LaporanController::class, 'pdf'])->middleware('permission:laporan.ekspor')->name('laporan.pdf');

        Route::livewire('pesan', 'pages::admin.pesan')->middleware('permission:pesan.lihat')->name('pesan');

        // Khusus Admin BPN
        Route::middleware('permission:pengguna.kelola')->group(function () {
            Route::livewire('pengguna', 'pages::admin.pengguna.index')->name('pengguna.index');
            Route::livewire('pengguna/buat', 'pages::admin.pengguna.form')->name('pengguna.create');
            Route::livewire('pengguna/{user}/ubah', 'pages::admin.pengguna.form')->name('pengguna.edit');
            Route::livewire('aktivitas', 'pages::admin.aktivitas')->name('aktivitas');
        });

        Route::middleware('permission:konten.kelola')->prefix('konten')->name('konten.')->group(function () {
            Route::livewire('artikel', 'pages::admin.konten.artikel')->name('artikel');
            Route::livewire('persyaratan', 'pages::admin.konten.persyaratan')->name('persyaratan');
            Route::livewire('panduan', 'pages::admin.konten.panduan')->name('panduan');
            Route::livewire('faq', 'pages::admin.konten.faq')->name('faq');
            Route::livewire('unduhan', 'pages::admin.konten.unduhan')->name('unduhan');
            Route::livewire('pengaturan', 'pages::admin.konten.pengaturan')->name('pengaturan');
        });
    });
