<?php

namespace Tests\Feature;

use App\Models\AcaoExtensiva;
use App\Models\Cadastro;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuditoriaCadastroTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Usuario
    {
        return Usuario::create([
            'nome' => 'Admin Auditoria',
            'email' => 'admin-auditoria@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678921',
            'perfil' => Usuario::PERFIL_ADMINISTRADOR,
            'status' => true,
            'unidade' => 'DF',
            'area' => 'CPED',
            'telefone' => '11999999921',
        ]);
    }

    private function editor(): Usuario
    {
        return Usuario::create([
            'nome' => 'Editor Auditoria',
            'email' => 'editor-auditoria@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678922',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'DF',
            'area' => 'Portfólio',
            'telefone' => '11999999922',
        ]);
    }

    public function test_criar_registro_gera_auditoria_e_autoria(): void
    {
        $editor = $this->editor();
        $this->actingAs($editor, 'sanctum');

        $response = $this->postJson('/api/acoes-extensivas', [
            'priorizacao' => 'Alta',
            'atribuido' => 'editor.teste',
            'eixo' => 'Gastronomia e Turismo',
            'numero_processo_sei' => '2026.000088888-88',
            'tipo' => 'Ação Extensiva',
            'assunto' => 'Ação com auditoria',
            'objetivo' => 'Validar log de criação.',
            'status' => 'CPED',
            'ultima_atualizacao' => '2026-07-20',
        ]);

        $response->assertCreated();

        $acao = AcaoExtensiva::query()->first();
        $this->assertNotNull($acao);
        $this->assertSame($editor->id, $acao->criado_por);

        $this->assertDatabaseHas('cadastros', [
            'usuario_id' => $editor->id,
            'acao' => 'criar',
            'modulo' => 'acoes-extensivas',
            'registro_id' => $acao->id,
        ]);
    }

    public function test_importacao_gera_um_evento_importar(): void
    {
        $editor = $this->editor();
        $this->actingAs($editor, 'sanctum');

        $path = base_path('tests/fixtures/acoes-extensivas-sample.xlsx');
        $arquivo = new UploadedFile(
            $path,
            'acoes-extensivas-sample.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $response = $this->post('/api/importacoes/acoes-extensivas/commit', [
            'arquivo' => $arquivo,
        ]);

        $response->assertOk();

        $this->assertSame(1, Cadastro::query()->where('acao', 'importar')->count());
        $this->assertDatabaseHas('cadastros', [
            'usuario_id' => $editor->id,
            'acao' => 'importar',
            'modulo' => 'acoes-extensivas',
        ]);

        $primeira = AcaoExtensiva::query()->first();
        $this->assertNotNull($primeira);
        $this->assertSame($editor->id, $primeira->criado_por);
    }

    public function test_apenas_admin_lista_auditoria(): void
    {
        $admin = $this->admin();
        $editor = $this->editor();

        Cadastro::query()->create([
            'usuario_id' => $editor->id,
            'acao' => 'criar',
            'modulo' => 'cursos',
            'resumo' => 'Cadastrou curso teste',
        ]);

        $this->actingAs($editor, 'sanctum');
        $this->getJson('/api/cadastros')->assertForbidden();

        $this->actingAs($admin, 'sanctum');
        $response = $this->getJson('/api/cadastros');
        $response->assertOk();
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath('data.0.resumo', 'Cadastrou curso teste');
    }
}
