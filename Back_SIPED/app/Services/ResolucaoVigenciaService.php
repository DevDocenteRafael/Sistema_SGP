<?php

namespace App\Services;

use App\Models\Resolucao;
use Carbon\Carbon;
use Illuminate\Support\Collection;

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
        $fim = $dataFimVigencia
            ? Carbon::parse($dataFimVigencia)->startOfDay()
            : self::calcularDataFimVigencia($dataInicioVigencia ?? now())->startOfDay();
        $hoje = Carbon::now()->startOfDay();

        if ($hoje->gt($fim)) {
            return 'vencida';
        }

        $alertaPreventivo = (int) config('resolucoes.alerta_preventivo_meses', 6);
        $alertaCritico = (int) config('resolucoes.alerta_critico_meses', 1);
        $limiteCritico = $fim->copy()->subMonthsNoOverflow($alertaCritico);
        $limitePreventivo = $fim->copy()->subMonthsNoOverflow($alertaPreventivo);

        if ($hoje->gte($limiteCritico)) {
            return 'critico';
        }

        if ($hoje->gte($limitePreventivo)) {
            return 'atencao';
        }

        return 'vigente';
    }

    /**
     * Status efetivo para o semáforo: Concluída permanece; demais usam a vigência.
     */
    public static function statusVigencia(Resolucao $resolucao): string
    {
        if ($resolucao->status === 'concluida') {
            return 'concluida';
        }

        return self::statusAutomatico(
            $resolucao->data_inicio_vigencia?->format('Y-m-d'),
            $resolucao->data_fim_vigencia?->format('Y-m-d')
        );
    }

    public static function corSemaforo(string $statusVigencia): string
    {
        return match ($statusVigencia) {
            'vigente' => 'verde',
            'atencao' => 'amarelo',
            'critico', 'vencida' => 'vermelho',
            default => 'verde',
        };
    }

    /**
     * @param  Collection<int, Resolucao>  $resolucoes
     * @return array{no_prazo: int, atencao: int, critico: int, vencidos: int, concluidas: int}
     */
    public static function contarPorSemaforo(Collection $resolucoes): array
    {
        $contagens = [
            'no_prazo' => 0,
            'atencao' => 0,
            'critico' => 0,
            'vencidos' => 0,
            'concluidas' => 0,
        ];

        foreach ($resolucoes as $resolucao) {
            $status = self::statusVigencia($resolucao);

            match ($status) {
                'vigente' => $contagens['no_prazo']++,
                'atencao' => $contagens['atencao']++,
                'critico' => $contagens['critico']++,
                'vencida' => $contagens['vencidos']++,
                'concluida' => $contagens['concluidas']++,
                default => null,
            };
        }

        return $contagens;
    }
}
