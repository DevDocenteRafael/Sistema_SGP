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

    public function planoDeMetas(): HasMany
    {
        return $this->hasMany(PlanoDeMeta::class, 'ciclo_id');
    }

    public function pcas(): HasMany
    {
        return $this->hasMany(Pca::class, 'ciclo_id');
    }

    public function cursosPorEixo(): HasMany
    {
        return $this->hasMany(CursoPorEixo::class, 'ciclo_id');
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

    /**
     * Anos inferidos do nome (ex.: 2025-2026 → 2025 e 2026).
     *
     * @return list<string>
     */
    public function anos(): array
    {
        if (! preg_match_all('/(20\d{2})/', (string) $this->nome, $matches)) {
            return [];
        }

        $anos = array_values(array_unique($matches[1]));
        sort($anos);

        return $anos;
    }

    /**
     * Filtra Metas/PCA/Eixos pelo ciclo aberto.
     * Sem ciclo_id, usa o ciclo atual. `todos` lista todos os ciclos.
     */
    public static function aplicarFiltroNaConsulta($query, mixed $cicloId): void
    {
        if ($cicloId === 'todos') {
            return;
        }

        if ($cicloId !== null && $cicloId !== '') {
            $query->where('ciclo_id', $cicloId);

            return;
        }

        $atualId = static::atual()?->id;
        if ($atualId) {
            $query->where('ciclo_id', $atualId);
        }
    }
}
