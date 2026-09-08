<?php

namespace App\Models;

use App\Models\Concerns\AuditaCadastro;
use App\Services\CpedEquipeFotoService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class CpedEquipe extends Model
{
    use AuditaCadastro;

    protected $table = 'cped_equipes';

    protected $fillable = [
        'nome',
        'cargo',
        'setor',
        'contato',
        'tipo',
        'eixo_vinculado',
        'iniciais',
        'foto',
        'cor',
        'ativo',
        'observacao',
        'criado_por',
        'atualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    /**
     * Na API/JSON, `foto` é a URL pública.
     * O caminho relativo no disco fica em getRawOriginal('foto').
     */
    protected function foto(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => app(CpedEquipeFotoService::class)->urlPublica($value),
        );
    }

    public function caminhoFoto(): ?string
    {
        $caminho = $this->getRawOriginal('foto');

        return is_string($caminho) && $caminho !== '' ? $caminho : null;
    }
}
