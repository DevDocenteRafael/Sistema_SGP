<?php

namespace App\Models;

use App\Models\Concerns\AuditaCadastro;
use App\Models\Concerns\ExclusaoLogica;
use App\Models\Concerns\IdentificaOrigem;
use App\Models\Concerns\PertenceAoCiclo;
use Illuminate\Database\Eloquent\Model;

class VisitaTecnica extends Model
{
    use AuditaCadastro;
    use ExclusaoLogica;
    use IdentificaOrigem;
    use PertenceAoCiclo;

    protected $table = 'visita_tecnicas';

    protected $fillable = [
        'ciclo_id',
        'unidade',
        'eixo',
        'processo_sei',
        'data_solicitacao',
        'data_visita_prevista',
        'prazo_limite',
        'status',
        'etapa_svt',
        'turma',
        'instrutor',
        'curso',
        'tipo_curso',
        'local_visita',
        'visitas_utilizadas',
        'visitas_limite',
        'justificativa_excedente',
        'motivo_recusa',
        'decisoes',
        'transporte',
        'relatorio_arquivo_nome',
        'relatorio_url',
        'responsavel',
        'relatorio',
        'observacao',
        'criado_por',
        'atualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'data_solicitacao' => 'date:Y-m-d',
            'data_visita_prevista' => 'date:Y-m-d',
            'prazo_limite' => 'date:Y-m-d',
            'visitas_utilizadas' => 'integer',
            'visitas_limite' => 'integer',
            'decisoes' => 'array',
            'transporte' => 'array',
        ];
    }
}
