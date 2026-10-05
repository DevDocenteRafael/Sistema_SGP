<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Documento;
use App\Services\CadastroAuditoriaService;
use App\Services\DocumentoService;
use App\Services\Importacao\ImportacaoHistoricoService;
use App\Support\TextoSeguro;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Documentos PDF (ex.: vindos do SEI) guardados como anexo controlado e
 * vinculados a um registro. O conteúdo do PDF nunca é interpretado.
 */
class DocumentoController extends Controller
{
    public function __construct(
        private readonly DocumentoService $documentos,
        private readonly ImportacaoHistoricoService $historico,
    ) {}

    public function index(Request $request): JsonResponse
    {
        if (! $request->user()?->podeConsultarDados()) {
            return response()->json(['message' => 'Você não tem permissão para consultar documentos.'], 403);
        }

        $query = Documento::query()->with('usuario')->orderByDesc('id');
        if ($request->filled('modulo')) {
            $query->where('modulo', (string) $request->modulo);
        }
        if ($request->filled('registro_id')) {
            $query->where('registro_id', (int) $request->registro_id);
        }
        if ($request->boolean('sem_vinculo')) {
            $query->whereNull('registro_id');
        }

        return response()->json([
            'data' => $query->limit(200)->get()->map(fn (Documento $doc) => $this->documentos->serializar($doc))->values(),
            'meta' => [
                'modulos' => collect(DocumentoService::modulos())
                    ->map(fn (array $item, string $key) => ['value' => $key, 'label' => $item['label'], 'rota' => $item['rota']])
                    ->values(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (! $this->podeAnexar($request)) {
            return response()->json(['message' => 'Você não tem permissão para enviar documentos.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'arquivo' => ['required', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:20480'],
            'modulo' => ['required', 'string'],
            'registro_id' => ['nullable', 'integer', 'min:1'],
            'titulo' => ['nullable', 'string', 'max:255', 'required_without:registro_id'],
            'processo_sei' => ['nullable', 'string', 'max:100'],
            'descricao' => ['nullable', 'string', 'max:2000'],
        ], [
            'arquivo.required' => 'Selecione o arquivo PDF.',
            'arquivo.mimes' => 'O documento deve ser um arquivo PDF.',
            'arquivo.mimetypes' => 'O documento deve ser um arquivo PDF.',
            'arquivo.max' => 'O PDF deve ter no máximo 20 MB.',
            'modulo.required' => 'Selecione o módulo do documento.',
            'titulo.required_without' => 'Sem registro vinculado, informe ao menos o título do documento.',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        $dados = $validator->validated();
        if ($erro = $this->validarVinculo($dados['modulo'], $dados['registro_id'] ?? null)) {
            return $erro;
        }

        try {
            $documento = $this->documentos->salvar($request->file('arquivo'), [
                'modulo' => $dados['modulo'],
                'registro_id' => $dados['registro_id'] ?? null,
                'titulo' => $this->limpar($dados['titulo'] ?? null),
                'processo_sei' => $this->limpar($dados['processo_sei'] ?? null),
                'descricao' => $this->limpar($dados['descricao'] ?? null),
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $historico = $this->historico->registrarDocumento(
            $documento,
            $request->file('arquivo'),
            DocumentoService::labelModulo($documento->modulo),
        );
        $documento->update(['importacao_historico_id' => $historico->id]);

        app(CadastroAuditoriaService::class)->registrar(
            CadastroAuditoriaService::ACAO_CRIAR,
            'documentos',
            $documento,
            'Documento PDF "'.$documento->titulo.'" enviado em '.DocumentoService::labelModulo($documento->modulo),
        );

        return response()->json([
            'message' => $documento->registro_id
                ? 'Documento PDF guardado e vinculado ao registro.'
                : 'Documento PDF guardado. Vincule-o ao registro quando ele for cadastrado.',
            'documento' => $this->documentos->serializar($documento->fresh('usuario')),
        ], 201);
    }

    public function update(Request $request, Documento $documento): JsonResponse
    {
        if (! $this->podeAnexar($request)) {
            return response()->json(['message' => 'Você não tem permissão para alterar documentos.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'registro_id' => ['nullable', 'integer', 'min:1'],
            'titulo' => ['sometimes', 'required', 'string', 'max:255'],
            'processo_sei' => ['nullable', 'string', 'max:100'],
            'descricao' => ['nullable', 'string', 'max:2000'],
        ], [
            'titulo.required' => 'Informe o título do documento.',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        $dados = $validator->validated();
        if (array_key_exists('registro_id', $dados) && ($erro = $this->validarVinculo($documento->modulo, $dados['registro_id']))) {
            return $erro;
        }

        foreach (['titulo', 'processo_sei', 'descricao'] as $campo) {
            if (array_key_exists($campo, $dados)) {
                $dados[$campo] = $this->limpar($dados[$campo]);
            }
        }

        $documento->update($dados);

        return response()->json([
            'message' => 'Documento atualizado.',
            'documento' => $this->documentos->serializar($documento->fresh('usuario')),
        ]);
    }

    public function destroy(Request $request, Documento $documento): JsonResponse
    {
        if (! $request->user()?->podeEditarDados()) {
            return response()->json(['message' => 'Você não tem permissão para remover documentos.'], 403);
        }

        $titulo = $documento->titulo;
        // Exclusão lógica: o PDF fica guardado e pode ser restaurado.
        $documento->delete();

        return response()->json(['message' => 'Documento "'.$titulo.'" removido.']);
    }

    public function download(Request $request, Documento $documento): StreamedResponse|JsonResponse
    {
        if (! $request->user()?->podeConsultarDados()) {
            return response()->json(['message' => 'Você não tem permissão para baixar documentos.'], 403);
        }

        if (! Storage::disk(DocumentoService::DISK)->exists($documento->arquivo_path)) {
            return response()->json(['message' => 'Arquivo do documento não encontrado.'], 404);
        }

        return Storage::disk(DocumentoService::DISK)->download(
            $documento->arquivo_path,
            $documento->arquivo_nome,
            ['Content-Type' => 'application/pdf', 'X-Content-Type-Options' => 'nosniff'],
        );
    }

    private function podeAnexar(Request $request): bool
    {
        $usuario = $request->user();

        return (bool) ($usuario?->podeEditarDados() || $usuario?->podeImportarDados());
    }

    private function validarVinculo(string $modulo, ?int $registroId): ?JsonResponse
    {
        if (! DocumentoService::moduloValido($modulo)) {
            return response()->json(['message' => 'Este módulo não aceita documentos PDF.'], 422);
        }

        if ($registroId !== null && ! $this->documentos->registroExiste($modulo, $registroId)) {
            return response()->json(['message' => 'Registro não encontrado para vincular o documento.'], 422);
        }

        return null;
    }

    private function limpar(?string $valor): ?string
    {
        $valor = $valor === null ? null : trim(strip_tags($valor));

        return $valor === '' ? null : $valor;
    }
}
