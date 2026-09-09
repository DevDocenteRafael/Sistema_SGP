<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Persistência dos 5 eixos oficiais e do mapeamento de segmentos.
 */
class CatalogoInstitucional
{
    /**
     * @var array<string, int>|null
     */
    private static ?array $eixoIds = null;

    /**
     * @var array<string, int>|null
     */
    private static ?array $segmentoIds = null;

    public static function resetCache(): void
    {
        self::$eixoIds = null;
        self::$segmentoIds = null;
    }

    public static function sincronizar(): void
    {
        if (! Schema::hasTable('eixos') || ! Schema::hasTable('segmentos')) {
            return;
        }

        $agora = now();
        $ordemEixo = 0;

        foreach (config('eixos', []) as $nomeEixo) {
            $nomeEixo = trim((string) $nomeEixo);
            if ($nomeEixo === '') {
                continue;
            }

            $ordemEixo++;
            $slug = CatalogoOficial::chave($nomeEixo);

            DB::table('eixos')->updateOrInsert(
                ['slug' => $slug],
                [
                    'nome' => $nomeEixo,
                    'ordem' => $ordemEixo,
                    'updated_at' => $agora,
                    'created_at' => DB::table('eixos')->where('slug', $slug)->value('created_at') ?? $agora,
                ]
            );
        }

        $eixosPorNome = DB::table('eixos')->pluck('id', 'nome');
        $ordemSeg = 0;

        foreach (config('segmentos', []) as $nomeEixo => $segmentos) {
            $eixoId = $eixosPorNome[$nomeEixo] ?? null;
            if (! $eixoId) {
                continue;
            }

            foreach ((array) $segmentos as $nomeSegmento) {
                $nomeSegmento = trim((string) $nomeSegmento);
                if ($nomeSegmento === '') {
                    continue;
                }

                $ordemSeg++;
                $slug = CatalogoOficial::chave($nomeSegmento);

                DB::table('segmentos')->updateOrInsert(
                    ['slug' => $slug],
                    [
                        'eixo_id' => $eixoId,
                        'nome' => $nomeSegmento,
                        'ordem' => $ordemSeg,
                        'updated_at' => $agora,
                        'created_at' => DB::table('segmentos')->where('slug', $slug)->value('created_at') ?? $agora,
                    ]
                );
            }
        }

        self::resetCache();
    }

    public static function eixoId(?string $nome): ?int
    {
        $canon = CatalogoOficial::canonicalizarEixo($nome);
        if ($canon === null) {
            return null;
        }

        return self::mapaEixos()[CatalogoOficial::chave($canon)] ?? null;
    }

    public static function segmentoId(?string $nome): ?int
    {
        $canon = CatalogoOficial::canonicalizarSegmento($nome);
        if ($canon === null) {
            return null;
        }

        return self::mapaSegmentos()[CatalogoOficial::chave($canon)] ?? null;
    }

    /**
     * @return array{eixo_id: ?int, segmento_id: ?int}
     */
    public static function ids(?string $eixo, ?string $segmento): array
    {
        return [
            'eixo_id' => self::eixoId($eixo),
            'segmento_id' => self::segmentoId($segmento),
        ];
    }

    /**
     * @return array<string, int>
     */
    private static function mapaEixos(): array
    {
        if (self::$eixoIds !== null) {
            return self::$eixoIds;
        }

        if (! Schema::hasTable('eixos')) {
            return self::$eixoIds = [];
        }

        self::$eixoIds = [];
        foreach (DB::table('eixos')->select('id', 'nome')->get() as $eixo) {
            self::$eixoIds[CatalogoOficial::chave($eixo->nome)] = (int) $eixo->id;
        }

        return self::$eixoIds;
    }

    /**
     * @return array<string, int>
     */
    private static function mapaSegmentos(): array
    {
        if (self::$segmentoIds !== null) {
            return self::$segmentoIds;
        }

        if (! Schema::hasTable('segmentos')) {
            return self::$segmentoIds = [];
        }

        self::$segmentoIds = [];
        foreach (DB::table('segmentos')->select('id', 'nome')->get() as $segmento) {
            self::$segmentoIds[CatalogoOficial::chave($segmento->nome)] = (int) $segmento->id;
        }

        return self::$segmentoIds;
    }
}
