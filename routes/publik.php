<?php

use App\Http\Controllers\PetaDataController;
use App\Http\Controllers\UnduhanController;
use Illuminate\Support\Facades\Route;

/*
 * Halaman publik (docs/01-rancangan-halaman.md). Halaman berupa Livewire single-file component
 * di resources/views/pages/publik/.
 */

Route::livewire('/', 'pages::publik.beranda')->name('beranda');
Route::livewire('tentang', 'pages::publik.tentang')->name('tentang');
Route::livewire('informasi-wakaf', 'pages::publik.informasi-wakaf')->name('informasi.index');
Route::livewire('informasi-wakaf/{artikel:slug}', 'pages::publik.artikel')->name('informasi.show');
Route::livewire('persyaratan', 'pages::publik.persyaratan')->name('persyaratan');
Route::livewire('peta-wakaf', 'pages::publik.peta-wakaf')->name('peta');
Route::livewire('peta-wakaf/{tanah:kode}', 'pages::publik.tanah-detail')->name('peta.show');
Route::livewire('panduan', 'pages::publik.panduan')->name('panduan');
Route::livewire('faq', 'pages::publik.faq')->name('faq');
Route::livewire('kontak', 'pages::publik.kontak')->name('kontak');
Route::livewire('daftar-nazir', 'pages::publik.daftar-nazir')->name('nazir.publik');

Route::get('unduh/semua', [UnduhanController::class, 'semua'])->name('unduhan.semua');
Route::get('unduh/{unduhan}', [UnduhanController::class, 'show'])->name('unduhan.download');

Route::get('api/peta', PetaDataController::class)->middleware('throttle:60,1')->name('peta.data');
