<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AutorizaConsulta;
use App\Http\Controllers\Controller;
use App\Http\Requests\CursoRequest;
use App\Models\Curso;
use App\Models\PortfolioCiclo;
use App\Services\CursoDuplicidadeService;
use App\Support\CatalogoOficial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CursoController extends Controller
{
    use AutorizaConsulta;

    public function __construct(
        private readonly CursoDuplicidadeService $duplicidade,
    ) {}

    public function index(Request $request): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar cursos.')) {
            return $negado;
        }

        $query = \App\Support\ConsultaCatalogoCursos::query($request->all())->with('ciclo')->orderBy('id');

        $cursos = $query->get();

        $ciclos = PortfolioCiclo::query()
            ->withCount('cursos')
            ->orderByDesc('atual')
            ->orderByDesc('id')
            ->get()
            ->map(fn (PortfolioCiclo $ciclo) => [
                'id' => $ciclo->id,
                'nome' => $ciclo->nome,
                'atual' => (bool) $ciclo->atual,
                'anos' => $ciclo->anos(),
                'cursos_count' => (int) $ciclo->cursos_count,
            ]);

        $cicloAtual = $ciclos->firstWhere('atual', true) ?? $ciclos->first();
        $cicloAtualId = is_array($cicloAtual) ? ($cicloAtual['id'] ?? null) : null;

        return response()->json([
            'data' => $cursos,
            'meta' => [
                'total' => $cursos->count(),
                'eixos' => CatalogoOficial::eixos(),
                'segmentos' => CatalogoOficial::segmentos(),
                'segmentos_por_eixo' => CatalogoOficial::segmentosPorEixo(),
                'programas' => CatalogoOficial::programas(),
                'status' => config('cursos.status'),
                'modalidades' => CatalogoOficial::modalidades(),
                'sim_nao' => config('cursos.sim_nao'),
                'ciclos' => $ciclos,
                'ciclo_atual_id' => $cicloAtualId,
            ],
        ]);
    }

    public function store(CursoRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $cicloId = $payload['ciclo_id'] ?? PortfolioCiclo::atual()?->id;

        if ($bloqueio = $this->bloquearDuplicidade($payload, null, $cicloId)) {
            return $bloqueio;
        }

        $curso = Curso::create($payload);

        return response()->json([
            'message' => 'Curso cadastrado com sucesso.',
            'curso' => $curso->load('ciclo'),
        ], 201);
    }

    public function show(Request $request, Curso $curso): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar este curso.')) {
            return $negado;
        }

        return response()->json([
            'curso' => $curso->load('ciclo'),
            'dados_do_ciclo' => $curso->acompanhamentos()
                ->orderBy('codigo')
                ->orderBy('id')
                ->get([
                    'id', 'ciclo_id', 'curso_id', 'curso', 'codigo', 'unidade',
                    'turmas', 'alunos', 'instrutores', 'ch', 'status', 'observacao',
                ]),
        ]);
    }

    public function update(CursoRequest $request, Curso $curso): JsonResponse
    {
        $payload = $request->validated();
        $cicloId = $payload['ciclo_id'] ?? $curso->ciclo_id;

        if ($bloqueio = $this->bloquearDuplicidade($payload, $curso->id, $cicloId)) {
            return $bloqueio;
        }

        $curso->update($payload);

        return response()->json([
            'message' => 'Curso atualizado com sucesso.',
            'curso' => $curso->fresh()->load('ciclo'),
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

    /**
     * @param  array<string, mixed>  $payload
     */
    private function bloquearDuplicidade(array $payload, ?int $excetoId, mixed $cicloId): ?JsonResponse
    {
        $similares = $this->duplicidade->buscarSimilares(
            $payload,
            $excetoId,
            $cicloId ? (int) $cicloId : null,
        );

        if ($similares->isEmpty()) {
            return null;
        }

        if ($this->duplicidade->justificativaValida($payload['justificativa_duplicidade'] ?? null)) {
            return null;
        }

        return response()->json([
            'message' => 'Já existe curso semelhante neste ciclo. Confirme a criação e informe a justificativa.',
            'duplicidade' => true,
            'exige_justificativa' => true,
            'similares' => $similares->map(fn (Curso $curso) => [
                'id' => $curso->id,
                'titulo' => $curso->titulo,
                'codigo_sig' => $curso->codigo_sig,
                'processo_sei' => $curso->processo_sei,
                'status' => $curso->status,
            ])->values(),
        ], 409);
    }
}
