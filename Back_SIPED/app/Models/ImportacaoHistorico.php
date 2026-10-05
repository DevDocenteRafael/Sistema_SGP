<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportacaoHistorico extends Model
{
    public const TIPO_PLANILHA = 'planilha';

    public const TIPO_DOCUMENTO = 'documento';

    public const SITUACAO_CONCLUIDA = 'concluida';

    public const SITUACAO_BLOQUEADA = 'bloqueada';

    protected $table = 'importacao_historicos';

    protected $fillable = [
        'tipo',
        'modulo',
        'modulo_label',
        'situacao',
        'arquivo_nome',
        'arquivo_path',
        'arquivo_hash',
        'arquivo_tamanho',
        'usuario_id',
        'ciclo_id',
        'novos',
        'atualizados',
        'sem_alteracao',
        'incompletos',
        'ignorados',
        'erros',
        'mensagem',
        'detalhes',
    ];

    protected function casts(): array
    {
        return [
            'detalhes' => 'array',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function ciclo(): BelongsTo
    {
        return $this->belongsTo(PortfolioCiclo::class, 'ciclo_id');
    }
}
