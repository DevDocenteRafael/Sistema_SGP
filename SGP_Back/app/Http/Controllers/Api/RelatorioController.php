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
                    'api' => '/api/relatorios/'.$item['key'].'/preview',
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
                'eixos' => $this->relatorioService->eixosDisponiveis(),
                'unidades' => config('unidades', []),
            ],
        ]);
    }

    public function preview(Request $request, string $tipo): JsonResponse
    {
        if ($negado = $this->negarSeNaoPodeConsultar($request, 'Você não tem permissão para consultar relatórios.')) {
            return $negado;
        }

        if (! $this->relatorioService->tipoExiste($tipo)) {
            return response()->json([
                'message' => 'Tipo de relatório não encontrado.',
            ], 404);
        }

        $payload = $this->relatorioService->montar(
            $tipo,
            $request->only([
                'ano',
                'unidade',
                'eixo',
                'status',
                'busca',
                'categoria',
                'setor',
                'relator',
            ]),
            RelatorioService::LIMITE_PREVIEW
        );

        $definicao = $payload['definicao'];
        $previewKeys = $definicao['preview_keys'] ?? [];
        $colunasMap = collect($definicao['colunas'] ?? [])->keyBy('key');

        $registros = collect($payload['registros'])->map(function (array $linha) use ($previewKeys) {
            if ($previewKeys === []) {
                return $linha;
            }

            $saida = ['id' => $linha['id'] ?? null];
            foreach ($previewKeys as $chave) {
                $saida[$chave] = $linha[$chave] ?? null;
            }

            return $saida;
        })->values();

        return response()->json([
            'data' => $registros,
            'meta' => [
                'total' => $payload['total'],
                'total_exibido' => $payload['total_exibido'],
                'truncado' => $payload['truncado'],
                'limite' => $payload['limite'],
                'eixos' => $this->relatorioService->eixosDisponiveis(),
                'unidades' => config('unidades', []),
                'status' => $registros->pluck('status')->filter()->unique()->sort()->values(),
                'categorias' => $registros->pluck('categoria')->filter()->unique()->sort()->values(),
                'setores' => $registros->pluck('setor')->filter()->unique()->sort()->values(),
                'colunas_preview' => collect($previewKeys)
                    ->map(fn ($key) => $colunasMap->get($key) ?? ['key' => $key, 'label' => $key])
                    ->values(),
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
            'categoria',
            'setor',
            'relator',
        ]), RelatorioService::LIMITE_PDF);

        $pdf = Pdf::loadView('relatorios.tabela', [
            'titulo' => $payload['definicao']['label'],
            'descricao' => $payload['definicao']['description'],
            'colunas' => $payload['definicao']['colunas'],
            'registros' => $payload['registros'],
            'filtros' => $payload['filtros'],
            'total' => $payload['total'],
            'truncado' => $payload['truncado'],
            'limite' => $payload['limite'],
            'emitidoEm' => now()->timezone(config('app.timezone'))->format('d/m/Y H:i'),
            'usuario' => $request->user()?->nome,
        ])->setPaper('a4', 'landscape');

        $nome = 'relatorio-'.$tipo.'-'.now()->format('Ymd').'.pdf';

        return $pdf->download($nome);
    }
}
