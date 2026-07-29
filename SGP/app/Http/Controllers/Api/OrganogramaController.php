<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AutorizaConsulta;
use App\Http\Controllers\Controller;
use App\Services\OrganogramaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganogramaController extends Controller
{
    use AutorizaConsulta;

    public function __construct(
        private readonly OrganogramaService $organogramaService
    ) {}

    public function index(Request $request): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar o organograma.')) {
            return $negado;
        }

        $dados = $this->organogramaService->montar();

        return response()->json([
            'data' => [
                'ordenador' => $dados['ordenador'],
                'assistentes' => $dados['assistentes'],
                'ramos' => $dados['ramos'],
                'administrativos' => $dados['administrativos'],
            ],
            'meta' => array_merge($dados['meta'], [
                'pode_editar' => $request->user()->podeEditarDados(),
                'gerenciar_em' => '/app/cped',
            ]),
        ]);
    }
}
