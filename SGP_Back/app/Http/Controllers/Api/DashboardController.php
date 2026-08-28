<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AutorizaConsulta;
use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use AutorizaConsulta;

    public function __construct(
        private readonly DashboardService $dashboardService
    ) {}

    public function resumo(Request $request): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar o dashboard.')) {
            return $negado;
        }

        $payload = $this->dashboardService->resumo();

        return response()->json([
            'data' => $payload,
        ]);
    }
}
