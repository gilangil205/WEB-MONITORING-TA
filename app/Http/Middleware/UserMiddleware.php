<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UserMiddleware
{
    /**
     * Rute ini khusus untuk Petani (User).
     * Jika Administrator mencoba membuka rute pengguna secara langsung,
     * alihkan ke Panel Administrator.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if ($user && $user->isAdmin()) {
            if ($request->expectsJson()) {
                return $next($request);
            }

            return redirect()->route('admin.dashboard')
                ->with('info', 'Halaman tersebut khusus Petani. Anda dialihkan ke Panel Administrator.');
        }

        return $next($request);
    }
}
