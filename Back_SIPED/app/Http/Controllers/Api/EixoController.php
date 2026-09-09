<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AutorizaConsulta;
use App\Http\Controllers\Controller;
use App\Models\Eixo;
use App\Services\EixoResumoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EixoController extends Controller
{
    use AutorizaConsulta;

    public function __construct(
        private readonly EixoResumoService $resumoService,
    ) {}

    public function resumo(Request $request): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar os eixos.')) {
            return $negado;
        }

        return response()->json([
            'data' => $this->resumoService->resumo($request->input('ciclo_id')),
        ]);
    }

    public function pendentes(Request $request): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar os eixos.')) {
            return $negado;
        }

        return response()->json([
            'data' => $this->resumoService->pendentes(
                $request->filled('ciclo_id') ? (int) $request->input('ciclo_id') : null
            ),
        ]);
    }

    public function detalhes(Request $request, Eixo $eixo): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar este eixo.')) {
            return $negado;
        }

        return response()->json([
            'data' => $this->resumoService->detalhes($eixo, $request->input('ciclo_id')),
        ]);
    }
}
