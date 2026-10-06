<?php

namespace Database\Seeders\Concerns;

use RuntimeException;

/**
 * Seeders de demonstração (usuários com senha conhecida, massa de dados, limpeza de tabelas)
 * nunca rodam em produção (SPEC 08). Root de produção: php artisan siped:criar-root.
 */
trait SomenteForaDeProducao
{
    protected function bloquearEmProducao(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException(static::class.' é só para desenvolvimento/homologação e não roda em produção. '
                .'Para criar o Root use: php artisan siped:criar-root');
        }
    }
}
