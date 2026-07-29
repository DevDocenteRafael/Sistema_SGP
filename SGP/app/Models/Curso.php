<?php

namespace App\Models;

use App\Models\Concerns\AuditaCadastro;
use Illuminate\Database\Eloquent\Model;

class Curso extends Model
{
    use AuditaCadastro;

    protected $fillable = [
        'titulo',
        'eixo',
        'modalidade',
        'carga_horaria',
        'turmas',
        'codigo_processo',
        'alunos',
        'instrutor',
        'descricao',
        'codigo_dn',
        'codigo_sig',
        'identificacao',
        'tipo',
        'status',
        'ultima_revisao',
        'processo_sei',
        'data_inicio',
        'data_fim',
        'unidade',
        'unidades_oferta',
        'observacoes',
        'valores',
        'compativel_bolsa',
        'comercial',
        'pcn',
        'pcr',
        'criado_por',
        'atualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'unidades_oferta' => 'array',
            'data_inicio' => 'date',
            'data_fim' => 'date',
        ];
    }
}
