<?php

namespace App\Support;

use App\Models\Ciclo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class BackfillCicloOperacional
{
    /**
     * @return array<string, array{total: int, vinculados: int, fallback_atual: int, pendentes: int}>
     */
    public static function executar(): array
    {
        $ciclos = Ciclo::query()->orderBy('id')->get();
        $atualId = Ciclo::atual()?->id;
        $relatorio = [];

        foreach (self::tabelas() as $tabela => $fontes) {
            if (! Schema::hasTable($tabela) || ! Schema::hasColumn($tabela, 'ciclo_id')) {
                continue;
            }

            $stats = ['total' => 0, 'vinculados' => 0, 'fallback_atual' => 0, 'pendentes' => 0];
            $linhas = DB::table($tabela)->select(array_values(array_unique(['id', 'ciclo_id', ...$fontes])))->whereNull('ciclo_id')->get();
            $stats['total'] = $linhas->count();

            foreach ($linhas as $linha) {
                $cicloId = self::resolverLinha($linha, $fontes, $ciclos);
                if ($cicloId) {
                    DB::table($tabela)->where('id', $linha->id)->update(['ciclo_id' => $cicloId]);
                    $stats['vinculados']++;
                    continue;
                }

                if ($atualId) {
                    DB::table($tabela)->where('id', $linha->id)->update(['ciclo_id' => $atualId]);
                    $stats['fallback_atual']++;
                    Log::info('Backfill ciclo: fallback para ciclo atual', [
                        'tabela' => $tabela,
                        'id' => $linha->id,
                        'ciclo_id' => $atualId,
                    ]);
                    continue;
                }

                $stats['pendentes']++;
            }

            $relatorio[$tabela] = $stats;
        }

        Log::info('Backfill ciclo operacional concluído', $relatorio);

        return $relatorio;
    }

    /**
     * @return array<string, list<string>>
     */
    public static function tabelas(): array
    {
        return [
            'visita_tecnicas' => ['data_visita_prevista', 'data_solicitacao', 'prazo_limite', 'created_at'],
            'hora_pedagogicas' => ['ano', 'created_at'],
            'acao_extensivas' => ['ultima_atualizacao', 'created_at'],
            'jornadas_pedagogicas' => ['data_inicio', 'data_pre_jornada', 'data_fim', 'created_at'],
            'eventos' => ['ano', 'data', 'created_at'],
        ];
    }

    /**
     * @param  list<string>  $fontes
     * @param  \Illuminate\Support\Collection<int, Ciclo>  $ciclos
     */
    private static function resolverLinha(object $linha, array $fontes, $ciclos): ?int
    {
        foreach ($fontes as $campo) {
            $valor = $linha->{$campo} ?? null;
            if ($valor === null || $valor === '') {
                continue;
            }

            $ano = self::extrairAno($valor);
            $cicloId = self::resolverAno($ano, $ciclos);
            if ($cicloId) {
                return $cicloId;
            }
        }

        return null;
    }

    private static function extrairAno(mixed $valor): ?int
    {
        if (is_int($valor) || (is_string($valor) && preg_match('/^\d{4}$/', trim($valor)))) {
            $ano = (int) $valor;

            return $ano >= 2000 && $ano <= 2100 ? $ano : null;
        }

        try {
            $data = Carbon::parse((string) $valor);
            $ano = (int) $data->format('Y');

            return $ano >= 2000 && $ano <= 2100 ? $ano : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Ciclo>  $ciclos
     */
    private static function resolverAno(?int $ano, $ciclos): ?int
    {
        if ($ano === null) {
            return null;
        }

        $anoStr = (string) $ano;
        $candidatos = $ciclos->filter(fn (Ciclo $ciclo) => in_array($anoStr, $ciclo->anos(), true))->values();
        if ($candidatos->count() === 1) {
            return $candidatos->first()?->id;
        }

        $porInicio = $candidatos->filter(function (Ciclo $ciclo) use ($anoStr) {
            $anos = $ciclo->anos();

            return ($anos[0] ?? null) === $anoStr;
        })->values();

        if ($porInicio->count() === 1) {
            return $porInicio->first()?->id;
        }

        return null;
    }
}
