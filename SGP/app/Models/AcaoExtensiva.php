<?php

namespace App\Models;

use App\Models\Concerns\AuditaCadastro;
use Illuminate\Database\Eloquent\Model;

class AcaoExtensiva extends Model
{
    use AuditaCadastro;

    protected $table = 'acao_extensivas';

    protected $fillable = [
        'priorizacao',
        'atribuido',
        'eixo',
        'numero_processo_sei',
        'tipo',
        'assunto',
        'objetivo',
        'status',
        'ultima_atualizacao',
        'criado_por',
        'atualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'ultima_atualizacao' => 'date:Y-m-d',
        ];
    }
}
