<?php

namespace App\Support;

use App\Models\Curso;
use Illuminate\Database\Eloquent\Builder;

/** A mesma população de cursos em Cursos, Dashboard e relatórios. */
class ConsultaCatalogoCursos
{
    public static function ciclo(mixed $valor = null): ?int
    {
        if ($valor === 'todos') {
            return null;
        }
        return ($valor !== null && $valor !== '') ? (int) $valor : app(\App\Services\CicloContextoService::class)->id();
    }

    public static function query(array $filtros = []): Builder
    {
        $query = Curso::query();
        $ciclo = self::ciclo($filtros['ciclo_id'] ?? null);
        if ($ciclo !== null) {
            $query->where('ciclo_id', $ciclo);
        }
        if (! empty($filtros['busca'])) {
            $query->where(function ($q) use ($filtros) {
                foreach (['titulo', 'codigo_sig', 'processo_sei', 'eixo', 'unidade'] as $campo) {
                    $q->orWhere($campo, 'like', '%'.$filtros['busca'].'%');
                }
            });
        }
        if (! empty($filtros['ano'])) {
            $query->where('ultima_revisao', 'like', '%'.$filtros['ano'].'%');
        }
        if (! empty($filtros['eixo'])) {
            CatalogoOficial::aplicarFiltroEixo($query, $filtros['eixo']);
        }
        foreach (['status', 'tipo', 'segmento', 'programa'] as $campo) {
            if (! empty($filtros[$campo])) {
                $query->where($campo, $filtros[$campo]);
            }
        }
        if (! empty($filtros['unidade'])) {
            $query->where(fn ($q) => $q->where('unidade', $filtros['unidade'])
                ->orWhereJsonContains('unidades_oferta', $filtros['unidade']));
        }
        return $query;
    }
}
