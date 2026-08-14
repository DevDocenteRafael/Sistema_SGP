<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AutorizaConsulta;
use App\Http\Controllers\Controller;
use App\Http\Requests\TermoReferenciaRequest;
use App\Models\TermoReferencia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TermoReferenciaController extends Controller
{
    use AutorizaConsulta;

    public function index(Request $request): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar Termos de Referência.')) {
            return $negado;
        }

        $query = TermoReferencia::query()->orderBy('id');

        if ($request->filled('busca')) {
            $busca = $request->busca;
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                    ->orWhere('processo_sei', 'like', "%{$busca}%")
                    ->orWhere('eixo', 'like', "%{$busca}%")
                    ->orWhere('observacao', 'like', "%{$busca}%");
            });
        }

        if ($request->filled('eixo')) {
            $query->where('eixo', $request->eixo);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('prazo')) {
            $prazo = $request->prazo;
            // Filtro de prazo pode ser: 'vencido', 'proximo', 'normal'
            // A ser configurado conforme definição da CPED
            if ($prazo === 'vencido') {
                $query->where('prazo_deadline', '<', now()->toDateString())
                    ->where('status', '!=', 'Concluído');
            } elseif ($prazo === 'proximo') {
                $query->whereBetween('prazo_deadline', [now()->toDateString(), now()->addDays(30)->toDateString()])
                    ->where('status', '!=', 'Concluído');
            }
        }

        $termos = $query->get();

        $eixosConfig = config('eixos', []);
        $eixosDb = TermoReferencia::query()
            ->whereNotNull('eixo')
            ->where('eixo', '!=', '')
            ->distinct()
            ->orderBy('eixo')
            ->pluck('eixo')
            ->all();
        $eixos = array_values(array_unique(array_merge($eixosConfig, $eixosDb)));

        return response()->json([
            'data' => $termos,
            'meta' => [
                'total' => $termos->count(),
                'eixos' => $eixos,
                'status' => config('termos_referencia.status', ['Planejamento', 'Em Andamento', 'Concluído', 'Arquivado']),
            ],
        ]);
    }

    public function store(TermoReferenciaRequest $request): JsonResponse
    {
        $termo = TermoReferencia::create($request->validated());

        return response()->json([
            'message' => 'Termo de Referência cadastrado com sucesso.',
            'termo' => $termo,
        ], 201);
    }

    public function show(Request $request, TermoReferencia $termoReferencia): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar este Termo de Referência.')) {
            return $negado;
        }

        return response()->json([
            'data' => $termoReferencia,
        ]);
    }

    public function update(TermoReferenciaRequest $request, TermoReferencia $termoReferencia): JsonResponse
    {
        $termoReferencia->update($request->validated());

        return response()->json([
            'message' => 'Termo de Referência atualizado com sucesso.',
            'termo' => $termoReferencia,
        ]);
    }

    public function destroy(Request $request, TermoReferencia $termoReferencia): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para deletar este Termo de Referência.')) {
            return $negado;
        }

        $termoReferencia->delete();

        return response()->json([
            'message' => 'Termo de Referência deletado com sucesso.',
        ]);
    }
}
