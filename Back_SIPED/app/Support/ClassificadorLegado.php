<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reclassifica registros legados para os 5 eixos oficiais sem excluir nada.
 */
class ClassificadorLegado
{
    /**
     * @return array{eixo: ?string, segmento: ?string, programa: ?string, erro: ?string}
     */
    public static function classificar(?string $eixo, ?string $segmento, ?string $titulo = null): array
    {
        $resolvido = CatalogoOficial::resolverEixoESegmento($eixo, $segmento);
        if (($resolvido['eixo'] ?? null) !== null && ($resolvido['erro'] ?? null) === null) {
            return self::completarSegmentoPadrao($resolvido);
        }

        $programa = $resolvido['programa']
            ?? CatalogoOficial::canonicalizarPrograma($eixo)
            ?? CatalogoOficial::canonicalizarPrograma($segmento);

        $porTitulo = self::inferirPorTitulo($titulo);
        if ($porTitulo['eixo'] !== null) {
            return self::completarSegmentoPadrao([
                'eixo' => $porTitulo['eixo'],
                'segmento' => $resolvido['segmento'] ?? $porTitulo['segmento'],
                'programa' => $programa,
                'erro' => null,
            ]);
        }

        return [
            'eixo' => $resolvido['eixo'] ?? null,
            'segmento' => $resolvido['segmento'] ?? null,
            'programa' => $programa,
            'erro' => $resolvido['erro'] ?? 'Não classificado',
        ];
    }

    public static function reclassificarTabelas(): void
    {
        CatalogoInstitucional::sincronizar();
        CatalogoInstitucional::resetCache();

        $ofertasPorTitulo = [];

        if (Schema::hasTable('curso_por_eixos')) {
            $ofertas = DB::table('curso_por_eixos')
                ->select('id', 'eixo', 'segmento', 'curso', 'programa')
                ->get();

            foreach ($ofertas as $linha) {
                $resolvido = self::classificar($linha->eixo ?? null, $linha->segmento ?? null, $linha->curso ?? null);
                if (($resolvido['eixo'] ?? null) === null) {
                    continue;
                }

                self::persistirClassificacao('curso_por_eixos', (int) $linha->id, $resolvido, $linha->programa ?? null);

                $chave = CatalogoOficial::chave($linha->curso ?? null);
                if ($chave !== '') {
                    $ofertasPorTitulo[$chave][] = $resolvido;
                }
            }
        }

        if (Schema::hasTable('cursos')) {
            $cursos = DB::table('cursos')
                ->select('id', 'eixo', 'segmento', 'titulo', 'programa')
                ->get();

            foreach ($cursos as $linha) {
                $resolvido = self::classificar($linha->eixo ?? null, $linha->segmento ?? null, $linha->titulo ?? null);
                $chave = CatalogoOficial::chave($linha->titulo ?? null);
                $resolvido = self::enriquecerComOfertas($resolvido, $ofertasPorTitulo[$chave] ?? []);

                if (($resolvido['eixo'] ?? null) === null) {
                    continue;
                }

                self::persistirClassificacao('cursos', (int) $linha->id, $resolvido, $linha->programa ?? null);
            }
        }
    }

    /**
     * @param  array{eixo: ?string, segmento: ?string, programa: ?string, erro: ?string}  $resolvido
     * @param  list<array{eixo: ?string, segmento: ?string, programa: ?string, erro: ?string}>  $ofertas
     * @return array{eixo: ?string, segmento: ?string, programa: ?string, erro: ?string}
     */
    private static function enriquecerComOfertas(array $resolvido, array $ofertas): array
    {
        if ($ofertas === []) {
            return self::completarSegmentoPadrao($resolvido);
        }

        $eixoOferta = self::valorMaisFrequente(array_column($ofertas, 'eixo'));
        $segOferta = self::valorMaisFrequente(array_column($ofertas, 'segmento'));

        if (($resolvido['eixo'] ?? null) === null && $eixoOferta !== null) {
            $resolvido['eixo'] = $eixoOferta;
            $resolvido['segmento'] = $segOferta ?? $resolvido['segmento'] ?? null;
            $resolvido['erro'] = null;
        }

        if (($resolvido['segmento'] ?? null) === null || $resolvido['segmento'] === '') {
            if ($segOferta !== null && (
                ($resolvido['eixo'] ?? null) === $eixoOferta
                || CatalogoOficial::eixoDoSegmento($segOferta) === ($resolvido['eixo'] ?? null)
            )) {
                $resolvido['segmento'] = $segOferta;
            }
        }

        return self::completarSegmentoPadrao($resolvido);
    }

    /**
     * @param  array{eixo: ?string, segmento: ?string, programa: ?string, erro: ?string}  $resolvido
     */
    private static function persistirClassificacao(string $tabela, int $id, array $resolvido, mixed $programaAtual): void
    {
        $ids = CatalogoInstitucional::ids($resolvido['eixo'], $resolvido['segmento']);
        $payload = [
            'eixo' => $resolvido['eixo'],
            'segmento' => $resolvido['segmento'],
            'eixo_id' => $ids['eixo_id'],
            'segmento_id' => $ids['segmento_id'],
        ];
        if (Schema::hasColumn($tabela, 'programa')) {
            $payload['programa'] = $resolvido['programa'] ?? $programaAtual ?? null;
        }

        DB::table($tabela)->where('id', $id)->update($payload);
    }

