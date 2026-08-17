<?php

namespace App\Models;

use App\Models\Concerns\AuditaCadastro;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TermoReferencia extends Model
{
    use AuditaCadastro;

    protected $table = 'termos_referencia';

    protected $fillable = [
        'nome',
        'eixo',
        'processo_sei',
        'prazo_deadline',
        'status',
        'observacao',
        'data_inicio',
        'data_fim',
        'concluido_em',
        'criado_por',
        'atualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'data_inicio' => 'date',
            'data_fim' => 'date',
            'prazo_deadline' => 'date',
            'concluido_em' => 'datetime',
        ];
    }

    public function historicos(): HasMany
    {
        return $this->hasMany(TermoReferenciaHistorico::class)->orderByDesc('id');
    }
}
