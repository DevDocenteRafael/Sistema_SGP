<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AutorizaConsulta;
use App\Http\Controllers\Controller;
use App\Http\Requests\CursoPorEixoRequest;
use App\Models\CursoPorEixo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CursoPorEixoController extends Controller
{
    use AutorizaConsulta;

    public function index(Request $request): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar cursos por eixo.')) {
            return $negado;
        }

        $query = CursoPorEixo::query()->orderBy('id');

        if ($request->filled('busca')) {
            $busca = $request->busca;

            $query->where(function ($q) use ($busca) {
                $q->where('curso', 'like', "%{$busca}%")
                    ->orWhere('eixo', 'like', "%{$busca}%")
                    ->orWhere('unidade', 'like', "%{$busca}%")
                    ->orWhere('codigo', 'like', "%{$busca}%")
                    ->orWhere('instrutores', 'like', "%{$busca}%")
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

        $registros = $query->get();
        $totalGeral = CursoPorEixo::query()->count();

        return response()->json([
            'data' => $registros,
            'meta' => [
                'total' => $registros->count(),
                'total_geral' => $totalGeral,
                'eixos' => config('eixos_tecnologicos'),
                'status' => config('curso_por_eixos.status'),
                'anos' => config('curso_por_eixos.anos'),
                'unidades' => config('unidades'),
            ],
        ]);
    }

    public function store(CursoPorEixoRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $payload['is_novo'] = (bool) ($payload['is_novo'] ?? false);
        $payload['unidade'] = $payload['unidade'] ?? null;

        $registro = CursoPorEixo::create($payload);

        return response()->json([
            'message' => 'Curso por eixo cadastrado com sucesso.',
            'cursoPorEixo' => $registro,
        ], 201);
    }

    public function show(Request $request, CursoPorEixo $cursoPorEixo): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar este registro.')) {
            return $negado;
        }

        return response()->json([
            'cursoPorEixo' => $cursoPorEixo,
        ]);
    }

    public function update(CursoPorEixoRequest $request, CursoPorEixo $cursoPorEixo): JsonResponse
    {
        $payload = $request->validated();
        $payload['is_novo'] = (bool) ($payload['is_novo'] ?? false);
        $payload['unidade'] = $payload['unidade'] ?? null;

        $cursoPorEixo->update($payload);

        return response()->json([
            'message' => 'Curso por eixo atualizado com sucesso.',
            'cursoPorEixo' => $cursoPorEixo->fresh(),
        ]);
    }

    public function destroy(Request $request, CursoPorEixo $cursoPorEixo): JsonResponse
    {
        if (! $request->user()?->podeEditarDados()) {
            return response()->json([
                'message' => 'Você não tem permissão para excluir cursos por eixo.',
            ], 403);
        }

        $titulo = $cursoPorEixo->curso;
        $cursoPorEixo->delete();

        return response()->json([
            'message' => "Curso \"{$titulo}\" excluído com sucesso.",
        ]);
    }
}
