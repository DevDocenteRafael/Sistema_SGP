<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const PERFIL_ADMINISTRADOR = 'Administrador';

    public const PERFIL_EDITOR = 'Editor';

    public const PERFIL_CONSULTOR = 'Consultor';

    protected $table = 'usuarios';

    protected $fillable = [
        'nome',
        'email',
        'senha',
        'cpf',
        'perfil',
        'status',
        'unidade',
        'area',
        'telefone',
    ];

    protected $hidden = [
        'senha',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->senha;
    }

    public function isAdministrador(): bool
    {
        return $this->perfil === self::PERFIL_ADMINISTRADOR;
    }

    public function isEditor(): bool
    {
        return $this->perfil === self::PERFIL_EDITOR;
    }

    public function isConsultor(): bool
    {
        return $this->perfil === self::PERFIL_CONSULTOR;
    }

    public function pode(string $acao): bool
    {
        $perfis = config("permissoes.acoes.{$acao}", []);

        return in_array($this->perfil, $perfis, true);
    }

    public function podeGerenciarUsuarios(): bool
    {
        return $this->pode('gerenciar_usuarios');
    }

    public function podeEditarDados(): bool
    {
        return $this->pode('editar_dados');
    }

    public function podeConsultarDados(): bool
    {
        return $this->pode('consultar_dados');
    }
}
