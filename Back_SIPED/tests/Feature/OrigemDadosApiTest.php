<?php

namespace Tests\Feature;

use App\Models\Curso;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OrigemDadosApiTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): Usuario
    {
        return Usuario::create([
            'nome' => 'Editor Origem',
            'email' => 'editor-origem@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678141',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'Asa Norte',
            'area' => 'Portfólio',
            'telefone' => '61999991041',
        ]);
    }

    private function admin(): Usuario
    {
        return Usuario::create([
            'nome' => 'Admin Origem',
            'email' => 'admin-origem@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678142',
            'perfil' => Usuario::PERFIL_ADMINISTRADOR,
            'status' => true,
            'unidade' => 'Asa Norte',
            'area' => 'CPED',
            'telefone' => '61999991042',
        ]);
    }

    public function test_entidades_externas_continuam_consultaveis(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        foreach ([
            '/api/cursos',
            '/api/curso-execucoes',
            '/api/plano-de-metas',
            '/api/pcas',
            '/api/visitas-tecnicas',
            '/api/horas-pedagogicas',
            '/api/acoes-extensivas',
            '/api/eventos',
            '/api/resolucoes',
            '/api/termos-referencia',
            '/api/jornadas-pedagogicas',
            '/api/unidades-oferta',
            '/api/revisao-dados',
            '/api/eixos/resumo',
            '/api/dashboard',
        ] as $endpoint) {
            $this->getJson($endpoint)->assertOk();
        }
    }

    public function test_escrita_em_curso_e_bloqueada_e_show_expoe_origem(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $curso = Curso::create([
            'titulo' => 'Curso somente leitura',
            'status' => 'ATIVO',
            'eixo' => 'Gestão e Moda',
            'source_type' => 'seeder',
        ]);

        $this->assertEscritaExternaBloqueada($this->postJson('/api/cursos', [
            'titulo' => 'Novo curso',
            'status' => 'ATIVO',
        ]));
        $this->assertEscritaExternaBloqueada($this->putJson('/api/cursos/'.$curso->id, [
            'titulo' => 'Curso alterado',
            'status' => 'INATIVO',
        ]));
        $this->assertEscritaExternaBloqueada($this->deleteJson('/api/cursos/'.$curso->id));

        $this->getJson('/api/cursos/'.$curso->id)
            ->assertOk()
            ->assertJsonPath('curso.titulo', 'Curso somente leitura')
            ->assertJsonPath('curso.origem.source_type', 'seeder');
    }

    public function test_commit_de_importacao_e_bloqueado_mas_preview_continua(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->post('/api/importacoes/cursos/commit')->assertForbidden()
            ->assertJsonPath('message', config('origem_dados.mensagem_bloqueio'));

        $this->post('/api/importacoes/cursos/preview')->assertStatus(422);
    }

    public function test_logout_continua_permitido(): void
    {
        $this->actingAs($this->editor(), 'sanctum')
            ->postJson('/api/logout')
            ->assertOk();
    }

    public function test_usuarios_e_cped_continuam_com_crud(): void
    {
        $this->actingAs($this->admin(), 'sanctum');

        $usuario = $this->postJson('/api/usuarios', [
            'nome' => 'Novo Usuário',
            'email' => 'novo-origem@teste.com',
            'senha' => 'senha123',
            'cpf' => '39053344705',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'telefone' => '61999990088',
            'unidade' => 'Asa Norte',
            'area' => 'Portfólio',
        ]);
        $usuario->assertCreated();

        $this->actingAs($this->editor(), 'sanctum');
        $cped = $this->postJson('/api/cped-equipes', [
            'nome' => 'Membro Origem',
            'cargo' => 'Assistente',
            'setor' => 'CPED',
            'contato' => 'membro.origem@senac.df.br',
            'tipo' => 'assistente',
            'iniciais' => 'MO',
            'cor' => '#003F7D',
            'ativo' => true,
            'observacao' => 'Cadastro interno.',
        ]);
        $cped->assertCreated();
        $cped->assertJsonPath('cped_equipe.nome', 'Membro Origem');
    }

    public function test_ciclos_continuam_editaveis(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->postJson('/api/ciclos', [
            'nome' => '2031',
            'observacao' => 'Ciclo administrativo',
            'atual' => false,
        ])->assertCreated();
    }
}
