<?php

namespace App\Services;

use App\Models\Usuario;

class FerramentaService
{
    /**
     * Lista o catálogo completo resolvido para o perfil do usuário.
     *
     * @return list<array{
     *     key: string,
     *     label: string,
     *     description: string,
     *     type: string,
     *     route: ?string,
     *     url: ?string,
     *     enabled: bool,
     *     status: string,
     *     icon: string
     * }>
     */
    public function listForUsuario(?Usuario $usuario): array
    {
        $perfil = $usuario?->perfil;

        if (! $perfil) {
            return [];
        }

        $catalogo = config('ferramentas.catalogo', []);

        $itens = [];

        foreach ($catalogo as $item) {
            $perfis = $item['profiles'] ?? [];

            if (! in_array($perfil, $perfis, true)) {
                continue;
            }

            $itens[] = [
                'key' => $item['key'],
                'label' => $item['label'],
                'description' => $item['description'],
                'type' => $item['type'],
                'route' => $item['route'] ?? null,
                'url' => $item['url'] ?? null,
                'enabled' => (bool) ($item['default_enabled'] ?? false),
                'status' => $item['status'] ?? 'coming_soon',
                'icon' => $item['icon'] ?? 'kanban',
            ];
        }

        return $itens;
    }

    public function isEnabled(string $toolKey, ?Usuario $usuario = null): bool
    {
        $catalogo = config('ferramentas.catalogo', []);

        foreach ($catalogo as $item) {
            if (($item['key'] ?? null) !== $toolKey) {
                continue;
            }

            if ($usuario) {
                $perfis = $item['profiles'] ?? [];

                if (! in_array($usuario->perfil, $perfis, true)) {
                    return false;
                }
            }

            return (bool) ($item['default_enabled'] ?? false);
        }

        return false;
    }

    public function exists(string $toolKey): bool
    {
        foreach (config('ferramentas.catalogo', []) as $item) {
            if (($item['key'] ?? null) === $toolKey) {
                return true;
            }
        }

        return false;
    }
}
