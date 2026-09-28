<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EventoApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_create_show_update_and_delete_evento(): void
    {
        $usuario = Usuario::create([
            'nome' => 'Teste Editor',
            'email' => 'editor-eventos@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678911',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'DF',
            'area' => 'Portfólio',
            'telefone' => '11999999999',
        ]);

        $this->actingAs($usuario, 'sanctum');

        $listResponse = $this->getJson('/api/eventos');
        $listResponse->assertOk();
        $listResponse->assertJsonStructure([
            'data',
            'meta' => [
                'total',
                'total_geral',
                'status',
                'anos',
                'eixos',
                'unidades',
                'possui_acao_extensiva',
                'acoes_vinculaveis',
            ],
        ]);

        $payload = [
            'nome' => 'Evento de Teste da API',
            'ano' => '2026',
            'data' => '2026-07-20',
            'unidade' => 'Sobradinho',
            'eixo' => 'Gastronomia',
            'quantidade_pessoas' => 50,
            'equipe' => 'Equipe Teste',
            'possui_acao_extensiva' => 'Não',
            'acao_vinculada' => 'Deve ser limpa',
            'status' => 'Planejado',
            'observacao' => 'Registro de teste.',
        ];

        $createResponse = $this->postJson('/api/eventos', $payload);
        $createResponse->assertCreated();
        $createResponse->assertJsonPath('evento.nome', 'Evento de Teste da API');
        $createResponse->assertJsonPath('evento.possui_acao_extensiva', 'Não');
        $createResponse->assertJsonPath('evento.acao_vinculada', null);

        $id = $createResponse->json('evento.id');
        $this->assertNotNull($id);

        $showResponse = $this->getJson("/api/eventos/{$id}");
        $showResponse->assertOk();
        $showResponse->assertJsonPath('evento.id', $id);

        $updatePayload = [
            ...$payload,
            'nome' => 'Evento Atualizado',
            'status' => 'Realizado',
            'possui_acao_extensiva' => 'Sim',
            'acao_vinculada' => 'Oficina vinculada de teste',
        ];

        $updateResponse = $this->putJson("/api/eventos/{$id}", $updatePayload);
        $updateResponse->assertOk();
        $updateResponse->assertJsonPath('evento.nome', 'Evento Atualizado');
        $updateResponse->assertJsonPath('evento.status', 'Realizado');
        $updateResponse->assertJsonPath('evento.acao_vinculada', 'Oficina vinculada de teste');

        $deleteResponse = $this->deleteJson("/api/eventos/{$id}");
        $deleteResponse->assertOk();
        $this->assertDatabaseMissing('eventos', ['id' => $id]);
    }

    public function test_filters_eventos_by_status_and_acao_extensiva(): void
    {
        $usuario = Usuario::create([
            'nome' => 'Teste Consultor',
            'email' => 'consulta-eventos@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678912',
            'perfil' => Usuario::PERFIL_CONSULTOR,
            'status' => true,
            'unidade' => 'DF',
            'area' => 'Portfólio',
            'telefone' => '11988888888',
        ]);

        Evento::create([
            'nome' => 'Evento com ação',
            'ano' => '2025',
            'data' => '2025-08-12',
            'unidade' => 'Sobradinho',
            'eixo' => 'Gastronomia',
            'quantidade_pessoas' => 10,
            'equipe' => 'Equipe A',
            'possui_acao_extensiva' => 'Sim',
            'acao_vinculada' => 'Ação X',
            'status' => 'Realizado',
            'observacao' => null,
        ]);

        Evento::create([
            'nome' => 'Evento sem ação',
            'ano' => '2025',
            'data' => '2025-09-25',
            'unidade' => 'Taguatinga',
            'eixo' => 'Ambiente e Saúde',
            'quantidade_pessoas' => 20,
            'equipe' => 'Equipe B',
            'possui_acao_extensiva' => 'Não',
            'acao_vinculada' => null,
            'status' => 'Planejado',
            'observacao' => null,
        ]);

        $this->actingAs($usuario, 'sanctum');

        $filtered = $this->getJson('/api/eventos?status=Realizado&possui_acao_extensiva=Sim');
        $filtered->assertOk();
        $filtered->assertJsonPath('meta.total', 1);
        $filtered->assertJsonPath('data.0.nome', 'Evento com ação');
    }
}
