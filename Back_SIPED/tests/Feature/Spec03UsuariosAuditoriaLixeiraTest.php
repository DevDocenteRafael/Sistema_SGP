<?php

namespace Tests\Feature;

use App\Models\Cadastro;
use App\Models\Curso;
use App\Models\KanbanCartao;
use App\Models\KanbanColuna;
use App\Models\KanbanQuadro;
use App\Models\Resolucao;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use LogicException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * SPEC 03 — Root, login individual auditado, inativação de usuários,
 * exclusão lógica com lixeira/restauração e bloqueio de exclusão definitiva.
 */
class Spec03UsuariosAuditoriaLixeiraTest extends TestCase
{
    use RefreshDatabase;

    private const CPFS = ['11144477735', '52998224725', '39053344705', '15350946056', '86288366757'];

    private int $seq = 0;

    private function usuario(string $perfil, string $senha = 'senha123'): Usuario
    {
        $i = $this->seq++;

        return Usuario::create([
            'nome' => "{$perfil} {$i}",
            'email' => strtolower($perfil)."{$i}@teste.com",
            'senha' => Hash::make($senha),
            'cpf' => self::CPFS[$i],
            'perfil' => $perfil,
            'status' => true,
            'unidade' => 'Asa Norte',
            'area' => 'CPED',
            'telefone' => '6199999000'.$i,
        ]);
    }

    private function payload(Usuario $alvo, array $extra = []): array
    {
        return array_merge([
            'nome' => $alvo->nome,
            'email' => $alvo->email,
            'cpf' => $alvo->cpf,
            'perfil' => $alvo->perfil,
            'status' => true,
            'unidade' => 'Asa Norte',
            'area' => 'CPED',
            'telefone' => $alvo->telefone,
        ], $extra);
    }

    // ---------- Perfis / Root ----------

    public function test_administrador_so_consulta_usuarios_e_root_gerencia(): void
    {
        $root = $this->usuario(Usuario::PERFIL_ROOT);
        $admin = $this->usuario(Usuario::PERFIL_ADMINISTRADOR);
        $editor = $this->usuario(Usuario::PERFIL_EDITOR);
        $this->actingAs($admin, 'sanctum');

        // Administrador vê a lista e o detalhe...
        $this->getJson('/api/usuarios')->assertOk();
        $this->getJson('/api/usuarios/'.$editor->id)->assertOk();
        // ...mas não cadastra, edita, inativa nem reativa ninguém.
        $this->postJson('/api/usuarios', $this->payload($editor, ['email' => 'novo-por-admin@teste.com']))->assertForbidden();
        $this->putJson('/api/usuarios/'.$editor->id, $this->payload($editor, ['nome' => 'Alterado']))->assertForbidden();
        $this->putJson('/api/usuarios/'.$root->id, $this->payload($root, ['nome' => 'Hackeado']))->assertForbidden();
        $this->deleteJson('/api/usuarios/'.$editor->id)->assertForbidden();
        $this->postJson('/api/usuarios/'.$editor->id.'/reativar')->assertForbidden();
        $this->assertSame('Root', $root->fresh()->perfil);
        $this->assertTrue((bool) $editor->fresh()->status);

        // Administrador continua com auditoria e restauração.
        $this->getJson('/api/cadastros')->assertOk();
        $this->getJson('/api/lixeira')->assertOk();
    }

    public function test_ultimo_root_ativo_nao_pode_ser_inativado_nem_rebaixado(): void
    {
        $root = $this->usuario(Usuario::PERFIL_ROOT);
        $outroRoot = $this->usuario(Usuario::PERFIL_ROOT);
        $this->actingAs($root, 'sanctum');

        // Com dois Roots, um pode ser inativado.
        $this->deleteJson('/api/usuarios/'.$outroRoot->id)->assertOk();
        // Sobrando um só Root ativo, ele não pode perder o perfil.
        $this->putJson('/api/usuarios/'.$root->id, $this->payload($root, ['perfil' => Usuario::PERFIL_ADMINISTRADOR]))
            ->assertStatus(422)->assertJsonPath('message', 'Não é possível alterar o perfil do último Root ativo.');
        $this->assertSame('Root', $root->fresh()->perfil);
        // ...nem ser inativado.
        $this->putJson('/api/usuarios/'.$root->id, $this->payload($root, ['status' => false]))
            ->assertStatus(422)->assertJsonPath('message', 'Não é possível inativar o último Root ativo.');
        $this->assertTrue((bool) $root->fresh()->status);

        // Reativado o segundo Root, a trava deixa de valer.
        $this->postJson('/api/usuarios/'.$outroRoot->id.'/reativar')->assertOk();
        $this->putJson('/api/usuarios/'.$root->id, $this->payload($root, ['perfil' => Usuario::PERFIL_ADMINISTRADOR]))
            ->assertOk();
        $this->assertSame('Administrador', $root->fresh()->perfil);
    }

