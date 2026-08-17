<?php

namespace App\Models;

use App\Models\Concerns\AuditaCadastro;
use App\Models\Concerns\PertenceAoCicloPortfolio;
use Illuminate\Database\Eloquent\Model;

class PlanoDeMeta extends Model
{
    use AuditaCadastro;
    use PertenceAoCicloPortfolio;

    protected $table = 'plano_de_metas';

    protected $fillable = [
        'ciclo_id',
        'segmento',
        'curso',
        'tipo',
        'numero_sei',
        'codigo_sig',
        'mes_entrega',
        'status',
        'origem',
        'status_final',
        'observacao',
        'ano',
        'criado_por',
        'atualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'ano' => 'integer',
        ];
    }
}
