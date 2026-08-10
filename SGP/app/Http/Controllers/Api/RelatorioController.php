<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AutorizaConsulta;
use App\Http\Controllers\Controller;
use App\Services\RelatorioService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class RelatorioController extends Controller
{
    use AutorizaConsulta;

    public function __construct(
        private readonly RelatorioService $relatorioService
    ) {}

    public function index(Request $request): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar relatórios.')) {
            return $negado;
        }

        $contagens = $this->relatorioService->contagens();
        $catalogo = collect($this->relatorioService->catalogo())
            ->map(function (array $item) use ($contagens) {
                return [
                    'key' => $item['key'],
                    'label' => $item['label'],
                    'description' => $item['description'],
                    'api' => $item['api'],
                    'icon' => $item['icon'],
                    'filtros' => $item['filtros'],
                    'preview_keys' => $item['preview_keys'],
                    'colunas' => $item['colunas'],
                    'total' => $contagens[$item['key']] ?? 0,
                ];
            })
            ->values();

        return response()->json([
            'data' => $catalogo,
            'meta' => [
                'eixos' => config('eixos', []),
                'unidades' => config('unidades', []),
            ],
        ]);
    }

    public function pdf(Request $request, string $tipo): Response|JsonResponse|SymfonyResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para exportar relatórios.')) {
            return $negado;
        }

        if (! $this->relatorioService->tipoExiste($tipo)) {
            return response()->json([
                'message' => 'Tipo de relatório não encontrado.',
            ], 404);
        }

        $payload = $this->relatorioService->montar($tipo, $request->only([
            'ano',
            'unidade',
            'eixo',
            'status',
            'busca',
        ]));

        $pdf = Pdf::loadView('relatorios.tabela', [
            'titulo' => $payload['definicao']['label'],
            'descricao' => $payload['definicao']['description'],
            'colunas' => $payload['definicao']['colunas'],
            'registros' => $payload['registros'],
            'filtros' => $payload['filtros'],
            'total' => $payload['total'],
            'emitidoEm' => now()->timezone(config('app.timezone'))->format('d/m/Y H:i'),
            'usuario' => $request->user()?->nome,
        ])->setPaper('a4', 'landscape');

        $nome = 'relatorio-'.$tipo.'-'.now()->format('Ymd').'.pdf';

        return $pdf->download($nome);
    }
}
