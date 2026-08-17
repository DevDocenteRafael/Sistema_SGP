<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResolucaoHistorico extends Model
{
    protected $table = 'resolucao_historicos';

    protected $fillable = [
        'resolucao_id',
        'evento',
        'status_anterior',
        'status_novo',
        'usuario_id',
        'observacao',
        'justificativa',
    ];

    public function resolucao()
    {
        return $this->belongsTo(Resolucao::class);
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
