<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Documento PDF (ex.: oriundo do SEI) guardado como anexo controlado.
 * Não é interpretado: nenhum registro é criado a partir do conteúdo do PDF.
 */
class Documento extends Model
{
    protected $table = 'documentos';

    protected $fillable = [
        'modulo',
        'registro_id',
        'titulo',
        'processo_sei',
        'descricao',
        'arquivo_nome',
        'arquivo_path',
        'mime',
        'tamanho',
        'hash',
        'usuario_id',
        'importacao_historico_id',
    ];

    protected $hidden = [
        'arquivo_path',
    ];

    protected function casts(): array
    {
        return [
            'registro_id' => 'integer',
            'tamanho' => 'integer',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
