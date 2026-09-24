<?php

namespace App\Models;

use App\Models\Concerns\AuditaCadastro;
use App\Models\Concerns\IdentificaOrigem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegiaoAdministrativa extends Model
{
    use AuditaCadastro;
    use IdentificaOrigem;

    protected $table = 'regioes_administrativas';

    protected $fillable = [
        'nome',
        'ativo',
        'criado_por',
        'atualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    public function unidadesOferta(): HasMany
    {
        return $this->hasMany(UnidadeOferta::class, 'regiao_administrativa_id');
    }
}
