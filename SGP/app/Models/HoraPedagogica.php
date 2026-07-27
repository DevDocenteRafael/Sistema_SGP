<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HoraPedagogica extends Model
{
    protected $table = 'hora_pedagogicas';

    protected $fillable = [
        'matricula',
        'pessoa',
        'segmento',
        'eixo',
        'processo_sei',
        'ano',
        'motivo',
        'status',
        'ativo',
        'observacao',
    ];

    protected function casts(): array
    {
        return [
            'ano' => 'integer',
            'ativo' => 'boolean',
        ];
    }
}
