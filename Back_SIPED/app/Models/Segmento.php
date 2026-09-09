<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Segmento extends Model
{
    protected $fillable = [
        'eixo_id',
        'nome',
        'slug',
        'ordem',
    ];

    public function eixo(): BelongsTo
    {
        return $this->belongsTo(Eixo::class);
    }

    public function cursos(): HasMany
    {
        return $this->hasMany(Curso::class);
    }

    public function ofertas(): HasMany
    {
        return $this->hasMany(CursoPorEixo::class);
    }
}
