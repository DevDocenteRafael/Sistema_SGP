<?php

namespace App\Models;

use App\Models\Concerns\AuditaCadastro;
use App\Models\Concerns\PertenceAoCiclo;
use Illuminate\Database\Eloquent\Model;

class Evento extends Model
{
    use AuditaCadastro;
    use PertenceAoCiclo;

    protected $table = 'eventos';

    protected $fillable = [
        'ciclo_id',
        'nome',
        'ano',
        'data',
        'unidade',
        'eixo',
        'quantidade_pessoas',
        'equipe',
        'possui_acao_extensiva',
        'acao_vinculada',
        'status',
        'observacao',
        'criado_por',
        'atualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'date:Y-m-d',
            'quantidade_pessoas' => 'integer',
        ];
    }
}
