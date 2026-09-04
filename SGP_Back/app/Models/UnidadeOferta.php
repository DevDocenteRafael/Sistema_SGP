<?php

namespace App\Models;

use App\Models\Concerns\AuditaCadastro;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class UnidadeOferta extends Model
{
    use AuditaCadastro;

    public const TIPO_CEP = 'cep';

    public const TIPO_POLO = 'polo';

    public const TIPO_FACULDADE = 'faculdade';

    public const TIPOS = [
        self::TIPO_CEP,
        self::TIPO_POLO,
        self::TIPO_FACULDADE,
    ];

    protected $table = 'unidades_oferta';

    protected $fillable = [
        'regiao_administrativa_id',
        'nome',
        'tipo',
        'ativo',
        'criado_por',
        'atualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'regiao_administrativa_id' => 'integer',
        ];
    }

    public function regiaoAdministrativa(): BelongsTo
    {
        return $this->belongsTo(RegiaoAdministrativa::class, 'regiao_administrativa_id');
    }

    /**
     * Nomes de unidades ativas para validação e selects flat.
     * Fallback para config('unidades') se a tabela ainda não existir.
     *
     * @return list<string>
     */
    public static function nomesAtivos(): array
    {
        if (! Schema::hasTable('unidades_oferta')) {
            return array_values(config('unidades', []));
        }

        $nomes = static::query()
            ->where('ativo', true)
            ->orderBy('nome')
            ->pluck('nome')
            ->unique()
            ->values()
            ->all();

        return $nomes !== [] ? $nomes : array_values(config('unidades', []));
    }

    /**
     * @return list<string>
     */
    public static function nomesExistentes(): array
    {
        if (! Schema::hasTable('unidades_oferta')) {
            return array_values(config('unidades', []));
        }

        return static::query()
            ->orderBy('nome')
            ->pluck('nome')
            ->unique()
            ->values()
            ->all();
    }
}
