<?php

namespace App\Http\Middleware;

use App\Services\CicloContextoService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveCicloContexto
{
    public function __construct(
        private readonly CicloContextoService $cicloContexto,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $temContexto = $request->filled('ciclo_id') || $request->headers->has(CicloContextoService::HEADER);
        $ciclo = $temContexto ? $this->cicloContexto->resolver($request, true) : null;
        $request->attributes->set('ciclo', $ciclo);
        $request->attributes->set('ciclo_id', $ciclo?->id);

        return $next($request);
    }
}
