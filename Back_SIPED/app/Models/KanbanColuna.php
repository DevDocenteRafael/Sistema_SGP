<?php

namespace App\Models;

use App\Models\Concerns\ExclusaoLogica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KanbanColuna extends Model
{
    use ExclusaoLogica;

    protected $table = 'kanban_colunas';

    protected $fillable = [
        'kanban_quadro_id',
        'titulo',
        'position',
        'cor',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    public function quadro(): BelongsTo
    {
        return $this->belongsTo(KanbanQuadro::class, 'kanban_quadro_id');
    }

    public function cartoes(): HasMany
    {
        return $this->hasMany(KanbanCartao::class, 'kanban_coluna_id')
            ->orderBy('position');
    }
}
