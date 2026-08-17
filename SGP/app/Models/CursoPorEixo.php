<?php

namespace App\Models;

use App\Models\Concerns\AuditaCadastro;
use App\Models\Concerns\PertenceAoCicloPortfolio;
use Illuminate\Database\Eloquent\Model;

class CursoPorEixo extends Model
{
    use AuditaCadastro;
    use PertenceAoCicloPortfolio;

    protected $table = 'curso_por_eixos';

    protected $fillable = [
        'ciclo_id',
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
        'criado_por',
        'atualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'is_novo' => 'boolean',
        ];
    }
}
