<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CpedEquipeFotoService
{
    private const DISK = 'public';

    private const DIR = 'cped';

    public function salvar(UploadedFile $arquivo): string
    {
        return $arquivo->store(self::DIR, self::DISK);
    }

    public function apagar(?string $caminho): void
    {
        if (! $caminho || $this->ehUrlExternaOuData($caminho)) {
            return;
        }

        if (Storage::disk(self::DISK)->exists($caminho)) {
            Storage::disk(self::DISK)->delete($caminho);
        }
    }

    public function urlPublica(?string $caminho): ?string
    {
        if (! $caminho) {
            return null;
        }

        if ($this->ehUrlExternaOuData($caminho)) {
            return $caminho;
        }

        // URL relativa: funciona no artisan serve e via proxy do Vite.
        return '/storage/'.ltrim(str_replace('\\', '/', $caminho), '/');
    }

    private function ehUrlExternaOuData(string $valor): bool
    {
        return str_starts_with($valor, 'data:')
            || str_starts_with($valor, 'http://')
            || str_starts_with($valor, 'https://')
            || str_starts_with($valor, '/storage/');
    }
}
