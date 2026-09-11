<?php

namespace App\Services;

use App\Models\Ciclo;
use App\Models\Curso;
use App\Models\CursoExecucao;
use App\Models\Eixo;
use App\Models\Segmento;
use App\Support\CatalogoOficial;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EixoResumoService
{
    /**
     * @return array<string, mixed>
     */
    public function resumo(mixed $cicloId = null): array
    {
        [$ciclo, $cicloFiltro] = $this->resolverCiclo($cicloId);

        $eixos = Eixo::query()
            ->whereIn('nome', CatalogoOficial::eixos())
            ->orderBy('ordem')
            ->orderBy('nome')
            ->get();

        $cards = $eixos->map(fn (Eixo $eixo) => $this->cardEixo($eixo, $cicloFiltro))->all();

        $cursosClassificados = Curso::query()->whereNotNull('eixo_id');
        if ($cicloFiltro) {
            $cursosClassificados->where('ciclo_id', $cicloFiltro);
        }

        return [
            'ciclo_id' => $cicloFiltro,
            'ciclo_nome' => $ciclo?->nome,
            'totais' => [
                'eixos' => count($cards),
                'cursos_classificados' => $cursosClassificados->count(),
                'cursos' => $cursosClassificados->count(),
                'turmas' => array_sum(array_column($cards, 'turmas')),
                'alunos' => array_sum(array_column($cards, 'alunos')),
            ],
            'eixos' => $cards,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function detalhes(Eixo $eixo, mixed $cicloId = null): array
    {
        [$ciclo, $cicloFiltro] = $this->resolverCiclo($cicloId);
        $indicadores = $this->cardEixo($eixo, $cicloFiltro);
        $dados = $this->queryDadosValidos($cicloFiltro, $eixo->id)->get();

        $segmentos = Segmento::query()
            ->where('eixo_id', $eixo->id)
            ->orderBy('ordem')
            ->orderBy('nome')
            ->get()
            ->map(function (Segmento $segmento) use ($cicloFiltro, $dados) {
                $cursos = Curso::query()->where('segmento_id', $segmento->id);
                if ($cicloFiltro) {
                    $cursos->where('ciclo_id', $cicloFiltro);
                }

                $doSegmento = $dados->where('segmento_id', $segmento->id);

                return [
                    'id' => $segmento->id,
                    'nome' => $segmento->nome,
                    'cursos' => $cursos->count(),
                    'turmas' => $this->somarInteiros($doSegmento->pluck('turmas')),
                    'alunos' => $this->somarInteiros($doSegmento->pluck('alunos')),
                ];
            })
            ->all();

        return [
            'ciclo_id' => $cicloFiltro,
            'ciclo_nome' => $ciclo?->nome,
            'eixo' => $indicadores,
            'indicadores' => $indicadores,
            'segmentos' => $segmentos,
        ];
    }

    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, mixed>}
     */
    public function cursos(Eixo $eixo, mixed $cicloId = null, mixed $busca = null, int $perPage = 25): array
    {
        [, $cicloFiltro] = $this->resolverCiclo($cicloId);

        $query = Curso::query()->where('eixo_id', $eixo->id)->orderBy('titulo');
        if ($cicloFiltro) {
            $query->where('ciclo_id', $cicloFiltro);
        }
        if (is_string($busca) && trim($busca) !== '') {
            $termo = trim($busca);
            $query->where(function ($q) use ($termo) {
                $q->where('titulo', 'like', "%{$termo}%")
                    ->orWhere('segmento', 'like', "%{$termo}%")
                    ->orWhere('modalidade', 'like', "%{$termo}%")
                    ->orWhere('codigo_sig', 'like', "%{$termo}%");
            });
        }

        $paginator = $query->paginate(max(1, min(50, $perPage)));
        $totais = $this->totaisPorCurso($paginator, $cicloFiltro);

        $linhas = $paginator->getCollection()->map(function (Curso $curso) use ($totais) {
            $dados = $totais[$curso->id] ?? ['turmas' => 0, 'alunos' => 0];

            return [
                'id' => $curso->id,
                'titulo' => $curso->titulo,
                'segmento' => $curso->segmento,
                'modalidade' => $curso->modalidade,
                'codigo_sig' => $curso->codigo_sig,
                'status' => $curso->status,
                'turmas' => $dados['turmas'],
                'alunos' => $dados['alunos'],
            ];
        })->values()->all();

        return [
            'data' => $linhas,
            'meta' => $this->metaPaginacao($paginator),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function cardEixo(Eixo $eixo, ?int $cicloId): array
    {
        $cursos = Curso::query()->where('eixo_id', $eixo->id);
        if ($cicloId) {
            $cursos->where('ciclo_id', $cicloId);
        }

        $dados = $this->queryDadosValidos($cicloId, $eixo->id)->get(['turmas', 'alunos']);

        return [
            'id' => $eixo->id,
            'nome' => $eixo->nome,
            'ordem' => $eixo->ordem,
            'cursos' => $cursos->count(),
            'turmas' => $this->somarInteiros($dados->pluck('turmas')),
            'alunos' => $this->somarInteiros($dados->pluck('alunos')),
        ];
    }

    private function queryDadosValidos(?int $cicloId, ?int $eixoId = null)
    {
        $query = CursoExecucao::query()
            ->whereNotNull('curso_id')
            ->whereNotNull('eixo_id');

        if ($eixoId) {
            $query->where('eixo_id', $eixoId);
        }

        if ($cicloId) {
            $query->where('ciclo_id', $cicloId)
                ->whereHas('cursoRef', function ($cursos) use ($cicloId, $eixoId) {
                    $cursos->where('ciclo_id', $cicloId);
                    if ($eixoId) {
                        $cursos->where('eixo_id', $eixoId);
                    }
                });
        }

        return $query;
    }

    /**
     * @param  LengthAwarePaginator<int, Curso>  $paginator
     * @return array<int, array{turmas: int, alunos: int}>
     */
    private function totaisPorCurso(LengthAwarePaginator $paginator, ?int $cicloId): array
    {
        $ids = $paginator->getCollection()->pluck('id')->filter()->all();
        if ($ids === []) {
            return [];
        }

        $linhas = CursoExecucao::query()
            ->whereIn('curso_id', $ids)
            ->whereNotNull('curso_id')
            ->whereNotNull('eixo_id');
        if ($cicloId) {
            $linhas->where('ciclo_id', $cicloId);
        }

        $agrupado = [];
        foreach ($linhas->get(['curso_id', 'turmas', 'alunos']) as $linha) {
            $cursoId = (int) $linha->curso_id;
            if (! isset($agrupado[$cursoId])) {
                $agrupado[$cursoId] = ['turmas' => 0, 'alunos' => 0];
            }
            $agrupado[$cursoId]['turmas'] += $this->somarInteiros([$linha->turmas]);
            $agrupado[$cursoId]['alunos'] += $this->somarInteiros([$linha->alunos]);
        }

        return $agrupado;
    }

    /**
     * @param  LengthAwarePaginator<int, mixed>  $paginator
     * @return array<string, mixed>
     */
    private function metaPaginacao(LengthAwarePaginator $paginator): array
    {
        return [
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }

    /**
     * @return array{0: ?Ciclo, 1: ?int}
     */
    private function resolverCiclo(mixed $cicloId): array
    {
        if ($cicloId === null || $cicloId === '' || $cicloId === 'todos') {
            $ciclo = app(CicloContextoService::class)->resolver();

            return [$ciclo, $ciclo?->id];
        }

        $ciclo = Ciclo::query()->find($cicloId);

        return [$ciclo, (int) $cicloId];
    }

    /**
     * @param  iterable<int, mixed>  $valores
     */
    private function somarInteiros(iterable $valores): int
    {
        $total = 0;
        foreach ($valores as $valor) {
            $total += (int) preg_replace('/\D+/', '', (string) $valor);
        }

        return $total;
    }
}
