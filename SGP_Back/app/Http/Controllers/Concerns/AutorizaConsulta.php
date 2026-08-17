<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait AutorizaConsulta
{
    protected function negarSeNaoPodeConsultar(Request $request, string $mensagem): ?JsonResponse
    {
        if ($request->user()?->podeConsultarDados()) {
            return null;
        }

        return response()->json([
            'message' => $mensagem,
        ], 403);
    }
}
