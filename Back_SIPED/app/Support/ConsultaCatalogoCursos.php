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

    /** Expressão SQL que remove a pontuação comum de SEI/SIG (funciona em MySQL e SQLite). */
    private static function somenteDigitosSql(string $coluna): string
    {
        $sql = $coluna;
        foreach (['.', '/', '-', ' '] as $caractere) {
            $sql = "REPLACE({$sql}, '{$caractere}', '')";
        }

        return $sql;
    }

    public static function query(array $filtros = []): Builder
    {
        $query = Curso::query();
        $ciclo = self::ciclo($filtros['ciclo_id'] ?? null);
        if ($ciclo !== null) {
            $query->where('ciclo_id', $ciclo);
        }
        $busca = trim((string) ($filtros['busca'] ?? ''));
        if ($busca !== '') {
            $query->where(function ($q) use ($busca) {
                foreach (['titulo', 'codigo_sig', 'codigo_dn', 'processo_sei', 'eixo', 'segmento', 'unidade', 'programa'] as $campo) {
                    $q->orWhere($campo, 'like', '%'.$busca.'%');
                }

                // SEI/SIG digitados com ou sem pontuação: compara só os dígitos.
                $digitos = preg_replace('/\D+/', '', $busca);
                if (strlen($digitos) >= 4) {
                    foreach (['processo_sei', 'codigo_sig'] as $campo) {
                        $q->orWhereRaw(self::somenteDigitosSql($campo).' like ?', ['%'.$digitos.'%']);
                    }
                }
            });
        }
        if (! empty($filtros['ano'])) {
            $query->where('ultima_revisao', 'like', '%'.$filtros['ano'].'%');
        }
        if (! empty($filtros['eixo'])) {
            CatalogoOficial::aplicarFiltroEixo($query, $filtros['eixo']);
        }
        if (! empty($filtros['eixo_id'])) {
            $query->where('eixo_id', (int) $filtros['eixo_id']);
        }
        foreach (['status', 'tipo', 'segmento'] as $campo) {
            if (! empty($filtros[$campo])) {
                $query->where($campo, $filtros[$campo]);
            }
        }
        if (! empty($filtros['programa'])) {
            $programa = CatalogoOficial::canonicalizarPrograma($filtros['programa']) ?? $filtros['programa'];
            $query->whereIn('programa', array_unique([$programa, $filtros['programa']]));
        }
        if (! empty($filtros['unidade'])) {
            $query->where(fn ($q) => $q->where('unidade', $filtros['unidade'])
                ->orWhereJsonContains('unidades_oferta', $filtros['unidade']));
        }
        return $query;
    }
}
