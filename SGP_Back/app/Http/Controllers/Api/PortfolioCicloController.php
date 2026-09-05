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

        $portfolioCiclo->loadCount(['cursos', 'planoDeMetas', 'pcas', 'cursosPorEixo']);
        $temRegistros = $portfolioCiclo->cursos_count > 0
            || $portfolioCiclo->plano_de_metas_count > 0
            || $portfolioCiclo->pcas_count > 0
            || $portfolioCiclo->cursos_por_eixo_count > 0;

        $limparRegistros = $request->boolean('limpar_registros');

        if ($temRegistros && ! $limparRegistros) {
            return response()->json([
                'message' => 'Não é possível excluir um ciclo que ainda possui cursos, metas, PCA ou eixos. Confirme a exclusão com limpeza ou mova os registros antes.',
                'exige_limpeza' => true,
                'composicao' => [
                    'cursos' => (int) $portfolioCiclo->cursos_count,
                    'plano_de_metas' => (int) $portfolioCiclo->plano_de_metas_count,
                    'pca' => (int) $portfolioCiclo->pcas_count,
                    'eixos' => (int) $portfolioCiclo->cursos_por_eixo_count,
                ],
            ], 422);
        }

        $nome = $portfolioCiclo->nome;
        $resumo = [
            'cursos' => (int) $portfolioCiclo->cursos_count,
            'plano_de_metas' => (int) $portfolioCiclo->plano_de_metas_count,
            'pca' => (int) $portfolioCiclo->pcas_count,
            'eixos' => (int) $portfolioCiclo->cursos_por_eixo_count,
        ];

        DB::transaction(function () use ($portfolioCiclo, $limparRegistros) {
            if ($limparRegistros) {
                $portfolioCiclo->cursos()->delete();
                $portfolioCiclo->planoDeMetas()->delete();
                $portfolioCiclo->pcas()->delete();
                $portfolioCiclo->cursosPorEixo()->delete();
            }

            $portfolioCiclo->delete();
        });

        $sufixo = $limparRegistros && $temRegistros
            ? ' Os registros vinculados a este ciclo também foram removidos.'
            : '';

        return response()->json([
            'message' => "Ciclo de portfólio \"{$nome}\" excluído com sucesso.{$sufixo}",
            'limpeza' => $limparRegistros ? $resumo : null,
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
        $copiarCursos = $request->deveCopiarCursos();
        $cursosOrigem = $copiarCursos
            ? Curso::query()->where('ciclo_id', $origem->id)->orderBy('id')->get()
            : collect();

        $novo = DB::transaction(function () use ($request, $origem, $marcarAtual, $copiarCursos, $cursosOrigem) {
            $observacao = $request->validated('observacao');
            if ($observacao === null) {
                $observacao = $copiarCursos
                    ? 'Gerado a partir do ciclo '.$origem->nome
                    : 'Gerado a partir do ciclo '.$origem->nome.' (sem copiar cursos)';
            }

            $ciclo = PortfolioCiclo::create([
                'nome' => $request->validated('nome'),
                'origem_id' => $origem->id,
                'atual' => false,
                'observacao' => $observacao,
            ]);

            if ($copiarCursos) {
                $cursosOrigem->each(fn (Curso $curso) => $curso->replicarParaCiclo($ciclo));
            }

            if ($marcarAtual) {
                $ciclo->marcarComoAtual();
            }

            return $ciclo->fresh()->load('origem:id,nome')->loadCount(['cursos', 'planoDeMetas', 'pcas', 'cursosPorEixo']);
        });

        $copiados = $copiarCursos ? $cursosOrigem->count() : 0;
        $mensagem = $copiarCursos
            ? ($copiados === 1
                ? 'Próximo ciclo gerado com sucesso. 1 curso foi copiado.'
                : "Próximo ciclo gerado com sucesso. {$copiados} cursos foram copiados.")
            : 'Próximo ciclo gerado com sucesso, sem copiar cursos.';

        return response()->json([
            'message' => $mensagem,
            'ciclo' => $this->serializar($novo),
            'cursos_copiados' => $copiados,
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
