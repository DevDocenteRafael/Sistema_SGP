<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUsuarioAtivo
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario && ! $usuario->status) {
            $usuario->currentAccessToken()?->delete();

            return response()->json([
                'message' => 'Usuário inativo. Entre em contato com o administrador.',
            ], 401);
        }

        return $next($request);
    }
}
