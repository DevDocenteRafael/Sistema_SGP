<?php

namespace Tests\Feature;

use App\Models\Fluxograma;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FluxogramaApiTest extends TestCase
{
    use RefreshDatabase;

    private function criarUsuario(string $perfil, string $email): Usuario
    {
        return Usuario::create([
            'nome' => "Teste {$perfil}",
            'email' => $email,
            'senha' => Hash::make('senha123'),
            'cpf' => (string) random_int(10000000000, 99999999999),
            'perfil' => $perfil,
            'status' => true,
            'unidade' => 'DF',
            'area' => 'Portfólio',
            'telefone' => '11999999999',
        ]);
    }

    public function test_editor_can_manage_fluxogramas(): void
    {
        $usuario = $this->criarUsuario(Usuario::PERFIL_EDITOR, 'editor-fluxograma@teste.com');
        $this->actingAs($usuario, 'sanctum');

        $listResponse = $this->getJson('/api/fluxogramas');
        $listResponse->assertOk();
        $listResponse->assertJsonPath('meta.pode_editar', true);
        $listResponse->assertJsonPath('meta.total', 0);

        $createResponse = $this->postJson('/api/fluxogramas', [
            'titulo' => 'Admissão de aluno',
            'descricao' => 'Fluxo de entrada no SENAC DF',
            'tipo' => 'linear',
        ]);
        $createResponse->assertCreated();
        $createResponse->assertJsonPath('fluxograma.titulo', 'Admissão de aluno');
        $createResponse->assertJsonPath('fluxograma.tipo', 'linear');
        $createResponse->assertJsonPath('fluxograma.total_nos', 3);

        $slug = $createResponse->json('fluxograma.slug');
        $this->assertNotEmpty($slug);

        $showResponse = $this->getJson("/api/fluxogramas/{$slug}");
        $showResponse->assertOk();
        $showResponse->assertJsonPath('data.titulo', 'Admissão de aluno');
        $showResponse->assertJsonPath('data.diagrama.nodes.0.type', 'inicio');
        $showResponse->assertJsonPath('data.diagrama.nodes.1.type', 'processo');
        $showResponse->assertJsonPath('data.diagrama.nodes.2.type', 'fim');
        $this->assertCount(2, $showResponse->json('data.diagrama.edges'));
        $showResponse->assertJsonPath('meta.pode_editar', true);

        $createFuncional = $this->postJson('/api/fluxogramas', [
            'titulo' => 'Fluxo com raias',
            'tipo' => 'funcional',
        ]);
        $createFuncional->assertCreated();
        $createFuncional->assertJsonPath('fluxograma.tipo', 'funcional');
        $createFuncional->assertJsonPath('fluxograma.total_nos', 3);

        $slugFuncional = $createFuncional->json('fluxograma.slug');
        $showFuncional = $this->getJson("/api/fluxogramas/{$slugFuncional}");
        $showFuncional->assertOk();
        $this->assertCount(2, $showFuncional->json('data.diagrama.raias'));
        $showFuncional->assertJsonPath('data.diagrama.raias.0.nome', 'Área / Setor 1');
        $showFuncional->assertJsonPath('data.diagrama.nodes.0.data.raiaId', 'raia-1');

        $updateMeta = $this->putJson("/api/fluxogramas/{$slug}", [
            'titulo' => 'Admissão de aluno CPED',
            'descricao' => 'Escopo revisado',
        ]);
        $updateMeta->assertOk();
        $updateMeta->assertJsonPath('fluxograma.titulo', 'Admissão de aluno CPED');

        $updateDiagrama = $this->putJson("/api/fluxogramas/{$slug}", [
            'diagrama' => [
                'nodes' => [
                    ['id' => 'n1', 'type' => 'inicio', 'position' => ['x' => 0, 'y' => 0], 'data' => ['label' => 'Início']],
                ],
                'edges' => [],
                'viewport' => ['x' => 10, 'y' => 20, 'zoom' => 1.2],
            ],
        ]);
        $updateDiagrama->assertOk();
        $updateDiagrama->assertJsonPath('fluxograma.diagrama.nodes.0.id', 'n1');
        $updateDiagrama->assertJsonPath('fluxograma.diagrama.viewport.zoom', 1.2);

        $this->getJson('/api/fluxogramas')
            ->assertOk()
            ->assertJsonPath('data.0.total_nos', 1);

        $this->deleteJson("/api/fluxogramas/{$slug}")->assertOk();
        $this->assertDatabaseMissing('fluxogramas', ['slug' => $slug]);
    }

    public function test_consultor_can_list_but_cannot_mutate_fluxogramas(): void
    {
        $editor = $this->criarUsuario(Usuario::PERFIL_EDITOR, 'editor-setup-flux@teste.com');
        $fluxograma = Fluxograma::query()->create([
            'titulo' => 'Processo teste',
            'slug' => 'processo-teste',
            'descricao' => null,
            'tipo' => Fluxograma::TIPO_LINEAR,
            'diagrama' => [
                'nodes' => [],
                'edges' => [],
                'viewport' => ['x' => 0, 'y' => 0, 'zoom' => 1],
            ],
            'ativo' => true,
            'criado_por' => $editor->id,
        ]);

        $consultor = $this->criarUsuario(Usuario::PERFIL_CONSULTOR, 'consultor-fluxograma@teste.com');
        $this->actingAs($consultor, 'sanctum');

        $this->getJson('/api/fluxogramas')->assertOk()->assertJsonPath('meta.pode_editar', false);
        $this->getJson("/api/fluxogramas/{$fluxograma->slug}")->assertOk();

        $this->postJson('/api/fluxogramas', ['titulo' => 'Novo'])->assertForbidden();
        $this->putJson("/api/fluxogramas/{$fluxograma->slug}", [
            'titulo' => 'Tentativa consultor',
        ])->assertForbidden();
        $this->deleteJson("/api/fluxogramas/{$fluxograma->slug}")->assertForbidden();
    }

    public function test_unauthenticated_cannot_access_fluxogramas(): void
    {
        $this->getJson('/api/fluxogramas')->assertUnauthorized();
    }
}
