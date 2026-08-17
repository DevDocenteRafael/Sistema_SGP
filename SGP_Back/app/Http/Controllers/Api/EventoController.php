<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AutorizaConsulta;
use App\Http\Controllers\Controller;
use App\Http\Requests\EventoRequest;
use App\Models\AcaoExtensiva;
use App\Models\Evento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventoController extends Controller
{
    use AutorizaConsulta;

    public function index(Request $request): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar eventos.')) {
            return $negado;
        }

        $query = Evento::query()
            ->orderByDesc('data')
            ->orderByDesc('id');

        if ($request->filled('busca')) {
            $busca = $request->busca;

            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                    ->orWhere('unidade', 'like', "%{$busca}%")
                    ->orWhere('eixo', 'like', "%{$busca}%")
                    ->orWhere('equipe', 'like', "%{$busca}%")
                    ->orWhere('acao_vinculada', 'like', "%{$busca}%")
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

        if ($request->filled('unidade')) {
            $query->where('unidade', $request->unidade);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('possui_acao_extensiva')) {
            $query->where('possui_acao_extensiva', $request->possui_acao_extensiva);
        }

        $registros = $query->get();

        $eixosBanco = Evento::query()
            ->whereNotNull('eixo')
            ->distinct()
            ->orderBy('eixo')
            ->pluck('eixo')
            ->values()
            ->all();

        $eixosConfig = config('eventos.eixos', []);
        $eixos = array_values(array_unique(array_filter([...$eixosConfig, ...$eixosBanco])));
        sort($eixos);

        $anosBanco = Evento::query()
            ->whereNotNull('ano')
            ->distinct()
            ->orderBy('ano')
            ->pluck('ano')
            ->values()
            ->all();

        $anosConfig = config('eventos.anos', []);
        $anos = array_values(array_unique(array_filter([...$anosConfig, ...$anosBanco])));
        sort($anos);

        $acoesVinculaveis = AcaoExtensiva::query()
            ->whereNotNull('assunto')
            ->where('assunto', '!=', '')
            ->orderBy('assunto')
            ->pluck('assunto')
            ->unique()
            ->values()
            ->all();

        return response()->json([
            'data' => $registros,
            'meta' => [
                'total' => $registros->count(),
                'total_geral' => Evento::query()->count(),
                'status' => config('eventos.status'),
                'anos' => $anos,
                'eixos' => $eixos,
                'unidades' => config('unidades'),
                'possui_acao_extensiva' => config('eventos.possui_acao_extensiva'),
                'acoes_vinculaveis' => $acoesVinculaveis,
            ],
        ]);
    }

    public function store(EventoRequest $request): JsonResponse
    {
        $payload = $this->normalizarPayload($request->validated());

        $registro = Evento::create($payload);

        return response()->json([
            'message' => 'Evento cadastrado com sucesso.',
            'evento' => $registro,
        ], 201);
    }

    public function show(Request $request, Evento $evento): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar este evento.')) {
            return $negado;
        }

        return response()->json([
            'evento' => $evento,
        ]);
    }

    public function update(EventoRequest $request, Evento $evento): JsonResponse
    {
        $payload = $this->normalizarPayload($request->validated());

        $evento->update($payload);

        return response()->json([
            'message' => 'Evento atualizado com sucesso.',
            'evento' => $evento->fresh(),
        ]);
    }

    public function destroy(Request $request, Evento $evento): JsonResponse
    {
        if (! $request->user()?->podeEditarDados()) {
            return response()->json([
                'message' => 'Você não tem permissão para excluir eventos.',
            ], 403);
        }

        $nome = $evento->nome ?: 'Evento';
        $evento->delete();

        return response()->json([
            'message' => "Evento \"{$nome}\" excluído com sucesso.",
        ]);
    }

    private function normalizarPayload(array $payload): array
    {
        $payload['possui_acao_extensiva'] = $payload['possui_acao_extensiva'] ?? 'Não';

        if (($payload['possui_acao_extensiva'] ?? 'Não') !== 'Sim') {
            $payload['acao_vinculada'] = null;
        }

        if (empty($payload['ano']) && ! empty($payload['data'])) {
            $payload['ano'] = substr((string) $payload['data'], 0, 4);
        }

        return $payload;
    }
}
