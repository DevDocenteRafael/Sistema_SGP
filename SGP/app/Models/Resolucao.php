<?php

namespace App\Models;

use App\Models\Concerns\AuditaCadastro;
use Illuminate\Database\Eloquent\Model;

class Resolucao extends Model
{
    use AuditaCadastro;

    protected $table = 'resolucoes';

    protected $fillable = [
        'numero',
        'curso_relacionado',
        'categoria',
        'resumo',
        'relator',
        'setor',
        'data_inicio_vigencia',
        'data_fim_vigencia',
        'status',
        'observacoes',
        'anexo_path',
        'criado_por',
        'atualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'data_inicio_vigencia' => 'date:Y-m-d',
            'data_fim_vigencia' => 'date:Y-m-d',
        ];
    }
}