    /**
     * @param  array{eixo: ?string, segmento: ?string, programa: ?string, erro: ?string}  $resolvido
     * @return array{eixo: ?string, segmento: ?string, programa: ?string, erro: ?string}
     */
    private static function completarSegmentoPadrao(array $resolvido): array
    {
        if (($resolvido['eixo'] ?? null) !== null && trim((string) ($resolvido['segmento'] ?? '')) === '') {
            $resolvido['segmento'] = self::segmentoPadrao($resolvido['eixo']);
        }

        return $resolvido;
    }

    private static function segmentoPadrao(string $eixo): ?string
    {
        return match ($eixo) {
            'Gastronomia e Turismo' => 'Gastronomia',
            'Beleza e Cuidado Pessoal' => 'Beleza e cuidado pessoal',
            default => null,
        };
    }

    /**
     * @param  list<mixed>  $valores
     */
    private static function valorMaisFrequente(array $valores): ?string
    {
        $contagem = [];
        foreach ($valores as $valor) {
            $texto = is_string($valor) ? trim($valor) : '';
            if ($texto === '') {
                continue;
            }
            $contagem[$texto] = ($contagem[$texto] ?? 0) + 1;
        }

        if ($contagem === []) {
            return null;
        }

        arsort($contagem);

        return array_key_first($contagem);
    }

    /**
     * @return array{eixo: ?string, segmento: ?string}
     */
    private static function inferirPorTitulo(?string $titulo): array
    {
        $chave = CatalogoOficial::chave($titulo);
        if ($chave === '') {
            return ['eixo' => null, 'segmento' => null];
        }

        $mapa = [
            'confeit' => ['Gastronomia e Turismo', 'Confeitaria'],
            'padeir' => ['Gastronomia e Turismo', 'Panificação'],
            'barista' => ['Gastronomia e Turismo', 'Bebidas'],
            'bartender' => ['Gastronomia e Turismo', 'Bebidas'],
            'sommelier' => ['Gastronomia e Turismo', 'Bebidas'],
            'cerveja' => ['Gastronomia e Turismo', 'Bebidas'],
            'garcom' => ['Gastronomia e Turismo', 'Hospitalidade'],
            'cumim' => ['Gastronomia e Turismo', 'Hospitalidade'],
            'restaurante' => ['Gastronomia e Turismo', 'Hospitalidade'],
            'cafeteria' => ['Gastronomia e Turismo', 'Bebidas'],
            'cozinh' => ['Gastronomia e Turismo', 'Gastronomia'],
            'gastronom' => ['Gastronomia e Turismo', 'Gastronomia'],
            'sushiman' => ['Gastronomia e Turismo', 'Gastronomia'],
            'pizzaiolo' => ['Gastronomia e Turismo', 'Gastronomia'],
            'salgadeiro' => ['Gastronomia e Turismo', 'Gastronomia'],
            'alimentacao escolar' => ['Gastronomia e Turismo', 'Gastronomia'],
            'compras e estoque' => ['Gastronomia e Turismo', 'Gastronomia'],
            'computacao grafica' => ['Tecnologia e Economia Criativa', 'Design, Paisagismo e Ambientação'],
            'design de interiores' => ['Tecnologia e Economia Criativa', 'Design, Paisagismo e Ambientação'],
            'desenvolvimento de sistemas' => ['Tecnologia e Economia Criativa', 'Tecnologia da Informação - Desenvolvimento'],
            'informatica para internet' => ['Tecnologia e Economia Criativa', 'Tecnologia da Informação - Desenvolvimento'],
            'programacao de jogos' => ['Tecnologia e Economia Criativa', 'Tecnologia da informação - Games'],
            'seguranca cibernetica' => ['Tecnologia e Economia Criativa', 'Tecnologia da Informação - Suporte'],
            'tecnico em informatica' => ['Tecnologia e Economia Criativa', 'Tecnologia da Informação - Suporte'],
            'administracao' => ['Gestão e Moda', 'Gestão e Comércio'],
            'contabilidade' => ['Gestão e Moda', 'Gestão e Comércio'],
            'financas' => ['Gestão e Moda', 'Gestão e Comércio'],
            'logistica' => ['Gestão e Moda', 'Gestão e Comércio'],
            'marketing' => ['Gestão e Moda', 'Vendas e Marketing'],
            'recursos humanos' => ['Gestão e Moda', 'Gestão e Comércio'],
            'secretariado' => ['Gestão e Moda', 'Gestão e Comércio'],
            'eventos' => ['Gastronomia e Turismo', 'Turismo'],
            'seguranca do trabalho' => ['Ambiente e Saúde', 'Segurança e NRs'],
            'meio ambiente' => ['Ambiente e Saúde', 'Segurança e NRs'],
            'nutricao' => ['Ambiente e Saúde', 'Nutrição'],
            'podologia' => ['Beleza e Cuidado Pessoal', 'Estética e massoterapia'],
            'analises clinicas' => ['Ambiente e Saúde', 'Análises Clínicas'],
            'hemoterapia' => ['Ambiente e Saúde', 'Análises Clínicas'],
        ];

        foreach ($mapa as $trecho => [$eixo, $segmento]) {
            if (str_contains($chave, $trecho)) {
                return ['eixo' => $eixo, 'segmento' => $segmento];
            }
        }

        return ['eixo' => null, 'segmento' => null];
    }
}
