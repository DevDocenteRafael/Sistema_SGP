<?php

namespace App\Services;

use App\Models\TermoReferencia;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TermoReferenciaPrazoService
{
    /**
     * Classifica o prazo do TR usando os limiares já existentes em config/termos_referencia.php.
     * Não define prazo oficial da CPED — só agrupa no mesmo espírito do semáforo (no prazo / atenção / crítico / vencido).
     */
    public static function statusPrazo(?string $prazoDeadline): string
    {
        if (! $prazoDeadline) {
            return 'atencao';
        }

        $hoje = Carbon::now()->startOfDay();
        $prazo = Carbon::parse($prazoDeadline)->startOfDay();
        $diasRestantes = (int) $hoje->diffInDays($prazo, false);

        if ($diasRestantes < 0) {
            return 'vencido';
        }

        $diasVerde = (int) config('termos_referencia.prazos.dias_verde', 30);
        $diasAmarelo = (int) config('termos_referencia.prazos.dias_amarelo', 15);

        if ($diasRestantes > $diasVerde) {
            return 'no_prazo';
        }

        if ($diasRestantes > $diasAmarelo) {
            return 'atencao';
        }

        return 'critico';
    }

    public static function corSemaforo(string $statusPrazo): string
    {
        return match ($statusPrazo) {
            'no_prazo' => 'verde',
            'atencao' => 'amarelo',
            'critico', 'vencido' => 'vermelho',
            default => 'amarelo',
        };
    }

    /**
     * @param  Collection<int, TermoReferencia>  $termos
     * @return array{no_prazo: int, atencao: int, critico: int, vencidos: int}
     */
    public static function contarPorPrazo(Collection $termos): array
    {
        $contagens = [
            'no_prazo' => 0,
            'atencao' => 0,
            'critico' => 0,
            'vencidos' => 0,
        ];

        foreach ($termos as $termo) {
            $statusPrazo = self::statusPrazo($termo->prazo_deadline?->format('Y-m-d'));

            match ($statusPrazo) {
                'no_prazo' => $contagens['no_prazo']++,
                'atencao' => $contagens['atencao']++,
                'critico' => $contagens['critico']++,
                'vencido' => $contagens['vencidos']++,
                default => null,
            };
        }

        return $contagens;
    }
}
