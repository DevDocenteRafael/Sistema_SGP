<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AutorizaConsulta;
use App\Http\Controllers\Controller;
use App\Http\Requests\PortfolioCicloGerarRequest;
use App\Http\Requests\PortfolioCicloRequest;
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

        $query = PortfolioCiclo::query()
            ->with('origem:id,nome')
            ->withCount(['cursos', 'planoDeMetas', 'pcas', 'cursosPorEixo'])
            ->orderByDesc('atual')
            ->orderByDesc('id');

        if ($request->filled('busca')) {
            $busca = $request->busca;
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                    ->orWhere('observacao', 'like', "%{$busca}%");
            });
        }

        $ciclos = $query->get()->map(fn (PortfolioCiclo $ciclo) => $this->serializar($ciclo));

        return response()->json([
            'data' => $ciclos,
            'meta' => [
                'total' => $ciclos->count(),
                'total_geral' => PortfolioCiclo::query()->count(),
                'ciclo_atual_id' => PortfolioCiclo::atual()?->id,
            ],
        ]);
    }

    public function store(PortfolioCicloRequest $request): JsonResponse
    {
        $ciclo = DB::transaction(function () use ($request) {
            $ciclo = PortfolioCiclo::create([
                'nome' => $request->validated('nome'),
                'observacao' => $request->validated('observacao'),
                'atual' => false,
            ]);

            if ($request->boolean('atual')) {
                $ciclo->marcarComoAtual();
            }

            return $ciclo->fresh()->load('origem:id,nome')->loadCount(['cursos', 'planoDeMetas', 'pcas', 'cursosPorEixo']);
        });

        return response()->json([
            'message' => 'Ciclo de portfólio cadastrado com sucesso.',
            'ciclo' => $this->serializar($ciclo),
        ], 201);
    }

    public function show(Request $request, PortfolioCiclo $portfolioCiclo): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar este ciclo de portfólio.')) {
            return $negado;
        }

        $portfolioCiclo->load('origem:id,nome')->loadCount(['cursos', 'planoDeMetas', 'pcas', 'cursosPorEixo']);

        return response()->json([
            'ciclo' => $this->serializar($portfolioCiclo),
        ]);
    }

    public function update(PortfolioCicloRequest $request, PortfolioCiclo $portfolioCiclo): JsonResponse
    {
        $ciclo = DB::transaction(function () use ($request, $portfolioCiclo) {
            $portfolioCiclo->update([
                'nome' => $request->validated('nome'),
                'observacao' => $request->validated('observacao'),
            ]);

            if ($request->boolean('atual')) {
                $portfolioCiclo->marcarComoAtual();
            }

            return $portfolioCiclo->fresh()->load('origem:id,nome')->loadCount(['cursos', 'planoDeMetas', 'pcas', 'cursosPorEixo']);
        });

        return response()->json([
            'message' => 'Ciclo de portfólio atualizado com sucesso.',
            'ciclo' => $this->serializar($ciclo),
        ]);
    }

    public function destroy(Request $request, PortfolioCiclo $portfolioCiclo): JsonResponse
    {
        if (! $request->user()?->podeEditarDados()) {
            return response()->json([
                'message' => 'Você não tem permissão para excluir ciclos de portfólio.',
            ], 403);
        }

        if ($portfolioCiclo->atual) {
            return response()->json([
                'message' => 'Não é possível excluir o ciclo atual. Defina outro ciclo como atual antes.',
            ], 422);
        }

        if ($portfolioCiclo->cursos()->exists()
            || $portfolioCiclo->planoDeMetas()->exists()
            || $portfolioCiclo->pcas()->exists()
            || $portfolioCiclo->cursosPorEixo()->exists()) {
            return response()->json([
                'message' => 'Não é possível excluir um ciclo que ainda possui cursos, metas, PCA ou eixos. Mova ou exclua os registros primeiro.',
            ], 422);
        }

        $nome = $portfolioCiclo->nome;
        $portfolioCiclo->delete();

        return response()->json([
            'message' => "Ciclo de portfólio \"{$nome}\" excluído com sucesso.",
        ]);
    }

    public function marcarAtual(Request $request, PortfolioCiclo $portfolioCiclo): JsonResponse
    {
        if (! $request->user()?->podeEditarDados()) {
            return response()->json([
                'message' => 'Você não tem permissão para definir o ciclo atual.',
            ], 403);
        }

        $portfolioCiclo->marcarComoAtual();
        $portfolioCiclo->load('origem:id,nome')->loadCount(['cursos', 'planoDeMetas', 'pcas', 'cursosPorEixo']);

        return response()->json([
            'message' => "O ciclo \"{$portfolioCiclo->nome}\" passou a ser o portfólio atual.",
            'ciclo' => $this->serializar($portfolioCiclo),
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
                'observacao' => $request->validated('observacao')
                    ?? 'Gerado a partir do ciclo '.$origem->nome,
            ]);

            Curso::query()
                ->where('ciclo_id', $origem->id)
                ->orderBy('id')
                ->get()
                ->each(fn (Curso $curso) => $curso->replicarParaCiclo($ciclo));

            if ($marcarAtual) {
                $ciclo->marcarComoAtual();
            }

            return $ciclo->fresh()->load('origem:id,nome')->loadCount(['cursos', 'planoDeMetas', 'pcas', 'cursosPorEixo']);
        });

        return response()->json([
            'message' => 'Próximo portfólio gerado com sucesso.',
            'ciclo' => $this->serializar($novo),
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializar(PortfolioCiclo $ciclo): array
    {
        $cursosCount = (int) ($ciclo->cursos_count ?? $ciclo->cursos()->count());

        return [
            'id' => $ciclo->id,
            'nome' => $ciclo->nome,
            'origem_id' => $ciclo->origem_id,
            'origem_nome' => $ciclo->origem?->nome,
            'atual' => (bool) $ciclo->atual,
            'observacao' => $ciclo->observacao,
            'anos' => $ciclo->anos(),
            'cursos_count' => $cursosCount,
            'composicao' => [
                'cursos' => $cursosCount,
                'plano_de_metas' => (int) ($ciclo->plano_de_metas_count ?? $ciclo->planoDeMetas()->count()),
                'pca' => (int) ($ciclo->pcas_count ?? $ciclo->pcas()->count()),
                'eixos' => (int) ($ciclo->cursos_por_eixo_count ?? $ciclo->cursosPorEixo()->count()),
            ],
            'created_at' => $ciclo->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
        ];
    }
}
