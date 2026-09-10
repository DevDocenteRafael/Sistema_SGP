<?php

namespace App\Support;

use App\Models\Curso;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Vincula oferta/execução a um curso oficial sem criar curso novo.
 */
class ConciliadorCursoOferta
{
    /**
     * @param  array{titulo?: ?string, eixo?: ?string, segmento?: ?string, ch?: ?string, codigo?: ?string}  $contexto
     */
    public static function localizar(array $contexto, ?int $cicloId = null): ?Curso
    {
        $cursos = Curso::query();
        $cursos->where('ciclo_id', $cicloId);

        return self::escolher(
            $cursos->get(['id', 'titulo', 'eixo', 'segmento', 'carga_horaria', 'codigo_sig', 'codigo_dn', 'ciclo_id', 'programa']),
            $contexto,
        );
    }

    /**
     * @return array{vinculados: int, ambiguos: int, sem_correspondencia: int}
     */
    public static function vincularOfertasPendentes(bool $simular = false): array
    {
        $stats = ['vinculados' => 0, 'ambiguos' => 0, 'sem_correspondencia' => 0];

        if (! Schema::hasTable('curso_por_eixos') || ! Schema::hasTable('cursos')) {
            return $stats;
        }

        $cursosPorCiclo = [];

        $ofertas = DB::table('curso_por_eixos')
            ->select('id', 'curso', 'eixo', 'segmento', 'ch', 'codigo', 'ciclo_id', 'programa')
            ->whereNull('curso_id')
            ->get();

        foreach ($ofertas as $oferta) {
            $cicloId = $oferta->ciclo_id ? (int) $oferta->ciclo_id : 0;
            if (! isset($cursosPorCiclo[$cicloId])) {
                $query = Curso::query()->select([
                    'id', 'titulo', 'eixo', 'segmento', 'carga_horaria', 'codigo_sig', 'codigo_dn', 'ciclo_id', 'programa',
                ]);
                $query->where('ciclo_id', $cicloId ?: null);
                $cursosPorCiclo[$cicloId] = $query->get();
            }

            $escolha = self::escolherComMotivo($cursosPorCiclo[$cicloId], [
                'titulo' => $oferta->curso,
                'eixo' => $oferta->eixo,
                'segmento' => $oferta->segmento,
                'ch' => $oferta->ch,
                'codigo' => $oferta->codigo,
                'programa' => $oferta->programa,
            ]);

            if ($escolha['curso'] instanceof Curso) {
                if (! $simular) {
                    DB::table('curso_por_eixos')->where('id', $oferta->id)->whereNull('curso_id')->update([
                        'curso_id' => $escolha['curso']->id,
                    ]);
                }
                $stats['vinculados']++;
                continue;
            }

            if (($escolha['motivo'] ?? '') === 'ambiguo') {
                $stats['ambiguos']++;
            } else {
                $stats['sem_correspondencia']++;
            }
        }

        return $stats;
    }

    public static function chaveTitulo(?string $titulo): string
    {
        $texto = CatalogoOficial::chave($titulo);
        $texto = strtr($texto, [
            '–' => '-', '—' => '-', '−' => '-', "'" => '', '"' => '', '`' => '',
        ]);
        $texto = preg_replace('/[^a-z0-9]+/u', ' ', $texto) ?? $texto;

        return trim(preg_replace('/\s+/u', ' ', $texto) ?? $texto);
    }

    /**
     * @param  iterable<int, Curso>  $cursos
     * @param  array{titulo?: ?string, eixo?: ?string, segmento?: ?string, ch?: ?string, codigo?: ?string}  $contexto
     */
    public static function escolher(iterable $cursos, array $contexto): ?Curso
    {
        return self::escolherComMotivo($cursos, $contexto)['curso'];
    }

