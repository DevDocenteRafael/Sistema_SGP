<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Database\Seeders\CpedEquipeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OrganogramaApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_load_organograma_tree_from_cped_equipe(): void
    {
        $this->seed(CpedEquipeSeeder::class);

        $usuario = Usuario::create([
            'nome' => 'Teste Editor',
            'email' => 'editor-organograma@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678955',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'DF',
            'area' => 'Portfólio',
            'telefone' => '11999999999',
        ]);

        $this->actingAs($usuario, 'sanctum');

        $response = $this->getJson('/api/organograma');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'ordenador' => ['id', 'nome', 'cargo', 'tipo'],
                'assistentes',
                'ramos' => [
                    '*' => [
                        'eixo',
                        'cor',
                        'responsavel',
                        'equipe',
                        'total',
                    ],
                ],
                'administrativos',
            ],
            'meta' => [
                'total',
                'total_eixos',
                'total_instrutores',
                'total_administrativos',
                'pode_editar',
                'gerenciar_em',
            ],
        ]);

        $response->assertJsonPath('data.ordenador.tipo', 'ordenador');
        $response->assertJsonPath('meta.pode_editar', true);
        $response->assertJsonPath('meta.gerenciar_em', '/app/cped');
        $this->assertGreaterThanOrEqual(1, count($response->json('data.ramos')));
        $this->assertGreaterThanOrEqual(1, count($response->json('data.assistentes')));
    }

    public function test_unauthenticated_cannot_access_organograma(): void
    {
        $this->getJson('/api/organograma')->assertUnauthorized();
    }
}
