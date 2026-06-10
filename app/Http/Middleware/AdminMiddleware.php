<?php
// ============================================================
// FILE 5: app/Http/Middleware/AdminMiddleware.php  (FILE BARU)
// ============================================================

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Hanya user dengan role 'admin' yang boleh akses route /admin/*.
     * User biasa → redirect ke dashboard dengan pesan error.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            return redirect()->route('dashboard')
                ->with('error', 'Akses ditolak. Halaman ini khusus Administrator.');
        }

        return $next($request);
    }

    
}