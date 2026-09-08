<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AutorizaConsulta;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SistemaApoioController extends Controller
{
    use AutorizaConsulta;

    public function index(Request $request): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar os sistemas de apoio.')) {
            return $negado;
        }

        $links = array_values(config('sistemas_apoio.links', []));

        return response()->json([
            'data' => $links,
            'meta' => [
                'total' => count($links),
            ],
        ]);
    }
}
