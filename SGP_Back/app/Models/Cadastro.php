<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cadastro extends Model
{
    protected $table = 'cadastros';

    protected $fillable = [
        'usuario_id',
        'acao',
        'modulo',
        'registro_tipo',
        'registro_id',
        'resumo',
        'dados',
        'ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'dados' => 'array',
            'registro_id' => 'integer',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
