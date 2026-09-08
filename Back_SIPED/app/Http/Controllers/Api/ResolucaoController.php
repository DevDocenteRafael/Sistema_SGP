<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AutorizaConsulta;
use App\Http\Controllers\Controller;
use App\Http\Requests\ResolucaoRequest;
use App\Models\Resolucao;
use App\Models\ResolucaoHistorico;
use App\Services\ResolucaoAnexoService;
use App\Services\ResolucaoVigenciaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResolucaoController extends Controller
{
    use AutorizaConsulta;

    public function __construct(
        private readonly ResolucaoAnexoService $anexos,
    ) {}

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

        if ($request->filled('ano')) {
            $ano = $request->ano;
            $query->where(function ($q) use ($ano) {
                $q->whereYear('data_inicio_vigencia', $ano)
                    ->orWhereYear('data_fim_vigencia', $ano);
            });
        }

        $todasLeves = Resolucao::query()->get([
            'id',
            'status',
            'data_inicio_vigencia',
            'data_fim_vigencia',
        ]);
        $registros = $query->get()->map(fn (Resolucao $resolucao) => $this->serializarResolucao($resolucao));

        return response()->json([
            'data' => $registros,
            'meta' => [
                'total' => $registros->count(),
                'total_geral' => $todasLeves->count(),
                'vigencia_anos' => ResolucaoVigenciaService::vigenciaAnos(),
                'status' => config('resolucoes.status'),
                'categorias' => config('resolucoes.categorias', []),
                'setores' => config('resolucoes.setores', []),
                'semaforo' => config('resolucoes.semaforo'),
                'contagens' => ResolucaoVigenciaService::contarPorSemaforo($todasLeves),
            ],
        ]);
    }

    public function store(ResolucaoRequest $request): JsonResponse
    {
        $payload = $request->safe()->except(['anexo']);
        $payload['data_fim_vigencia'] = ResolucaoVigenciaService::calcularDataFimVigencia(
            $payload['data_inicio_vigencia']
        )->format('Y-m-d');
        $payload['status'] = $payload['status'] ?? ResolucaoVigenciaService::statusAutomatico(
            $payload['data_inicio_vigencia'],
            $payload['data_fim_vigencia']
        );
        $payload = $this->aplicarAnexo($request, $payload);

        $resolucao = Resolucao::create($payload);

        $this->registrarHistorico(
            $resolucao,
            'Resolução cadastrada',
            null,
            $resolucao->status,
        );

        $resolucaoFresh = $resolucao->fresh();

        return response()->json([
            'message' => 'Resolução cadastrada com sucesso.',
            'resolucao' => $this->serializarResolucao($resolucaoFresh),
        ], 201);
    }

    public function show(Request $request, Resolucao $resolucao): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar esta resolução.')) {
            return $negado;
        }

        $resolucao->load(['historicos.usuario']);

        $payload = $this->serializarResolucao($resolucao);
        $payload['historicos'] = $resolucao->historicos
            ->map(fn (ResolucaoHistorico $historico) => $this->serializarHistorico($historico))
            ->values();

        return response()->json([
            'resolucao' => $payload,
        ]);
    }

    public function update(ResolucaoRequest $request, Resolucao $resolucao): JsonResponse
    {
        $anterior = [
            'status' => $resolucao->status,
            'data_inicio_vigencia' => $resolucao->data_inicio_vigencia?->format('Y-m-d'),
            'data_fim_vigencia' => $resolucao->data_fim_vigencia?->format('Y-m-d'),
        ];

        $payload = $request->safe()->except(['anexo']);
        $payload['data_fim_vigencia'] = ResolucaoVigenciaService::calcularDataFimVigencia(
            $payload['data_inicio_vigencia']
        )->format('Y-m-d');
        $payload['status'] = $payload['status'] ?? ResolucaoVigenciaService::statusAutomatico(
            $payload['data_inicio_vigencia'],
            $payload['data_fim_vigencia']
        );
        $payload = $this->aplicarAnexo($request, $payload, $resolucao);

        $resolucao->update($payload);
        $resolucaoFresh = $resolucao->fresh();

        $this->registrarAlteracoesImportantes($resolucaoFresh, $anterior);

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
        $this->anexos->apagar($resolucao->anexo_path);
        $resolucao->delete();

        return response()->json([
            'message' => "Resolução \"{$numero}\" excluída com sucesso.",
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function aplicarAnexo(Request $request, array $payload, ?Resolucao $existente = null): array
    {
        if ($request->hasFile('anexo')) {
            if ($existente?->anexo_path) {
                $this->anexos->apagar($existente->anexo_path);
            }
            $payload['anexo_path'] = $this->anexos->salvar($request->file('anexo'));
        }

        unset($payload['anexo']);

        return $payload;
    }

    /**
     * @param  array{status: ?string, data_inicio_vigencia: ?string, data_fim_vigencia: ?string}  $anterior
     */
    private function registrarAlteracoesImportantes(Resolucao $resolucao, array $anterior): void
    {
        $statusNovo = $resolucao->status;
        $inicioNovo = $resolucao->data_inicio_vigencia?->format('Y-m-d');
        $fimNovo = $resolucao->data_fim_vigencia?->format('Y-m-d');

        if ($anterior['status'] !== $statusNovo) {
            $this->registrarHistorico(
                $resolucao,
                'Status alterado',
                $anterior['status'],
                $statusNovo,
            );
        }

        if ($anterior['data_inicio_vigencia'] !== $inicioNovo || $anterior['data_fim_vigencia'] !== $fimNovo) {
            $de = trim(($anterior['data_inicio_vigencia'] ?? '—').' → '.($anterior['data_fim_vigencia'] ?? '—'));
            $para = trim(($inicioNovo ?? '—').' → '.($fimNovo ?? '—'));
            $this->registrarHistorico(
                $resolucao,
                'Vigência alterada',
                $de,
                $para,
            );
        }

        if (
            $anterior['status'] === $statusNovo
            && $anterior['data_inicio_vigencia'] === $inicioNovo
            && $anterior['data_fim_vigencia'] === $fimNovo
        ) {
            $this->registrarHistorico(
                $resolucao,
                'Resolução atualizada',
                $anterior['status'],
                $statusNovo,
            );
        }
    }

    private function registrarHistorico(
        Resolucao $resolucao,
        string $evento,
        ?string $statusAnterior,
        ?string $statusNovo,
        ?string $observacao = null,
    ): void {
        ResolucaoHistorico::create([
            'resolucao_id' => $resolucao->id,
            'evento' => $evento,
            'status_anterior' => $statusAnterior,
            'status_novo' => $statusNovo,
            'usuario_id' => request()->user()?->id,
            'observacao' => $observacao,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializarHistorico(ResolucaoHistorico $historico): array
    {
        return [
            'id' => $historico->id,
            'acao' => $historico->evento,
            'data' => $historico->created_at?->toISOString(),
            'usuario' => $historico->usuario?->nome,
            'tipo' => $this->tipoHistorico($historico->evento),
            'detalhe' => $historico->status_anterior || $historico->status_novo,
            'situacaoAnterior' => $historico->status_anterior,
            'novaSituacao' => $historico->status_novo,
            'observacao' => $historico->observacao,
        ];
    }

    private function tipoHistorico(string $evento): string
    {
        return match (true) {
            str_contains($evento, 'cadastrada') => 'sucesso',
            str_contains($evento, 'Status') => 'aviso',
            str_contains($evento, 'Vigência') => 'info',
            default => 'padrao',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function serializarResolucao(Resolucao $resolucao): array
    {
        $dataFimVigencia = $resolucao->data_fim_vigencia
            ?: ResolucaoVigenciaService::calcularDataFimVigencia($resolucao->data_inicio_vigencia ?? now());
        $statusVigencia = ResolucaoVigenciaService::statusVigencia($resolucao);

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
            'status' => $resolucao->status ?: $statusVigencia,
            'status_vigencia' => $statusVigencia,
            'semaforo' => ResolucaoVigenciaService::corSemaforo($statusVigencia),
            'observacoes' => $resolucao->observacoes,
            'anexo_path' => $resolucao->anexo_path,
            'anexo_url' => $this->anexos->urlPublica($resolucao->anexo_path),
            'created_at' => $resolucao->created_at?->toISOString(),
            'updated_at' => $resolucao->updated_at?->toISOString(),
        ];
    }
}
