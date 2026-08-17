<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Database\Seeders\CpedEquipeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CarometroApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_carometro_from_cped_equipe(): void
    {
        $this->seed(CpedEquipeSeeder::class);

        $usuario = Usuario::create([
            'nome' => 'Teste Editor',
            'email' => 'editor-carometro@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678966',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'DF',
            'area' => 'Portfólio',
            'telefone' => '11999999999',
        ]);

        $this->actingAs($usuario, 'sanctum');

        $response = $this->getJson('/api/carometro');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'nome',
                    'cargo',
                    'tipo',
                    'setor',
                ],
            ],
            'meta' => [
                'total',
                'por_tipo',
                'tipos_filtro',
                'tipos_labels',
                'eixos',
                'pode_editar',
                'gerenciar_em',
            ],
        ]);

        $response->assertJsonPath('meta.pode_editar', true);
        $response->assertJsonPath('meta.gerenciar_em', '/app/cped');
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    public function test_unauthenticated_cannot_access_carometro(): void
    {
        $this->getJson('/api/carometro')->assertUnauthorized();
    }
}
