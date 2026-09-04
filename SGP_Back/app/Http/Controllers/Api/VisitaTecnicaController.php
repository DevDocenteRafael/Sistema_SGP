<?php

namespace App\Http\Controllers\Api;

use App\Models\UnidadeOferta;
use App\Http\Controllers\Concerns\AutorizaConsulta;
use App\Http\Controllers\Controller;
use App\Http\Requests\VisitaTecnicaRequest;
use App\Models\VisitaTecnica;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisitaTecnicaController extends Controller
{
    use AutorizaConsulta;

    public function index(Request $request): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar visitas técnicas.')) {
            return $negado;
        }

        $query = VisitaTecnica::query()
            ->orderByDesc('data_solicitacao')
            ->orderByDesc('id');

        if ($request->filled('busca')) {
            $busca = $request->busca;

            $query->where(function ($q) use ($busca) {
                $q->where('unidade', 'like', "%{$busca}%")
                    ->orWhere('eixo', 'like', "%{$busca}%")
                    ->orWhere('processo_sei', 'like', "%{$busca}%")
                    ->orWhere('responsavel', 'like', "%{$busca}%")
                    ->orWhere('status', 'like', "%{$busca}%")
                    ->orWhere('relatorio', 'like', "%{$busca}%")
                    ->orWhere('observacao', 'like', "%{$busca}%");
            });
        }

        if ($request->filled('ano')) {
            $query->whereYear('data_solicitacao', $request->ano);
        }

        if ($request->filled('unidade')) {
            $query->where('unidade', $request->unidade);
        }

        if ($request->filled('eixo')) {
            $query->where('eixo', $request->eixo);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('prazo')) {
            $this->aplicarFiltroPrazo($query, $request->prazo);
        }

        $registros = $query->get();

        return response()->json([
            'data' => $registros,
            'meta' => [
                'total' => $registros->count(),
                'total_geral' => VisitaTecnica::query()->count(),
                'eixos' => config('visitas_tecnicas.eixos'),
                'status' => config('visitas_tecnicas.status'),
                'anos' => config('visitas_tecnicas.anos'),
                'unidades' => UnidadeOferta::nomesAtivos(),
                'prazos' => config('visitas_tecnicas.prazos'),
            ],
        ]);
    }

    public function store(VisitaTecnicaRequest $request): JsonResponse
    {
        $registro = VisitaTecnica::create($request->validated());

        return response()->json([
            'message' => 'Visita técnica cadastrada com sucesso.',
            'visitaTecnica' => $registro,
        ], 201);
    }

    public function show(Request $request, VisitaTecnica $visitaTecnica): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar esta visita técnica.')) {
            return $negado;
        }

        return response()->json([
            'visitaTecnica' => $visitaTecnica,
        ]);
    }

    public function update(VisitaTecnicaRequest $request, VisitaTecnica $visitaTecnica): JsonResponse
    {
        $visitaTecnica->update($request->validated());

        return response()->json([
            'message' => 'Visita técnica atualizada com sucesso.',
            'visitaTecnica' => $visitaTecnica->fresh(),
        ]);
    }

    public function destroy(Request $request, VisitaTecnica $visitaTecnica): JsonResponse
    {
        if (! $request->user()?->podeEditarDados()) {
            return response()->json([
                'message' => 'Você não tem permissão para excluir visitas técnicas.',
            ], 403);
        }

        $sei = $visitaTecnica->processo_sei;
        $visitaTecnica->delete();

        return response()->json([
            'message' => "Visita técnica \"{$sei}\" excluída com sucesso.",
        ]);
    }

    private function aplicarFiltroPrazo($query, string $prazo): void
    {
        $hoje = Carbon::today()->toDateString();

        if ($prazo === 'dentro') {
            $query->whereDate('prazo_limite', '>=', $hoje)
                ->whereNotIn('status', ['Atrasada', 'Cancelada']);

            return;
        }

        if ($prazo === 'fora') {
            $query->where(function ($q) use ($hoje) {
                $q->whereDate('prazo_limite', '<', $hoje)
                    ->orWhere('status', 'Atrasada');
            });
        }
    }
}
