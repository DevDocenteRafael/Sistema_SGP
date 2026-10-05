<?php

namespace App\Models\Concerns;

use App\Models\Usuario;
use App\Services\CadastroAuditoriaService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use LogicException;

/**
 * Exclusão lógica (lixeira) para registros de negócio.
 *
 * - delete() só marca deleted_at e guarda quem excluiu (excluido_por);
 * - restore() devolve o registro e entra na auditoria;
 * - exclusão definitiva fica bloqueada, salvo procedimento técnico
 *   (config('exclusao.permitir_definitiva') = true).
 */
trait ExclusaoLogica
{
    use SoftDeletes;

    public static function bootExclusaoLogica(): void
    {
        static::softDeleted(function ($model) {
            $model->newQueryWithoutScopes()
                ->whereKey($model->getKey())
                ->update(['excluido_por' => Auth::id()]);
            $model->excluido_por = Auth::id();
        });

        static::restored(function ($model) {
            $model->newQueryWithoutScopes()
                ->whereKey($model->getKey())
                ->update(['excluido_por' => null]);
            $model->excluido_por = null;

            if (Auth::check()) {
                app(CadastroAuditoriaService::class)->registrarModelo(
                    CadastroAuditoriaService::ACAO_RESTAURAR,
                    $model,
                );
            }
        });

        static::forceDeleting(function () {
            if (! config('exclusao.permitir_definitiva', false)) {
                throw new LogicException('Exclusão definitiva bloqueada. Use a exclusão lógica (lixeira).');
            }
        });
    }

    public function excluidoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'excluido_por');
    }
}
