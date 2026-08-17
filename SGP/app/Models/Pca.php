<?php

namespace App\Models;

use App\Models\Concerns\AuditaCadastro;
use App\Models\Concerns\PertenceAoCicloPortfolio;
use Illuminate\Database\Eloquent\Model;

class Pca extends Model
{
    use AuditaCadastro;
    use PertenceAoCicloPortfolio;

    protected $table = 'pcas';

    protected $fillable = [
        'ciclo_id',
        'titulo',
        'semestre',
        'numero_sei',
        'codigo_sig',
        'eixo',
        'unidade',
        'carga_horaria',
        'precificacao',
        'valor_primeiro_modulo',
        'valor',
        'parcelas_boleto',
        'valor_parcela_boleto',
        'parcelas_cartao',
        'valor_cartao',
        'parcela_desc_20',
        'parcela_desc_15',
        'status',
        'observacao',
        'ano',
        'criado_por',
        'atualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'ano' => 'integer',
        ];
    }
}
