<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FerramentaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FerramentaController extends Controller
{
    public function __construct(
        private readonly FerramentaService $ferramentaService
    ) {}

    public function index(Request $request): JsonResponse
    {
        if (! $request->user()?->podeConsultarDados()) {
            return response()->json([
                'message' => 'Você não tem permissão para consultar ferramentas.',
            ], 403);
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
