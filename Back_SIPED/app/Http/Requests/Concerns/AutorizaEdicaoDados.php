<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Auth\Access\AuthorizationException;

trait AutorizaEdicaoDados
{
    public function authorize(): bool
    {
        return $this->user()?->podeEditarDados() === true;
    }

    protected function failedAuthorization(): void
    {
        throw new AuthorizationException('Seu perfil não tem permissão para alterar estes registros.');
    }
}
