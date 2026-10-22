<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi rute ke role tertentu, mis. `role:nazir` atau `role:admin_bpn|bpn|kua`.
 */
class PastikanRole
{
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        $user = $request->user();

        abort_unless($user && $user->hasRole(...explode('|', $roles)), 403);

        return $next($request);
    }
}
