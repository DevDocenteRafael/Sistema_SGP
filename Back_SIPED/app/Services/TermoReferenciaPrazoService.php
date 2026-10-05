<?php

namespace App\Services;

use App\Models\TermoReferencia;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Semáforo de prazo dos Termos de Referência / Ata: exatamente três estados.
 * Verde (no prazo), Amarelo (atenção, prazo próximo) e Vermelho (vencido).
 *
 * A data que dirige o semáforo é o vencimento da Ata quando existir; sem Ata,
 * usa o prazo/deadline do TR. O status de tramitação não cria um quarto estado.
 */
class TermoReferenciaPrazoService
{
    public const NO_PRAZO = 'no_prazo';

    public const ATENCAO = 'atencao';

    public const VENCIDO = 'vencido';

    /** @return list<string> */
    public static function estados(): array
    {
        return [self::NO_PRAZO, self::ATENCAO, self::VENCIDO];
    }

    public static function diasAtencao(): int
    {
        return (int) config('termos_referencia.prazos.dias_atencao', 30);
    }

    /**
     * Data que dirige o semáforo: vencimento da Ata, senão o prazo do TR.
     */
    public static function dataReferencia(TermoReferencia $termo): ?string
    {
        return $termo->data_vencimento_ata?->format('Y-m-d')
            ?? $termo->prazo_deadline?->format('Y-m-d');
    }

    public static function origemPrazo(TermoReferencia $termo): ?string
    {
        if ($termo->data_vencimento_ata) {
            return 'ata';
        }

        return $termo->prazo_deadline ? 'tr' : null;
    }

    public static function statusDoTermo(TermoReferencia $termo): string
    {
        return self::statusPrazo(self::dataReferencia($termo));
    }

    /**
     * hoje > vencimento                 => vencido
     * faltam até N dias (janela)        => atencao
     * caso contrário                    => no_prazo
     */
    public static function statusPrazo(?string $dataVencimento): string
    {
        if (! $dataVencimento) {
            return self::ATENCAO;
        }

        $hoje = Carbon::now()->startOfDay();
        $prazo = Carbon::parse($dataVencimento)->startOfDay();
        $diasRestantes = (int) $hoje->diffInDays($prazo, false);

        if ($diasRestantes < 0) {
            return self::VENCIDO;
        }

        if ($diasRestantes <= self::diasAtencao()) {
            return self::ATENCAO;
        }

        return self::NO_PRAZO;
    }

    public static function corSemaforo(string $statusPrazo): string
    {
        return match ($statusPrazo) {
            self::NO_PRAZO => 'verde',
            self::VENCIDO => 'vermelho',
            default => 'amarelo',
        };
    }

    /**
     * Filtra pelo estado do semáforo usando a mesma regra de statusDoTermo().
     * Aceita os aliases antigos do filtro ("proximo" => atenção, "vencida" => vencido).
     *
     * @param  Builder<TermoReferencia>  $query
     * @param  Collection<int, TermoReferencia>  $todos  id + datas de prazo
     */
    public static function aplicarFiltro(Builder $query, string $status, Collection $todos): void
    {
        $status = match ($status) {
            'proximo' => self::ATENCAO,
            'vencida' => self::VENCIDO,
            'vigente' => self::NO_PRAZO,
            default => $status,
        };

        if (! in_array($status, self::estados(), true)) {
            return;
        }

        $ids = $todos
            ->filter(fn (TermoReferencia $termo) => self::statusDoTermo($termo) === $status)
            ->pluck('id')
            ->all();

        $query->whereIn('id', $ids);
    }

    /**
     * @param  Collection<int, TermoReferencia>  $termos
     * @return array{no_prazo: int, atencao: int, vencidos: int}
     */
    public static function contarPorPrazo(Collection $termos): array
    {
        $contagens = [
            'no_prazo' => 0,
            'atencao' => 0,
            'vencidos' => 0,
        ];

        foreach ($termos as $termo) {
            match (self::statusDoTermo($termo)) {
                self::NO_PRAZO => $contagens['no_prazo']++,
                self::VENCIDO => $contagens['vencidos']++,
                default => $contagens['atencao']++,
            };
        }

        return $contagens;
    }
}
