<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\Response;

/**
 * Ekspor laporan Excel & PDF. Diisi pada Fase 7 (laporan).
 */
class LaporanController extends Controller
{
    public function excel(): Response
    {
        abort(404);
    }

    public function pdf(): Response
    {
        abort(404);
    }
}