    public function test_root_gerencia_administradores_e_concede_root(): void
    {
        $root = $this->usuario(Usuario::PERFIL_ROOT);
        $admin = $this->usuario(Usuario::PERFIL_ADMINISTRADOR);
        $this->actingAs($root, 'sanctum');

        $this->putJson('/api/usuarios/'.$admin->id, $this->payload($admin, ['perfil' => 'Root']))
            ->assertOk()->assertJsonPath('usuario.perfil', 'Root');
        $this->getJson('/api/cadastros')->assertOk();
        $this->getJson('/api/lixeira')->assertOk();
    }

    public function test_comando_cria_root_sem_senha_fixa_e_root_consegue_entrar(): void
    {
        $this->assertSame(0, Artisan::call('siped:criar-root', ['email' => 'root@df.senac.br', '--gerar-senha' => true]));
        $saida = Artisan::output();
        $senha = trim(collect(explode("\n", trim($saida)))->last());

        $root = Usuario::query()->where('email', 'root@df.senac.br')->firstOrFail();
        $this->assertSame('Root', $root->perfil);
        $this->assertTrue(Hash::check($senha, $root->senha));

        $this->postJson('/api/login', ['email' => 'root@df.senac.br', 'senha' => $senha])
            ->assertOk()->assertJsonPath('usuario.perfil', 'Root');
    }

    // ---------- Inativação de usuários ----------

    public function test_excluir_usuario_inativa_revoga_tokens_e_mantem_historico(): void
    {
        $admin = $this->usuario(Usuario::PERFIL_ROOT);
        $editor = $this->usuario(Usuario::PERFIL_EDITOR, 'senha-editor');
        $token = $editor->createToken('teste')->plainTextToken;
        $this->actingAs($admin, 'sanctum');

        $this->deleteJson('/api/usuarios/'.$editor->id)->assertOk()->assertJsonPath('usuario.status', false);

        $this->assertDatabaseHas('usuarios', ['id' => $editor->id, 'status' => false]);
        $this->assertSame(0, $editor->tokens()->count());
        $this->assertDatabaseHas('cadastros', ['acao' => 'inativar', 'registro_id' => $editor->id]);

        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/user')->assertUnauthorized();
        $this->postJson('/api/login', ['email' => $editor->email, 'senha' => 'senha-editor'])->assertStatus(422);

        $this->actingAs($admin, 'sanctum');
        $this->postJson('/api/usuarios/'.$editor->id.'/reativar')->assertOk()->assertJsonPath('usuario.status', true);
        $this->assertDatabaseHas('cadastros', ['acao' => 'reativar', 'registro_id' => $editor->id]);
    }

    // ---------- Auditoria de login ----------

    public function test_login_logout_e_falhas_entram_na_auditoria_sem_senha(): void
    {
        $editor = $this->usuario(Usuario::PERFIL_EDITOR, 'senha-certa');

        $this->postJson('/api/login', ['email' => $editor->email, 'senha' => 'senha-errada'])->assertStatus(422);
        $this->postJson('/api/login', ['email' => 'ninguem@teste.com', 'senha' => 'qualquer'])->assertStatus(422);
        $token = $this->postJson('/api/login', ['email' => $editor->email, 'senha' => 'senha-certa'])->assertOk()->json('token');
        $this->withHeader('Authorization', 'Bearer '.$token)->postJson('/api/logout')->assertOk();

        $falhas = Cadastro::query()->where('acao', 'login_falha')->get();
        $this->assertCount(2, $falhas);
        $this->assertSame($editor->id, $falhas->first()->usuario_id);
        $this->assertNull($falhas->last()->usuario_id);
        $this->assertSame('ninguem@teste.com', $falhas->last()->dados['email']);
        $this->assertStringNotContainsString('senha-errada', json_encode($falhas->toArray()));
        $this->assertDatabaseHas('cadastros', ['acao' => 'login', 'usuario_id' => $editor->id, 'modulo' => 'autenticacao']);
        $this->assertDatabaseHas('cadastros', ['acao' => 'logout', 'usuario_id' => $editor->id]);
    }

