<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->role === 'admin') {
            return $next($request);
        }

        // Jika bukan admin, arahkan kembali ke halaman planner atau tampilkan error 403
        return redirect()->route('planner.index')->with('error', 'Anda tidak memiliki akses ke halaman ini.');
    }
}