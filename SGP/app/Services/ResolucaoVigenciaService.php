<?php

namespace App\Services;

use Carbon\Carbon;

class ResolucaoVigenciaService
{
    public static function vigenciaAnos(): int
    {
        return (int) config('resolucoes.vigencia_anos', 5);
    }

    public static function calcularDataFimVigencia(string|Carbon|null $inicio): Carbon
    {
        $inicioCarbon = $inicio ? Carbon::parse($inicio) : Carbon::now();

        return $inicioCarbon->copy()->addYears(self::vigenciaAnos());
    }

    public static function statusAutomatico(?string $dataInicioVigencia, ?string $dataFimVigencia = null): string
    {
        $fim = $dataFimVigencia ? Carbon::parse($dataFimVigencia) : self::calcularDataFimVigencia($dataInicioVigencia ?? now());
        $hoje = Carbon::now();

        if ($hoje->gt($fim)) {
            return 'vencida';
        }

        $mesesRestantes = $fim->diffInMonths($hoje, false);
        $alertaPreventivo = (int) config('resolucoes.alerta_preventivo_meses', 6);
        $alertaCritico = (int) config('resolucoes.alerta_critico_meses', 1);

        if ($mesesRestantes <= $alertaCritico) {
            return 'critico';
        }

        if ($mesesRestantes <= $alertaPreventivo) {
            return 'atencao';
        }

        return 'vigente';
    }
}
