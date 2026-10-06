<?php

namespace Tests\Feature;

use App\Models\Curso;
use App\Models\CursoPorEixo;
use App\Models\PortfolioCiclo;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * SPEC 04 — Cursos/Portfólio: códigos oficiais SIG/SEI, busca e links configuráveis.
 */
class Spec04CursosSigSeiTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): Usuario
    {
        return Usuario::create([
            'nome' => 'Editor Spec 04',
            'email' => 'editor-spec04@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '52998224725',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'Asa Norte',
            'area' => 'Portfólio',
            'telefone' => '61999990404',
        ]);
    }

    // ---------- URLs configuráveis ----------

    public function test_urls_externas_vem_da_configuracao_e_recusam_valores_invalidos(): void
    {
        config([
            'sistemas_externos.sei.base_url' => 'https://sei.exemplo.gov.br/sei/',
            'sistemas_externos.sei.processo_url' => 'https://sei.exemplo.gov.br/processo?n={processo}',
            'sistemas_externos.sig.base_url' => 'javascript:alert(1)',
            'sistemas_externos.sig.curso_url' => 'https://sig.exemplo/sem-marcador',
        ]);
        $this->actingAs($this->editor(), 'sanctum');

        $this->getJson('/api/sistemas-externos')->assertOk()
            ->assertJsonPath('data.sei.base_url', 'https://sei.exemplo.gov.br/sei/')
            ->assertJsonPath('data.sei.processo_url', 'https://sei.exemplo.gov.br/processo?n={processo}')
            ->assertJsonPath('data.sig.base_url', null)
            ->assertJsonPath('data.sig.curso_url', null);

        $links = collect($this->getJson('/api/sistemas-apoio')->assertOk()->json('data'))->keyBy('key');
        $this->assertSame('https://sei.exemplo.gov.br/sei/', $links['sei']['url']);
        $this->assertArrayNotHasKey('sig', $links->all(), 'link inválido não aparece em Sistemas de Apoio');
    }

    public function test_sem_padrao_oficial_do_sig_nao_ha_link_direto(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->getJson('/api/sistemas-externos')->assertOk()
            ->assertJsonPath('data.sig.curso_url', null)
            ->assertJsonPath('data.sei.processo_url', null);
    }

    // ---------- Busca ----------

    public function test_busca_por_curso_sig_sei_e_eixo_inclusive_sem_pontuacao(): void
    {
        $this->actingAs($this->editor(), 'sanctum');
        Curso::create([
            'titulo' => 'Técnico em Enfermagem', 'status' => 'ATIVO', 'eixo' => 'Ambiente e Saúde',
            'codigo_sig' => '2437', 'codigo_dn' => 'DN-77', 'processo_sei' => '0001.123456/2026-01',
        ]);
        Curso::create(['titulo' => 'Barista', 'status' => 'ATIVO', 'eixo' => 'Gastronomia e Turismo', 'codigo_sig' => '9999']);

        foreach (['Enfermagem', '2437', '0001.123456/2026-01', '000112345620260', 'Ambiente e Saúde', 'DN-77'] as $termo) {
            $this->getJson('/api/cursos?busca='.urlencode($termo))->assertOk()
                ->assertJsonPath('meta.total', 1)
                ->assertJsonPath('data.0.titulo', 'Técnico em Enfermagem');
        }
    }

    // ---------- Importação fiel aos códigos oficiais ----------

    private function xlsx(callable $preencher): UploadedFile
    {
        $ss = new Spreadsheet;
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Gestão e Moda');
        $sheet->fromArray([['Status SIG', 'Segmento', 'Modalidade', 'Título - Nome do Curso', 'CH', 'Cód. SIG', 'Cód. DN', 'Processo SEI']]);
        $preencher($sheet);
        $path = tempnam(sys_get_temp_dir(), 'siped-spec04-').'.xlsx';
        (new Xlsx($ss))->save($path);

        return new UploadedFile($path, 'cursos-spec04.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    public function test_importacao_preserva_codigos_oficiais_exatamente(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $arquivo = $this->xlsx(function ($sheet) {
            $sheet->fromArray(['ATIVO', 'Gestão e Comércio', 'Qualificação Profissional', 'Curso com códigos'], null, 'A2');
            $sheet->setCellValue('E2', 40);
            // SIG numérico exibido com zeros à esquerda pelo formato da célula.
            $sheet->setCellValue('F2', 123);
            $sheet->getStyle('F2')->getNumberFormat()->setFormatCode('000000');
            // SEI digitado como número grande (Excel mostraria em notação científica).
            $sheet->setCellValue('G2', 20260000123456);
            $sheet->setCellValueExplicit('H2', '0001.123456/2026-01', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        });

        $this->post('/api/importacoes/cursos/commit', ['arquivo' => $arquivo])->assertOk();

        $this->assertDatabaseHas('cursos', [
            'titulo' => 'Curso com códigos',
            'codigo_sig' => '000123',
            'codigo_dn' => '20260000123456',
            'processo_sei' => '0001.123456/2026-01',
        ]);
    }

    public function test_importacao_registra_divergencia_de_codigo_e_texto_que_nao_e_codigo(): void
    {
        $this->actingAs($this->editor(), 'sanctum');
        Curso::create([
            'titulo' => 'Curso divergente', 'status' => 'ATIVO', 'eixo' => 'Gestão e Moda', 'segmento' => 'Gestão e Comércio',
            'codigo_sig' => 'SIG-ANTIGO', 'ciclo_id' => PortfolioCiclo::atual()?->id,
        ]);

        $arquivo = fn () => $this->xlsx(function ($sheet) {
            $sheet->fromArray(['ATIVO', 'Gestão e Comércio', 'Qualificação Profissional', 'Curso divergente', '40', 'SIG-NOVO'], null, 'A2');
            $sheet->fromArray([
                'ATIVO', 'Gestão e Comércio', 'Qualificação Profissional', 'Curso com observação no código', '20',
                str_repeat('Observação que caiu na coluna de código do SIG. ', 3),
            ], null, 'A3');
        });

        $preview = $this->post('/api/importacoes/cursos/preview', ['arquivo' => $arquivo()])->assertOk();
        $mensagens = collect($preview->json('erros'))->pluck('mensagem');
        $this->assertTrue($mensagens->contains(fn ($m) => str_contains($m, 'cadastro "SIG-ANTIGO", planilha "SIG-NOVO"')));
        $this->assertTrue($mensagens->contains(fn ($m) => str_contains($m, 'não parece um código oficial')));
        $this->assertTrue(collect($preview->json('erros'))->every(fn ($e) => empty($e['bloqueante'])));

        $this->post('/api/importacoes/cursos/commit', ['arquivo' => $arquivo()])->assertOk();
        $this->assertDatabaseHas('cursos', ['titulo' => 'Curso divergente', 'codigo_sig' => 'SIG-NOVO']);
        $this->assertDatabaseHas('cursos', ['titulo' => 'Curso com observação no código', 'codigo_sig' => null]);
        $this->assertSame(1, Curso::where('titulo', 'Curso divergente')->count());
    }

    // ---------- Ofertas não inflam a quantidade de Cursos ----------

    public function test_ofertas_operacionais_nao_aumentam_a_quantidade_de_cursos(): void
    {
        $this->actingAs($this->editor(), 'sanctum');
        $curso = Curso::create([
            'titulo' => 'Curso com várias ofertas', 'status' => 'ATIVO', 'eixo' => 'Gestão e Moda',
            'segmento' => 'Gestão e Comércio', 'ciclo_id' => PortfolioCiclo::atual()?->id,
        ]);
        foreach (['OF-1', 'OF-2', 'OF-3'] as $codigo) {
            CursoPorEixo::create(['curso' => $curso->titulo, 'curso_id' => $curso->id, 'eixo' => 'Gestão e Moda', 'segmento' => 'Gestão e Comércio', 'codigo' => $codigo, 'turmas' => '1', 'alunos' => '10']);
        }
        CursoPorEixo::create(['curso' => 'Oferta sem curso no catálogo', 'eixo' => 'Gestão e Moda', 'segmento' => 'Gestão e Comércio', 'codigo' => 'OF-X', 'turmas' => '1', 'alunos' => '5']);

        $card = collect($this->getJson('/api/eixos/resumo')->assertOk()->json('data.eixos'))->firstWhere('nome', 'Gestão e Moda');
        $this->assertSame(1, $card['cursos']);
        $this->getJson('/api/cursos')->assertOk()->assertJsonPath('meta.total', 1);
    }
}
