<?php

namespace App\Models;

use App\Models\Concerns\AuditaCadastro;
use Illuminate\Database\Eloquent\Model;

class JornadaPedagogica extends Model
{
    use AuditaCadastro;

    public string $moduloAuditoria = 'jornadas-pedagogicas';

    protected $table = 'jornadas_pedagogicas';

    protected $fillable = [
        'titulo',
        'data_inicio',
        'data_fim',
        'tem_pre_jornada',
        'data_pre_jornada',
        'local',
        'espaco',
        'verba',
        'custos',
        'programacao',
        'setores',
        'observacoes',
        'status',
        'anexo_path',
        'criado_por',
        'atualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'data_inicio' => 'date',
            'data_fim' => 'date',
            'data_pre_jornada' => 'date',
        ];
    }
}
