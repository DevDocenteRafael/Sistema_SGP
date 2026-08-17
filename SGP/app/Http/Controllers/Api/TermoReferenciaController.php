<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AutorizaConsulta;
use App\Http\Controllers\Controller;
use App\Http\Requests\TermoReferenciaRequest;
use App\Models\TermoReferencia;
use App\Models\TermoReferenciaHistorico;
use App\Services\TermoReferenciaPrazoService;
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
            if ($prazo === 'vencido') {
                $query->where('prazo_deadline', '<', now()->toDateString())
                    ->whereNotIn('status', ['Concluído', 'Arquivado']);
            } elseif ($prazo === 'proximo') {
                $query->whereBetween('prazo_deadline', [now()->toDateString(), now()->addDays(30)->toDateString()])
                    ->whereNotIn('status', ['Concluído', 'Arquivado']);
            }
        }

        $todos = TermoReferencia::query()->get();
        $termos = $query->get()->map(fn (TermoReferencia $termo) => $this->serializarTermo($termo));

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
                'total_geral' => $todos->count(),
                'eixos' => $eixos,
                'status' => config('termos_referencia.status', ['Planejamento', 'Em Andamento', 'Concluído', 'Arquivado']),
                'contagens' => TermoReferenciaPrazoService::contarPorPrazo($todos),
            ],
        ]);
    }

    public function store(TermoReferenciaRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $payload = $this->aplicarConclusao($payload);

        $termo = TermoReferencia::create($payload);

        $this->registrarHistorico(
            $termo,
            'Termo de Referência criado',
            'sucesso',
            null,
            $termo->status,
        );

        return response()->json([
            'message' => 'Termo de Referência cadastrado com sucesso.',
            'termo' => $this->serializarTermo($termo->fresh()),
        ], 201);
    }

    public function show(Request $request, TermoReferencia $termoReferencia): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar este Termo de Referência.')) {
            return $negado;
        }

        $termoReferencia->load(['historicos.usuario']);

        $payload = $this->serializarTermo($termoReferencia);
        $payload['historicos'] = $termoReferencia->historicos()
            ->with('usuario')
            ->orderByDesc('id')
            ->get()
            ->map(fn (TermoReferenciaHistorico $historico) => $this->serializarHistorico($historico))
            ->values();

        return response()->json([
            'data' => $payload,
            'termo' => $payload,
        ]);
    }

    public function update(TermoReferenciaRequest $request, TermoReferencia $termoReferencia): JsonResponse
    {
        $anterior = [
            'status' => $termoReferencia->status,
            'prazo_deadline' => $termoReferencia->prazo_deadline?->format('Y-m-d'),
        ];

        $payload = $this->aplicarConclusao($request->validated(), $termoReferencia);
        $termoReferencia->update($payload);
        $termoFresh = $termoReferencia->fresh();

        $this->registrarAlteracoesImportantes($termoFresh, $anterior);

        return response()->json([
            'message' => 'Termo de Referência atualizado com sucesso.',
            'termo' => $this->serializarTermo($termoFresh),
        ]);
    }

    public function destroy(Request $request, TermoReferencia $termoReferencia): JsonResponse
    {
        if (! $request->user()?->podeEditarDados()) {
            return response()->json([
                'message' => 'Você não tem permissão para excluir Termos de Referência.',
            ], 403);
        }

        $termoReferencia->delete();

        return response()->json([
            'message' => 'Termo de Referência deletado com sucesso.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function aplicarConclusao(array $payload, ?TermoReferencia $existente = null): array
    {
        if (($payload['status'] ?? null) === 'Concluído' && empty($payload['concluido_em'])) {
            $payload['concluido_em'] = $existente?->concluido_em?->format('Y-m-d H:i:s') ?: now();
        }

        return $payload;
    }

    /**
     * @param  array{status: ?string, prazo_deadline: ?string}  $anterior
     */
    private function registrarAlteracoesImportantes(TermoReferencia $termo, array $anterior): void
    {
        $statusNovo = $termo->status;
        $prazoNovo = $termo->prazo_deadline?->format('Y-m-d');
        $registrou = false;

        if ($anterior['status'] !== $statusNovo) {
            $this->registrarHistorico(
                $termo,
                'Status alterado',
                'aviso',
                $anterior['status'],
                $statusNovo,
            );
            $registrou = true;
        }

        if ($anterior['prazo_deadline'] !== $prazoNovo) {
            $this->registrarHistorico(
                $termo,
                'Prazo alterado',
                'info',
                $anterior['prazo_deadline'],
                $prazoNovo,
            );
            $registrou = true;
        }

        if (! $registrou) {
            $this->registrarHistorico(
                $termo,
                'Termo de Referência atualizado',
                'padrao',
                $anterior['status'],
                $statusNovo,
            );
        }
    }

    private function registrarHistorico(
        TermoReferencia $termo,
        string $acao,
        string $tipo,
        ?string $situacaoAnterior,
        ?string $situacaoNova,
        ?string $observacao = null,
    ): void {
        TermoReferenciaHistorico::create([
            'termo_referencia_id' => $termo->id,
            'acao' => $acao,
            'tipo' => $tipo,
            'situacao_anterior' => $situacaoAnterior,
            'situacao_nova' => $situacaoNova,
            'observacao' => $observacao,
            'usuario_id' => request()->user()?->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializarHistorico(TermoReferenciaHistorico $historico): array
    {
        return [
            'id' => $historico->id,
            'acao' => $historico->acao,
            'data' => $historico->created_at?->toISOString(),
            'usuario' => $historico->usuario?->nome,
            'tipo' => $historico->tipo ?: 'padrao',
            'detalhe' => $historico->situacao_anterior || $historico->situacao_nova,
            'situacaoAnterior' => $historico->situacao_anterior,
            'novaSituacao' => $historico->situacao_nova,
            'observacao' => $historico->observacao,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializarTermo(TermoReferencia $termo): array
    {
        $prazoDeadline = $termo->prazo_deadline?->format('Y-m-d');
        $statusPrazo = TermoReferenciaPrazoService::statusPrazo($prazoDeadline);

        return array_merge($termo->toArray(), [
            'prazo_deadline' => $prazoDeadline,
            'data_inicio' => $termo->data_inicio?->format('Y-m-d'),
            'data_fim' => $termo->data_fim?->format('Y-m-d'),
            'status_prazo' => $statusPrazo,
            'semaforo' => TermoReferenciaPrazoService::corSemaforo($statusPrazo),
        ]);
    }
}