    // ---------- Exclusão lógica e restauração ----------

    public function test_exclusao_logica_lixeira_e_restauracao_com_auditoria(): void
    {
        $admin = $this->usuario(Usuario::PERFIL_ADMINISTRADOR);
        $editor = $this->usuario(Usuario::PERFIL_EDITOR);
        $curso = Curso::create(['titulo' => 'Curso para a lixeira', 'status' => 'ATIVO', 'eixo' => 'Gestão e Moda']);

        $this->actingAs($editor, 'sanctum');
        $this->deleteJson('/api/cursos/'.$curso->id)->assertOk();
        $this->assertSoftDeleted('cursos', ['id' => $curso->id, 'excluido_por' => $editor->id]);
        $this->getJson('/api/cursos/'.$curso->id)->assertNotFound();
        $this->getJson('/api/lixeira')->assertForbidden();
        $this->postJson('/api/lixeira/cursos/'.$curso->id.'/restaurar')->assertForbidden();

        $this->actingAs($admin, 'sanctum');
        $lista = $this->getJson('/api/lixeira?modulo=cursos')->assertOk();
        $item = collect($lista->json('data'))->firstWhere('id', $curso->id);
        $this->assertSame('Curso para a lixeira', $item['titulo']);
        $this->assertSame($editor->nome, $item['excluido_por']);
        $this->assertNotNull($item['excluido_em']);

        $this->postJson('/api/lixeira/cursos/'.$curso->id.'/restaurar')->assertOk();
        $this->assertNotSoftDeleted('cursos', ['id' => $curso->id, 'excluido_por' => null]);
        $this->getJson('/api/cursos/'.$curso->id)->assertOk();
        $this->assertDatabaseHas('cadastros', ['acao' => 'excluir', 'registro_id' => $curso->id, 'usuario_id' => $editor->id]);
        $this->assertDatabaseHas('cadastros', ['acao' => 'restaurar', 'registro_id' => $curso->id, 'usuario_id' => $admin->id]);
    }

    public function test_auditoria_oferece_restaurar_so_para_exclusoes_pendentes(): void
    {
        $admin = $this->usuario(Usuario::PERFIL_ADMINISTRADOR);
        $editor = $this->usuario(Usuario::PERFIL_EDITOR);
        $pendente = Curso::create(['titulo' => 'Excluído pendente', 'status' => 'ATIVO', 'eixo' => 'Gestão e Moda']);
        $voltou = Curso::create(['titulo' => 'Excluído e restaurado', 'status' => 'ATIVO', 'eixo' => 'Gestão e Moda']);

        $this->actingAs($editor, 'sanctum');
        $this->deleteJson('/api/cursos/'.$pendente->id)->assertOk();
        $this->deleteJson('/api/cursos/'.$voltou->id)->assertOk();

        $this->actingAs($admin, 'sanctum');
        $this->postJson('/api/lixeira/cursos/'.$voltou->id.'/restaurar')->assertOk();

        $exclusoes = collect($this->getJson('/api/cadastros?acao=excluir')->assertOk()->json('data'))->keyBy('registro_id');
        $this->assertTrue($exclusoes[$pendente->id]['restauravel']);
        $this->assertSame('cursos', $exclusoes[$pendente->id]['modulo_lixeira']);
        $this->assertFalse($exclusoes[$voltou->id]['restauravel']);

        $aRestaurar = $this->getJson('/api/cadastros?a_restaurar=1')->assertOk();
        $this->assertSame([$pendente->id], collect($aRestaurar->json('data'))->pluck('registro_id')->all());

        $this->postJson('/api/lixeira/'.$exclusoes[$pendente->id]['modulo_lixeira'].'/'.$pendente->id.'/restaurar')->assertOk();
        $this->getJson('/api/cadastros?a_restaurar=1')->assertOk()->assertJsonPath('meta.total', 0);
    }