    /**
     * @param  iterable<int, Curso>  $cursos
     * @param  array{titulo?: ?string, eixo?: ?string, segmento?: ?string, ch?: ?string, codigo?: ?string}  $contexto
     * @return array{curso: ?Curso, motivo: string}
     */
    public static function escolherComMotivo(iterable $cursos, array $contexto): array
    {
        $lista = collect($cursos);
        if ($lista->isEmpty()) {
            return ['curso' => null, 'motivo' => 'sem_catalogo'];
        }

        if (! empty($contexto['programa'])) {
            $lista = $lista->filter(fn (Curso $curso) => CatalogoOficial::chave($curso->programa) === CatalogoOficial::chave($contexto['programa']));
        }
        $porCodigo = self::porCodigoOficial($lista, $contexto['codigo'] ?? null);
        if ($porCodigo['curso'] instanceof Curso || $porCodigo['motivo'] === 'ambiguo') {
            return $porCodigo;
        }

        $chave = self::chaveTitulo($contexto['titulo'] ?? null);
        if ($chave === '') {
            return ['curso' => null, 'motivo' => 'sem_titulo'];
        }

        $candidatos = $lista->filter(
            fn (Curso $curso) => self::chaveTitulo($curso->titulo) === $chave
        )->values();

        if ($candidatos->isEmpty()) {
            return ['curso' => null, 'motivo' => 'sem_correspondencia'];
        }

        if ($candidatos->count() === 1) {
            return ['curso' => $candidatos->first(), 'motivo' => 'titulo_unico'];
        }

        $criteriosAplicados = [];

        $eixo = CatalogoOficial::canonicalizarEixo($contexto['eixo'] ?? null)
            ?? CatalogoOficial::resolverEixoESegmento($contexto['eixo'] ?? null, $contexto['segmento'] ?? null)['eixo'];
        if ($eixo) {
            $porEixo = $candidatos->filter(
                fn (Curso $curso) => CatalogoOficial::chave($curso->eixo) === CatalogoOficial::chave($eixo)
            )->values();
            if ($porEixo->count() < $candidatos->count()) {
                $criteriosAplicados[] = 'eixo';
            }
            $candidatos = $porEixo;
        }

        $segmento = CatalogoOficial::canonicalizarSegmento($contexto['segmento'] ?? null)
            ?? ($contexto['segmento'] ?? null);
        if ($segmento) {
            $porSeg = $candidatos->filter(
                fn (Curso $curso) => CatalogoOficial::chave((string) $curso->segmento) === CatalogoOficial::chave($segmento)
            )->values();
            if ($porSeg->count() < $candidatos->count()) {
                $criteriosAplicados[] = 'segmento';
            }
            $candidatos = $porSeg;
        }

        $ch = self::chaveTitulo((string) ($contexto['ch'] ?? ''));
        if ($ch !== '') {
            $porCh = $candidatos->filter(
                fn (Curso $curso) => self::chaveTitulo((string) $curso->carga_horaria) === $ch
            )->values();
            if ($porCh->count() < $candidatos->count()) {
                $criteriosAplicados[] = 'ch';
            }
            $candidatos = $porCh;
        }

        if ($candidatos->count() === 1 && $criteriosAplicados !== []) {
            return ['curso' => $candidatos->first(), 'motivo' => 'titulo_'.implode('_', $criteriosAplicados)];
        }

        return ['curso' => null, 'motivo' => $candidatos->isEmpty() ? 'dados_divergentes' : 'ambiguo'];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Curso>  $lista
     * @return array{curso: ?Curso, motivo: string}
     */
    private static function porCodigoOficial($lista, mixed $codigo): array
    {
        $codigo = trim((string) $codigo);
        if ($codigo === '' || in_array(mb_strtolower($codigo), ['-', '--', '—', 'n/a', '?', 'sem código', 'em criação'], true)
            || preg_match('/^\d{4}\.\d{2}\.\d+/', $codigo) === 1) {
            return ['curso' => null, 'motivo' => 'codigo_operacional'];
        }

        $chave = mb_strtolower($codigo);
        $candidatos = $lista->filter(function (Curso $curso) use ($chave) {
            $sig = mb_strtolower(trim((string) $curso->codigo_sig));
            $dn = mb_strtolower(trim((string) $curso->codigo_dn));

            return ($sig !== '' && $sig === $chave) || ($dn !== '' && $dn === $chave);
        })->values();

        if ($candidatos->count() === 1) {
            return ['curso' => $candidatos->first(), 'motivo' => 'codigo'];
        }
        if ($candidatos->count() > 1) {
            return ['curso' => null, 'motivo' => 'ambiguo'];
        }

        return ['curso' => null, 'motivo' => 'sem_codigo'];
    }

    /** Sugestões para revisão humana; nunca utilizadas na persistência do vínculo. */
    public static function sugerir(iterable $cursos, string $titulo): array
    {
        $chave = self::chaveTitulo($titulo);
        if ($chave === '') {
            return [];
        }
        return collect($cursos)->map(function (Curso $curso) use ($chave) {
            similar_text($chave, self::chaveTitulo($curso->titulo), $percentual);
            return ['id' => $curso->id, 'titulo' => $curso->titulo, 'segmento' => $curso->segmento,
                'programa' => $curso->programa, 'ch' => $curso->carga_horaria, 'similaridade' => round($percentual, 1)];
        })->filter(fn ($item) => $item['similaridade'] >= 75)->sortByDesc('similaridade')->take(3)->values()->all();
    }
}
