<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CarometroService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarometroController extends Controller
{
    public function __construct(
        private readonly CarometroService $carometroService
    ) {}

    public function index(Request $request): JsonResponse
    {
        if (! $request->user()?->podeConsultarDados()) {
            return response()->json([
                'message' => 'Você não tem permissão para consultar o carômetro.',
            ], 403);
        }

        $dados = $this->carometroService->listar();

        return response()->json([
            'data' => $dados['membros'],
            'meta' => array_merge($dados['meta'], [
                'pode_editar' => $request->user()->podeEditarDados(),
            ]),
        ]);
    }
}
