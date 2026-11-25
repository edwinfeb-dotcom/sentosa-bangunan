<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();
        $userRole = $user ? strtolower(trim((string) $user->role)) : '';
        $allowedRoles = array_map(fn ($r) => strtolower(trim((string) $r)), $roles);

        if (! $user || ! in_array($userRole, $allowedRoles, true)) {
            abort(403, 'Akses ditolak. Role Anda tidak memiliki izin.');
        }

        return $next($request);
    }
}