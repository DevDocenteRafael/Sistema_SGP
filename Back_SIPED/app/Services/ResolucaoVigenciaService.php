<?php

namespace App\Services;

use App\Models\Resolucao;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Semáforo de vigência das resoluções: exatamente três estados.
 * Vigente (verde), Atenção (amarelo) e Vencida (vermelho).
 */
class ResolucaoVigenciaService
{
    public const VIGENTE = 'vigente';

    public const ATENCAO = 'atencao';

    public const VENCIDA = 'vencida';

    /** @return list<string> */
    public static function estados(): array
    {
        return [self::VIGENTE, self::ATENCAO, self::VENCIDA];
    }

    public static function vigenciaAnos(): int
    {
        return (int) config('resolucoes.vigencia_anos', 5);
    }

    public static function alertaPreventivoMeses(): int
    {
        return (int) config('resolucoes.alerta_preventivo_meses', 6);
    }

    public static function calcularDataFimVigencia(string|Carbon|null $inicio): Carbon
    {
        $inicioCarbon = $inicio ? Carbon::parse($inicio) : Carbon::now();

        return $inicioCarbon->copy()->addYears(self::vigenciaAnos());
    }

    /**
     * hoje > vencimento              => vencida
     * hoje >= vencimento - alerta    => atencao
     * caso contrário                 => vigente
     */
    public static function statusAutomatico(?string $dataInicioVigencia, ?string $dataFimVigencia = null): string
    {
        $fim = $dataFimVigencia
            ? Carbon::parse($dataFimVigencia)->startOfDay()
            : self::calcularDataFimVigencia($dataInicioVigencia ?? now())->startOfDay();
        $hoje = Carbon::now()->startOfDay();

        if ($hoje->gt($fim)) {
            return self::VENCIDA;
        }

        if ($hoje->gte($fim->copy()->subMonthsNoOverflow(self::alertaPreventivoMeses()))) {
            return self::ATENCAO;
        }

        return self::VIGENTE;
    }

    /**
     * O semáforo depende só da data de fim da vigência. Status legados gravados
     * (ex.: "concluida", "critico") não criam um quarto estado.
     */
    public static function statusVigencia(Resolucao $resolucao): string
    {
        return self::statusAutomatico(
            $resolucao->data_inicio_vigencia?->format('Y-m-d'),
            $resolucao->data_fim_vigencia?->format('Y-m-d')
        );
    }

    public static function corSemaforo(string $statusVigencia): string
    {
        return match ($statusVigencia) {
            self::ATENCAO => 'amarelo',
            self::VENCIDA => 'vermelho',
            default => 'verde',
        };
    }

    /**
     * Filtra a consulta pelo estado do semáforo usando a mesma regra de statusAutomatico().
     *
     * @param  Builder<Resolucao>  $query
     * @param  Collection<int, Resolucao>  $todas  id + datas de vigência
     */
    public static function aplicarFiltro(Builder $query, string $status, Collection $todas): void
    {
        if (! in_array($status, self::estados(), true)) {
            return;
        }

        $ids = $todas
            ->filter(fn (Resolucao $resolucao) => self::statusVigencia($resolucao) === $status)
            ->pluck('id')
            ->all();

        $query->whereIn('id', $ids);
    }

    /**
     * @param  Collection<int, Resolucao>  $resolucoes
     * @return array{no_prazo: int, atencao: int, vencidos: int}
     */
    public static function contarPorSemaforo(Collection $resolucoes): array
    {
        $contagens = [
            'no_prazo' => 0,
            'atencao' => 0,
            'vencidos' => 0,
        ];

        foreach ($resolucoes as $resolucao) {
            match (self::statusVigencia($resolucao)) {
                self::ATENCAO => $contagens['atencao']++,
                self::VENCIDA => $contagens['vencidos']++,
                default => $contagens['no_prazo']++,
            };
        }

        return $contagens;
    }
}
