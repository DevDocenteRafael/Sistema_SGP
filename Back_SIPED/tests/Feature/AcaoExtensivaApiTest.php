<?php

namespace Tests\Feature;

use App\Models\AcaoExtensiva;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AcaoExtensivaApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_create_show_update_and_delete_acao_extensiva(): void
    {
        $usuario = Usuario::create([
            'nome' => 'Teste Editor',
            'email' => 'editor-acoes@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678901',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'DF',
            'area' => 'Portfólio',
            'telefone' => '11999999999',
        ]);

        $this->actingAs($usuario, 'sanctum');

        $listResponse = $this->getJson('/api/acoes-extensivas');
        $listResponse->assertOk();
        $listResponse->assertJsonStructure([
            'data',
            'meta' => [
                'total',
                'total_geral',
                'priorizacoes',
                'status',
                'tipos',
                'eixos',
            ],
        ]);

        $payload = [
            'priorizacao' => 'Alta',
            'atribuido' => 'teste.editor',
            'eixo' => 'Gastronomia e Turismo',
            'numero_processo_sei' => '2026.000099999-99',
            'tipo' => 'Ação Extensiva',
            'assunto' => 'Ação extensiva de teste da API',
            'objetivo' => 'Validar CRUD completo da API de ações extensivas.',
            'status' => 'CPED',
            'ultima_atualizacao' => '2026-07-15',
        ];

        $createResponse = $this->postJson('/api/acoes-extensivas', $payload);
        $createResponse->assertCreated();
        $createResponse->assertJsonPath('acaoExtensiva.assunto', 'Ação extensiva de teste da API');
        $createResponse->assertJsonPath('acaoExtensiva.numero_processo_sei', '2026.000099999-99');

        $id = $createResponse->json('acaoExtensiva.id');
        $this->assertNotNull($id);

        $showResponse = $this->getJson("/api/acoes-extensivas/{$id}");
        $showResponse->assertOk();
        $showResponse->assertJsonPath('acaoExtensiva.id', $id);
        $showResponse->assertJsonPath('acaoExtensiva.atribuido', 'teste.editor');

        $updatePayload = [
            ...$payload,
            'priorizacao' => 'Resolvido',
            'status' => 'NC',
            'assunto' => 'Ação extensiva atualizada',
        ];

        $updateResponse = $this->putJson("/api/acoes-extensivas/{$id}", $updatePayload);
        $updateResponse->assertOk();
        $updateResponse->assertJsonPath('acaoExtensiva.assunto', 'Ação extensiva atualizada');
        $updateResponse->assertJsonPath('acaoExtensiva.priorizacao', 'Resolvido');
        $updateResponse->assertJsonPath('acaoExtensiva.status', 'NC');

        $deleteResponse = $this->deleteJson("/api/acoes-extensivas/{$id}");
        $deleteResponse->assertOk();
        $this->assertDatabaseMissing('acao_extensivas', ['id' => $id]);
    }

    public function test_filters_acoes_extensivas_by_status_and_priorizacao(): void
    {
        $usuario = Usuario::create([
            'nome' => 'Teste Consulta',
            'email' => 'consulta-acoes@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678902',
            'perfil' => Usuario::PERFIL_CONSULTOR,
            'status' => true,
            'unidade' => 'DF',
            'area' => 'Portfólio',
            'telefone' => '11988888888',
        ]);

        AcaoExtensiva::create([
            'priorizacao' => 'Alta',
            'atribuido' => 'ana.5041',
            'eixo' => 'Gastronomia e Turismo',
            'numero_processo_sei' => '2026.000000001-01',
            'tipo' => 'Ação Extensiva',
            'assunto' => 'Registro alta CPED',
            'objetivo' => null,
            'status' => 'CPED',
            'ultima_atualizacao' => '2026-07-01',
        ]);

        AcaoExtensiva::create([
            'priorizacao' => 'Baixa',
            'atribuido' => 'barbara.6003',
            'eixo' => 'Gestão e Negócios',
            'numero_processo_sei' => '2026.000000002-02',
            'tipo' => 'Ação Extensiva',
            'assunto' => 'Registro baixa DEP',
            'objetivo' => null,
            'status' => 'DEP',
            'ultima_atualizacao' => '2026-07-02',
        ]);

        $this->actingAs($usuario, 'sanctum');

        $filtered = $this->getJson('/api/acoes-extensivas?status=CPED&priorizacao=Alta');
        $filtered->assertOk();
        $filtered->assertJsonPath('meta.total', 1);
        $filtered->assertJsonPath('data.0.numero_processo_sei', '2026.000000001-01');
    }
}
