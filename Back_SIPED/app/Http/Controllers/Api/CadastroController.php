<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cadastro;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CadastroController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $usuario = $request->user();

        if (! $usuario || ! $usuario->podeConsultarAuditoria()) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $query = Cadastro::query()
            ->with(['usuario:id,nome,email,perfil'])
            ->orderByDesc('id');

        if ($request->filled('modulo')) {
            $query->where('modulo', $request->modulo);
        }

        if ($request->filled('acao')) {
            $query->where('acao', $request->acao);
        }

        if ($request->filled('usuario_id')) {
            $query->where('usuario_id', $request->usuario_id);
        }

        if ($request->filled('busca')) {
            $busca = $request->busca;
            $query->where(function ($q) use ($busca) {
                $q->where('resumo', 'like', "%{$busca}%")
                    ->orWhere('modulo', 'like', "%{$busca}%")
                    ->orWhere('acao', 'like', "%{$busca}%");
            });
        }

        if ($request->filled('data_inicio')) {
            $query->whereDate('created_at', '>=', $request->data_inicio);
        }

        if ($request->filled('data_fim')) {
            $query->whereDate('created_at', '<=', $request->data_fim);
        }

        $porPagina = min(max((int) $request->input('per_page', 50), 1), 100);
        $paginator = $query->paginate($porPagina);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'acoes' => ['criar', 'editar', 'excluir', 'importar'],
                'modulos' => Cadastro::query()
                    ->select('modulo')
                    ->distinct()
                    ->orderBy('modulo')
                    ->pluck('modulo'),
            ],
        ]);
    }

    public function show(Request $request, Cadastro $cadastro): JsonResponse
    {
        $usuario = $request->user();

        if (! $usuario || ! $usuario->podeConsultarAuditoria()) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $cadastro->load(['usuario:id,nome,email,perfil']);

        return response()->json([
            'cadastro' => $cadastro,
        ]);
    }
}
