<?php

use App\Http\Controllers\DokumenController;
use Illuminate\Support\Facades\Route;

/*
 * Rute dipecah per area agar bisa dikerjakan paralel (docs/07-tahapan-pengerjaan.md):
 * publik.php, nazir.php, admin.php, settings.php. Rute auth disediakan Laravel Fortify.
 */

require __DIR__.'/publik.php';

Route::middleware(['auth', 'verified'])->group(function () {
    // Tujuan default Fortify setelah login/verifikasi: diteruskan ke dashboard sesuai role.
    Route::get('dashboard', fn () => redirect(auth()->user()->dashboardUrl()))->name('dashboard');

    Route::get('dokumen/{dokumen}', [DokumenController::class, 'show'])->name('dokumen.show');
});

require __DIR__.'/nazir.php';
require __DIR__.'/admin.php';
require __DIR__.'/settings.php';
