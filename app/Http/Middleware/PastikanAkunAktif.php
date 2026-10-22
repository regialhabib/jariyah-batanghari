<?php

namespace App\Http\Middleware;

use App\Providers\FortifyServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mengeluarkan pengguna yang akunnya dinonaktifkan admin walaupun sesinya masih aktif.
 */
class PastikanAkunAktif
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => FortifyServiceProvider::PESAN_AKUN_NONAKTIF,
            ]);
        }

        return $next($request);
    }
}
