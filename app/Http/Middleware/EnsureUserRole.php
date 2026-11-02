<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        $isAllowed = match (strtolower($role)) {
            'owner' => $user->isOwner(),
            'tenant' => $user->isTenant(),
            default => false,
        };

        if (! $isAllowed) {
            abort(403, 'Akses Ditolak: Peran akun Anda tidak memiliki izin untuk membuka halaman ini.');
        }

        return $next($request);
    }
}
