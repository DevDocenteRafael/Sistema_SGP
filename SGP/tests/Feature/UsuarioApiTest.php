<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UsuarioApiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Usuario
    {
        return Usuario::create([
            'nome' => 'Admin Usuarios',
            'email' => 'admin-usuarios@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678004',
            'perfil' => Usuario::PERFIL_ADMINISTRADOR,
            'status' => true,
            'unidade' => 'Asa Norte',
            'area' => 'CPED',
            'telefone' => '61999990004',
        ]);
    }

    public function test_admin_can_list_create_show_update_and_delete_usuario(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'sanctum');

        $this->getJson('/api/usuarios')
            ->assertOk()
            ->assertJsonStructure(['data']);

        $payload = [
            'nome' => 'Novo Editor',
            'email' => 'novo-editor@teste.com',
            'senha' => 'senha123',
            'cpf' => '12345678005',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'Asa Norte',
            'area' => 'Portfólio',
            'telefone' => '61999990005',
        ];

        $create = $this->postJson('/api/usuarios', $payload);
        $create->assertCreated();
        $create->assertJsonPath('usuario.email', 'novo-editor@teste.com');
        $create->assertJsonPath('usuario.perfil', Usuario::PERFIL_EDITOR);

        $id = $create->json('usuario.id');

        $this->getJson("/api/usuarios/{$id}")
            ->assertOk()
            ->assertJsonPath('usuario.nome', 'Novo Editor');

        $this->putJson("/api/usuarios/{$id}", [
            'nome' => 'Editor Atualizado',
            'email' => 'novo-editor@teste.com',
            'perfil' => Usuario::PERFIL_CONSULTOR,
            'status' => true,
            'unidade' => 'Asa Sul',
            'area' => 'Gestão',
            'telefone' => '61999990005',
            'cpf' => '12345678005',
        ])
            ->assertOk()
            ->assertJsonPath('usuario.nome', 'Editor Atualizado')
            ->assertJsonPath('usuario.perfil', Usuario::PERFIL_CONSULTOR);

        $this->deleteJson("/api/usuarios/{$id}")->assertOk();
        $this->assertDatabaseMissing('usuarios', ['id' => $id]);
    }

    public function test_editor_cannot_manage_usuarios(): void
    {
        $editor = Usuario::create([
            'nome' => 'Editor Sem Acesso',
            'email' => 'editor-sem@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678006',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'Asa Norte',
            'area' => 'Portfólio',
            'telefone' => '61999990006',
        ]);

        $this->actingAs($editor, 'sanctum')
            ->getJson('/api/usuarios')
            ->assertForbidden();
    }

    public function test_filters_usuarios_by_perfil(): void
    {
        $admin = $this->admin();
        Usuario::create([
            'nome' => 'Consultor Filtro',
            'email' => 'consultor-filtro@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678007',
            'perfil' => Usuario::PERFIL_CONSULTOR,
            'status' => true,
            'unidade' => 'Asa Norte',
            'area' => 'Gestão',
            'telefone' => '61999990007',
        ]);

        $this->actingAs($admin, 'sanctum');

        $filtered = $this->getJson('/api/usuarios?perfil=Consultor');
        $filtered->assertOk();
        $filtered->assertJsonPath('data.0.perfil', Usuario::PERFIL_CONSULTOR);
        $this->assertCount(1, $filtered->json('data'));
    }
}
