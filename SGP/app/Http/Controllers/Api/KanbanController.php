<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AutorizaConsulta;
use App\Http\Controllers\Controller;
use App\Http\Requests\KanbanCartaoRequest;
use App\Http\Requests\KanbanColunaRequest;
use App\Http\Requests\KanbanMoverCartaoRequest;
use App\Http\Requests\KanbanQuadroRequest;
use App\Models\KanbanCartao;
use App\Models\KanbanColuna;
use App\Models\KanbanQuadro;
use App\Services\KanbanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class KanbanController extends Controller
{
    use AutorizaConsulta;

    public function __construct(
        private readonly KanbanService $kanbanService
    ) {}

    public function indexQuadros(Request $request): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar o Kanban.')) {
            return $negado;
        }

        $quadros = $this->kanbanService->listarQuadros();

        return response()->json([
            'data' => $quadros,
            'meta' => [
                'total' => count($quadros),
                'pode_editar' => $request->user()->podeEditarDados(),
            ],
        ]);
    }

    public function storeQuadro(KanbanQuadroRequest $request): JsonResponse
    {
        $quadro = $this->kanbanService->criarQuadro($request->validated('nome'));

        return response()->json([
            'message' => 'Quadro criado com sucesso.',
            'kanban_quadro' => $this->kanbanService->formatarQuadroResumo($quadro),
        ], 201);
    }

    public function showQuadro(Request $request, KanbanQuadro $kanbanQuadro): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar este quadro.')) {
            return $negado;
        }

        if (! $kanbanQuadro->ativo) {
            return response()->json([
                'message' => 'Quadro não encontrado.',
            ], 404);
        }

        $quadro = $this->kanbanService->obterQuadroPorSlug($kanbanQuadro->slug);
        $formatado = $this->kanbanService->formatarQuadro($quadro);

        return response()->json([
            'data' => [
                'quadro' => $formatado['quadro'],
                'colunas' => $formatado['colunas'],
            ],
            'meta' => [
                'total_cartoes' => $formatado['total_cartoes'],
                'pode_editar' => $request->user()->podeEditarDados(),
            ],
        ]);
    }

    public function updateQuadro(KanbanQuadroRequest $request, KanbanQuadro $kanbanQuadro): JsonResponse
    {
        $quadro = $this->kanbanService->atualizarQuadro(
            $kanbanQuadro,
            $request->validated('nome')
        );

        return response()->json([
            'message' => 'Quadro atualizado com sucesso.',
            'kanban_quadro' => $this->kanbanService->formatarQuadroResumo($quadro),
        ]);
    }

    public function destroyQuadro(Request $request, KanbanQuadro $kanbanQuadro): JsonResponse
    {
        if (! $request->user()?->podeEditarDados()) {
            return response()->json([
                'message' => 'Você não tem permissão para excluir quadros do Kanban.',
            ], 403);
        }

        $nome = $kanbanQuadro->nome ?: 'Quadro';
        $this->kanbanService->excluirQuadro($kanbanQuadro);

        return response()->json([
            'message' => "Quadro \"{$nome}\" excluído com sucesso.",
        ]);
    }

    public function store(KanbanCartaoRequest $request, KanbanQuadro $kanbanQuadro): JsonResponse
    {
        $resultado = $this->kanbanService->criarCartao(
            $kanbanQuadro,
            $request->validated(),
            $request->user()?->id
        );

        $mensagem = $resultado['coluna_criada']
            ? 'Cartão criado e nova coluna adicionada ao quadro.'
            : 'Cartão criado com sucesso.';

        return response()->json([
            'message' => $mensagem,
            'kanban_cartao' => $this->kanbanService->formatarCartao($resultado['cartao']),
            'kanban_coluna' => $this->kanbanService->formatarColuna($resultado['coluna']),
            'coluna_criada' => $resultado['coluna_criada'],
        ], 201);
    }

    public function update(KanbanCartaoRequest $request, KanbanCartao $kanbanCartao): JsonResponse
    {
        $cartao = $this->kanbanService->atualizarCartao(
            $kanbanCartao,
            $request->validated()
        );

        return response()->json([
            'message' => 'Cartão atualizado com sucesso.',
            'kanban_cartao' => $this->kanbanService->formatarCartao($cartao),
        ]);
    }

    public function destroy(Request $request, KanbanCartao $kanbanCartao): JsonResponse
    {
        if (! $request->user()?->podeEditarDados()) {
            return response()->json([
                'message' => 'Você não tem permissão para excluir cartões do Kanban.',
            ], 403);
        }

        $titulo = $kanbanCartao->titulo ?: 'Cartão';
        $this->kanbanService->excluirCartao($kanbanCartao);

        return response()->json([
            'message' => "Cartão \"{$titulo}\" excluído com sucesso.",
        ]);
    }

    public function mover(KanbanMoverCartaoRequest $request, KanbanCartao $kanbanCartao): JsonResponse
    {
        try {
            $cartao = $this->kanbanService->moverCartao(
                $kanbanCartao,
                (int) $request->validated('kanban_coluna_id'),
                (int) $request->validated('position')
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Cartão movido com sucesso.',
            'kanban_cartao' => $this->kanbanService->formatarCartao($cartao),
        ]);
    }

    public function storeColuna(KanbanColunaRequest $request, KanbanQuadro $kanbanQuadro): JsonResponse
    {
        try {
            $coluna = $this->kanbanService->criarColuna(
                $kanbanQuadro,
                $request->validated('titulo')
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Coluna criada com sucesso.',
            'kanban_coluna' => $this->kanbanService->formatarColuna($coluna, true),
        ], 201);
    }

    public function updateColuna(KanbanColunaRequest $request, KanbanColuna $kanbanColuna): JsonResponse
    {
        try {
            $coluna = $this->kanbanService->atualizarColuna(
                $kanbanColuna,
                $request->validated('titulo')
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Coluna atualizada com sucesso.',
            'kanban_coluna' => $this->kanbanService->formatarColuna($coluna),
        ]);
    }

    public function destroyColuna(Request $request, KanbanColuna $kanbanColuna): JsonResponse
    {
        if (! $request->user()?->podeEditarDados()) {
            return response()->json([
                'message' => 'Você não tem permissão para excluir colunas do Kanban.',
            ], 403);
        }

        $titulo = $kanbanColuna->titulo ?: 'Coluna';
        $totalCartoes = $kanbanColuna->cartoes()->count();

        $this->kanbanService->excluirColuna($kanbanColuna);

        $sufixo = $totalCartoes > 0
            ? " e {$totalCartoes} cartão(ões)"
            : '';

        return response()->json([
            'message' => "Coluna \"{$titulo}\"{$sufixo} excluída com sucesso.",
        ]);
    }
}
