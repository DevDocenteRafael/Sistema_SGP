<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlanoDeMetaRequest;
use App\Models\PlanoDeMeta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanoDeMetaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()?->podeConsultarDados()) {
            return response()->json([
                'message' => 'Você não tem permissão para consultar planos de metas.',
            ], 403);
        }

        $query = PlanoDeMeta::query()->orderByDesc('created_at');

        if ($request->filled('busca')) {
            $busca = $request->busca;

            $query->where(function ($q) use ($busca) {
                $q->where('segmento', 'like', "%{$busca}%")
                    ->orWhere('curso', 'like', "%{$busca}%")
                    ->orWhere('tipo', 'like', "%{$busca}%")
                    ->orWhere('numero_sei', 'like', "%{$busca}%")
                    ->orWhere('codigo_sig', 'like', "%{$busca}%")
                    ->orWhere('mes_entrega', 'like', "%{$busca}%")
                    ->orWhere('status', 'like', "%{$busca}%")
                    ->orWhere('status_final', 'like', "%{$busca}%")
                    ->orWhere('observacao', 'like', "%{$busca}%");
            });
        }

        if ($request->filled('ano')) {
            $query->where('ano', $request->ano);
        }

        if ($request->filled('segmento')) {
            $query->where('segmento', $request->segmento);
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('mes')) {
            $query->where('mes_entrega', $request->mes);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('situacao')) {
            $query->where('status_final', $request->situacao);
        }

        $registros = $query->get();

        return response()->json([
            'data' => $registros,
            'meta' => [
                'total' => $registros->count(),
            ],
        ]);
    }

    public function store(PlanoDeMetaRequest $request): JsonResponse
    {
        $payload = $request->validated();

        if (empty($payload['ano'])) {
            $payload['ano'] = now()->year;
        }

        $planoDeMeta = PlanoDeMeta::create($payload);

        return response()->json([
            'message' => 'Plano de metas cadastrado com sucesso.',
            'planoDeMeta' => $planoDeMeta,
        ], 201);
    }

    public function show(PlanoDeMeta $planoDeMeta): JsonResponse
    {
        return response()->json([
            'planoDeMeta' => $planoDeMeta,
        ]);
    }

    public function update(PlanoDeMetaRequest $request, PlanoDeMeta $planoDeMeta): JsonResponse
    {
        $payload = $request->validated();

        if (empty($payload['ano'])) {
            $payload['ano'] = now()->year;
        }

        $planoDeMeta->update($payload);

        return response()->json([
            'message' => 'Plano de metas atualizado com sucesso.',
            'planoDeMeta' => $planoDeMeta->fresh(),
        ]);
    }

    public function destroy(Request $request, PlanoDeMeta $planoDeMeta): JsonResponse
    {
        if (! $request->user()?->podeEditarDados()) {
            return response()->json([
                'message' => 'Você não tem permissão para excluir planos de metas.',
            ], 403);
        }

        $curso = $planoDeMeta->curso;
        $planoDeMeta->delete();

        return response()->json([
            'message' => "Plano de metas \"{$curso}\" excluído com sucesso.",
        ]);
    }
}
