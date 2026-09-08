<?php

namespace Tests\Feature;

use App\Models\CursoPorEixo;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CursoPorEixoApiTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): Usuario
    {
        return Usuario::create([
            'nome' => 'Editor Eixos',
            'email' => 'editor-eixos@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678101',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'Asa Norte',
            'area' => 'Portfólio',
            'telefone' => '61999991001',
        ]);
    }

    public function test_can_list_create_show_update_and_delete_curso_por_eixo(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->getJson('/api/curso-por-eixos')
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['total', 'total_geral', 'eixos', 'status', 'anos', 'unidades'],
            ]);

        $payload = [
            'curso' => 'Curso Eixo Teste',
            'eixo' => 'Gastronomia',
            'unidade' => 'Asa Norte',
            'ano' => '2026',
            'ch' => '40',
            'turmas' => '2',
            'codigo' => 'EIXO-001',
            'alunos' => '20',
            'instrutores' => 'Instrutor Teste',
            'status' => 'Ativo',
            'observacao' => 'Registro de teste',
            'is_novo' => true,
        ];

        $create = $this->postJson('/api/curso-por-eixos', $payload);
        $create->assertCreated();
        $create->assertJsonPath('cursoPorEixo.curso', 'Curso Eixo Teste');
        $create->assertJsonPath('cursoPorEixo.is_novo', true);

        $id = $create->json('cursoPorEixo.id');

        $this->getJson("/api/curso-por-eixos/{$id}")
            ->assertOk()
            ->assertJsonPath('cursoPorEixo.codigo', 'EIXO-001');

        $this->putJson("/api/curso-por-eixos/{$id}", [
            ...$payload,
            'curso' => 'Curso Eixo Atualizado',
            'status' => 'Suspenso',
            'is_novo' => false,
        ])
            ->assertOk()
            ->assertJsonPath('cursoPorEixo.curso', 'Curso Eixo Atualizado')
            ->assertJsonPath('cursoPorEixo.status', 'Suspenso');

        $this->deleteJson("/api/curso-por-eixos/{$id}")->assertOk();
        $this->assertDatabaseMissing('curso_por_eixos', ['id' => $id]);
    }

    public function test_filters_curso_por_eixo_by_ano_and_eixo(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        CursoPorEixo::create([
            'curso' => 'Curso 2026',
            'eixo' => 'Gastronomia',
            'unidade' => 'Asa Norte',
            'ano' => '2026',
            'status' => 'Ativo',
            'is_novo' => false,
        ]);
        CursoPorEixo::create([
            'curso' => 'Curso 2025',
            'eixo' => 'Enfermagem',
            'unidade' => 'Asa Sul',
            'ano' => '2025',
            'status' => 'Inativo',
            'is_novo' => false,
        ]);

        $filtered = $this->getJson('/api/curso-por-eixos?ano=2026&eixo=Gastronomia');
        $filtered->assertOk();
        $filtered->assertJsonPath('meta.total', 1);
        $filtered->assertJsonPath('data.0.curso', 'Curso 2026');
    }
}
