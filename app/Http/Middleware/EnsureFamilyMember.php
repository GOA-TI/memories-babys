<?php

namespace App\Http\Middleware;

use App\Support\FamilySession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFamilyMember
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! FamilySession::member()) {
            return redirect()->route('login')->with('info', 'Entra con la cédula que te dieron de alta y tu clave familiar.');
        }

        return $next($request);
    }
}
