<?php

use Illuminate\Support\Facades\Route;

$irParaFront = function (?string $path = null) {
    $base = rtrim((string) config('app.frontend_url'), '/');
    $destino = $path ? '/'.ltrim($path, '/') : '/login';
    $query = request()->getQueryString();

    return redirect()->away($base.$destino.($query ? '?'.$query : ''));
};

Route::get('/', fn () => $irParaFront('/login'));

Route::get('/{path}', fn (string $path) => $irParaFront($path))
    ->where('path', '^(?!up$).*');
