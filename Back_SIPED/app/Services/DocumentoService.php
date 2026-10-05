<?php

namespace App\Services;

use App\Models\Documento;
use App\Models\Evento;
use App\Models\Resolucao;
use App\Models\TermoReferencia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * PDF como documento/anexo controlado: guarda o arquivo com segurança e o vincula
 * a um registro existente. Não lê nem interpreta o conteúdo do PDF.
 */
class DocumentoService
{
    public const DISK = 'local';

    /**
     * Módulos que aceitam documentos PDF vinculados.
     *
     * @return array<string, array{label: string, model: class-string<Model>, titulo: string, rota: string}>
     */
    public static function modulos(): array
    {
        return [
            'eventos' => ['label' => 'Eventos', 'model' => Evento::class, 'titulo' => 'nome', 'rota' => '/app/eventos'],
            'resolucoes' => ['label' => 'Resoluções', 'model' => Resolucao::class, 'titulo' => 'numero', 'rota' => '/app/controle-de-resolucoes'],
            'termos-referencia' => ['label' => 'Termos de Referência', 'model' => TermoReferencia::class, 'titulo' => 'nome', 'rota' => '/app/termos-de-referencia'],
        ];
    }

    public static function moduloValido(?string $modulo): bool
    {
        return $modulo !== null && array_key_exists($modulo, self::modulos());
    }

    public static function labelModulo(string $modulo): string
    {
        return self::modulos()[$modulo]['label'] ?? $modulo;
    }

    public function registroExiste(string $modulo, int $registroId): bool
    {
        $modelClass = self::modulos()[$modulo]['model'] ?? null;

        return $modelClass !== null && $modelClass::query()->whereKey($registroId)->exists();
    }

    /**
     * Confere a assinatura do arquivo: um PDF de verdade começa com "%PDF-".
     */
    public function pareceSerPdf(UploadedFile $arquivo): bool
    {
        $handle = @fopen($arquivo->getRealPath(), 'rb');
        if (! $handle) {
            return false;
        }
        $inicio = (string) fread($handle, 1024);
        fclose($handle);

        return str_contains($inicio, '%PDF-');
    }

    /**
     * @param  array{modulo: string, registro_id?: ?int, titulo?: ?string, processo_sei?: ?string, descricao?: ?string}  $dados
     */
    public function salvar(UploadedFile $arquivo, array $dados): Documento
    {
        if (! $this->pareceSerPdf($arquivo)) {
            throw new InvalidArgumentException('O arquivo enviado não é um PDF válido.');
        }

        $modulo = $dados['modulo'];
        $caminho = 'documentos/'.$modulo.'/'.now()->format('Y/m').'/'.Str::uuid().'.pdf';
        Storage::disk(self::DISK)->put($caminho, file_get_contents($arquivo->getRealPath()));

        $nomeOriginal = mb_substr($arquivo->getClientOriginalName() ?: 'documento.pdf', 0, 255);
        $titulo = trim((string) ($dados['titulo'] ?? ''));

        return Documento::query()->create([
            'modulo' => $modulo,
            'registro_id' => $dados['registro_id'] ?? null,
            'titulo' => $titulo !== '' ? mb_substr($titulo, 0, 255) : pathinfo($nomeOriginal, PATHINFO_FILENAME),
            'processo_sei' => $dados['processo_sei'] ?? null,
            'descricao' => $dados['descricao'] ?? null,
            'arquivo_nome' => $nomeOriginal,
            'arquivo_path' => $caminho,
            'mime' => 'application/pdf',
            'tamanho' => (int) $arquivo->getSize(),
            'hash' => (string) hash_file('sha256', $arquivo->getRealPath()),
            'usuario_id' => Auth::id(),
        ]);
    }

    public function apagarArquivo(Documento $documento): void
    {
        if ($documento->arquivo_path && Storage::disk(self::DISK)->exists($documento->arquivo_path)) {
            Storage::disk(self::DISK)->delete($documento->arquivo_path);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function serializar(Documento $documento): array
    {
        $config = self::modulos()[$documento->modulo] ?? null;
        $registroTitulo = null;
        if ($config && $documento->registro_id) {
            $registro = $config['model']::query()->find($documento->registro_id);
            $registroTitulo = $registro?->getAttribute($config['titulo']);
        }

        return [
            'id' => $documento->id,
            'modulo' => $documento->modulo,
            'modulo_label' => self::labelModulo($documento->modulo),
            'registro_id' => $documento->registro_id,
            'registro_titulo' => $registroTitulo,
            'titulo' => $documento->titulo,
            'processo_sei' => $documento->processo_sei,
            'descricao' => $documento->descricao,
            'arquivo_nome' => $documento->arquivo_nome,
            'tamanho' => $documento->tamanho,
            'usuario' => $documento->usuario?->nome,
            'created_at' => $documento->created_at?->toISOString(),
        ];
    }
}
