<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fluxograma extends Model
{
    public const TIPO_LINEAR = 'linear';

    public const TIPO_FUNCIONAL = 'funcional';

    protected $table = 'fluxogramas';

    protected $fillable = [
        'titulo',
        'slug',
        'descricao',
        'tipo',
        'diagrama',
        'ativo',
        'criado_por',
    ];

    protected function casts(): array
    {
        return [
            'diagrama' => 'array',
            'ativo' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'criado_por');
    }
}
