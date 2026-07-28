<?php

namespace Tests\Feature;

use App\Models\KanbanCartao;
use App\Models\KanbanColuna;
use App\Models\KanbanQuadro;
use App\Models\Usuario;
use Database\Seeders\KanbanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class KanbanApiTest extends TestCase
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

    public function test_editor_can_manage_boards_columns_and_cards(): void
    {
        $this->seed(KanbanSeeder::class);

        $usuario = $this->criarUsuario(Usuario::PERFIL_EDITOR, 'editor-kanban@teste.com');
        $this->actingAs($usuario, 'sanctum');

        $listResponse = $this->getJson('/api/kanban/quadros');
        $listResponse->assertOk();
        $listResponse->assertJsonPath('meta.pode_editar', true);
        $this->assertTrue(
            collect($listResponse->json('data'))->contains(fn ($quadro) => $quadro['slug'] === 'cped')
        );

        $showResponse = $this->getJson('/api/kanban/quadros/cped');
        $showResponse->assertOk();
        $showResponse->assertJsonPath('data.quadro.slug', 'cped');

        $colunaEmProgressoId = collect($showResponse->json('data.colunas'))
            ->firstWhere('titulo', 'Em Progresso')['id'];

        $createBoard = $this->postJson('/api/kanban/quadros', [
            'nome' => 'Eventos 2026',
        ]);
        $createBoard->assertCreated();
        $createBoard->assertJsonPath('kanban_quadro.nome', 'Eventos 2026');
        $createBoard->assertJsonPath('kanban_quadro.total_colunas', 3);

        $novoSlug = $createBoard->json('kanban_quadro.slug');
        $this->assertNotEmpty($novoSlug);

        $renameBoard = $this->putJson("/api/kanban/quadros/{$novoSlug}", [
            'nome' => 'Eventos CPED 2026',
        ]);
        $renameBoard->assertOk();
        $renameBoard->assertJsonPath('kanban_quadro.nome', 'Eventos CPED 2026');

        $createResponse = $this->postJson('/api/kanban/quadros/cped/cartoes', [
            'coluna_titulo' => 'A Fazer',
            'titulo' => 'Cartão de teste API',
            'descricao' => 'Descrição do cartão de teste.',
        ]);
        $createResponse->assertCreated();
        $createResponse->assertJsonPath('kanban_cartao.titulo', 'Cartão de teste API');
        $createResponse->assertJsonPath('coluna_criada', false);

        $cartaoId = $createResponse->json('kanban_cartao.id');

        $novaColunaResponse = $this->postJson('/api/kanban/quadros/cped/cartoes', [
            'coluna_titulo' => 'Em Revisão',
            'titulo' => 'Cartão em nova coluna',
        ]);
        $novaColunaResponse->assertCreated();
        $novaColunaResponse->assertJsonPath('coluna_criada', true);

        $updateResponse = $this->putJson("/api/kanban/cartoes/{$cartaoId}", [
            'titulo' => 'Cartão atualizado',
            'descricao' => 'Descrição atualizada.',
        ]);
        $updateResponse->assertOk();

        $moveResponse = $this->putJson("/api/kanban/cartoes/{$cartaoId}/mover", [
            'kanban_coluna_id' => $colunaEmProgressoId,
            'position' => 0,
        ]);
        $moveResponse->assertOk();
        $moveResponse->assertJsonPath('kanban_cartao.kanban_coluna_id', $colunaEmProgressoId);

        $colunaCreate = $this->postJson('/api/kanban/quadros/cped/colunas', [
            'titulo' => 'Bloqueado',
        ]);
        $colunaCreate->assertCreated();
        $colunaId = $colunaCreate->json('kanban_coluna.id');

        $this->putJson("/api/kanban/colunas/{$colunaId}", [
            'titulo' => 'Em Espera',
        ])->assertOk();

        $this->deleteJson("/api/kanban/colunas/{$colunaId}")->assertOk();
        $this->deleteJson("/api/kanban/cartoes/{$cartaoId}")->assertOk();

        $this->deleteJson("/api/kanban/quadros/{$novoSlug}")->assertOk();
        $this->assertDatabaseMissing('kanban_quadros', ['slug' => $novoSlug]);
    }

    public function test_consultor_can_list_but_cannot_mutate_kanban(): void
    {
        $this->seed(KanbanSeeder::class);

        $usuario = $this->criarUsuario(Usuario::PERFIL_CONSULTOR, 'consultor-kanban@teste.com');
        $this->actingAs($usuario, 'sanctum');

        $this->getJson('/api/kanban/quadros')->assertOk()->assertJsonPath('meta.pode_editar', false);
        $this->getJson('/api/kanban/quadros/cped')->assertOk();

        $colunaId = KanbanColuna::query()->value('id');
        $cartaoId = KanbanCartao::query()->value('id');

        $this->postJson('/api/kanban/quadros', ['nome' => 'Novo'])->assertForbidden();
        $this->postJson('/api/kanban/quadros/cped/cartoes', [
            'coluna_titulo' => 'A Fazer',
            'titulo' => 'Tentativa consultor',
        ])->assertForbidden();
        $this->postJson('/api/kanban/quadros/cped/colunas', [
            'titulo' => 'Nova coluna consultor',
        ])->assertForbidden();
        $this->putJson("/api/kanban/cartoes/{$cartaoId}", [
            'titulo' => 'Tentativa consultor',
        ])->assertForbidden();
        $this->putJson("/api/kanban/cartoes/{$cartaoId}/mover", [
            'kanban_coluna_id' => $colunaId,
            'position' => 0,
        ])->assertForbidden();
        $this->deleteJson("/api/kanban/cartoes/{$cartaoId}")->assertForbidden();
        $this->deleteJson('/api/kanban/quadros/cped')->assertForbidden();
    }

    public function test_unauthenticated_cannot_access_kanban(): void
    {
        $this->getJson('/api/kanban/quadros')->assertUnauthorized();
    }
}
