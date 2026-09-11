<?php

namespace App\Services;

use App\Models\Ciclo;
use App\Models\Curso;
use App\Models\CursoExecucao;
use App\Support\CatalogoOficial;
use App\Support\ConciliadorCursoOferta;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RevisaoDadosService
{
    /**
     * @return array{total: int, sem_eixo: int, sem_vinculo: int, cursos: int, sem_correspondencia: int}
     */
    public function totais(?int $cicloId = null): array
    {
        $cicloId = $this->resolverCicloId($cicloId);
        $semEixo = $this->querySemEixo($cicloId)->count();
        $semVinculo = $this->querySemVinculo($cicloId)->count();

        return [
            'total' => $semEixo + $semVinculo,
            'sem_eixo' => $semEixo,
            'sem_vinculo' => $semVinculo,
            'cursos' => $semEixo,
            'sem_correspondencia' => $semVinculo,
            'ofertas' => 0,
        ];
    }

    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, mixed>, totais: array<string, int>}
     */
    public function listar(string $tipo, mixed $cicloId = null, mixed $busca = null, int $perPage = 25): array
    {
        $cicloId = $this->resolverCicloId($cicloId);
        $perPage = max(1, min(50, $perPage));

        if ($tipo === 'sem_vinculo') {
            $paginator = $this->querySemVinculo($cicloId, is_string($busca) ? $busca : null)
                ->orderBy('id')
                ->paginate($perPage);
            $cursos = $this->cursosDoCiclo($cicloId);
            $linhas = $paginator->getCollection()->map(function (CursoExecucao $item) use ($cursos) {
                return [
                    'id' => $item->id,
                    'tipo' => 'registro',
                    'nome' => $item->curso,
                    'eixo' => $item->eixo,
                    'segmento' => $item->segmento,
                    'codigo' => $item->codigo,
                    'unidade' => $item->unidade,
                    'turmas' => $item->turmas,
                    'alunos' => $item->alunos,
                    'ciclo_id' => $item->ciclo_id,
                    'sugestoes' => ConciliadorCursoOferta::sugerir($cursos, (string) $item->curso),
                ];
            })->values()->all();
        } else {
            $paginator = $this->querySemEixo($cicloId, is_string($busca) ? $busca : null)
                ->orderBy('id')
                ->paginate($perPage);
            $linhas = $paginator->getCollection()->map(fn (Curso $curso) => [
                'id' => $curso->id,
                'tipo' => 'curso',
                'nome' => $curso->titulo,
                'eixo_original' => $curso->eixo,
                'segmento' => $curso->segmento,
                'programa' => $curso->programa,
                'codigo_sig' => $curso->codigo_sig,
                'ciclo_id' => $curso->ciclo_id,
            ])->values()->all();
        }

        return [
            'data' => $linhas,
            'meta' => $this->metaPaginacao($paginator),
            'totais' => $this->totais($cicloId),
            'eixos' => CatalogoOficial::eixos(),
            'segmentos_por_eixo' => CatalogoOficial::segmentosPorEixo(),
        ];
    }

    public function classificar(Curso $curso, string $eixo, ?string $segmento = null): Curso
    {
        $curso->eixo = $eixo;
        $curso->segmento = $segmento;
        $curso->save();

        return $curso->fresh();
    }

    private function querySemEixo(?int $cicloId, ?string $busca = null)
    {
        $query = Curso::query()->whereNull('eixo_id');
        if ($cicloId) {
            $query->where('ciclo_id', $cicloId);
        }
        if ($busca && trim($busca) !== '') {
            $termo = trim($busca);
            $query->where(function ($q) use ($termo) {
                $q->where('titulo', 'like', "%{$termo}%")
                    ->orWhere('eixo', 'like', "%{$termo}%")
                    ->orWhere('segmento', 'like', "%{$termo}%")
                    ->orWhere('codigo_sig', 'like', "%{$termo}%");
            });
        }

        return $query;
    }

    private function querySemVinculo(?int $cicloId, ?string $busca = null)
    {
        $query = CursoExecucao::query()->whereNull('curso_id');
        if ($cicloId) {
            $query->where('ciclo_id', $cicloId);
        }
        if ($busca && trim($busca) !== '') {
            $termo = trim($busca);
            $query->where(function ($q) use ($termo) {
                $q->where('curso', 'like', "%{$termo}%")
                    ->orWhere('eixo', 'like', "%{$termo}%")
                    ->orWhere('segmento', 'like', "%{$termo}%")
                    ->orWhere('codigo', 'like', "%{$termo}%");
            });
        }

        return $query;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Curso>
     */
    private function cursosDoCiclo(?int $cicloId)
    {
        $query = Curso::query()->select([
            'id', 'titulo', 'eixo', 'segmento', 'carga_horaria', 'codigo_sig', 'codigo_dn', 'ciclo_id', 'programa',
        ]);
        if ($cicloId) {
            $query->where('ciclo_id', $cicloId);
        }

        return $query->orderBy('titulo')->get();
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

    private function resolverCicloId(mixed $cicloId): ?int
    {
        if ($cicloId === null || $cicloId === '' || $cicloId === 'todos') {
            return app(CicloContextoService::class)->id();
        }

        $ciclo = Ciclo::query()->find($cicloId);

        return $ciclo?->id ?? (int) $cicloId;
    }
}
