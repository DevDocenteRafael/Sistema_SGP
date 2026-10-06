<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SvtIntegracaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Endpoint chamado pelo SVT (máquina a máquina). Autenticação por token
 * compartilhado (SVT_INTEGRACAO_TOKEN), enviado em "Authorization: Bearer ...".
 */
class SvtIntegracaoController extends Controller
{
    public function __construct(
        private readonly SvtIntegracaoService $svt,
    ) {}

    public function visitas(Request $request): JsonResponse
    {
        if ($negado = $this->autenticar($request)) {
            return $negado;
        }

        $validator = Validator::make($request->all(), [
            'visitas' => ['required', 'array', 'min:1', 'max:'.(int) config('svt.lote_maximo', 500)],
            'visitas.*' => ['array'],
        ], [
            'visitas.required' => 'Envie a lista "visitas".',
            'visitas.max' => 'Envie no máximo :max visitas por chamada.',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        $resultado = $this->svt->sincronizar(array_values($request->input('visitas')));

        return response()->json([
            'message' => 'Sincronização concluída.',
            ...$resultado,
        ]);
    }

    public function situacao(Request $request): JsonResponse
    {
        if ($negado = $this->autenticar($request)) {
            return $negado;
        }

        return response()->json(['data' => $this->svt->situacao()]);
    }

    private function autenticar(Request $request): ?JsonResponse
    {
        if (! SvtIntegracaoService::integracaoAtiva()) {
            return response()->json(['message' => 'Integração com o SVT desativada (SVT_INTEGRACAO_TOKEN não configurado).'], 503);
        }

        $token = (string) $request->bearerToken();
        if ($token === '' || ! hash_equals((string) config('svt.token'), $token)) {
            return response()->json(['message' => 'Token de integração inválido.'], 401);
        }

        return null;
    }
}
