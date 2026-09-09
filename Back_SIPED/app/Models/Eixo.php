<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Eixo extends Model
{
    protected $fillable = [
        'nome',
        'slug',
        'ordem',
    ];

    public function segmentos(): HasMany
    {
        return $this->hasMany(Segmento::class)->orderBy('nome');
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
