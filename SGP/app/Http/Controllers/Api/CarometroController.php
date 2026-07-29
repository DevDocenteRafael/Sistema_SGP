<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AutorizaConsulta;
use App\Http\Controllers\Controller;
use App\Services\CarometroService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarometroController extends Controller
{
    use AutorizaConsulta;

    public function __construct(
        private readonly CarometroService $carometroService
    ) {}

    public function index(Request $request): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar o carômetro.')) {
            return $negado;
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
