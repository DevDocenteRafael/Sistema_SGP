<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Importacao\ImportacaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Throwable;

class ImportacaoController extends Controller
{
    public function __construct(
        private readonly ImportacaoService $importacaoService
    ) {}

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

        $validator = Validator::make($request->all(), [
            'arquivo' => ['required', 'file', 'mimes:xlsx,xls', 'max:20480'],
        ], [
            'arquivo.required' => 'Envie um arquivo Excel (.xlsx ou .xls).',
            'arquivo.mimes' => 'O arquivo deve ser .xlsx ou .xls.',
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
            'aviso' => 'A confirmação substituirá todos os registros atuais de '.$resultado['label'].'.',
        ]);
    }

    public function commit(Request $request, string $modulo): JsonResponse
    {
        if (! $request->user()?->podeImportarDados()) {
            return response()->json([
                'message' => 'Você não tem permissão para importar dados.',
            ], 403);
        }

        if (! $this->importacaoService->existe($modulo)) {
            return response()->json(['message' => 'Módulo de importação não encontrado.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'arquivo' => ['required', 'file', 'mimes:xlsx,xls', 'max:20480'],
        ], [
            'arquivo.required' => 'Envie novamente o arquivo Excel para confirmar a importação.',
            'arquivo.mimes' => 'O arquivo deve ser .xlsx ou .xls.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $resultado = $this->importacaoService->commit($modulo, $request->file('arquivo'));
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Não foi possível concluir a importação: '.$e->getMessage(),
            ], 422);
        }

        Cache::forget('relatorios.contagens');

        return response()->json([
            'message' => 'Importação concluída. Os dados de '.$resultado['label'].' foram substituídos.',
            'modulo' => $modulo,
            'aba' => $resultado['aba'],
            'importados' => $resultado['total'],
            'ignoradas' => $resultado['ignoradas'],
            'erros' => $resultado['erros'],
            'backup' => $resultado['backup'] ?? null,
        ]);
    }
}
