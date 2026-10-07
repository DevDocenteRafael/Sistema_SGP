<?php

namespace Tests\Feature;

use App\Models\Curso;
use App\Models\PlanoDeMeta;
use App\Models\PortfolioCiclo;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * SPEC 05 — Plano de Metas por área do planejamento e fonte dos indicadores de Eixos.
 */
class Spec05PlanoMetasEixosTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): Usuario
    {
        return Usuario::create([
            'nome' => 'Editor Spec 05',
            'email' => 'editor-spec05@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '52998224725',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'Asa Norte',
            'area' => 'Portfólio',
            'telefone' => '61999990505',
        ]);
    }

    private function payload(string $sufixo, array $extra = []): array
    {
        return array_merge([
            'segmento' => 'Infraestrutura',
            'curso' => 'Meta '.$sufixo,
            'tipo' => 'QUALIFICAÇÃO',
            'numero_sei' => 'SEI-2026-'.$sufixo,
            'codigo_sig' => 'SIG-'.$sufixo,
            'mes_entrega' => 'Janeiro',
            'status' => 'EM ANÁLISE',
            'status_final' => 'PENDENTE',
            'ano' => 2026,
        ], $extra);
    }

    public function test_area_do_planejamento_e_opcional_validada_e_aceita_grafias(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->postJson('/api/plano-de-metas', $this->payload('001'))->assertCreated()
            ->assertJsonPath('planoDeMeta.area_planejamento', null);
        $this->postJson('/api/plano-de-metas', $this->payload('002', ['area_planejamento' => 'planejamento estrategico']))
            ->assertCreated()->assertJsonPath('planoDeMeta.area_planejamento', 'Planejamento Estratégico');
        $this->postJson('/api/plano-de-metas', $this->payload('003', ['area_planejamento' => 'Área inventada']))
            ->assertStatus(422)->assertJsonValidationErrors(['area_planejamento']);
    }

    public function test_visao_geral_abas_por_area_e_contagens(): void
    {
        $this->actingAs($this->editor(), 'sanctum');
        foreach ([['001', 'DN'], ['002', 'DN'], ['003', 'CPED'], ['004', null]] as [$sufixo, $area]) {
            $this->postJson('/api/plano-de-metas', $this->payload($sufixo, ['area_planejamento' => $area]))->assertCreated();
        }

        $geral = $this->getJson('/api/plano-de-metas')->assertOk();
        $geral->assertJsonPath('meta.total', 4)
            ->assertJsonPath('meta.areas', ['Planejamento Estratégico', 'DN', 'DEF', 'CPED'])
            ->assertJsonPath('meta.contagens_area.total', 4)
            ->assertJsonPath('meta.contagens_area.sem_area', 1)
            ->assertJsonPath('meta.contagens_area.por_area.DN', 2)
            ->assertJsonPath('meta.contagens_area.por_area.DEF', 0);
        $this->assertArrayNotHasKey('percentual', $geral->json('meta.contagens_area'));

        $this->getJson('/api/plano-de-metas?area=DN')->assertOk()->assertJsonPath('meta.total', 2);
        $this->getJson('/api/plano-de-metas?area=sem_area')->assertOk()->assertJsonPath('meta.total', 1);
        $this->getJson('/api/plano-de-metas?area=CPED')->assertOk()->assertJsonPath('data.0.curso', 'Meta 003');
    }

    public function test_importacao_traz_a_area_e_avisa_area_desconhecida(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $ss = new Spreadsheet;
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('PLANO DE METAS 2025');
        $sheet->fromArray([
            ['Segmento', 'Curso', 'Tipo', 'Número SEI', 'Código SIG', 'Mês de entrega', 'Status', 'Status final', 'Área do planejamento'],
            ['Gestão', 'Meta DN', 'QUALIFICAÇÃO', 'SEI-IMP-1', 'SIG-IMP-1', 'Março', 'PLANEJADO', 'PENDENTE', 'dn'],
            ['Gestão', 'Meta área estranha', 'QUALIFICAÇÃO', 'SEI-IMP-2', 'SIG-IMP-2', 'Março', 'PLANEJADO', 'PENDENTE', 'Diretoria X'],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'siped-spec05-').'.xlsx';
        (new Xlsx($ss))->save($path);
        $arquivo = fn () => new UploadedFile($path, 'metas.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $preview = $this->post('/api/importacoes/plano-de-metas/preview', ['arquivo' => $arquivo()])->assertOk();
        $this->assertTrue(collect($preview->json('erros'))->contains(fn ($e) => str_contains($e['mensagem'], 'Origem do planejamento não reconhecida: "Diretoria X"')));

        $this->post('/api/importacoes/plano-de-metas/commit', ['arquivo' => $arquivo()])->assertOk();
        $this->assertSame('DN', PlanoDeMeta::where('curso', 'Meta DN')->value('area_planejamento'));
        $this->assertNull(PlanoDeMeta::where('curso', 'Meta área estranha')->value('area_planejamento'));
    }

    public function test_coluna_origem_da_planilha_vira_origem_oficial_sem_perder_texto_livre(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $ss = new Spreadsheet;
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('PLANO DE METAS 2025');
        $sheet->fromArray([
            ['Segmento', 'Curso', 'Tipo', 'Número SEI', 'Código SIG', 'Mês de entrega', 'Status', 'Origem', 'Status final'],
            ['Gestão', 'Meta origem DEF', 'QUALIFICAÇÃO', 'SEI-ORI-1', 'SIG-ORI-1', 'Março', 'PLANEJADO', 'def', 'PENDENTE'],
            ['Gestão', 'Meta origem livre', 'QUALIFICAÇÃO', 'SEI-ORI-2', 'SIG-ORI-2', 'Março', 'PLANEJADO', 'PCA', 'PENDENTE'],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'siped-spec05-').'.xlsx';
        (new Xlsx($ss))->save($path);
        $arquivo = fn () => new UploadedFile($path, 'metas.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $preview = $this->post('/api/importacoes/plano-de-metas/preview', ['arquivo' => $arquivo()])->assertOk();
        $this->assertFalse(collect($preview->json('erros'))->contains(fn ($e) => str_contains($e['mensagem'], 'Origem do planejamento')));

        $this->post('/api/importacoes/plano-de-metas/commit', ['arquivo' => $arquivo()])->assertOk();
        $this->assertSame('DEF', PlanoDeMeta::where('curso', 'Meta origem DEF')->value('area_planejamento'));
        $livre = PlanoDeMeta::where('curso', 'Meta origem livre')->first();
        $this->assertNull($livre->area_planejamento);
        $this->assertSame('PCA', $livre->origem);
    }

    public function test_eixos_informam_a_fonte_e_avisam_quando_nao_ha_importacao(): void
    {
        $this->actingAs($this->editor(), 'sanctum');
        $ciclo = PortfolioCiclo::atual();
        Curso::create(['titulo' => 'Curso manual', 'status' => 'ATIVO', 'eixo' => 'Gestão e Moda', 'ciclo_id' => $ciclo?->id]);

        $resumo = $this->getJson('/api/eixos/resumo')->assertOk();
        $this->assertNull($resumo->json('data.fontes.cursos.ultima_importacao'));
        $this->assertNull($resumo->json('data.fontes.turmas_alunos.ultima_importacao'));
        $this->assertStringContainsString('Catálogo de Cursos', $resumo->json('data.fontes.cursos.descricao'));

        $ss = new Spreadsheet;
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Gestão e Moda');
        $sheet->fromArray([
            ['Status SIG', 'Segmento', 'Modalidade', 'Título - Nome do Curso', 'CH', 'Cód. SIG'],
            ['ATIVO', 'Gestão e Comércio', 'Qualificação Profissional', 'Curso importado', '40', 'SIG-F-1'],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'siped-spec05-').'.xlsx';
        (new Xlsx($ss))->save($path);
        $this->post('/api/importacoes/cursos/commit', [
            'arquivo' => new UploadedFile($path, 'portfolio-oficial.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
        ])->assertOk();

        $depois = $this->getJson('/api/eixos/resumo')->assertOk();
        $this->assertSame('portfolio-oficial.xlsx', $depois->json('data.fontes.cursos.ultima_importacao.arquivo'));
        $this->assertSame('Editor Spec 05', $depois->json('data.fontes.cursos.ultima_importacao.usuario'));
        $this->assertNull($depois->json('data.fontes.turmas_alunos.ultima_importacao'));

        $eixo = collect($depois->json('data.eixos'))->firstWhere('nome', 'Gestão e Moda');
        $this->getJson('/api/eixos/'.$eixo['id'].'/detalhes')->assertOk()
            ->assertJsonPath('data.fontes.cursos.ultima_importacao.arquivo', 'portfolio-oficial.xlsx');
    }
}
