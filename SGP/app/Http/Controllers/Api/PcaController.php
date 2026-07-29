<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PcaRequest;
use App\Models\Pca;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PcaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()?->podeConsultarDados()) {
            return response()->json([
                'message' => 'Você não tem permissão para consultar PCA.',
            ], 403);
        }

        $query = Pca::query()->orderBy('id');

        if ($request->filled('busca')) {
            $busca = $request->busca;

            $query->where(function ($q) use ($busca) {
                $q->where('titulo', 'like', "%{$busca}%")
                    ->orWhere('numero_sei', 'like', "%{$busca}%")
                    ->orWhere('codigo_sig', 'like', "%{$busca}%")
                    ->orWhere('eixo', 'like', "%{$busca}%")
                    ->orWhere('unidade', 'like', "%{$busca}%")
                    ->orWhere('semestre', 'like', "%{$busca}%")
                    ->orWhere('status', 'like', "%{$busca}%")
                    ->orWhere('observacao', 'like', "%{$busca}%");
            });
        }

        if ($request->filled('ano')) {
            $query->where('ano', $request->ano);
        }

        if ($request->filled('semestre')) {
            $query->where('semestre', $request->semestre);
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

        $registros = $query->get();

        $eixosBanco = Pca::query()
            ->whereNotNull('eixo')
            ->where('eixo', '!=', '')
            ->distinct()
            ->orderBy('eixo')
            ->pluck('eixo')
            ->values()
            ->all();

        $eixosConfig = config('pcas.eixos', []);
        $eixos = array_values(array_unique(array_filter([...$eixosConfig, ...$eixosBanco])));
        sort($eixos);

        $anosBanco = Pca::query()
            ->whereNotNull('ano')
            ->distinct()
            ->orderBy('ano')
            ->pluck('ano')
            ->map(fn ($ano) => (string) $ano)
            ->values()
            ->all();

        $anosConfig = config('pcas.anos', []);
        $anos = array_values(array_unique(array_filter([...$anosConfig, ...$anosBanco])));
        sort($anos);

        $semestresBanco = Pca::query()
            ->whereNotNull('semestre')
            ->where('semestre', '!=', '')
            ->distinct()
            ->orderBy('semestre')
            ->pluck('semestre')
            ->values()
            ->all();

        $semestresConfig = config('pcas.semestres', []);
        $semestres = array_values(array_unique(array_filter([...$semestresConfig, ...$semestresBanco])));
        sort($semestres);

        $statusBanco = Pca::query()
            ->whereNotNull('status')
            ->where('status', '!=', '')
            ->distinct()
            ->orderBy('status')
            ->pluck('status')
            ->values()
            ->all();

        $statusConfig = config('pcas.status', []);
        $status = array_values(array_unique(array_filter([...$statusConfig, ...$statusBanco])));
        sort($status);

        return response()->json([
            'data' => $registros,
            'meta' => [
                'total' => $registros->count(),
                'total_geral' => Pca::query()->count(),
                'status' => $status,
                'anos' => $anos,
                'semestres' => $semestres,
                'eixos' => $eixos,
                'unidades' => config('unidades'),
            ],
        ]);
    }

    public function store(PcaRequest $request): JsonResponse
    {
        $payload = $this->normalizarPayload($request->validated());

        $pca = Pca::create($payload);

        return response()->json([
            'message' => 'PCA cadastrado com sucesso.',
            'pca' => $pca,
        ], 201);
    }

    public function show(Request $request, Pca $pca): JsonResponse
    {
        if (! $request->user()?->podeConsultarDados()) {
            return response()->json([
                'message' => 'Você não tem permissão para consultar este PCA.',
            ], 403);
        }

        return response()->json([
            'pca' => $pca,
        ]);
    }

    public function update(PcaRequest $request, Pca $pca): JsonResponse
    {
        $payload = $this->normalizarPayload($request->validated());

        $pca->update($payload);

        return response()->json([
            'message' => 'PCA atualizado com sucesso.',
            'pca' => $pca->fresh(),
        ]);
    }

    public function destroy(Request $request, Pca $pca): JsonResponse
    {
        if (! $request->user()?->podeEditarDados()) {
            return response()->json([
                'message' => 'Você não tem permissão para excluir PCA.',
            ], 403);
        }

        $titulo = $pca->titulo ?: 'PCA';
        $pca->delete();

        return response()->json([
            'message' => "PCA \"{$titulo}\" excluído com sucesso.",
        ]);
    }

    private function normalizarPayload(array $payload): array
    {
        if (empty($payload['ano']) && ! empty($payload['semestre'])) {
            $payload['ano'] = (int) substr((string) $payload['semestre'], 0, 4);
        }

        if (empty($payload['ano'])) {
            $payload['ano'] = now()->year;
        }

        return $payload;
    }
}
