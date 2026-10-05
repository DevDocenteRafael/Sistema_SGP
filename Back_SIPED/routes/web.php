<?php

use Illuminate\Support\Facades\Route;

$irParaFront = function (?string $path = null) {
    $build = public_path('build/siped/index.html');
    if (! config('app.debug')) {
        abort_unless(is_file($build), 503, 'O frontend SIPED ainda não foi compilado. Execute npm run build em Front_SIPED.');

        return response()->file($build, ['Cache-Control' => 'no-cache']);
    }

    $base = rtrim((string) config('app.frontend_url'), '/');
    $destino = $path ? '/'.ltrim($path, '/') : '/login';
    $query = request()->getQueryString();

    return redirect()->away($base.$destino.($query ? '?'.$query : ''));
};

Route::get('/', fn () => $irParaFront('/login'));

Route::get('/{path}', fn (string $path) => $irParaFront($path))
    ->where('path', '^(?!api(?:/|$)|up$|storage(?:/|$)|IMG(?:/|$)).*');
