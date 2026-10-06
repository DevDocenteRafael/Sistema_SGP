<?php

namespace App\Support;

/**
 * Áreas/origens do Plano de Metas (config/plano_de_metas.php).
 */
class AreaPlanejamento
{
    /** @return list<string> */
    public static function areas(): array
    {
        return array_values(config('plano_de_metas.areas', []));
    }

    /**
     * Converte a grafia informada na área oficial; null se vazio ou não reconhecido.
     */
    public static function canonicalizar(mixed $valor): ?string
    {
        if (! is_scalar($valor)) {
            return null;
        }

        $texto = trim((string) $valor);
        if ($texto === '') {
            return null;
        }

        $chave = CatalogoOficial::chave($texto);
        foreach (self::areas() as $area) {
            if (CatalogoOficial::chave($area) === $chave) {
                return $area;
            }
        }

        $aliases = config('plano_de_metas.aliases_area', []);

        return $aliases[$chave] ?? null;
    }
}
