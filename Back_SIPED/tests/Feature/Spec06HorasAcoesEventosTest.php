<?php

namespace Tests\Feature;

use App\Models\AcaoExtensiva;
use App\Models\Evento;
use App\Models\HoraPedagogica;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * SPEC 06 — Horas Pedagógicas, Ações Extensivas e Eventos.
 */
class Spec06HorasAcoesEventosTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): Usuario
    {
        return Usuario::create([
            'nome' => 'Editor Spec 06',
            'email' => 'editor-spec06@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '52998224725',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'Asa Norte',
            'area' => 'CPED',
            'telefone' => '61999990606',
        ]);
    }

    // ---------- Horas Pedagógicas ----------

    private function hora(array $extra = []): array
    {
        return array_merge([
            'matricula' => '5041', 'pessoa' => 'Ana Teste', 'eixo' => 'Ambiente e Saúde', 'segmento' => 'Enfermagem',
            'processo_sei' => '2026.000022222-22', 'ano' => 2026, 'motivo' => 'Planejamento', 'status' => 'Pendente',
            'ativo' => true, 'observacao' => 'Registro de teste',
        ], $extra);
    }

    public function test_segmento_da_hora_pedagogica_vem_do_catalogo_e_pertence_ao_eixo(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->postJson('/api/horas-pedagogicas', $this->hora())->assertCreated()
            ->assertJsonPath('horaPedagogica.segmento', 'Enfermagem');
        $this->postJson('/api/horas-pedagogicas', $this->hora(['segmento' => 'Ambiente e Saúde']))
            ->assertStatus(422)->assertJsonValidationErrors(['segmento']);
        $this->postJson('/api/horas-pedagogicas', $this->hora(['segmento' => 'Gastronomia']))
            ->assertStatus(422)->assertJsonPath('errors.segmento.0', fn ($m) => str_contains($m, 'não pertence ao eixo'));

        $meta = $this->getJson('/api/horas-pedagogicas')->assertOk()->json('meta');
        $this->assertContains('Enfermagem', $meta['segmentos_por_eixo']['Ambiente e Saúde']);
        $this->assertNotContains('Ambiente e Saúde', $meta['segmentos']);
    }

    public function test_status_cancelada_fica_so_como_legado(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->postJson('/api/horas-pedagogicas', $this->hora(['status' => 'Cancelada']))
            ->assertStatus(422)->assertJsonValidationErrors(['status']);

        $legado = HoraPedagogica::create($this->hora(['status' => 'Cancelada']));
        $this->putJson('/api/horas-pedagogicas/'.$legado->id, $this->hora(['status' => 'Cancelada', 'motivo' => 'Ajuste']))
            ->assertOk();

        $meta = $this->getJson('/api/horas-pedagogicas')->json('meta');
        $this->assertSame(['Pendente', 'Em andamento', 'Concluída'], $meta['status']);
        $this->assertSame(['Cancelada'], $meta['status_legados']);
    }

    // ---------- Ações Extensivas ----------

    private function acao(array $extra = []): array
    {
        return array_merge([
            'priorizacao' => 'Alta', 'atribuido' => 'equipe.cped', 'eixo' => 'Gestão e Moda',
            'numero_processo_sei' => '0001.123456/2026-01', 'tipo' => 'Ação Extensiva', 'assunto' => 'Assunto',
            'objetivo' => 'Objetivo', 'setor_atual' => 'DEP', 'ultima_atualizacao' => '2026-10-01',
        ], $extra);
    }

    public function test_prioridade_e_setor_sao_campos_separados(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->postJson('/api/acoes-extensivas', $this->acao(['priorizacao' => 'Resolvido']))
            ->assertStatus(422)->assertJsonValidationErrors(['priorizacao']);
        $this->postJson('/api/acoes-extensivas', $this->acao(['setor_atual' => 'Em andamento']))
            ->assertStatus(422)->assertJsonValidationErrors(['setor_atual']);
        $this->postJson('/api/acoes-extensivas', $this->acao())->assertCreated()
            ->assertJsonPath('acaoExtensiva.setor_atual', 'DEP')
            ->assertJsonPath('acaoExtensiva.priorizacao', 'Alta');

        $meta = $this->getJson('/api/acoes-extensivas')->json('meta');
        $this->assertSame(['Baixa', 'Média', 'Alta'], $meta['priorizacoes']);
        $this->assertSame(['CPED', 'DEP', 'DIREG', 'NC'], $meta['setores']);
        $this->getJson('/api/acoes-extensivas?setor=DEP')->assertJsonPath('meta.total', 1);
        $this->getJson('/api/acoes-extensivas?setor=CPED')->assertJsonPath('meta.total', 0);
    }

    public function test_migracao_move_setor_e_resolvido_sem_apagar_dados(): void
    {
        $migration = require database_path('migrations/2026_10_06_110000_spec06_acoes_eventos.php');
        $migration->down();
        DB::table('acao_extensivas')->insert([
            ['assunto' => 'Setor no status', 'priorizacao' => 'Alta', 'status' => 'DIREG', 'created_at' => now(), 'updated_at' => now()],
            ['assunto' => 'Era resolvido', 'priorizacao' => 'Resolvido', 'status' => 'NC', 'created_at' => now(), 'updated_at' => now()],
            ['assunto' => 'Status de execução', 'priorizacao' => 'Baixa', 'status' => 'Em andamento', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $migration->up();

        $porAssunto = AcaoExtensiva::query()->get()->keyBy('assunto');
        $this->assertSame('DIREG', $porAssunto['Setor no status']->setor_atual);
        $this->assertNull($porAssunto['Setor no status']->status);
        $this->assertNull($porAssunto['Era resolvido']->priorizacao);
        $this->assertSame('Resolvido', $porAssunto['Era resolvido']->situacao_legada);
        $this->assertSame('NC', $porAssunto['Era resolvido']->setor_atual);
        $this->assertSame('Em andamento', $porAssunto['Status de execução']->status);
        $this->assertNull($porAssunto['Status de execução']->setor_atual);
        $this->assertSame(3, AcaoExtensiva::count());
    }

    public function test_importacao_de_acoes_separa_setor_status_e_resolvido(): void
    {
        $this->actingAs($this->editor(), 'sanctum');
        $ss = new Spreadsheet;
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Ações extensivas');
        $sheet->fromArray([
            ['Priorização', 'Atribuído', 'Eixo', 'Número do processo SEI', 'Tipo', 'Assunto', 'Objetivo', 'Status', 'Última atualização'],
            ['alta', 'a', 'Gestão e Moda', '0001.000001/2026-01', 'Ação Extensiva', 'Ação com setor', 'O', 'dep', '2026-10-01'],
            ['Resolvido', 'b', 'Gestão e Moda', '0001.000002/2026-01', 'Ação Extensiva', 'Ação resolvida', 'O', 'Concluída', '2026-10-01'],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'siped-spec06-').'.xlsx';
        (new Xlsx($ss))->save($path);

        $this->post('/api/importacoes/acoes-extensivas/commit', [
            'arquivo' => new UploadedFile($path, 'acoes.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
        ])->assertOk();

        $comSetor = AcaoExtensiva::where('assunto', 'Ação com setor')->firstOrFail();
        $this->assertSame(['Alta', 'DEP'], [$comSetor->priorizacao, $comSetor->setor_atual]);
        $resolvida = AcaoExtensiva::where('assunto', 'Ação resolvida')->firstOrFail();
        $this->assertNull($resolvida->priorizacao);
        $this->assertSame('Resolvido', $resolvida->situacao_legada);
        $this->assertSame('Concluída', $resolvida->status);
        $this->assertNull($resolvida->setor_atual);
    }

    // ---------- Eventos ----------

    private function evento(array $extra = []): array
    {
        return array_merge([
            'nome' => 'Semana Pedagógica', 'ano' => '2026', 'data' => '2026-11-10', 'unidade' => 'Asa Norte',
            'eixo' => 'Gestão e Moda', 'quantidade_pessoas' => 50, 'equipe' => 'CPED', 'possui_acao_extensiva' => 'Não',
            'status' => 'Planejado', 'observacao' => 'Obs', 'processo_sei' => '0001.987654/2026-01', 'tipo_evento' => 'Palestra',
        ], $extra);
    }

    public function test_evento_exige_processo_sei_valido_e_tipo_controlado(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->postJson('/api/eventos', $this->evento(['processo_sei' => '']))
            ->assertStatus(422)->assertJsonValidationErrors(['processo_sei']);
        $this->postJson('/api/eventos', $this->evento(['processo_sei' => 'abc!@#']))
            ->assertStatus(422)->assertJsonValidationErrors(['processo_sei']);
        $this->postJson('/api/eventos', $this->evento(['tipo_evento' => '']))
            ->assertStatus(422)->assertJsonValidationErrors(['tipo_evento']);

        $this->postJson('/api/eventos', $this->evento(['tipo_evento' => '  PALESTRA ']))->assertCreated()
            ->assertJsonPath('evento.tipo_evento', 'Palestra')
            ->assertJsonPath('evento.processo_sei', '0001.987654/2026-01');
        $this->postJson('/api/eventos', $this->evento(['nome' => 'Outro', 'tipo_evento' => 'mostra cultural']))->assertCreated()
            ->assertJsonPath('evento.tipo_evento', 'Mostra cultural');

        $tipos = $this->getJson('/api/eventos')->assertOk()->json('meta.tipos_evento');
        $this->assertContains('Mostra cultural', $tipos);
        $this->assertSame(1, collect($tipos)->filter(fn ($t) => mb_strtolower($t) === 'palestra')->count());
        $this->getJson('/api/eventos?busca=987654')->assertJsonPath('meta.total', 2);
        $this->assertSame(2, Evento::whereNotNull('processo_sei')->count());
    }
}
