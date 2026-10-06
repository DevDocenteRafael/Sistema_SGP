<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Throwable;

/**
 * Backup completo do banco (SPEC 08). MySQL via mysqldump; SQLite por cópia do arquivo.
 * Os arquivos ficam fora de public/ e os antigos são apagados após a retenção.
 * Restauração (sempre testar em um banco separado): docs/producao.md.
 */
class BackupBanco extends Command
{
    protected $signature = 'siped:backup
        {--pasta= : Pasta de destino (padrão: config producao.backup.pasta)}
        {--conexao= : Conexão do banco (padrão: a principal)}
        {--sem-limpeza : Não apaga backups antigos}';

    protected $description = 'Gera um backup completo do banco do SIPED';

    public function handle(): int
    {
        $pasta = (string) ($this->option('pasta') ?: config('producao.backup.pasta'));
        File::ensureDirectoryExists($pasta);

        $conexao = DB::connection($this->option('conexao') ?: null);
        $driver = $conexao->getDriverName();
        $carimbo = now()->format('Y-m-d_His');

        try {
            $arquivo = match ($driver) {
                'mysql', 'mariadb' => $this->mysql($conexao->getConfig(), $pasta."/siped-{$carimbo}.sql"),
                'sqlite' => $this->sqlite((string) $conexao->getConfig('database'), $pasta."/siped-{$carimbo}.sqlite"),
                default => throw new \RuntimeException("Banco {$driver} não suportado pelo backup."),
            };
        } catch (Throwable $e) {
            Log::error('Backup do SIPED falhou: '.$e->getMessage());
            $this->error('Backup falhou: '.$e->getMessage());

            return self::FAILURE;
        }

        $tamanho = round(filesize($arquivo) / 1024 / 1024, 2);
        Log::info("Backup do SIPED gerado: {$arquivo} ({$tamanho} MB).");
        $this->info("Backup gerado: {$arquivo} ({$tamanho} MB).");

        if (! $this->option('sem-limpeza')) {
            $removidos = $this->limpar($pasta, (int) config('producao.backup.dias_retencao', 30));
            if ($removidos > 0) {
                $this->line("{$removidos} backup(s) com mais de ".config('producao.backup.dias_retencao').' dias removido(s).');
            }
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $cfg
     */
    private function mysql(array $cfg, string $destino): string
    {
        $comando = [
            (string) config('producao.backup.mysqldump', 'mysqldump'),
            '--host='.($cfg['host'] ?? '127.0.0.1'),
            '--port='.($cfg['port'] ?? 3306),
            '--user='.($cfg['username'] ?? 'root'),
            '--single-transaction',
            '--routines',
            '--triggers',
            '--default-character-set=utf8mb4',
            '--result-file='.$destino,
            (string) $cfg['database'],
        ];

        // A senha vai por variável de ambiente para não aparecer na lista de processos.
        $resultado = Process::env(['MYSQL_PWD' => (string) ($cfg['password'] ?? '')])
            ->timeout(3600)
            ->run($comando);

        if (! $resultado->successful() || ! is_file($destino) || filesize($destino) === 0) {
            @unlink($destino);
            throw new \RuntimeException(trim($resultado->errorOutput()) ?: 'mysqldump não gerou o arquivo. Confira BACKUP_MYSQLDUMP.');
        }

        return $destino;
    }

    private function sqlite(string $origem, string $destino): string
    {
        if (! is_file($origem)) {
            throw new \RuntimeException("Arquivo SQLite não encontrado: {$origem}");
        }
        File::copy($origem, $destino);

        return $destino;
    }

    private function limpar(string $pasta, int $dias): int
    {
        if ($dias <= 0) {
            return 0;
        }
        $limite = now()->subDays($dias)->getTimestamp();
        $removidos = 0;
        foreach (File::files($pasta) as $arquivo) {
            if (str_starts_with($arquivo->getFilename(), 'siped-') && $arquivo->getMTime() < $limite) {
                File::delete($arquivo->getPathname());
                $removidos++;
            }
        }

        return $removidos;
    }
}
