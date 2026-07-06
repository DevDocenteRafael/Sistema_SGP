<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CursoRequest;
use App\Models\Curso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CursoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Curso::query()->orderBy('titulo');

        if ($request->filled('busca')) {
            $busca = $request->busca;
            $query->where(function ($q) use ($busca) {
                $q->where('titulo', 'like', "%{$busca}%")
                    ->orWhere('codigo_sig', 'like', "%{$busca}%")
                    ->orWhere('processo_sei', 'like', "%{$busca}%")
                    ->orWhere('eixo', 'like', "%{$busca}%")
                    ->orWhere('unidade', 'like', "%{$busca}%");
            });
        }

        if ($request->filled('ano')) {
            $query->where('ultima_revisao', 'like', "%{$request->ano}%");
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

        if ($request->filled('unidade')) {
            $unidade = $request->unidade;
            $query->where(function ($q) use ($unidade) {
                $q->where('unidade', $unidade)
                    ->orWhereJsonContains('unidades_oferta', $unidade);
            });
        }

        $cursos = $query->get();

        return response()->json([
            'data' => $cursos,
            'meta' => [
                'total' => $cursos->count(),
                'eixos' => config('eixos'),
                'status' => config('cursos.status'),
                'tipos' => config('cursos.tipos'),
                'modalidades' => config('cursos.modalidades'),
                'sim_nao' => config('cursos.sim_nao'),
            ],
        ]);
    }

    public function store(CursoRequest $request): JsonResponse
    {
        $curso = Curso::create($request->validated());

        return response()->json([
            'message' => 'Curso cadastrado com sucesso.',
            'curso' => $curso,
        ], 201);
    }

    public function show(Curso $curso): JsonResponse
    {
        return response()->json([
            'curso' => $curso,
        ]);
    }

    public function update(CursoRequest $request, Curso $curso): JsonResponse
    {
        $curso->update($request->validated());

        return response()->json([
            'message' => 'Curso atualizado com sucesso.',
            'curso' => $curso,
        ]);
    }

    public function destroy(Curso $curso): JsonResponse
    {
        if (! request()->user()?->podeEditarDados()) {
            return response()->json([
                'message' => 'Você não tem permissão para excluir cursos.',
            ], 403);
        }

        $titulo = $curso->titulo;
        $curso->delete();

        return response()->json([
            'message' => "Curso \"{$titulo}\" excluído com sucesso.",
        ]);
    }
}
