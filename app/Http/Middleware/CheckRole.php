<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, $roles): Response
    {
        if (!auth()->check()) {
            abort(403);
        }

        // Convertir a array los roles separados por "|"
        $roles = explode('|', $roles);

        // Verificar si el usuario tiene alguno de los roles permitidos
        if (!auth()->user()->hasAnyRole($roles)) {
            abort(403);
        }

        return $next($request);
    }
}
