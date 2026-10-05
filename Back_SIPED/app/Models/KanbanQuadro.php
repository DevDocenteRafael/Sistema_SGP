<?php

namespace App\Models;

use App\Models\Concerns\ExclusaoLogica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class KanbanQuadro extends Model
{
    use ExclusaoLogica;

    protected $table = 'kanban_quadros';

    protected $fillable = [
        'nome',
        'slug',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function colunas(): HasMany
    {
        return $this->hasMany(KanbanColuna::class, 'kanban_quadro_id')
            ->orderBy('position');
    }

    public function cartoes(): HasManyThrough
    {
        return $this->hasManyThrough(
            KanbanCartao::class,
            KanbanColuna::class,
            'kanban_quadro_id',
            'kanban_coluna_id'
        );
    }
}
