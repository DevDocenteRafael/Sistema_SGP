<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // SPEC 08: HTTPS obrigatório em produção.
        if (config('producao.forcar_https')) {
            URL::forceScheme('https');
        }

        // Limite geral por usuário e limite menor para operações pesadas (importação, upload, PDF).
        RateLimiter::for('api-usuario', fn (Request $request) => Limit::perMinute(config('producao.limites.api_por_minuto', 300))
            ->by($request->user()?->getAuthIdentifier() ?: $request->ip()));
        RateLimiter::for('pesado', fn (Request $request) => Limit::perMinute(config('producao.limites.pesado_por_minuto', 20))
            ->by($request->user()?->getAuthIdentifier() ?: $request->ip())
            ->response(fn () => response()->json(['message' => 'Muitas operações em sequência. Aguarde um minuto e tente de novo.'], 429)));

        RateLimiter::for('svt', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));

        RateLimiter::for('login', function (Request $request) {
            $email = mb_strtolower((string) $request->input('email', ''));

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });
    }
}
