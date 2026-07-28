<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CpedEquipe extends Model
{
    protected $table = 'cped_equipes';

    protected $fillable = [
        'nome',
        'cargo',
        'setor',
        'contato',
        'tipo',
        'eixo_vinculado',
        'iniciais',
        'foto',
        'cor',
        'ativo',
        'observacao',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }
}
