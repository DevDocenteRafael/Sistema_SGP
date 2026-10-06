<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ImportacaoInvalidaException;
use App\Http\Controllers\Controller;
use App\Models\ImportacaoHistorico;
use App\Services\CicloContextoService;
use App\Services\Importacao\ImportacaoHistoricoService;
use App\Services\Importacao\ImportacaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ImportacaoController extends Controller
{
    public function __construct(
        private readonly ImportacaoService $importacaoService,
        private readonly ImportacaoHistoricoService $historicoService,
    ) {}

    /**
     * Excel é o formato de importação estruturada. PDF é documento/anexo e segue
     * outro fluxo (POST /api/documentos) — nunca é interpretado como planilha.
     */
    private function respostaSeForPdf(?UploadedFile $arquivo): ?JsonResponse
    {
        if (! $arquivo) {
            return null;
        }

        $mime = (string) $arquivo->getMimeType();
        $extensao = strtolower((string) $arquivo->getClientOriginalExtension());
        if ($mime !== 'application/pdf' && $extensao !== 'pdf') {
            return null;
        }

        return response()->json([
            'tipo' => 'documento',
            'message' => 'Arquivos PDF não são importados como planilha. Envie o PDF como documento e vincule-o ao registro (Evento, Resolução ou Termo de Referência).',
        ], 422);
    }

    public function catalogo(Request $request): JsonResponse
    {
        if (! $request->user()?->podeImportarDados()) {
            return response()->json([
                'message' => 'Você não tem permissão para importar dados.',
            ], 403);
        }

        $itens = collect($this->importacaoService->catalogo())
            ->map(fn (array $item) => [
                'key' => $item['key'],
                'label' => $item['label'],
                'description' => $item['description'],
                'ajuda' => $item['ajuda'] ?? $item['description'],
                'preview_columns' => $item['preview_columns'] ?? [],
            ])
            ->values();

        return response()->json(['data' => $itens]);
    }

    public function preview(Request $request, string $modulo): JsonResponse
    {
        if (! $request->user()?->podeImportarDados()) {
            return response()->json([
                'message' => 'Você não tem permissão para importar dados.',
            ], 403);
        }

        if (! $this->importacaoService->existe($modulo)) {
            return response()->json(['message' => 'Módulo de importação não encontrado.'], 404);
        }

        if ($pdf = $this->respostaSeForPdf($request->file('arquivo'))) {
            return $pdf;
        }

        $validator = Validator::make($request->all(), [
            'arquivo' => ['required', 'file', 'mimes:xlsx,xls', 'max:20480'],
            'ciclo_id' => ['nullable', 'integer', 'exists:portfolio_ciclos,id'],
        ], [
            'arquivo.required' => 'Envie um arquivo Excel (.xlsx ou .xls).',
            'arquivo.mimes' => 'O arquivo deve ser .xlsx ou .xls.',
            'ciclo_id.exists' => 'Ciclo de gestão de destino não encontrado.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $resultado = $this->importacaoService->parse($modulo, $request->file('arquivo'));
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Não foi possível ler a planilha: '.$e->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Prévia gerada com sucesso.',
            'modulo' => $modulo,
            'label' => $resultado['label'],
            'aba' => $resultado['aba'],
            'total' => $resultado['total'],
            'ignoradas' => $resultado['ignoradas'],
            'erros' => $resultado['erros'],
            'linhas' => $resultado['linhas'],
            'colunas_preview' => $resultado['colunas_preview'],
            'resumo_acoes' => $resultado['resumo_acoes'] ?? null,
            'incompletos' => $resultado['incompletos'] ?? 0,
            'campos_esperados' => $resultado['campos_esperados'] ?? [],
            'ciclo' => $resultado['ciclo'] ?? null,
            'aviso' => $resultado['ciclo']['nome'] ?? null
                ? 'A confirmação fará upsert no ciclo '.$resultado['ciclo']['nome'].'. Nenhum registro de outro ciclo é apagado automaticamente.'
                : 'A confirmação atualiza o ciclo selecionado por upsert. Nenhum registro de outro ciclo é apagado automaticamente.',
        ]);
    }

    public function commit(Request $request, string $modulo): JsonResponse
    {
        if ($modulo === 'visitas-tecnicas' && \App\Services\SvtIntegracaoService::modoSvt()) {
            return response()->json([
                'message' => 'As visitas técnicas vêm do SVT por integração; a importação por planilha está desativada.',
            ], 409);
        }

        if (! $request->user()?->podeImportarDados()) {
            return response()->json([
                'message' => 'Você não tem permissão para importar dados.',
            ], 403);
        }

        if (! $this->importacaoService->existe($modulo)) {
            return response()->json(['message' => 'Módulo de importação não encontrado.'], 404);
        }

        if ($pdf = $this->respostaSeForPdf($request->file('arquivo'))) {
            return $pdf;
        }

        $validator = Validator::make($request->all(), [
            'arquivo' => ['required', 'file', 'mimes:xlsx,xls', 'max:20480'],
            'ciclo_id' => ['nullable', 'integer', 'exists:portfolio_ciclos,id'],
        ], [
            'arquivo.required' => 'Envie novamente o arquivo Excel para confirmar a importação.',
            'arquivo.mimes' => 'O arquivo deve ser .xlsx ou .xls.',
            'ciclo_id.exists' => 'Ciclo de gestão de destino não encontrado.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $arquivo = $request->file('arquivo');
        $def = $this->importacaoService->definicao($modulo);

        try {
            $resultado = $this->importacaoService->commit($modulo, $arquivo);
        } catch (ImportacaoInvalidaException $e) {
            $this->registrarBloqueio($modulo, $def, $arquivo, $e->getMessage(), $e->erros);

            return response()->json([
                'message' => $e->getMessage(),
                'erros' => $e->erros,
            ], 422);
        } catch (InvalidArgumentException $e) {
            $this->registrarBloqueio($modulo, $def, $arquivo, $e->getMessage());

            return response()->json(['message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            $mensagem = 'Não foi possível concluir a importação: '.$e->getMessage();
            $this->registrarBloqueio($modulo, $def, $arquivo, $mensagem);

            return response()->json(['message' => $mensagem], 422);
        }

        Cache::forget('relatorios.contagens');

        $resumo = $resultado['resumo_acoes'] ?? [];
        $mensagem = 'Importação concluída. Os dados de '.$resultado['label'].' foram atualizados (upsert).';
        if (! empty($resumo['incompleto'])) {
            $mensagem .= ' '.$resumo['incompleto'].' registro(s) entraram incompletos — veja o histórico para corrigir.';
        }

        $historico = $this->historicoService->registrarPlanilha(
            $modulo,
            $def,
            $arquivo,
            $resultado,
            ImportacaoHistorico::SITUACAO_CONCLUIDA,
            $mensagem,
        );

        return response()->json([
            'message' => $mensagem,
            'modulo' => $modulo,
            'aba' => $resultado['aba'],
            'importados' => $resultado['total'],
            'ignoradas' => $resultado['ignoradas'],
            'erros' => $resultado['erros'],
            'resumo_acoes' => $resultado['resumo_acoes'] ?? null,
            'ciclo' => $resultado['ciclo'] ?? null,
            'backup' => $resultado['backup'] ?? null,
            'historico_id' => $historico->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $def
     * @param  list<array<string, mixed>>  $erros
     */
    private function registrarBloqueio(string $modulo, array $def, UploadedFile $arquivo, string $mensagem, array $erros = []): void
    {
        try {
            $this->historicoService->registrarPlanilha(
                $modulo,
                $def,
                $arquivo,
                null,
                ImportacaoHistorico::SITUACAO_BLOQUEADA,
                $mensagem,
                $erros,
                app(CicloContextoService::class)->id(),
            );
        } catch (Throwable) {
            // O histórico nunca impede a resposta da importação.
        }
    }

    public function historico(Request $request): JsonResponse
    {
        if (! $request->user()?->podeImportarDados()) {
            return response()->json(['message' => 'Você não tem permissão para consultar importações.'], 403);
        }

        $query = ImportacaoHistorico::query()->with(['usuario', 'ciclo'])->orderByDesc('id');
        if ($request->filled('modulo')) {
            $query->where('modulo', (string) $request->modulo);
        }
        if ($request->filled('tipo')) {
            $query->where('tipo', (string) $request->tipo);
        }

        $perPage = min(max((int) $request->input('per_page', 10), 1), 50);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => collect($paginator->items())
                ->map(fn (ImportacaoHistorico $item) => $this->historicoService->serializar($item))
                ->values(),
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function historicoDetalhe(Request $request, ImportacaoHistorico $historico): JsonResponse
    {
        if (! $request->user()?->podeImportarDados()) {
            return response()->json(['message' => 'Você não tem permissão para consultar importações.'], 403);
        }

        $historico->load(['usuario', 'ciclo']);

        return response()->json([
            'data' => $this->historicoService->serializar($historico, true),
        ]);
    }

    public function historicoArquivo(Request $request, ImportacaoHistorico $historico): StreamedResponse|JsonResponse
    {
        if (! $request->user()?->podeImportarDados()) {
            return response()->json(['message' => 'Você não tem permissão para baixar arquivos de importação.'], 403);
        }

        if (! $historico->arquivo_path || ! Storage::disk('local')->exists($historico->arquivo_path)) {
            return response()->json(['message' => 'Arquivo desta importação não está disponível.'], 404);
        }

        return Storage::disk('local')->download($historico->arquivo_path, $historico->arquivo_nome);
    }
}
