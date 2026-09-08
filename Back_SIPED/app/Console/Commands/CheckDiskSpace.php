<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CheckDiskSpace extends Command
{
    protected $signature = 'sgp:check-disk
                            {--disk= : Unidade ou caminho (ex.: C: ou C:\\). Vazio = storage da app}
                            {--warn-percent= : Sobrescreve limiar de aviso (%)}
                            {--warn-gb= : Sobrescreve limiar de aviso (GB)}
                            {--crit-percent= : Sobrescreve limiar crítico (%)}
                            {--crit-gb= : Sobrescreve limiar crítico (GB)}
                            {--json : Saída JSON}';

    protected $description = 'Verifica espaço livre em disco (complemento ao script PowerShell externo).';

    public function handle(): int
    {
        $targets = $this->resolverAlvos();
        $warnPercent = (float) ($this->option('warn-percent') ?? config('system_health.warning.free_percent'));
        $warnGb = (float) ($this->option('warn-gb') ?? config('system_health.warning.free_gb'));
        $critPercent = (float) ($this->option('crit-percent') ?? config('system_health.critical.free_percent'));
        $critGb = (float) ($this->option('crit-gb') ?? config('system_health.critical.free_gb'));

        $resultados = [];
        $piorCodigo = 0;

        foreach ($targets as $alvo) {
            $stats = $this->medir($alvo);
            if ($stats === null) {
                $item = [
                    'path' => $alvo,
                    'ok' => false,
                    'status' => 'erro',
                    'message' => 'Não foi possível ler o espaço em disco deste caminho.',
                ];
                $resultados[] = $item;
                $piorCodigo = max($piorCodigo, 2);

                continue;
            }

            $status = 'ok';
            $codigo = 0;
            $message = sprintf(
                '%s: %.2f GB livres (%.1f%% de %.2f GB).',
                $alvo,
                $stats['free_gb'],
                $stats['free_percent'],
                $stats['total_gb']
            );

            if ($stats['free_percent'] < $critPercent || $stats['free_gb'] < $critGb) {
                $status = 'critical';
                $codigo = 2;
                $message = '[CRITICO] '.$message;
            } elseif ($stats['free_percent'] < $warnPercent || $stats['free_gb'] < $warnGb) {
                $status = 'warning';
                $codigo = 1;
                $message = '[AVISO] '.$message;
            }

            $resultados[] = array_merge($stats, [
                'path' => $alvo,
                'ok' => $codigo === 0,
                'status' => $status,
                'message' => $message,
                'thresholds' => [
                    'warning' => ['free_percent' => $warnPercent, 'free_gb' => $warnGb],
                    'critical' => ['free_percent' => $critPercent, 'free_gb' => $critGb],
                ],
            ]);
            $piorCodigo = max($piorCodigo, $codigo);
        }

        if ($this->option('json')) {
            $this->line(json_encode([
                'status' => match ($piorCodigo) {
                    0 => 'ok',
                    1 => 'warning',
                    default => 'critical',
                },
                'exit_code' => $piorCodigo,
                'disks' => $resultados,
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            return $piorCodigo;
        }

        foreach ($resultados as $item) {
            match ($item['status']) {
                'ok' => $this->info($item['message']),
                'warning' => $this->warn($item['message']),
                default => $this->error($item['message']),
            };
        }

        return $piorCodigo;
    }

    /**
     * @return list<string>
     */
    private function resolverAlvos(): array
    {
        if ($this->option('disk')) {
            return [(string) $this->option('disk')];
        }

        $configurados = config('system_health.disks', []);
        if (is_array($configurados) && $configurados !== []) {
            return array_values(array_map('strval', $configurados));
        }

        return [storage_path()];
    }

    /**
     * @return array{free_bytes: int, total_bytes: int, free_gb: float, total_gb: float, free_percent: float}|null
     */
    private function medir(string $path): ?array
    {
        $path = $this->normalizarCaminho($path);

        if (! File::exists($path) && ! is_dir($path)) {
            // Unidade Windows "C:" / "C:\" pode não passar em exists() em todos os contextos.
            if (! preg_match('/^[A-Za-z]:\\\\?$/', $path) && ! preg_match('/^[A-Za-z]:$/', rtrim($path, '\\/'))) {
                return null;
            }
        }

        $livre = @disk_free_space($path);
        $total = @disk_total_space($path);

        if ($livre === false || $total === false || $total <= 0) {
            return null;
        }

        $freeGb = $livre / 1024 / 1024 / 1024;
        $totalGb = $total / 1024 / 1024 / 1024;

        return [
            'free_bytes' => (int) $livre,
            'total_bytes' => (int) $total,
            'free_gb' => round($freeGb, 2),
            'total_gb' => round($totalGb, 2),
            'free_percent' => round(100 * ($livre / $total), 1),
        ];
    }

    private function normalizarCaminho(string $path): string
    {
        $path = trim($path);
        if (preg_match('/^[A-Za-z]:$/', $path)) {
            return $path.'\\';
        }

        return $path;
    }
}
