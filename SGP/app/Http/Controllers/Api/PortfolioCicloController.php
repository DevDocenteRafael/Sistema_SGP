<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AutorizaConsulta;
use App\Http\Controllers\Controller;
use App\Http\Requests\PortfolioCicloGerarRequest;
use App\Models\Curso;
use App\Models\PortfolioCiclo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PortfolioCicloController extends Controller
{
    use AutorizaConsulta;

    public function index(Request $request): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar ciclos de portfólio.')) {
            return $negado;
        }

        $ciclos = PortfolioCiclo::query()
            ->withCount('cursos')
            ->orderByDesc('atual')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'data' => $ciclos,
            'meta' => [
                'total' => $ciclos->count(),
                'ciclo_atual_id' => PortfolioCiclo::atual()?->id,
            ],
        ]);
    }

    public function gerarProximo(PortfolioCicloGerarRequest $request): JsonResponse
    {
        $origem = $request->origem();
        $marcarAtual = $request->boolean('marcar_atual', true);

        $novo = DB::transaction(function () use ($request, $origem, $marcarAtual) {
            $ciclo = PortfolioCiclo::create([
                'nome' => $request->validated('nome'),
                'origem_id' => $origem->id,
                'atual' => false,
                'observacao' => 'Gerado a partir do ciclo '.$origem->nome,
            ]);

            Curso::query()
                ->where('ciclo_id', $origem->id)
                ->orderBy('id')
                ->get()
                ->each(fn (Curso $curso) => $curso->replicarParaCiclo($ciclo));

            if ($marcarAtual) {
                $ciclo->marcarComoAtual();
            }

            return $ciclo->fresh()->loadCount('cursos');
        });

        return response()->json([
            'message' => 'Próximo portfólio gerado com sucesso.',
            'ciclo' => $novo,
        ], 201);
    }
}
