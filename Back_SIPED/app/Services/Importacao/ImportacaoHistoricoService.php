<?php

namespace App\Services\Importacao;

use App\Models\Documento;
use App\Models\ImportacaoHistorico;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Registra cada importação (planilha ou documento PDF) com arquivo, usuário,
 * data/hora, ciclo, módulo e contagens — inclusive as tentativas bloqueadas.
 */
class ImportacaoHistoricoService
{
    private const DISK = 'local';

    private const LIMITE_ERROS = 500;

    /**
     * @param  array<string, mixed>  $def
     * @param  array<string, mixed>|null  $resultado  retorno de ImportacaoService::commit()
     * @param  list<array<string, mixed>>  $erros
     */
    public function registrarPlanilha(
        string $modulo,
        array $def,
        UploadedFile $arquivo,
        ?array $resultado,
        string $situacao,
        ?string $mensagem = null,
        array $erros = [],
        ?int $cicloId = null,
    ): ImportacaoHistorico {
        $resumo = $resultado['resumo_acoes'] ?? [];
        $erros = $resultado['erros'] ?? $erros;

        return ImportacaoHistorico::query()->create([
            'tipo' => ImportacaoHistorico::TIPO_PLANILHA,
            'modulo' => $modulo,
            'modulo_label' => $def['label'] ?? $modulo,
            'situacao' => $situacao,
            ...$this->guardarArquivo($arquivo, 'importacoes'),
            'usuario_id' => Auth::id(),
            'ciclo_id' => $resultado['ciclo']['id'] ?? $cicloId,
            'novos' => (int) ($resumo['novo'] ?? 0),
            'atualizados' => (int) ($resumo['atualizar'] ?? 0),
            'sem_alteracao' => (int) ($resumo['sem_alteracao'] ?? 0),
            'incompletos' => (int) ($resumo['incompleto'] ?? 0),
            'ignorados' => (int) ($resultado['ignoradas'] ?? 0) + (int) ($resumo['falha'] ?? 0)
                + collect($resultado['linhas'] ?? [])->where('status_importacao', 'erro')->count(),
            'erros' => count($erros),
            'mensagem' => $mensagem,
            'detalhes' => [
                'aba' => $resultado['aba'] ?? null,
                'campos_esperados' => $resultado['campos_esperados'] ?? [],
                'incompletos' => $resultado['incompletos_registros'] ?? [],
                'erros' => array_slice(array_map(fn (array $erro) => [
                    'linha' => $erro['linha'] ?? null,
                    'coluna' => $erro['coluna'] ?? null,
                    'valor' => $erro['valor'] ?? null,
                    'mensagem' => $erro['mensagem'] ?? '',
                    'bloqueante' => (bool) ($erro['bloqueante'] ?? false),
                ], $erros), 0, self::LIMITE_ERROS),
                'erros_total' => count($erros),
                'backup' => $resultado['backup'] ?? null,
            ],
        ]);
    }

    public function registrarDocumento(Documento $documento, UploadedFile $arquivo, string $moduloLabel): ImportacaoHistorico
    {
        return ImportacaoHistorico::query()->create([
            'tipo' => ImportacaoHistorico::TIPO_DOCUMENTO,
            'modulo' => $documento->modulo,
            'modulo_label' => $moduloLabel,
            'situacao' => ImportacaoHistorico::SITUACAO_CONCLUIDA,
            'arquivo_nome' => $documento->arquivo_nome,
            'arquivo_path' => $documento->arquivo_path,
            'arquivo_hash' => $documento->hash,
            'arquivo_tamanho' => $documento->tamanho,
            'usuario_id' => Auth::id(),
            'mensagem' => $documento->registro_id
                ? 'Documento PDF vinculado ao registro #'.$documento->registro_id.'.'
                : 'Documento PDF guardado sem vínculo; vincule ao registro quando ele for cadastrado.',
            'detalhes' => [
                'documento_id' => $documento->id,
                'registro_id' => $documento->registro_id,
                'titulo' => $documento->titulo,
            ],
        ]);
    }

    /**
     * Guarda uma cópia do arquivo enviado (disco privado) para auditoria.
     *
     * @return array{arquivo_nome: string, arquivo_path: ?string, arquivo_hash: ?string, arquivo_tamanho: ?int}
     */
    public function guardarArquivo(UploadedFile $arquivo, string $pasta): array
    {
        $nome = mb_substr($arquivo->getClientOriginalName() ?: 'arquivo', 0, 255);
        $caminho = null;
        $hash = null;

        try {
            $hash = hash_file('sha256', $arquivo->getRealPath()) ?: null;
            $extensao = strtolower($arquivo->getClientOriginalExtension() ?: $arquivo->extension() ?: 'bin');
            $caminho = $pasta.'/'.now()->format('Y/m').'/'.Str::uuid().'.'.$extensao;
            Storage::disk(self::DISK)->put($caminho, file_get_contents($arquivo->getRealPath()));
        } catch (Throwable) {
            $caminho = null;
        }

        return [
            'arquivo_nome' => $nome,
            'arquivo_path' => $caminho,
            'arquivo_hash' => $hash,
            'arquivo_tamanho' => $arquivo->getSize() ?: null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializar(ImportacaoHistorico $historico, bool $comDetalhes = false): array
    {
        $dados = [
            'id' => $historico->id,
            'tipo' => $historico->tipo,
            'modulo' => $historico->modulo,
            'modulo_label' => $historico->modulo_label,
            'situacao' => $historico->situacao,
            'arquivo_nome' => $historico->arquivo_nome,
            'arquivo_disponivel' => (bool) $historico->arquivo_path,
            'usuario' => $historico->usuario?->nome,
            'ciclo_id' => $historico->ciclo_id,
            'ciclo' => $historico->ciclo?->nome,
            'novos' => $historico->novos,
            'atualizados' => $historico->atualizados,
            'sem_alteracao' => $historico->sem_alteracao,
            'incompletos' => $historico->incompletos,
            'ignorados' => $historico->ignorados,
            'erros' => $historico->erros,
            'mensagem' => $historico->mensagem,
            'created_at' => $historico->created_at?->toISOString(),
        ];

        if ($comDetalhes) {
            $dados['detalhes'] = $historico->detalhes ?? [];
        }

        return $dados;
    }
}
