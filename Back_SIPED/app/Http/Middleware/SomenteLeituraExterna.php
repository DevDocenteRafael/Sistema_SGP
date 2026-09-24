<?php

namespace App\Http\Middleware;

use App\Support\OrigemDados;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SomenteLeituraExterna
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $next($request);
        }

        $entidade = OrigemDados::entidadeDoPedido($request);

        if ($entidade === 'importacoes' && ! str_contains($request->path(), '/commit')) {
            return $next($request);
        }

        if (OrigemDados::permiteEscrita($entidade)) {
            return $next($request);
        }

        return response()->json([
            'message' => OrigemDados::mensagemBloqueio(),
        ], 403);
    }
}
