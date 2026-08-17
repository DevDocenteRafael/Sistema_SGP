<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AutorizaConsulta;
use App\Http\Controllers\Controller;
use App\Http\Requests\CursoRequest;
use App\Models\Curso;
use App\Models\PortfolioCiclo;
use App\Services\CursoDuplicidadeService;
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

        $query = Curso::query()->with('ciclo')->orderBy('id');

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

        if ($request->input('ciclo_id') === 'todos') {
            // sem filtro de ciclo
        } elseif ($request->filled('ciclo_id')) {
            $query->where('ciclo_id', $request->ciclo_id);
        } else {
            $cicloAtualId = PortfolioCiclo::atual()?->id;
            if ($cicloAtualId) {
                $query->where('ciclo_id', $cicloAtualId);
            }
        }

        $cursos = $query->get();

        $eixosConfig = config('eixos', []);
        $eixosDb = Curso::query()
            ->whereNotNull('eixo')
            ->where('eixo', '!=', '')
            ->distinct()
            ->orderBy('eixo')
            ->pluck('eixo')
            ->all();
        $eixos = array_values(array_unique(array_merge($eixosConfig, $eixosDb)));

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

        return response()->json([
            'data' => $cursos,
            'meta' => [
                'total' => $cursos->count(),
                'eixos' => $eixos,
                'status' => config('cursos.status'),
                'tipos' => config('cursos.tipos'),
                'modalidades' => config('cursos.modalidades'),
                'sim_nao' => config('cursos.sim_nao'),
                'ciclos' => $ciclos,
                'ciclo_atual_id' => PortfolioCiclo::atual()?->id,
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
