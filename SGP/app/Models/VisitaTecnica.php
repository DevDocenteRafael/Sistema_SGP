<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitaTecnica extends Model
{
    protected $table = 'visita_tecnicas';

    protected $fillable = [
        'unidade',
        'eixo',
        'processo_sei',
        'data_solicitacao',
        'data_visita_prevista',
        'prazo_limite',
        'status',
        'responsavel',
        'relatorio',
        'observacao',
    ];

    protected function casts(): array
    {
        return [
            'data_solicitacao' => 'date:Y-m-d',
            'data_visita_prevista' => 'date:Y-m-d',
            'prazo_limite' => 'date:Y-m-d',
        ];
    }
}
