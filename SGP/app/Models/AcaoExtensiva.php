<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcaoExtensiva extends Model
{
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
    ];

    protected function casts(): array
    {
        return [
            'ultima_atualizacao' => 'date:Y-m-d',
        ];
    }
}
