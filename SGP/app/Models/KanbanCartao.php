<?php

namespace App\Models;

use App\Models\Concerns\AuditaCadastro;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KanbanCartao extends Model
{
    use AuditaCadastro;

    protected $table = 'kanban_cartoes';

    protected $fillable = [
        'kanban_coluna_id',
        'titulo',
        'descricao',
        'position',
        'criado_por',
        'atualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    public function coluna(): BelongsTo
    {
        return $this->belongsTo(KanbanColuna::class, 'kanban_coluna_id');
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'criado_por');
    }
}
