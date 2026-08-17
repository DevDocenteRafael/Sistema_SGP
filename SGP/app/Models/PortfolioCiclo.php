<?php

namespace App\Models;

use App\Models\Concerns\AuditaCadastro;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PortfolioCiclo extends Model
{
    use AuditaCadastro;

    public string $moduloAuditoria = 'portfolio-ciclos';

    protected $fillable = [
        'nome',
        'origem_id',
        'atual',
        'observacao',
        'criado_por',
        'atualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'atual' => 'boolean',
        ];
    }

    public function cursos(): HasMany
    {
        return $this->hasMany(Curso::class, 'ciclo_id');
    }

    public function origem(): BelongsTo
    {
        return $this->belongsTo(self::class, 'origem_id');
    }

    public static function atual(): ?self
    {
        return static::query()->where('atual', true)->orderByDesc('id')->first()
            ?? static::query()->orderByDesc('id')->first();
    }

    public function marcarComoAtual(): void
    {
        static::query()->where('id', '!=', $this->id)->update(['atual' => false]);

        if (! $this->atual) {
            $this->update(['atual' => true]);
        }
    }
}
