<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ResolucaoAnexoService
{
    private const DISK = 'public';

    private const DIR = 'resolucoes';

    public function salvar(UploadedFile $arquivo): string
    {
        return $arquivo->store(self::DIR, self::DISK);
    }

    public function apagar(?string $caminho): void
    {
        if (! $caminho || $this->ehUrlExterna($caminho)) {
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

        if ($this->ehUrlExterna($caminho)) {
            return $caminho;
        }

        return '/storage/'.ltrim($caminho, '/');
    }

    private function ehUrlExterna(string $valor): bool
    {
        return str_starts_with($valor, 'http://')
            || str_starts_with($valor, 'https://')
            || str_starts_with($valor, '/storage/');
    }
}
