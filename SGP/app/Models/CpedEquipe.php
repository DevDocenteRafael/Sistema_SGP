<?php

namespace App\Models;

use App\Models\Concerns\AuditaCadastro;
use Illuminate\Database\Eloquent\Model;

class CpedEquipe extends Model
{
    use AuditaCadastro;

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
        'criado_por',
        'atualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }
}
