<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Fonte única de eixos oficiais e modalidades canônicas.
 */
class CatalogoOficial
{
    /**
     * Aliases conhecidos (chave normalizada => rótulo oficial).
     *
     * @var array<string, string>
     */
    private const EIXO_ALIASES = [
        'saude' => 'Ambiente e Saúde',
        'saude e seguranca' => 'Ambiente e Saúde',
        'gastronomia' => 'Gastronomia e Turismo',
        'turismo e hospitalidade' => 'Gastronomia e Turismo',
        'gestao' => 'Gestão e Moda',
        'gestao e negocios' => 'Gestão e Moda',
        'tecnologia' => 'Tecnologia e Economia Criativa',
        'economia criativa' => 'Tecnologia e Economia Criativa',
    ];

    /**
     * Abas da planilha que não são eixos oficiais.
     *
     * @var list<string>
     */
    private const ABAS_ESPECIAIS = [
        'ensino medio 2025',
        '60+',
        '60 +',
    ];

    /**
     * @return list<string>
     */
    public static function eixos(): array
    {
        return array_values(array_filter(array_map(
            static fn ($item) => is_string($item) ? trim($item) : '',
            config('eixos', [])
        )));
    }

    /**
     * @return array<string, list<string>>
     */
    public static function segmentosPorEixo(): array
    {
        $mapa = [];
        foreach (config('segmentos', []) as $eixo => $segmentos) {
            $nomeEixo = is_string($eixo) ? trim($eixo) : '';
            if ($nomeEixo === '') {
                continue;
            }
            $mapa[$nomeEixo] = array_values(array_filter(array_map(
                static fn ($item) => is_string($item) ? trim($item) : '',
                (array) $segmentos
            )));
        }

        return $mapa;
    }

    /**
     * @return list<string>
     */
    public static function segmentos(): array
    {
        $lista = [];
        foreach (self::segmentosPorEixo() as $segmentos) {
            foreach ($segmentos as $nome) {
                $lista[] = $nome;
            }
        }

        return array_values(array_unique($lista));
    }

    /**
     * @return list<string>
     */
    public static function modalidades(): array
    {
        return array_values(array_filter(array_map(
            static fn ($item) => is_string($item) ? trim($item) : '',
            config('cursos.modalidades', [])
        )));
    }

    /**
     * @return list<string>
     */
    public static function tiposCurso(): array
    {
        return array_values(array_filter(array_map(
            static fn ($item) => is_string($item) ? trim($item) : '',
            config('cursos.tipos', [])
        )));
    }

    public static function chave(?string $valor): string
    {
        $texto = trim((string) $valor);
        $texto = preg_replace('/\s+/u', ' ', $texto) ?? $texto;
        $texto = mb_strtolower($texto, 'UTF-8');

        return strtr($texto, [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a',
            'é' => 'e', 'ê' => 'e',
            'í' => 'i',
            'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ú' => 'u',
            'ç' => 'c',
        ]);
    }

    public static function eAbaEspecial(?string $valor): bool
    {
        $chave = self::chave($valor);

        return $chave !== '' && in_array($chave, self::ABAS_ESPECIAIS, true);
    }

