<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AutorizaConsulta;
use App\Http\Controllers\Controller;
use App\Support\SistemasExternos;
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

        $links = collect(config('sistemas_apoio.links', []))
            ->map(function (array $link) {
                $link['url'] = SistemasExternos::url($link['sistema'] ?? $link['key']);
                unset($link['sistema']);

                return $link;
            })
            ->filter(fn (array $link) => $link['url'] !== null)
            ->values()
            ->all();

        return response()->json([
            'data' => $links,
            'meta' => [
                'total' => count($links),
                'externos' => SistemasExternos::publico(),
            ],
        ]);
    }

    /**
     * URLs públicas dos sistemas externos (SEI, SIG, SIGIN, site) para links no front.
     */
    public function externos(Request $request): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar os sistemas de apoio.')) {
            return $negado;
        }

        return response()->json(['data' => SistemasExternos::publico()]);
    }
}
