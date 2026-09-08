<?php

namespace App\Models;

use App\Models\Concerns\AuditaCadastro;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Curso extends Model
{
    use AuditaCadastro;

    protected $fillable = [
        'ciclo_id',
        'titulo',
        'eixo',
        'modalidade',
        'carga_horaria',
        'turmas',
        'codigo_processo',
        'alunos',
        'instrutor',
        'descricao',
        'codigo_dn',
        'codigo_sig',
        'identificacao',
        'tipo',
        'status',
        'ultima_revisao',
        'processo_sei',
        'data_inicio',
        'data_fim',
        'unidade',
        'unidades_oferta',
        'observacoes',
        'valores',
        'compativel_bolsa',
        'comercial',
        'pcn',
        'pcr',
        'justificativa_duplicidade',
        'criado_por',
        'atualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'unidades_oferta' => 'array',
            'data_inicio' => 'date',
            'data_fim' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Curso $curso) {
            if (empty($curso->ciclo_id)) {
                $curso->ciclo_id = PortfolioCiclo::atual()?->id;
            }
        });
    }

    public function ciclo(): BelongsTo
    {
        return $this->belongsTo(PortfolioCiclo::class, 'ciclo_id');
    }

    public function replicarParaCiclo(PortfolioCiclo $ciclo): self
    {
        $copia = $this->replicate(['criado_por', 'atualizado_por', 'justificativa_duplicidade']);
        $copia->ciclo_id = $ciclo->id;
        $copia->justificativa_duplicidade = null;
        $copia->save();

        return $copia;
    }
}
