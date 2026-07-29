<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AutorizaConsulta;
use App\Http\Controllers\Controller;
use App\Services\FerramentaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FerramentaController extends Controller
{
    use AutorizaConsulta;

    public function __construct(
        private readonly FerramentaService $ferramentaService
    ) {}

    public function index(Request $request): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar ferramentas.')) {
            return $negado;
        }

        $data = $this->ferramentaService->listForUsuario($request->user());

        return response()->json([
            'data' => $data,
            'meta' => [
                'total' => count($data),
            ],
        ]);
    }
}
