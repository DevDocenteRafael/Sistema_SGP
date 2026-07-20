<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanoDeMeta extends Model
{
    protected $table = 'plano_de_metas';

    protected $fillable = [
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
    ];

    protected function casts(): array
    {
        return [
            'ano' => 'integer',
        ];
    }
}
