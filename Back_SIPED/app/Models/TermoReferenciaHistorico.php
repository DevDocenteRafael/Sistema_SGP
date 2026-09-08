<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TermoReferenciaHistorico extends Model
{
    protected $table = 'termos_referencia_historicos';

    protected $fillable = [
        'termo_referencia_id',
        'acao',
        'tipo',
        'situacao_anterior',
        'situacao_nova',
        'observacao',
        'usuario_id',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function termoReferencia(): BelongsTo
    {
        return $this->belongsTo(TermoReferencia::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
