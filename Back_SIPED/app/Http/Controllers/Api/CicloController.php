<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AutorizaConsulta;
use App\Http\Controllers\Controller;
use App\Http\Requests\PortfolioCicloGerarRequest;
use App\Http\Requests\PortfolioCicloRequest;
use App\Models\Ciclo;
use App\Models\Curso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CicloController extends Controller
{
    use AutorizaConsulta;

    /** @var list<string> */
    private const COUNTS = [
        'cursos', 'planoDeMetas', 'pcas', 'cursosPorEixo',
        'visitasTecnicas', 'horasPedagogicas', 'acoesExtensivas',
        'jornadasPedagogicas', 'eventos',
    ];

    public function index(Request $request): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar ciclos de gestão.')) {
            return $negado;
        }

        $query = Ciclo::query()
            ->with('origem:id,nome')
            ->withCount(self::COUNTS)
            ->orderByDesc('atual')
            ->orderByDesc('id');

        if ($request->filled('busca')) {
            $busca = $request->busca;
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                    ->orWhere('observacao', 'like', "%{$busca}%");
            });
        }

        $ciclos = $query->get()->map(fn (Ciclo $ciclo) => $this->serializar($ciclo));

        return response()->json([
            'data' => $ciclos,
            'meta' => [
                'total' => $ciclos->count(),
                'total_geral' => Ciclo::query()->count(),
                'ciclo_atual_id' => Ciclo::atual()?->id,
            ],
        ]);
    }

    public function store(PortfolioCicloRequest $request): JsonResponse
    {
        $ciclo = DB::transaction(function () use ($request) {
            $ciclo = Ciclo::create([
                'nome' => $request->validated('nome'),
                'observacao' => $request->validated('observacao'),
                'atual' => false,
            ]);

            if ($request->boolean('atual')) {
                $ciclo->marcarComoAtual();
            }

            return $ciclo->fresh()->load('origem:id,nome')->loadCount(self::COUNTS);
        });

        return response()->json([
            'message' => 'Ciclo de gestão cadastrado com sucesso.',
            'ciclo' => $this->serializar($ciclo),
        ], 201);
    }

    public function show(Request $request, Ciclo $ciclo): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar este ciclo de gestão.')) {
            return $negado;
        }

        $ciclo->load('origem:id,nome')->loadCount(self::COUNTS);

        return response()->json([
            'ciclo' => $this->serializar($ciclo),
        ]);
    }

    public function update(PortfolioCicloRequest $request, Ciclo $ciclo): JsonResponse
    {
        $ciclo = DB::transaction(function () use ($request, $ciclo) {
            $ciclo->update([
                'nome' => $request->validated('nome'),
                'observacao' => $request->validated('observacao'),
            ]);

            if ($request->boolean('atual')) {
                $ciclo->marcarComoAtual();
            }

            return $ciclo->fresh()->load('origem:id,nome')->loadCount(self::COUNTS);
        });

        return response()->json([
            'message' => 'Ciclo de gestão atualizado com sucesso.',
            'ciclo' => $this->serializar($ciclo),
        ]);
    }

    public function destroy(Request $request, Ciclo $ciclo): JsonResponse
    {
        if (! $request->user()?->podeEditarDados()) {
            return response()->json([
                'message' => 'Você não tem permissão para excluir ciclos de gestão.',
            ], 403);
        }

        if ($ciclo->atual) {
            return response()->json([
                'message' => 'Não é possível excluir o ciclo atual. Defina outro ciclo como atual antes.',
            ], 422);
        }

        $composicao = $ciclo->composicao();
        if (array_sum($composicao) > 0) {
            return response()->json([
                'message' => 'Não é possível excluir um ciclo que ainda possui registros vinculados. Encerre ou mova os registros antes; a exclusão em cascata não é permitida.',
                'exige_limpeza' => false,
                'composicao' => $composicao,
            ], 422);
        }

        $nome = $ciclo->nome;
        $ciclo->delete();

        return response()->json([
            'message' => "Ciclo de gestão \"{$nome}\" excluído com sucesso.",
        ]);
    }

    public function marcarAtual(Request $request, Ciclo $ciclo): JsonResponse
    {
        if (! $request->user()?->podeEditarDados()) {
            return response()->json([
                'message' => 'Você não tem permissão para definir o ciclo atual.',
            ], 403);
        }

        $ciclo->marcarComoAtual();
        $ciclo->load('origem:id,nome')->loadCount(self::COUNTS);

        return response()->json([
            'message' => "O ciclo \"{$ciclo->nome}\" passou a ser o ciclo de gestão atual.",
            'ciclo' => $this->serializar($ciclo),
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

            $ciclo = Ciclo::create([
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

            return $ciclo->fresh()->load('origem:id,nome')->loadCount(self::COUNTS);
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
    private function serializar(Ciclo $ciclo): array
    {
        $composicao = [
            'cursos' => (int) ($ciclo->cursos_count ?? $ciclo->cursos()->count()),
            'plano_de_metas' => (int) ($ciclo->plano_de_metas_count ?? $ciclo->planoDeMetas()->count()),
            'pca' => (int) ($ciclo->pcas_count ?? $ciclo->pcas()->count()),
            'eixos' => (int) ($ciclo->cursos_por_eixo_count ?? $ciclo->cursosPorEixo()->count()),
            'visitas' => (int) ($ciclo->visitas_tecnicas_count ?? $ciclo->visitasTecnicas()->count()),
            'horas_pedagogicas' => (int) ($ciclo->horas_pedagogicas_count ?? $ciclo->horasPedagogicas()->count()),
            'acoes' => (int) ($ciclo->acoes_extensivas_count ?? $ciclo->acoesExtensivas()->count()),
            'jornadas' => (int) ($ciclo->jornadas_pedagogicas_count ?? $ciclo->jornadasPedagogicas()->count()),
            'eventos' => (int) ($ciclo->eventos_count ?? $ciclo->eventos()->count()),
        ];

        return [
            'id' => $ciclo->id,
            'nome' => $ciclo->nome,
            'origem_id' => $ciclo->origem_id,
            'origem_nome' => $ciclo->origem?->nome,
            'atual' => (bool) $ciclo->atual,
            'observacao' => $ciclo->observacao,
            'anos' => $ciclo->anos(),
            'cursos_count' => $composicao['cursos'],
            'composicao' => $composicao,
            'created_at' => $ciclo->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
        ];
    }
}
