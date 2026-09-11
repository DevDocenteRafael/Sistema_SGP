<?php

namespace App\Models;

use App\Models\Concerns\AuditaCadastro;
use App\Models\Concerns\PertenceAoCiclo;
use App\Models\Concerns\SyncsEixoSegmento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Acompanhamento do ciclo: como o curso foi executado (unidade, turmas, alunos).
 * Tabela física permanece `curso_por_eixos`.
 */
class CursoExecucao extends Model
{
    use AuditaCadastro;
    use PertenceAoCiclo;
    use SyncsEixoSegmento;

    protected $table = 'curso_por_eixos';

    public string $moduloAuditoria = 'eixos';

    protected $fillable = [
        'ciclo_id',
        'curso_id',
        'curso',
        'eixo',
        'eixo_id',
        'segmento',
        'segmento_id',
        'programa',
        'unidade',
        'ano',
        'ch',
        'turmas',
        'codigo',
        'alunos',
        'instrutores',
        'status',
        'observacao',
        'is_novo',
        'criado_por',
        'atualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'is_novo' => 'boolean',
        ];
    }

    public function cursoRef(): BelongsTo
    {
        return $this->belongsTo(Curso::class, 'curso_id');
    }

    public function eixoRef(): BelongsTo
    {
        return $this->belongsTo(Eixo::class, 'eixo_id');
    }

    public function segmentoRef(): BelongsTo
    {
        return $this->belongsTo(Segmento::class, 'segmento_id');
    }
}
