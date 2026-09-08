<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AutorizaConsulta;
use App\Http\Controllers\Controller;
use App\Http\Requests\HoraPedagogicaRequest;
use App\Models\HoraPedagogica;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HoraPedagogicaController extends Controller
{
    use AutorizaConsulta;

    public function index(Request $request): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar horas pedagógicas.')) {
            return $negado;
        }

        $query = HoraPedagogica::query()
            ->orderByDesc('ano')
            ->orderBy('pessoa');

        if ($request->filled('busca')) {
            $busca = $request->busca;

            $query->where(function ($q) use ($busca) {
                $q->where('matricula', 'like', "%{$busca}%")
                    ->orWhere('pessoa', 'like', "%{$busca}%")
                    ->orWhere('segmento', 'like', "%{$busca}%")
                    ->orWhere('eixo', 'like', "%{$busca}%")
                    ->orWhere('processo_sei', 'like', "%{$busca}%")
                    ->orWhere('motivo', 'like', "%{$busca}%")
                    ->orWhere('status', 'like', "%{$busca}%")
                    ->orWhere('observacao', 'like', "%{$busca}%");
            });
        }

        if ($request->filled('ano')) {
            $query->where('ano', $request->ano);
        }

        if ($request->filled('eixo')) {
            $query->where('eixo', $request->eixo);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('ativo')) {
            $ativo = filter_var($request->ativo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if ($ativo !== null) {
                $query->where('ativo', $ativo);
            }
        }

        $registros = $query->get();

        return response()->json([
            'data' => $registros,
            'meta' => [
                'total' => $registros->count(),
                'total_geral' => HoraPedagogica::query()->count(),
                'total_ativos' => HoraPedagogica::query()->where('ativo', true)->count(),
                'eixos' => config('horas_pedagogicas.eixos'),
                'segmentos' => config('horas_pedagogicas.segmentos'),
                'status' => config('horas_pedagogicas.status'),
                'anos' => config('horas_pedagogicas.anos'),
            ],
        ]);
    }

    public function store(HoraPedagogicaRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $payload['ativo'] = (bool) ($payload['ativo'] ?? true);

        $registro = HoraPedagogica::create($payload);

        return response()->json([
            'message' => 'Hora pedagógica cadastrada com sucesso.',
            'horaPedagogica' => $registro,
        ], 201);
    }

    public function show(Request $request, HoraPedagogica $horaPedagogica): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar esta hora pedagógica.')) {
            return $negado;
        }

        return response()->json([
            'horaPedagogica' => $horaPedagogica,
        ]);
    }

    public function update(HoraPedagogicaRequest $request, HoraPedagogica $horaPedagogica): JsonResponse
    {
        $payload = $request->validated();
        $payload['ativo'] = (bool) ($payload['ativo'] ?? true);

        $horaPedagogica->update($payload);

        return response()->json([
            'message' => 'Hora pedagógica atualizada com sucesso.',
            'horaPedagogica' => $horaPedagogica->fresh(),
        ]);
    }

    public function destroy(Request $request, HoraPedagogica $horaPedagogica): JsonResponse
    {
        if (! $request->user()?->podeEditarDados()) {
            return response()->json([
                'message' => 'Você não tem permissão para excluir horas pedagógicas.',
            ], 403);
        }

        $pessoa = $horaPedagogica->pessoa;
        $horaPedagogica->delete();

        return response()->json([
            'message' => "Hora pedagógica de \"{$pessoa}\" excluída com sucesso.",
        ]);
    }
}
