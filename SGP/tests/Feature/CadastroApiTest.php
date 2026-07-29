<?php

namespace Tests\Feature;

use App\Models\Cadastro;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CadastroApiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Usuario
    {
        return Usuario::create([
            'nome' => 'Admin Cadastros',
            'email' => 'admin-cadastros@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678103',
            'perfil' => Usuario::PERFIL_ADMINISTRADOR,
            'status' => true,
            'unidade' => 'Asa Norte',
            'area' => 'CPED',
            'telefone' => '61999991003',
        ]);
    }

    private function editor(): Usuario
    {
        return Usuario::create([
            'nome' => 'Editor Cadastros',
            'email' => 'editor-cadastros@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678104',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'Asa Norte',
            'area' => 'Portfólio',
            'telefone' => '61999991004',
        ]);
    }

    public function test_admin_can_list_and_show_auditoria(): void
    {
        $admin = $this->admin();
        $editor = $this->editor();

        $evento = Cadastro::query()->create([
            'usuario_id' => $editor->id,
            'acao' => 'editar',
            'modulo' => 'cursos',
            'registro_tipo' => \App\Models\Curso::class,
            'registro_id' => 10,
            'resumo' => 'Atualizou curso demonstração',
            'dados' => ['alterados' => ['titulo', 'status']],
            'ip' => '127.0.0.1',
        ]);

        $this->actingAs($admin, 'sanctum');

        $this->getJson('/api/cadastros?modulo=cursos&acao=editar')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.resumo', 'Atualizou curso demonstração');

        $this->getJson("/api/cadastros/{$evento->id}")
            ->assertOk()
            ->assertJsonPath('cadastro.id', $evento->id)
            ->assertJsonPath('cadastro.modulo', 'cursos')
            ->assertJsonPath('cadastro.usuario.email', 'editor-cadastros@teste.com')
            ->assertJsonPath('cadastro.dados.alterados.0', 'titulo');
    }

    public function test_editor_cannot_access_auditoria_show(): void
    {
        $editor = $this->editor();
        $evento = Cadastro::query()->create([
            'usuario_id' => $editor->id,
            'acao' => 'criar',
            'modulo' => 'eventos',
            'resumo' => 'Cadastrou evento',
        ]);

        $this->actingAs($editor, 'sanctum')
            ->getJson("/api/cadastros/{$evento->id}")
            ->assertForbidden();
    }
}
