<?php

namespace App\Services\Importacao;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ImportBackupService
{
    private const MANTER_POR_MODULO = 10;

    /**
     * Salva JSON com o estado atual da tabela antes do wipe-and-replace.
     *
     * @param  class-string<Model>  $modelClass
     * @return array{path: string, total: int, disk: string}
     */
    public function backupAntesDeSubstituir(string $modulo, string $modelClass): array
    {
        $registros = $modelClass::query()->orderBy('id')->get()->toArray();
        $timestamp = now()->format('Y-m-d_His');
        $relative = "import-backups/{$modulo}/{$timestamp}.json";

        $payload = [
            'modulo' => $modulo,
            'model' => $modelClass,
            'gerado_em' => now()->toIso8601String(),
            'total' => count($registros),
            'registros' => $registros,
        ];

        Storage::disk('local')->put(
            $relative,
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );

        $this->podarAntigos($modulo);

        return [
            'path' => $relative,
            'total' => count($registros),
            'disk' => 'local',
        ];
    }

    private function podarAntigos(string $modulo): void
    {
        $dir = "import-backups/{$modulo}";
        $arquivos = collect(Storage::disk('local')->files($dir))
            ->filter(fn (string $path) => str_ends_with($path, '.json'))
            ->sort()
            ->values();

        $excedentes = $arquivos->count() - self::MANTER_POR_MODULO;
        if ($excedentes <= 0) {
            return;
        }

        foreach ($arquivos->take($excedentes) as $antigo) {
            Storage::disk('local')->delete($antigo);
        }
    }
}
