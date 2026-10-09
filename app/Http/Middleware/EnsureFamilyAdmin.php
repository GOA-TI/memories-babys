<?php

namespace App\Http\Middleware;

use App\Support\FamilySession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFamilyAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('samuel.admin_secret');
        $headerOk = $secret !== '' && hash_equals($secret, (string) $request->header('X-Samuel-Admin', ''));

        if (FamilySession::isAdmin() || $headerOk) {
            return $next($request);
        }

        abort(403, 'Solo administradores de la familia.');
    }
}
