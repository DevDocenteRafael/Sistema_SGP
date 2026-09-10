<?php

namespace App\Models;

use App\Models\Concerns\AuditaCadastro;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ciclo extends Model
{
    use AuditaCadastro;

    protected $table = 'portfolio_ciclos';

    public string $moduloAuditoria = 'ciclos';

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

    public function visitasTecnicas(): HasMany
    {
        return $this->hasMany(VisitaTecnica::class, 'ciclo_id');
    }

    public function horasPedagogicas(): HasMany
    {
        return $this->hasMany(HoraPedagogica::class, 'ciclo_id');
    }

    public function acoesExtensivas(): HasMany
    {
        return $this->hasMany(AcaoExtensiva::class, 'ciclo_id');
    }

    public function jornadasPedagogicas(): HasMany
    {
        return $this->hasMany(JornadaPedagogica::class, 'ciclo_id');
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(Evento::class, 'ciclo_id');
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
     * Filtra entidades periódicas pelo ciclo aberto.
     * Sem ciclo_id, usa o contexto resolvido (header ou ciclo atual). `todos` lista todos os ciclos.
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

        $resolvido = app(\App\Services\CicloContextoService::class)->id();
        if ($resolvido) {
            $query->where('ciclo_id', $resolvido);
        }
    }

    /**
     * @return array<string, int>
     */
    public function composicao(): array
    {
        $this->loadCount([
            'cursos', 'planoDeMetas', 'pcas', 'cursosPorEixo',
            'visitasTecnicas', 'horasPedagogicas', 'acoesExtensivas',
            'jornadasPedagogicas', 'eventos',
        ]);

        return [
            'cursos' => (int) $this->cursos_count,
            'plano_de_metas' => (int) $this->plano_de_metas_count,
            'pca' => (int) $this->pcas_count,
            'eixos' => (int) $this->cursos_por_eixo_count,
            'visitas' => (int) $this->visitas_tecnicas_count,
            'horas_pedagogicas' => (int) $this->horas_pedagogicas_count,
            'acoes' => (int) $this->acoes_extensivas_count,
            'jornadas' => (int) $this->jornadas_pedagogicas_count,
            'eventos' => (int) $this->eventos_count,
        ];
    }

    public function temRegistrosVinculados(): bool
    {
        return array_sum($this->composicao()) > 0;
    }

    /**
     * @return array{id: int, nome: string, atual: bool, anos: list<string>}
     */
    public function paraMeta(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'atual' => (bool) $this->atual,
            'anos' => $this->anos(),
        ];
    }
}
