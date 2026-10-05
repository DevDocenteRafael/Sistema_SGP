<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_retorna_contagens_reais_de_usuarios(): void
    {
        $administrador = Usuario::create([
            'nome' => 'Administrador Dashboard',
            'email' => 'admin-dashboard@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678901',
            'perfil' => Usuario::PERFIL_ADMINISTRADOR,
            'status' => true,
            'unidade' => 'DF',
            'area' => 'CPED',
            'telefone' => '61999999999',
        ]);

        Usuario::create([
            'nome' => 'Consultor Inativo',
            'email' => 'inativo-dashboard@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678902',
            'perfil' => Usuario::PERFIL_CONSULTOR,
            'status' => false,
            'unidade' => 'DF',
            'area' => 'CPED',
            'telefone' => '61999999998',
        ]);

        $this->actingAs($administrador, 'sanctum')
            ->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('data.contagens.usuarios', 2)
            ->assertJsonPath('data.contagens.usuarios_ativos', 1)
            ->assertJsonPath('data.contagens.usuarios_inativos', 1);
    }
}