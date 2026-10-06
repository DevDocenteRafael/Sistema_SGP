<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Throwable as ThrowableErro;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // SPEC 08: HTTPS obrigatório, cabeçalhos de segurança e proxies confiáveis.
        $middleware->prepend(\App\Http\Middleware\ForcarHttps::class);
        $middleware->append(\App\Http\Middleware\CabecalhosSeguranca::class);
        if ($proxies = env('TRUSTED_PROXIES')) {
            $middleware->trustProxies(at: $proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }

        $middleware->alias([
            'perfil' => \App\Http\Middleware\CheckPerfil::class,
            'usuario.ativo' => \App\Http\Middleware\EnsureUsuarioAtivo::class,
        ]);
        $middleware->api(append: [
            \App\Http\Middleware\ResolveCicloContexto::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // A API responde sempre em JSON; com APP_DEBUG=false, sem stack trace (SPEC 08).
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, ThrowableErro $e) => $request->is('api/*') || $request->expectsJson()
        );
    })->create();
