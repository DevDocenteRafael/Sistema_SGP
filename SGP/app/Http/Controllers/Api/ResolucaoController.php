<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AutorizaConsulta;
use App\Http\Controllers\Controller;
use App\Http\Requests\ResolucaoRequest;
use App\Models\Resolucao;
use App\Services\ResolucaoVigenciaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResolucaoController extends Controller
{
    use AutorizaConsulta;

    public function index(Request $request): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar resoluções.')) {
            return $negado;
        }

        $query = Resolucao::query()->orderBy('id');

        if ($request->filled('busca')) {
            $busca = $request->busca;
            $query->where(function ($q) use ($busca) {
                $q->where('numero', 'like', "%{$busca}%")
                    ->orWhere('curso_relacionado', 'like', "%{$busca}%")
                    ->orWhere('resumo', 'like', "%{$busca}%")
                    ->orWhere('relator', 'like', "%{$busca}%")
                    ->orWhere('setor', 'like', "%{$busca}%")
                    ->orWhere('categoria', 'like', "%{$busca}%");
            });
        }

        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('setor')) {
            $query->where('setor', $request->setor);
        }

        $registros = $query->get()->map(function (Resolucao $resolucao) {
            $status = $resolucao->status ?: ResolucaoVigenciaService::statusAutomatico(
                $resolucao->data_inicio_vigencia?->format('Y-m-d'),
                $resolucao->data_fim_vigencia?->format('Y-m-d')
            );

            return array_merge($resolucao->toArray(), [
                'status' => $status,
                'data_fim_vigencia' => $resolucao->data_fim_vigencia?->format('Y-m-d')
                    ?? ResolucaoVigenciaService::calcularDataFimVigencia($resolucao->data_inicio_vigencia)->format('Y-m-d'),
            ]);
        });

        $statusList = config('resolucoes.status');

        return response()->json([
            'data' => $registros,
            'meta' => [
                'total' => $registros->count(),
                'vigencia_anos' => ResolucaoVigenciaService::vigenciaAnos(),
                'status' => $statusList,
                'semaforo' => config('resolucoes.semaforo'),
            ],
        ]);
    }

    public function store(ResolucaoRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $payload['data_fim_vigencia'] = ResolucaoVigenciaService::calcularDataFimVigencia(
            $payload['data_inicio_vigencia']
        )->format('Y-m-d');
        $payload['status'] = $payload['status'] ?? ResolucaoVigenciaService::statusAutomatico(
            $payload['data_inicio_vigencia'],
            $payload['data_fim_vigencia']
        );

        $resolucao = Resolucao::create($payload);

        $resolucaoFresh = $resolucao->fresh();
        $payload = $this->serializarResolucao($resolucaoFresh);

        return response()->json([
            'message' => 'Resolução cadastrada com sucesso.',
            'resolucao' => $payload,
        ], 201);
    }

    public function show(Request $request, Resolucao $resolucao): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar esta resolução.')) {
            return $negado;
        }

        $payload = $this->serializarResolucao($resolucao);
        $payload['status'] = $resolucao->status ?: ResolucaoVigenciaService::statusAutomatico(
            $resolucao->data_inicio_vigencia?->format('Y-m-d'),
            $resolucao->data_fim_vigencia?->format('Y-m-d')
        );

        return response()->json([
            'resolucao' => $payload,
        ]);
    }

    public function update(ResolucaoRequest $request, Resolucao $resolucao): JsonResponse
    {
        $payload = $request->validated();
        $payload['data_fim_vigencia'] = ResolucaoVigenciaService::calcularDataFimVigencia(
            $payload['data_inicio_vigencia']
        )->format('Y-m-d');
        $payload['status'] = $payload['status'] ?? ResolucaoVigenciaService::statusAutomatico(
            $payload['data_inicio_vigencia'],
            $payload['data_fim_vigencia']
        );

        $resolucao->update($payload);
        $resolucaoFresh = $resolucao->fresh();

        return response()->json([
            'message' => 'Resolução atualizada com sucesso.',
            'resolucao' => $this->serializarResolucao($resolucaoFresh),
        ]);
    }

    public function destroy(Request $request, Resolucao $resolucao): JsonResponse
    {
        if (! $request->user()?->podeEditarDados()) {
            return response()->json([
                'message' => 'Você não tem permissão para excluir resoluções.',
            ], 403);
        }

        $numero = $resolucao->numero;
        $resolucao->delete();

        return response()->json([
            'message' => "Resolução \"{$numero}\" excluída com sucesso.",
        ]);
    }

    private function serializarResolucao(Resolucao $resolucao): array
    {
        $dataFimVigencia = $resolucao->data_fim_vigencia
            ?: ResolucaoVigenciaService::calcularDataFimVigencia($resolucao->data_inicio_vigencia ?? now());

        return [
            'id' => $resolucao->id,
            'numero' => $resolucao->numero,
            'curso_relacionado' => $resolucao->curso_relacionado,
            'categoria' => $resolucao->categoria,
            'resumo' => $resolucao->resumo,
            'relator' => $resolucao->relator,
            'setor' => $resolucao->setor,
            'data_inicio_vigencia' => $resolucao->data_inicio_vigencia?->format('Y-m-d'),
            'data_fim_vigencia' => $dataFimVigencia->format('Y-m-d'),
            'status' => $resolucao->status ?: ResolucaoVigenciaService::statusAutomatico(
                $resolucao->data_inicio_vigencia?->format('Y-m-d'),
                $dataFimVigencia->format('Y-m-d')
            ),
            'observacoes' => $resolucao->observacoes,
            'anexo_path' => $resolucao->anexo_path,
            'created_at' => $resolucao->created_at?->toISOString(),
            'updated_at' => $resolucao->updated_at?->toISOString(),
        ];
    }
}
