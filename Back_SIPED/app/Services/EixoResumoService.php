<?php

namespace App\Services;

use App\Models\Curso;
use App\Models\CursoPorEixo;
use App\Models\Eixo;
use App\Models\PortfolioCiclo;
use App\Models\Segmento;
use App\Support\CatalogoOficial;

class EixoResumoService
{
    /**
     * @return array{ciclo_id: ?int, ciclo_nome: ?string, eixos: list<array<string, mixed>>}
     */
    public function resumo(mixed $cicloId = null): array
    {
        [$ciclo, $cicloFiltro] = $this->resolverCiclo($cicloId);

        $eixos = Eixo::query()
            ->whereIn('nome', CatalogoOficial::eixos())
            ->orderBy('ordem')
            ->orderBy('nome')
            ->get();

        return [
            'ciclo_id' => $cicloFiltro,
            'ciclo_nome' => $ciclo?->nome,
            'eixos' => $eixos->map(fn (Eixo $eixo) => $this->cardEixo($eixo, $cicloFiltro))->all(),
            'pendentes' => $this->pendentes($cicloFiltro),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function detalhes(Eixo $eixo, mixed $cicloId = null): array
    {
        [$ciclo, $cicloFiltro] = $this->resolverCiclo($cicloId);
        $ofertas = $this->ofertasDoEixo($eixo->id, $cicloFiltro);

        $segmentos = Segmento::query()
            ->where('eixo_id', $eixo->id)
            ->orderBy('ordem')
            ->orderBy('nome')
            ->get()
            ->map(function (Segmento $segmento) use ($cicloFiltro, $ofertas) {
                $cursos = Curso::query()->where('segmento_id', $segmento->id);
                if ($cicloFiltro) {
                    $cursos->where('ciclo_id', $cicloFiltro);
                }

                $ofertasDoSegmento = $ofertas->where('segmento_id', $segmento->id);

                return [
                    'id' => $segmento->id,
                    'nome' => $segmento->nome,
                    'cursos' => $cursos->count(),
                    'ofertas' => $ofertasDoSegmento->count(),
                    'turmas' => $this->somarInteiros($ofertasDoSegmento->pluck('turmas')),
                    'alunos' => $this->somarInteiros($ofertasDoSegmento->pluck('alunos')),
                ];
            })
            ->all();

        $cursos = Curso::query()->where('eixo_id', $eixo->id);
        if ($cicloFiltro) {
            $cursos->where('ciclo_id', $cicloFiltro);
        }

        return [
            'ciclo_id' => $cicloFiltro,
            'ciclo_nome' => $ciclo?->nome,
            'eixo' => $this->cardEixo($eixo, $cicloFiltro),
            'segmentos' => $segmentos,
            'cursos' => $cursos->orderBy('titulo')->get(['id', 'titulo', 'segmento', 'programa', 'status', 'codigo_sig'])->all(),
            'ofertas' => $ofertas->map(fn (CursoPorEixo $oferta) => [
                'id' => $oferta->id,
                'curso_id' => $oferta->curso_id,
                'curso' => $oferta->curso,
                'segmento' => $oferta->segmento,
                'programa' => $oferta->programa,
                'codigo' => $oferta->codigo,
                'ch' => $oferta->ch,
                'turmas' => $oferta->turmas,
                'alunos' => $oferta->alunos,
                'instrutores' => $oferta->instrutores,
                'status' => $oferta->status,
            ])->values()->all(),
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

        $ofertas = $this->ofertasDoEixo($eixo->id, $cicloId);

        return [
            'id' => $eixo->id,
            'nome' => $eixo->nome,
            'ordem' => $eixo->ordem,
            'cursos' => $cursos->count(),
            'ofertas' => $ofertas->count(),
            'turmas' => $this->somarInteiros($ofertas->pluck('turmas')),
            'alunos' => $this->somarInteiros($ofertas->pluck('alunos')),
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, CursoPorEixo>
     */
    private function ofertasDoEixo(int $eixoId, ?int $cicloId)
    {
        $query = CursoPorEixo::query()->where('eixo_id', $eixoId)->orderBy('curso');
        if ($cicloId) {
            $query->where('ciclo_id', $cicloId);
        }

        return $query->get();
    }

    /**
     * @return array{0: ?PortfolioCiclo, 1: ?int}
     */
    private function resolverCiclo(mixed $cicloId): array
    {
        if ($cicloId === null || $cicloId === '' || $cicloId === 'todos') {
            $ciclo = PortfolioCiclo::atual();

            return [$ciclo, $ciclo?->id];
        }

        $ciclo = PortfolioCiclo::query()->find($cicloId);

        return [$ciclo, (int) $cicloId];
    }

    /**
     * @return array{cursos: int, ofertas: int, sem_correspondencia: int, amostra: list<array<string, mixed>>, amostra_sem_correspondencia: list<array<string, mixed>>}
     */
    public function pendentes(?int $cicloId = null): array
    {
        if ($cicloId === null) {
            [$ciclo, $cicloId] = $this->resolverCiclo(null);
            unset($ciclo);
        }

        $cursosQuery = Curso::query()->whereNull('eixo_id');
        $ofertasQuery = CursoPorEixo::query()->whereNull('eixo_id');
        if ($cicloId) {
            $cursosQuery->where('ciclo_id', $cicloId);
            $ofertasQuery->where('ciclo_id', $cicloId);
        }

        $cursosCount = (clone $cursosQuery)->count();
        $ofertasCount = (clone $ofertasQuery)->count();

        $amostra = (clone $cursosQuery)->orderBy('id')->limit(30)->get(['id', 'titulo', 'eixo', 'segmento', 'programa'])
            ->map(fn (Curso $curso) => [
                'tipo' => 'curso',
                'id' => $curso->id,
                'nome' => $curso->titulo,
                'eixo_original' => $curso->eixo,
                'segmento' => $curso->segmento,
                'programa' => $curso->programa,
            ])->all();

        $ofertasAmostra = (clone $ofertasQuery)->orderBy('id')->limit(30)->get(['id', 'curso', 'eixo', 'segmento', 'programa'])
            ->map(fn (CursoPorEixo $oferta) => [
                'tipo' => 'oferta',
                'id' => $oferta->id,
                'nome' => $oferta->curso,
                'eixo_original' => $oferta->eixo,
                'segmento' => $oferta->segmento,
                'programa' => $oferta->programa,
            ])->all();

        $semCorrespondenciaQuery = CursoPorEixo::query()
            ->whereNull('curso_id')
            ->whereNotNull('eixo_id');
        if ($cicloId) {
            $semCorrespondenciaQuery->where('ciclo_id', $cicloId);
        }

        $semCorrespondenciaCount = (clone $semCorrespondenciaQuery)->count();
        $semCorrespondenciaAmostra = (clone $semCorrespondenciaQuery)
            ->orderBy('id')
            ->limit(40)
            ->get(['id', 'curso', 'eixo', 'segmento', 'codigo', 'programa'])
            ->map(fn (CursoPorEixo $oferta) => [
                'tipo' => 'oferta',
                'id' => $oferta->id,
                'nome' => $oferta->curso,
                'eixo_original' => $oferta->eixo,
                'segmento' => $oferta->segmento,
                'programa' => $oferta->programa,
                'codigo' => $oferta->codigo,
                'motivo' => 'Sem correspondência no catálogo de Cursos',
            ])->all();

        return [
            'cursos' => $cursosCount,
            'ofertas' => $ofertasCount,
            'sem_correspondencia' => $semCorrespondenciaCount,
            'amostra' => array_slice(array_merge($amostra, $ofertasAmostra), 0, 40),
            'amostra_sem_correspondencia' => $semCorrespondenciaAmostra,
        ];
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
