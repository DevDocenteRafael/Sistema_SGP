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

        $query = Pca::query()->orderByDesc('created_at');

        if ($request->filled('busca')) {
            $busca = $request->busca;
            $query->where(function ($q) use ($busca) {
                $q->where('curso', 'like', "%{$busca}%")
                    ->orWhere('unidade', 'like', "%{$busca}%")
                    ->orWhere('numero_sei', 'like', "%{$busca}%")
                    ->orWhere('codigo_sig', 'like', "%{$busca}%")
                    ->orWhere('periodo', 'like', "%{$busca}%")
                    ->orWhere('status', 'like', "%{$busca}%")
                    ->orWhere('observacao', 'like', "%{$busca}%");
            });
        }

        if ($request->filled('ano')) {
            $query->where('ano', $request->ano);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $registros = $query->get();

        return response()->json([
            'data' => $registros,
            'meta' => [
                'total' => $registros->count(),
            ],
        ]);
    }

    public function store(PcaRequest $request): JsonResponse
    {
        $payload = $request->validated();

        if (empty($payload['ano'])) {
            $payload['ano'] = now()->year;
        }

        $pca = Pca::create($payload);

        return response()->json([
            'message' => 'PCA cadastrado com sucesso.',
            'pca' => $pca,
        ], 201);
    }

    public function show(Pca $pca): JsonResponse
    {
        return response()->json([
            'pca' => $pca,
        ]);
    }

    public function update(PcaRequest $request, Pca $pca): JsonResponse
    {
        $payload = $request->validated();

        if (empty($payload['ano'])) {
            $payload['ano'] = now()->year;
        }

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

        $curso = $pca->curso;
        $pca->delete();

        return response()->json([
            'message' => "PCA \"{$curso}\" excluído com sucesso.",
        ]);
    }
}
