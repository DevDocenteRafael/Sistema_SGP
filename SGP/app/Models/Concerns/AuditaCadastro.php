<?php

namespace App\Models\Concerns;

use App\Services\CadastroAuditoriaService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Preenche autoria e registra eventos em `cadastros`.
 */
trait AuditaCadastro
{
    public static function bootAuditaCadastro(): void
    {
        static::creating(function ($model) {
            $userId = Auth::id();
            if ($userId && empty($model->criado_por)) {
                $model->criado_por = $userId;
            }
        });

        static::updating(function ($model) {
            $userId = Auth::id();
            if ($userId) {
                $model->atualizado_por = $userId;
            }
        });

        static::created(function ($model) {
            if (! Auth::check()) {
                return;
            }

            app(CadastroAuditoriaService::class)->registrarModelo(
                CadastroAuditoriaService::ACAO_CRIAR,
                $model,
            );
        });

        static::updated(function ($model) {
            if (! Auth::check()) {
                return;
            }

            app(CadastroAuditoriaService::class)->registrarModelo(
                CadastroAuditoriaService::ACAO_EDITAR,
                $model,
                null,
                [
                    'alterados' => array_keys($model->getChanges()),
                ],
            );
        });

        static::deleted(function ($model) {
            if (! Auth::check()) {
                return;
            }

            app(CadastroAuditoriaService::class)->registrarModelo(
                CadastroAuditoriaService::ACAO_EXCLUIR,
                $model,
            );
        });
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Usuario::class, 'criado_por');
    }

    public function atualizador(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Usuario::class, 'atualizado_por');
    }
}
