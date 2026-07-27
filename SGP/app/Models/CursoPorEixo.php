<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CursoPorEixo extends Model
{
    protected $table = 'curso_por_eixos';

    protected $fillable = [
        'curso',
        'eixo',
        'unidade',
        'ano',
        'ch',
        'turmas',
        'codigo',
        'alunos',
        'instrutores',
        'status',
        'observacao',
        'is_novo',
    ];

    protected function casts(): array
    {
        return [
            'is_novo' => 'boolean',
        ];
    }
}