    public function test_anexo_e_mantido_na_exclusao_logica(): void
    {
        Storage::fake('public');
        $this->actingAs($this->usuario(Usuario::PERFIL_ADMINISTRADOR), 'sanctum');
        $resolucao = Resolucao::create([
            'numero' => 'RES-ANEXO', 'resumo' => 'Com anexo', 'status' => 'vigente',
            'data_inicio_vigencia' => '2025-01-01', 'data_fim_vigencia' => '2030-01-01',
            'anexo_path' => 'resolucoes/anexo.pdf',
        ]);
        Storage::disk('public')->put('resolucoes/anexo.pdf', '%PDF-1.4');

        $this->deleteJson('/api/resolucoes/'.$resolucao->id)->assertOk();
        Storage::disk('public')->assertExists('resolucoes/anexo.pdf');
        $this->postJson('/api/lixeira/resolucoes/'.$resolucao->id.'/restaurar')->assertOk();
        $this->assertSame('resolucoes/anexo.pdf', $resolucao->fresh()->anexo_path);
    }

    public function test_filho_nao_volta_enquanto_o_pai_estiver_excluido(): void
    {
        $this->actingAs($this->usuario(Usuario::PERFIL_ADMINISTRADOR), 'sanctum');
        $quadro = KanbanQuadro::create(['nome' => 'Quadro', 'slug' => 'quadro-teste', 'ativo' => true]);
        $coluna = KanbanColuna::create(['kanban_quadro_id' => $quadro->id, 'titulo' => 'Fazer', 'position' => 0]);
        $cartao = KanbanCartao::create(['kanban_coluna_id' => $coluna->id, 'titulo' => 'Cartão', 'position' => 0]);

        $cartao->delete();
        $coluna->delete();

        $this->postJson('/api/lixeira/kanban-cartoes/'.$cartao->id.'/restaurar')
            ->assertStatus(422)->assertJsonPath('message', fn ($m) => str_contains($m, 'coluna do Kanban'));
        $this->postJson('/api/lixeira/kanban-colunas/'.$coluna->id.'/restaurar')->assertOk();
        $this->postJson('/api/lixeira/kanban-cartoes/'.$cartao->id.'/restaurar')->assertOk();
    }

    public function test_exclusao_definitiva_fica_bloqueada(): void
    {
        $curso = Curso::create(['titulo' => 'Não apagar', 'status' => 'ATIVO', 'eixo' => 'Gestão e Moda']);

        $this->expectException(LogicException::class);
        $curso->forceDelete();
    }

    public function test_importacao_nao_recria_registro_que_esta_na_lixeira(): void
    {
        $this->actingAs($this->usuario(Usuario::PERFIL_ADMINISTRADOR), 'sanctum');
        $ciclo = \App\Models\Ciclo::atual() ?? \App\Models\Ciclo::create(['nome' => '2025-2026', 'atual' => true]);
        $curso = Curso::create(['titulo' => 'Curso excluído', 'status' => 'ATIVO', 'eixo' => 'Gestão e Moda', 'codigo_sig' => 'SIG-LIX', 'ciclo_id' => $ciclo->id]);
        $curso->delete();

        $ss = new Spreadsheet;
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Gestão e Moda');
        $sheet->fromArray([
            ['Status SIG', 'Segmento', 'Modalidade', 'Título - Nome do Curso', 'CH', 'Cód. SIG'],
            ['ATIVO', 'Gestão e Comércio', 'Qualificação Profissional', 'Curso excluído', '40', 'SIG-LIX'],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'siped-lix-').'.xlsx';
        (new Xlsx($ss))->save($path);
        $arquivo = new UploadedFile($path, 'lixeira.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $preview = $this->post('/api/importacoes/cursos/preview', ['arquivo' => $arquivo])->assertOk();
        $this->assertSame('erro', $preview->json('linhas.0.status_importacao'));
        $this->assertTrue(collect($preview->json('erros'))->contains(fn ($e) => str_contains($e['mensagem'], 'Restaure-o na Auditoria')));
        $this->assertSame(1, Curso::withTrashed()->where('codigo_sig', 'SIG-LIX')->count());
    }
}
