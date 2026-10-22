<?php

namespace App\Http\Controllers;

use App\Models\Unduhan;
use Symfony\Component\HttpFoundation\Response;

/**
 * Unduhan formulir publik. Diisi pada Fase 2 (halaman publik).
 */
class UnduhanController extends Controller
{
    public function show(Unduhan $unduhan): Response
    {
        abort(404);
    }

    public function semua(): Response
    {
        abort(404);
    }
}
