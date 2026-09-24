<?php

namespace App\Support;

use Illuminate\Http\Request;

class OrigemDados
{
    public const TIPO_SEEDER = 'seeder';

    public const TIPO_LOCAL = 'local';

    public const TIPO_INTEGRACAO = 'integration';

    public static function mensagemBloqueio(): string
    {
        return (string) config('origem_dados.mensagem_bloqueio');
    }

    public static function permiteEscrita(?string $entidade): bool
    {
        if (! $entidade) {
            return true;
        }

        if (in_array($entidade, config('origem_dados.escritas_sistema', []), true)) {
            return true;
        }

        return in_array($entidade, config('origem_dados.administrativas', []), true);
    }

    public static function entidadeDoPedido(Request $request): ?string
    {
        $nome = (string) ($request->route()?->getName() ?? '');
        if ($nome !== '') {
            $base = explode('.', $nome)[0];

            return self::resolverChave($base);
        }

        $path = trim($request->path(), '/');
        $path = preg_replace('#^api/#', '', $path) ?? $path;

        if (str_starts_with($path, 'importacoes')) {
            return 'importacoes';
        }
        if (str_starts_with($path, 'revisao-dados')) {
            return 'revisao-dados';
        }
        if (str_starts_with($path, 'kanban')) {
            return 'ferramentas';
        }

        $primeiro = explode('/', $path)[0] ?? '';

        return self::resolverChave($primeiro);
    }

    public static function resolverChave(string $rota): string
    {
        $mapa = config('origem_dados.rotas', []);

        return $mapa[$rota] ?? $rota;
    }
}
