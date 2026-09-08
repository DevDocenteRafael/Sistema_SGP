<?php

namespace Tests\Feature;

use App\Models\Curso;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CursoApiTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): Usuario
    {
        return Usuario::create([
            'nome' => 'Editor Cursos',
            'email' => 'editor-cursos@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678001',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'Asa Norte',
            'area' => 'Portfólio',
            'telefone' => '61999990001',
        ]);
    }

    public function test_can_list_create_show_update_and_delete_curso(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->getJson('/api/cursos')
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['total', 'eixos', 'status', 'tipos', 'modalidades', 'sim_nao'],
            ]);

        $payload = [
            'titulo' => 'Curso de teste API',
            'eixo' => 'Gastronomia e Turismo',
            'modalidade' => 'Presencial',
            'tipo' => 'Livre',
            'status' => 'ATIVO',
            'codigo_sig' => 'SIG-TEST-001',
            'unidades_oferta' => ['Asa Norte'],
        ];

        $create = $this->postJson('/api/cursos', $payload);
        $create->assertCreated();
        $create->assertJsonPath('curso.titulo', 'Curso de teste API');
        $create->assertJsonPath('curso.status', 'ATIVO');

        $id = $create->json('curso.id');
        $this->assertNotNull($id);

        $this->getJson("/api/cursos/{$id}")
            ->assertOk()
            ->assertJsonPath('curso.codigo_sig', 'SIG-TEST-001');

        $this->putJson("/api/cursos/{$id}", [
            ...$payload,
            'titulo' => 'Curso atualizado',
            'status' => 'INATIVO',
        ])
            ->assertOk()
            ->assertJsonPath('curso.titulo', 'Curso atualizado')
            ->assertJsonPath('curso.status', 'INATIVO');

        $this->deleteJson("/api/cursos/{$id}")->assertOk();
        $this->assertDatabaseMissing('cursos', ['id' => $id]);
    }

    public function test_filters_cursos_by_status_and_eixo(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        Curso::create([
            'titulo' => 'Curso Saúde',
            'eixo' => 'Saúde',
            'status' => 'ATIVO',
        ]);
        Curso::create([
            'titulo' => 'Curso Inativo',
            'eixo' => 'Gastronomia e Turismo',
            'status' => 'INATIVO',
        ]);

        $filtered = $this->getJson('/api/cursos?status=ATIVO&eixo=Saúde');
        $filtered->assertOk();
        $filtered->assertJsonPath('meta.total', 1);
        $filtered->assertJsonPath('data.0.titulo', 'Curso Saúde');
    }

    public function test_guest_cannot_list_cursos(): void
    {
        $this->getJson('/api/cursos')->assertUnauthorized();
    }
}
