<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pca extends Model
{
    protected $table = 'pcas';

    protected $fillable = [
        'unidade',
        'curso',
        'tipo',
        'periodo',
        'numero_sei',
        'codigo_sig',
        'status',
        'responsavel',
        'objetivo',
        'data_inicio',
        'data_fim',
        'observacao',
        'ano',
    ];

    protected function casts(): array
    {
        return [
            'ano' => 'integer',
            'data_inicio' => 'date',
            'data_fim' => 'date',
        ];
    }
}
