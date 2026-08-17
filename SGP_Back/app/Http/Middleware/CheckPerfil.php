<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPerfil
{
    /**
     * @param  string  ...$perfis  Perfis autorizados (ex.: Administrador)
     */
    public function handle(Request $request, Closure $next, string ...$perfis): Response
    {
        $usuario = $request->user();

        if (! $usuario || ! in_array($usuario->perfil, $perfis, true)) {
            return response()->json([
                'message' => 'Acesso não autorizado para o seu perfil.',
            ], 403);
        }

        return $next($request);
    }
}
