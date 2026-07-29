<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AutorizaConsulta;
use App\Http\Controllers\Controller;
use App\Http\Requests\AcaoExtensivaRequest;
use App\Models\AcaoExtensiva;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AcaoExtensivaController extends Controller
{
    use AutorizaConsulta;

    public function index(Request $request): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar ações extensivas.')) {
            return $negado;
        }

        $query = AcaoExtensiva::query()->orderBy('id');

        if ($request->filled('busca')) {
            $busca = $request->busca;

            $query->where(function ($q) use ($busca) {
                $q->where('atribuido', 'like', "%{$busca}%")
                    ->orWhere('eixo', 'like', "%{$busca}%")
                    ->orWhere('numero_processo_sei', 'like', "%{$busca}%")
                    ->orWhere('assunto', 'like', "%{$busca}%")
                    ->orWhere('objetivo', 'like', "%{$busca}%")
                    ->orWhere('tipo', 'like', "%{$busca}%")
                    ->orWhere('status', 'like', "%{$busca}%");
            });
        }

        if ($request->filled('priorizacao')) {
            $query->where('priorizacao', $request->priorizacao);
        }

        if ($request->filled('eixo')) {
            $query->where('eixo', $request->eixo);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        $registros = $query->get();

        $eixosBanco = AcaoExtensiva::query()
            ->whereNotNull('eixo')
            ->distinct()
            ->orderBy('eixo')
            ->pluck('eixo')
            ->values()
            ->all();

        $eixosConfig = config('acoes_extensivas.eixos', []);
        $eixos = array_values(array_unique(array_filter([...$eixosConfig, ...$eixosBanco])));
        sort($eixos);

        return response()->json([
            'data' => $registros,
            'meta' => [
                'total' => $registros->count(),
                'total_geral' => AcaoExtensiva::query()->count(),
                'priorizacoes' => config('acoes_extensivas.priorizacoes'),
                'status' => config('acoes_extensivas.status'),
                'tipos' => config('acoes_extensivas.tipos'),
                'eixos' => $eixos,
            ],
        ]);
    }

    public function store(AcaoExtensivaRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $payload['tipo'] = $payload['tipo'] ?? 'Ação Extensiva';

        $registro = AcaoExtensiva::create($payload);

        return response()->json([
            'message' => 'Ação extensiva cadastrada com sucesso.',
            'acaoExtensiva' => $registro,
        ], 201);
    }

    public function show(Request $request, AcaoExtensiva $acaoExtensiva): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar esta ação extensiva.')) {
            return $negado;
        }

        return response()->json([
            'acaoExtensiva' => $acaoExtensiva,
        ]);
    }

    public function update(AcaoExtensivaRequest $request, AcaoExtensiva $acaoExtensiva): JsonResponse
    {
        $payload = $request->validated();
        $payload['tipo'] = $payload['tipo'] ?? 'Ação Extensiva';

        $acaoExtensiva->update($payload);

        return response()->json([
            'message' => 'Ação extensiva atualizada com sucesso.',
            'acaoExtensiva' => $acaoExtensiva->fresh(),
        ]);
    }

    public function destroy(Request $request, AcaoExtensiva $acaoExtensiva): JsonResponse
    {
        if (! $request->user()?->podeEditarDados()) {
            return response()->json([
                'message' => 'Você não tem permissão para excluir ações extensivas.',
            ], 403);
        }

        $assunto = $acaoExtensiva->assunto ?: $acaoExtensiva->numero_processo_sei;
        $acaoExtensiva->delete();

        return response()->json([
            'message' => "Ação extensiva \"{$assunto}\" excluída com sucesso.",
        ]);
    }
}
