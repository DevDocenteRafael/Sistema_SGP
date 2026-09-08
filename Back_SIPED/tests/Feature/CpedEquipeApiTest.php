<?php

namespace Tests\Feature;

use App\Models\CpedEquipe;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CpedEquipeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_create_show_update_and_delete_cped_equipe(): void
    {
        Storage::fake('public');

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
        $this->assertNull($createResponse->json('cped_equipe.foto'));

        $id = $createResponse->json('cped_equipe.id');
        $this->assertNotNull($id);

        $jpegMinimo = base64_decode(
            '/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAn/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAGcP//EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAQUCf//EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQMBAT8Bf//EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQIBAT8Bf//Z'
        );

        $foto = UploadedFile::fake()->createWithContent('membro.jpg', $jpegMinimo);

        $createComFotoResponse = $this->post('/api/cped-equipes', [
            ...$payload,
            'nome' => 'Membro Novo Com Foto',
            'contato' => 'membro.novo.foto@senac.df.br',
            'foto' => $foto,
        ], [
            'Accept' => 'application/json',
        ]);
        $createComFotoResponse->assertCreated();
        $createComFotoResponse->assertJsonPath('cped_equipe.nome', 'Membro Novo Com Foto');
        $this->assertStringContainsString('/storage/cped/', $createComFotoResponse->json('cped_equipe.foto'));

        $uploadResponse = $this->post("/api/cped-equipes/{$id}", [
            ...$payload,
            'nome' => 'Membro Com Foto',
            'foto' => UploadedFile::fake()->createWithContent('membro-update.jpg', $jpegMinimo),
            '_method' => 'PUT',
        ], [
            'Accept' => 'application/json',
        ]);
        $uploadResponse->assertOk();
        $uploadResponse->assertJsonPath('cped_equipe.nome', 'Membro Com Foto');
        $this->assertNotEmpty($uploadResponse->json('cped_equipe.foto'));
        $this->assertStringContainsString('/storage/cped/', $uploadResponse->json('cped_equipe.foto'));

        $caminhoRelativo = CpedEquipe::query()->find($id)?->caminhoFoto();
        $this->assertNotNull($caminhoRelativo);
        Storage::disk('public')->assertExists($caminhoRelativo);

        $showResponse = $this->getJson("/api/cped-equipes/{$id}");
        $showResponse->assertOk();
        $showResponse->assertJsonPath('cped_equipe.id', $id);
        $this->assertStringContainsString('/storage/cped/', $showResponse->json('cped_equipe.foto'));

        $updatePayload = [
            ...$payload,
            'nome' => 'Membro Atualizado',
            'cargo' => 'Instrutor Sênior',
        ];

        $updateResponse = $this->putJson("/api/cped-equipes/{$id}", $updatePayload);
        $updateResponse->assertOk();
        $updateResponse->assertJsonPath('cped_equipe.nome', 'Membro Atualizado');
        $updateResponse->assertJsonPath('cped_equipe.cargo', 'Instrutor Sênior');
        // Atualização sem nova foto mantém a existente
        $this->assertStringContainsString('/storage/cped/', $updateResponse->json('cped_equipe.foto'));

        $deleteResponse = $this->deleteJson("/api/cped-equipes/{$id}");
        $deleteResponse->assertOk();
        $this->assertDatabaseMissing('cped_equipes', ['id' => $id]);
        Storage::disk('public')->assertMissing($caminhoRelativo);
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
