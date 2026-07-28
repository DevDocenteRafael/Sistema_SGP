<?php

namespace Tests\Feature;

use App\Models\CpedEquipe;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CpedEquipeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_create_show_update_and_delete_cped_equipe(): void
    {
        $usuario = Usuario::create([
            'nome' => 'Teste Editor',
            'email' => 'editor-cped@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678921',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'DF',
            'area' => 'Portfólio',
            'telefone' => '11999999999',
        ]);

        $this->actingAs($usuario, 'sanctum');

        $listResponse = $this->getJson('/api/cped-equipes');
        $listResponse->assertOk();
        $listResponse->assertJsonStructure([
            'data',
            'meta' => [
                'total',
                'total_geral',
                'contadores' => [
                    'colaboradores',
                    'eixos',
                    'instrutores',
                    'administrativos',
                ],
                'tipos',
                'tipos_labels',
                'eixos',
                'setores',
            ],
        ]);

        $payload = [
            'nome' => 'Membro Teste API',
            'cargo' => 'Instrutor',
            'setor' => 'Gastronomia',
            'contato' => 'membro.teste@senac.df.br',
            'tipo' => 'instrutor',
            'eixo_vinculado' => 'Gastronomia',
            'iniciais' => 'MT',
            'cor' => '#F57C00',
            'ativo' => true,
            'observacao' => 'Cadastro de teste.',
        ];

        $createResponse = $this->postJson('/api/cped-equipes', $payload);
        $createResponse->assertCreated();
        $createResponse->assertJsonPath('cped_equipe.nome', 'Membro Teste API');
        $createResponse->assertJsonPath('cped_equipe.tipo', 'instrutor');
        $createResponse->assertJsonPath('cped_equipe.eixo_vinculado', 'Gastronomia');

        $id = $createResponse->json('cped_equipe.id');
        $this->assertNotNull($id);

        $showResponse = $this->getJson("/api/cped-equipes/{$id}");
        $showResponse->assertOk();
        $showResponse->assertJsonPath('cped_equipe.id', $id);

        $updatePayload = [
            ...$payload,
            'nome' => 'Membro Atualizado',
            'cargo' => 'Instrutor Sênior',
        ];

        $updateResponse = $this->putJson("/api/cped-equipes/{$id}", $updatePayload);
        $updateResponse->assertOk();
        $updateResponse->assertJsonPath('cped_equipe.nome', 'Membro Atualizado');
        $updateResponse->assertJsonPath('cped_equipe.cargo', 'Instrutor Sênior');

        $deleteResponse = $this->deleteJson("/api/cped-equipes/{$id}");
        $deleteResponse->assertOk();
        $this->assertDatabaseMissing('cped_equipes', ['id' => $id]);
    }

    public function test_filters_cped_equipes_by_tipo_and_eixo(): void
    {
        $usuario = Usuario::create([
            'nome' => 'Teste Consultor',
            'email' => 'consulta-cped@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678922',
            'perfil' => Usuario::PERFIL_CONSULTOR,
            'status' => true,
            'unidade' => 'DF',
            'area' => 'Portfólio',
            'telefone' => '11988888888',
        ]);

        CpedEquipe::create([
            'nome' => 'Responsável Gastronomia',
            'cargo' => 'Responsável de Eixo',
            'setor' => 'Gastronomia',
            'contato' => 'resp.gastro@senac.df.br',
            'tipo' => 'responsavel',
            'eixo_vinculado' => 'Gastronomia',
            'iniciais' => 'RG',
            'cor' => '#E65100',
            'ativo' => true,
        ]);

        CpedEquipe::create([
            'nome' => 'Instrutor Moda',
            'cargo' => 'Instrutor',
            'setor' => 'Gestão e Moda',
            'contato' => 'inst.moda@senac.df.br',
            'tipo' => 'instrutor',
            'eixo_vinculado' => 'Gestão e Moda',
            'iniciais' => 'IM',
            'cor' => '#B71C1C',
            'ativo' => true,
        ]);

        $this->actingAs($usuario, 'sanctum');

        $porTipo = $this->getJson('/api/cped-equipes?tipo=responsavel');
        $porTipo->assertOk();
        $this->assertCount(1, $porTipo->json('data'));
        $this->assertSame('responsavel', $porTipo->json('data.0.tipo'));

        $porEixo = $this->getJson('/api/cped-equipes?eixo=Gestão e Moda');
        $porEixo->assertOk();
        $this->assertCount(1, $porEixo->json('data'));
        $this->assertSame('Gestão e Moda', $porEixo->json('data.0.eixo_vinculado'));
    }

    public function test_clears_eixo_vinculado_for_tipos_sem_eixo(): void
    {
        $usuario = Usuario::create([
            'nome' => 'Teste Editor 2',
            'email' => 'editor2-cped@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678923',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'DF',
            'area' => 'Portfólio',
            'telefone' => '11977777777',
        ]);

        $this->actingAs($usuario, 'sanctum');

        $response = $this->postJson('/api/cped-equipes', [
            'nome' => 'Assistente Sem Eixo',
            'cargo' => 'Assistente Administrativo',
            'setor' => 'Secretaria Geral',
            'contato' => 'assistente@senac.df.br',
            'tipo' => 'assistente',
            'eixo_vinculado' => 'Gastronomia',
            'iniciais' => 'AS',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('cped_equipe.eixo_vinculado', null);
        $response->assertJsonPath('cped_equipe.iniciais', 'AS');
    }
}
