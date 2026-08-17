<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AutorizaConsulta;
use App\Http\Controllers\Controller;
use App\Http\Requests\FluxogramaRequest;
use App\Models\Fluxograma;
use App\Services\FluxogramaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FluxogramaController extends Controller
{
    use AutorizaConsulta;

    public function __construct(
        private readonly FluxogramaService $fluxogramaService
    ) {}

    public function index(Request $request): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar os fluxogramas.')) {
            return $negado;
        }

        $itens = $this->fluxogramaService->listar();

        return response()->json([
            'data' => $itens,
            'meta' => [
                'total' => count($itens),
                'pode_editar' => $request->user()->podeEditarDados(),
            ],
        ]);
    }

    public function store(FluxogramaRequest $request): JsonResponse
    {
        $fluxograma = $this->fluxogramaService->criar(
            $request->validated(),
            $request->user()?->id
        );

        return response()->json([
            'message' => 'Fluxograma criado com sucesso.',
            'fluxograma' => $this->fluxogramaService->formatarResumo($fluxograma),
        ], 201);
    }

    public function show(Request $request, Fluxograma $fluxograma): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar este fluxograma.')) {
            return $negado;
        }

        if (! $fluxograma->ativo) {
            return response()->json([
                'message' => 'Fluxograma não encontrado.',
            ], 404);
        }

        return response()->json([
            'data' => $this->fluxogramaService->formatarDetalhe($fluxograma),
            'meta' => [
                'pode_editar' => $request->user()->podeEditarDados(),
            ],
        ]);
    }

    public function update(FluxogramaRequest $request, Fluxograma $fluxograma): JsonResponse
    {
        if (! $fluxograma->ativo) {
            return response()->json([
                'message' => 'Fluxograma não encontrado.',
            ], 404);
        }

        $atualizado = $this->fluxogramaService->atualizar(
            $fluxograma,
            $request->validated()
        );

        return response()->json([
            'message' => 'Fluxograma atualizado com sucesso.',
            'fluxograma' => $this->fluxogramaService->formatarDetalhe($atualizado),
        ]);
    }

    public function destroy(Request $request, Fluxograma $fluxograma): JsonResponse
    {
        if (! $request->user()?->podeEditarDados()) {
            return response()->json([
                'message' => 'Você não tem permissão para excluir fluxogramas.',
            ], 403);
        }

        $titulo = $fluxograma->titulo ?: 'Fluxograma';
        $this->fluxogramaService->excluir($fluxograma);

        return response()->json([
            'message' => "Fluxograma \"{$titulo}\" excluído com sucesso.",
        ]);
    }
}
