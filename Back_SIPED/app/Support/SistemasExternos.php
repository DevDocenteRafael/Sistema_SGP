<?php

namespace App\Support;

/**
 * Lê config/sistemas_externos.php e só devolve URLs http(s) válidas.
 */
class SistemasExternos
{
    public static function url(string $sistema, string $chave = 'base_url'): ?string
    {
        $valor = config("sistemas_externos.{$sistema}.{$chave}");
        if (! is_string($valor)) {
            return null;
        }

        $valor = trim($valor);
        if ($valor === '' || ! preg_match('#^https?://#i', $valor) || filter_var(str_replace(['{processo}', '{codigo}'], 'x', $valor), FILTER_VALIDATE_URL) === false) {
            return null;
        }

        return $valor;
    }

    /**
     * Configuração pública para o front (nenhum segredo).
     *
     * @return array<string, array<string, string|null>>
     */
    public static function publico(): array
    {
        return [
            'sei' => [
                'base_url' => self::url('sei'),
                'processo_url' => self::modelo('sei', 'processo_url', '{processo}'),
            ],
            'sig' => [
                'base_url' => self::url('sig'),
                'curso_url' => self::modelo('sig', 'curso_url', '{codigo}'),
            ],
            'sigin' => ['base_url' => self::url('sigin')],
            'svt' => ['base_url' => self::url('svt')],
            'senac' => ['base_url' => self::url('senac')],
        ];
    }

    /** Modelo de link direto: só vale se tiver o marcador ({processo}/{codigo}). */
    private static function modelo(string $sistema, string $chave, string $marcador): ?string
    {
        $url = self::url($sistema, $chave);

        return $url !== null && str_contains($url, $marcador) ? $url : null;
    }
}