    public static function canonicalizarEixo(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $bruto = trim(preg_replace('/\s+/u', ' ', $valor) ?? $valor);
        if ($bruto === '' || self::eAbaEspecial($bruto)) {
            return null;
        }

        $chave = self::chave($bruto);

        foreach (self::eixos() as $oficial) {
            if (self::chave($oficial) === $chave) {
                return $oficial;
            }
        }

        $alias = self::EIXO_ALIASES[$chave] ?? null;
        if ($alias !== null && in_array($alias, self::eixos(), true)) {
            return $alias;
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public static function programas(): array
    {
        return array_values(array_filter(array_map(
            static fn ($item) => is_string($item) ? trim($item) : '',
            config('programas', [])
        )));
    }

    public static function canonicalizarPrograma(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $bruto = trim(preg_replace('/\s+/u', ' ', $valor) ?? $valor);
        if ($bruto === '') {
            return null;
        }

        $chave = self::chave($bruto);
        $aliases = [
            '60+' => '60+',
            '60 +' => '60+',
            '60' => '60+',
            'ensino medio 2025' => 'Ensino Médio 2025',
            'ensino medio' => 'Ensino Médio 2025',
            'novo ensino medio' => 'Ensino Médio 2025',
            'tem' => 'Ensino Médio 2025',
        ];

        if (isset($aliases[$chave])) {
            return $aliases[$chave];
        }

        foreach (self::programas() as $oficial) {
            if (self::chave($oficial) === $chave) {
                return $oficial;
            }
        }

        return self::eAbaEspecial($bruto) ? ($aliases[$chave] ?? $bruto) : null;
    }

    public static function canonicalizarSegmento(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $bruto = trim(preg_replace('/\s+/u', ' ', $valor) ?? $valor);
        if ($bruto === '') {
            return null;
        }

        $chave = self::chave($bruto);

        foreach (self::segmentos() as $oficial) {
            if (self::chave($oficial) === $chave) {
                return $oficial;
            }
        }

        $aliases = config('segmento_aliases', []);
        $alvo = $aliases[$chave] ?? null;
        if (is_string($alvo) && $alvo !== '') {
            foreach (self::segmentos() as $oficial) {
                if (self::chave($oficial) === self::chave($alvo)) {
                    return $oficial;
                }
            }
        }

        return null;
    }

    public static function eixoDoSegmento(?string $segmento): ?string
    {
        $canon = self::canonicalizarSegmento($segmento);
        if ($canon === null) {
            return null;
        }

        foreach (self::segmentosPorEixo() as $eixo => $segmentos) {
            foreach ($segmentos as $nome) {
                if (self::chave($nome) === self::chave($canon)) {
                    return $eixo;
                }
            }
        }

        return null;
    }

    /**
     * Resolve eixo oficial + segmento + programa a partir da aba e/ou da coluna Segmento.
     *
     * @return array{eixo: ?string, segmento: ?string, programa: ?string, erro: ?string}
     */
    public static function resolverEixoESegmento(?string $eixoOuAba, ?string $segmentoColuna): array
    {
        $aba = trim((string) $eixoOuAba);
        $seg = trim((string) $segmentoColuna);
        $programa = self::canonicalizarPrograma($aba) ?? self::canonicalizarPrograma($seg);

        $abaParaEixo = ($programa !== null && self::canonicalizarPrograma($aba) !== null) ? '' : $aba;
        $segParaSeg = $seg;
        if ($programa !== null && self::canonicalizarPrograma($seg) !== null && self::canonicalizarSegmento($seg) === null) {
            $segParaSeg = '';
        }

        $eixoCanon = self::canonicalizarEixo($abaParaEixo !== '' ? $abaParaEixo : null);
        $segCanon = self::canonicalizarSegmento($segParaSeg !== '' ? $segParaSeg : null);
        $segDaAba = self::canonicalizarSegmento($abaParaEixo !== '' ? $abaParaEixo : null);

        if ($segCanon === null && $segDaAba !== null) {
            $segCanon = $segDaAba;
        }

        if ($segParaSeg !== '' && $segCanon === null) {
            $segComoEixo = self::canonicalizarEixo($segParaSeg);
            if ($segComoEixo !== null) {
                if ($eixoCanon !== null && $eixoCanon !== $segComoEixo) {
                    return [
                        'eixo' => $eixoCanon,
                        'segmento' => null,
                        'programa' => $programa,
                        'erro' => 'O valor "'.$seg.'" não pertence ao eixo "'.$eixoCanon.'".',
                    ];
                }

                $eixoCanon = $eixoCanon ?? $segComoEixo;
            } elseif (self::canonicalizarPrograma($segParaSeg) === null) {
                return [
                    'eixo' => $eixoCanon,
                    'segmento' => $seg,
                    'programa' => $programa,
                    'erro' => 'Segmento desconhecido: "'.$seg.'".',
                ];
            }
        }

        if ($eixoCanon === null && $segCanon !== null) {
            $eixoCanon = self::eixoDoSegmento($segCanon);
        }

        if ($eixoCanon !== null && $segCanon !== null) {
            $eixoDoSeg = self::eixoDoSegmento($segCanon);
            if ($eixoDoSeg !== null && $eixoDoSeg !== $eixoCanon) {
                return [
                    'eixo' => $eixoCanon,
                    'segmento' => $segCanon,
                    'programa' => $programa,
                    'erro' => 'O segmento "'.$segCanon.'" pertence ao eixo "'.$eixoDoSeg.'", não a "'.$eixoCanon.'".',
                ];
            }
        }

        if ($eixoCanon === null) {
            $mensagem = $programa !== null
                ? 'Não foi possível classificar o registro em um dos 5 Eixos.'
                : ($aba !== ''
                    ? 'Eixo inválido: "'.$aba.'". Use um dos 5 eixos oficiais.'
                    : 'Não foi possível classificar o registro em um dos 5 Eixos.');

            return [
                'eixo' => null,
                'segmento' => $segCanon,
                'programa' => $programa,
                'erro' => $mensagem,
            ];
        }

        return [
            'eixo' => $eixoCanon,
            'segmento' => $segCanon,
            'programa' => $programa,
            'erro' => null,
        ];
    }

    public static function canonicalizarModalidade(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $bruto = trim(preg_replace('/\s+/u', ' ', $valor) ?? $valor);
        if ($bruto === '') {
            return null;
        }

        $chave = self::chave($bruto);

        foreach (self::modalidades() as $oficial) {
            if (self::chave($oficial) === $chave) {
                return $oficial;
            }
        }

        return null;
    }

    public static function canonicalizarTipoCurso(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $bruto = trim(preg_replace('/\s+/u', ' ', $valor) ?? $valor);
        if ($bruto === '') {
            return null;
        }

        $chave = self::chave($bruto);

        foreach (self::tiposCurso() as $oficial) {
            if (self::chave($oficial) === $chave) {
                return $oficial;
            }
        }

        return null;
    }

    /**
     * @return array{modalidade: ?string, tipo: ?string, erro: ?string}
     */
    public static function resolverModalidadeImportacao(mixed $modalidade, mixed $tipo): array
    {
        $modalidadeTexto = is_scalar($modalidade) ? trim((string) $modalidade) : '';
        $tipoTexto = is_scalar($tipo) ? trim((string) $tipo) : '';

        $modCanon = self::canonicalizarModalidade($modalidadeTexto !== '' ? $modalidadeTexto : null);
        $tipoComoMod = self::canonicalizarModalidade($tipoTexto !== '' ? $tipoTexto : null);
        $tipoCanon = self::canonicalizarTipoCurso($tipoTexto !== '' ? $tipoTexto : null);

        if ($modCanon !== null) {
            return [
                'modalidade' => $modCanon,
                'tipo' => $tipoCanon,
                'erro' => null,
            ];
        }

        if ($tipoComoMod !== null && $tipoCanon === null) {
            return [
                'modalidade' => $tipoComoMod,
                'tipo' => null,
                'erro' => null,
            ];
        }

        if ($modalidadeTexto !== '') {
            return [
                'modalidade' => $modalidadeTexto,
                'tipo' => $tipoCanon,
                'erro' => 'Modalidade inválida: "'.$modalidadeTexto.'". Use um dos valores oficiais do cadastro de Cursos.',
            ];
        }

        return [
            'modalidade' => null,
            'tipo' => $tipoCanon,
            'erro' => null,
        ];
    }

    /**
     * Valores persistidos equivalentes ao filtro (canônico + aliases).
     *
     * @return list<string>
     */
    public static function valoresEquivalentes(?string $valor): array
    {
        $canon = self::canonicalizarEixo($valor);
        $bruto = trim((string) $valor);
        $lista = $canon !== null ? self::rotulosDoAlias($canon) : [];

        if ($bruto !== '') {
            $lista[] = $bruto;
        }

        return array_values(array_unique(array_filter($lista)));
    }

    /**
     * @return list<string>
     */
    private static function rotulosDoAlias(string $canon): array
    {
        $rotulos = [$canon];

        foreach (self::EIXO_ALIASES as $chave => $alvo) {
            if ($alvo !== $canon) {
                continue;
            }

            $rotulos[] = match ($chave) {
                'saude' => 'Saúde',
                'saude e seguranca' => 'Saúde e Segurança',
                'gastronomia' => 'Gastronomia',
                'turismo e hospitalidade' => 'Turismo e Hospitalidade',
                'gestao' => 'Gestão',
                'gestao e negocios' => 'Gestão e Negócios',
                'tecnologia' => 'Tecnologia',
                'economia criativa' => 'Economia Criativa',
                default => $chave,
            };
        }

        return array_values(array_unique($rotulos));
    }

    public static function aplicarFiltroEixo(Builder $query, mixed $valor, string $coluna = 'eixo'): void
    {
        $texto = is_scalar($valor) ? trim((string) $valor) : '';
        if ($texto === '') {
            return;
        }

        $query->whereIn($coluna, self::valoresEquivalentes($texto));
    }
}
