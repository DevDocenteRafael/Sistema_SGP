<?php

namespace App\Models;

use App\Models\Concerns\AuditaCadastro;
use App\Models\Concerns\PertenceAoCiclo;
use Illuminate\Database\Eloquent\Model;

class HoraPedagogica extends Model
{
    use AuditaCadastro;
    use PertenceAoCiclo;

    protected $table = 'hora_pedagogicas';

    protected $fillable = [
        'ciclo_id',
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
        'criado_por',
        'atualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'ano' => 'integer',
            'ativo' => 'boolean',
        ];
    }
}
