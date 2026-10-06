<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTPS obrigatório em produção (SPEC 08): GET é redirecionado; demais métodos são recusados
 * para que senha/token nunca trafeguem sem criptografia.
 */
class ForcarHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('producao.forcar_https') || $request->isSecure() || $request->is('up')) {
            return $next($request);
        }

        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        return response()->json(['message' => 'Use HTTPS para acessar o SIPED.'], 403);
    }
}
