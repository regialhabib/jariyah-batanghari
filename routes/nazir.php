<?php

use Illuminate\Support\Facades\Route;

/*
 * Area Nazir (docs/02-peran-hak-akses.md). Halaman di resources/views/pages/nazir/.
 */

Route::middleware(['auth', 'verified', 'role:nazir'])
    ->prefix('nazir')
    ->name('nazir.')
    ->group(function () {
        Route::livewire('dashboard', 'pages::nazir.dashboard')->name('dashboard');
        Route::livewire('tanah', 'pages::nazir.tanah')->name('tanah');
        Route::livewire('pengajuan', 'pages::nazir.pengajuan.index')->name('pengajuan.index');
        Route::livewire('pengajuan/buat', 'pages::nazir.pengajuan.buat')->name('pengajuan.create');
        Route::livewire('pengajuan/{pengajuan}', 'pages::nazir.pengajuan.show')->name('pengajuan.show');
        Route::livewire('dokumen', 'pages::nazir.dokumen')->name('dokumen');
        Route::livewire('peta', 'pages::nazir.peta')->name('peta');
    });
