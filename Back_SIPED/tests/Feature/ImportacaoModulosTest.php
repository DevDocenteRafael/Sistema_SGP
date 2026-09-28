<?php

namespace Tests\Feature;

use App\Models\Curso;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImportacaoModulosTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): Usuario
    {
        return Usuario::create([
            'nome' => 'Editor Importacao Modulos',
            'email' => 'editor-import-modulos@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678921',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'DF',
            'area' => 'Portfolio',
            'telefone' => '11999999993',
        ]);
    }

    private function uploadedFixture(string $filename): UploadedFile
    {
        $path = base_path('tests/fixtures/'.$filename);

        return new UploadedFile(
            $path,
            $filename,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }

    public function test_catalogo_lista_modulos(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $response = $this->getJson('/api/importacoes');

        $response->assertOk();
        $keys = collect($response->json('data'))->pluck('key')->all();
        $this->assertContains('cursos', $keys);
        $this->assertContains('acoes-extensivas', $keys);
        $this->assertContains('eventos', $keys);
        $this->assertCount(8, $keys);
    }

    public function test_import_cursos(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        Curso::create(['titulo' => 'Antigo', 'status' => 'ATIVO']);

        $preview = $this->post('/api/importacoes/cursos/preview', [
            'arquivo' => $this->uploadedFixture('cursos-sample.xlsx'),
        ]);
        $preview->assertOk();
        $preview->assertJsonPath('total', 3);
        // eixo = nome da aba, não o Segmento interno
        $preview->assertJsonPath('linhas.0.eixo', 'Ambiente e Saúde');
        $preview->assertJsonPath('linhas.2.eixo', 'Gestão e Moda');

        $commit = $this->post('/api/importacoes/cursos/commit', [
            'arquivo' => $this->uploadedFixture('cursos-sample.xlsx'),
        ]);
        $commit->assertOk();
        $this->assertDatabaseCount('cursos', 4);
        $this->assertDatabaseHas('cursos', ['titulo' => 'Antigo']);
        $this->assertDatabaseHas('cursos', [
            'titulo' => 'Cuidador de Idosos',
            'codigo_sig' => 'SIG-001',
            'eixo' => 'Ambiente e Saúde',
        ]);
    }

    public function test_import_cursos_sem_status_usa_ativo(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->post('/api/importacoes/cursos/commit', [
            'arquivo' => $this->uploadedFixture('cursos-sem-status.xlsx'),
        ])->assertOk()->assertJsonPath('importados', 1);

        $this->assertDatabaseHas('cursos', [
            'titulo' => 'Cuidador de Idoso',
            'status' => 'ATIVO',
        ]);
    }

    public function test_import_plano_de_metas(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $preview = $this->post('/api/importacoes/plano-de-metas/preview', [
            'arquivo' => $this->uploadedFixture('plano-de-metas-sample.xlsx'),
        ]);
        $preview->assertOk();
        $preview->assertJsonPath('total', 2);
        $preview->assertJsonPath('linhas.0.curso', 'Cuidador de Idosos');

        $this->post('/api/importacoes/plano-de-metas/commit', [
            'arquivo' => $this->uploadedFixture('plano-de-metas-sample.xlsx'),
        ])->assertOk();

        $this->assertDatabaseCount('plano_de_metas', 2);
        $this->assertDatabaseHas('plano_de_metas', [
            'curso' => 'Cuidador de Idosos',
            'numero_sei' => '2026.200',
        ]);
    }

    public function test_import_plano_de_metas_com_sig_placeholder_duplicado(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->post('/api/importacoes/plano-de-metas/commit', [
            'arquivo' => $this->uploadedFixture('plano-de-metas-sig-dup.xlsx'),
        ])->assertOk()->assertJsonPath('importados', 2);

        $this->assertDatabaseCount('plano_de_metas', 2);
        $this->assertDatabaseHas('plano_de_metas', ['curso' => 'Curso A', 'codigo_sig' => null]);
        $this->assertDatabaseHas('plano_de_metas', ['curso' => 'Curso B', 'codigo_sig' => null]);
    }

    public function test_import_pcas(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->post('/api/importacoes/pcas/preview', [
            'arquivo' => $this->uploadedFixture('pcas-sample.xlsx'),
        ])->assertOk()->assertJsonPath('total', 2);

        $this->post('/api/importacoes/pcas/commit', [
            'arquivo' => $this->uploadedFixture('pcas-sample.xlsx'),
        ])->assertOk();

        $this->assertDatabaseCount('pcas', 2);
        $this->assertDatabaseHas('pcas', [
            'titulo' => 'Curso PCA A',
            'codigo_sig' => 'PCA-SIG-1',
        ]);
    }

    public function test_import_eixos(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        Curso::create(['titulo' => 'Cuidador', 'status' => 'ATIVO', 'eixo' => 'Ambiente e Saúde']);
        Curso::create(['titulo' => 'Primeiros Socorros', 'status' => 'ATIVO', 'eixo' => 'Ambiente e Saúde']);

        $preview = $this->post('/api/importacoes/eixos/preview', [
            'arquivo' => $this->uploadedFixture('eixos-sample.xlsx'),
        ]);
        $preview->assertOk();
        $preview->assertJsonPath('total', 2);
        $preview->assertJsonPath('linhas.1.eixo', 'Ambiente e Saúde');

        $this->post('/api/importacoes/eixos/commit', [
            'arquivo' => $this->uploadedFixture('eixos-sample.xlsx'),
        ])->assertOk();

        $this->assertDatabaseCount('curso_por_eixos', 2);
        $this->assertDatabaseHas('curso_por_eixos', [
            'curso' => 'Primeiros Socorros',
            'eixo' => 'Ambiente e Saúde',
            'codigo' => 'EIX-2',
        ]);
        $this->assertNotNull(
            \App\Models\CursoPorEixo::query()->where('codigo', 'EIX-2')->value('curso_id')
        );
    }

    public function test_import_eixos_prefere_aba_detalhada(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $preview = $this->post('/api/importacoes/eixos/preview', [
            'arquivo' => $this->uploadedFixture('eixos-duas-abas.xlsx'),
        ]);

        $preview->assertOk();
        $preview->assertJsonPath('total', 3);
        $preview->assertJsonPath('linhas.0.codigo', 'EIX-10');
        $preview->assertJsonPath('linhas.1.curso', 'Cuidador'); // forward-fill
        $preview->assertJsonPath('linhas.1.codigo', 'EIX-11');
    }

    public function test_import_visitas(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->post('/api/importacoes/visitas-tecnicas/commit', [
            'arquivo' => $this->uploadedFixture('visitas-sample.xlsx'),
        ])->assertOk()->assertJsonPath('importados', 2);

        $this->assertDatabaseHas('visita_tecnicas', [
            'unidade' => 'Taguatinga',
            'processo_sei' => '2026.401',
        ]);
    }

    public function test_import_horas(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->post('/api/importacoes/horas-pedagogicas/commit', [
            'arquivo' => $this->uploadedFixture('horas-sample.xlsx'),
        ])->assertOk()->assertJsonPath('importados', 2);

        $this->assertDatabaseHas('hora_pedagogicas', [
            'processo_sei' => '2026.501',
            'eixo' => 'Ambiente e Saúde',
            'pessoa' => 'Carla',
        ]);
    }

    public function test_import_eventos(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $preview = $this->post('/api/importacoes/eventos/preview', [
            'arquivo' => $this->uploadedFixture('eventos-sample.xlsx'),
        ]);
        $preview->assertOk();
        $preview->assertJsonPath('total', 2);
        $preview->assertJsonPath('linhas.0.data', '2026-03-15');

        $this->post('/api/importacoes/eventos/commit', [
            'arquivo' => $this->uploadedFixture('eventos-sample.xlsx'),
        ])->assertOk();

        $this->assertDatabaseCount('eventos', 2);
        $this->assertDatabaseHas('eventos', [
            'nome' => 'Feira de Profissões',
            'unidade' => 'Taguatinga',
        ]);
    }

    public function test_modulo_inexistente_404(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->post('/api/importacoes/nao-existe/preview', [
            'arquivo' => $this->uploadedFixture('acoes-extensivas-sample.xlsx'),
        ])->assertNotFound();
    }

    public function test_import_cursos_canonicaliza_modalidade_em_caixa_alta(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        Curso::create(['titulo' => 'Antigo', 'status' => 'ATIVO']);

        $arquivo = $this->xlsxCursosAba('Gastronomia e Turismo', '  APERFEIÇOAMENTO  ', 'Curso caixa alta');

        $preview = $this->post('/api/importacoes/cursos/preview', ['arquivo' => $arquivo]);
        $preview->assertOk();
        $preview->assertJsonPath('linhas.0.modalidade', 'Aperfeiçoamento');
        $this->assertTrue(collect($preview->json('erros'))->every(fn ($erro) => empty($erro['bloqueante'])));

        $commit = $this->post('/api/importacoes/cursos/commit', [
            'arquivo' => $this->xlsxCursosAba('Gastronomia e Turismo', '  APERFEIÇOAMENTO  ', 'Curso caixa alta'),
        ]);
        $commit->assertOk();
        $this->assertDatabaseHas('cursos', ['titulo' => 'Antigo']);
        $this->assertDatabaseHas('cursos', [
            'titulo' => 'Curso caixa alta',
            'modalidade' => 'Aperfeiçoamento',
            'eixo' => 'Gastronomia e Turismo',
        ]);
    }

    public function test_import_cursos_bloqueia_modalidade_invalida_sem_apagar_dados(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        Curso::create(['titulo' => 'Antigo', 'status' => 'ATIVO', 'eixo' => 'Gestão e Moda']);

        $arquivo = $this->xlsxCursosAba('Gestão e Moda', 'PRESENCIAL', 'Curso inválido');

        $preview = $this->post('/api/importacoes/cursos/preview', ['arquivo' => $arquivo]);
        $preview->assertOk();
        $this->assertNotEmpty($preview->json('erros'));
        $this->assertTrue(collect($preview->json('erros'))->contains(fn ($erro) => ($erro['bloqueante'] ?? false) === true));

        $commit = $this->post('/api/importacoes/cursos/commit', [
            'arquivo' => $this->xlsxCursosAba('Gestão e Moda', 'PRESENCIAL', 'Curso inválido'),
        ]);
        $commit->assertStatus(422);
        $this->assertDatabaseHas('cursos', ['titulo' => 'Antigo']);
        $this->assertDatabaseMissing('cursos', ['titulo' => 'Curso inválido']);
    }

    public function test_import_cursos_e_idempotente_e_preserva_ciclo_anterior(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $cicloAnterior = \App\Models\PortfolioCiclo::create([
            'nome' => '2023-2024',
            'atual' => false,
        ]);
        Curso::create([
            'titulo' => 'Curso do ciclo anterior',
            'status' => 'ATIVO',
            'eixo' => 'Gestão e Moda',
            'ciclo_id' => $cicloAnterior->id,
        ]);

        $this->post('/api/importacoes/cursos/commit', [
            'arquivo' => $this->uploadedFixture('cursos-sample.xlsx'),
        ])->assertOk();

        $this->post('/api/importacoes/cursos/commit', [
            'arquivo' => $this->uploadedFixture('cursos-sample.xlsx'),
        ])->assertOk()->assertJsonPath('resumo_acoes.sem_alteracao', 3);

        $this->assertDatabaseCount('cursos', 4);
        $this->assertDatabaseHas('cursos', [
            'titulo' => 'Curso do ciclo anterior',
            'ciclo_id' => $cicloAnterior->id,
        ]);
    }

    public function test_import_cursos_bloqueia_segmento_desconhecido(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $arquivo = $this->xlsxCursosAba('Saúde', 'Qualificação Profissional', 'Curso segmento', 'Segmento Inventado');

        $preview = $this->post('/api/importacoes/cursos/preview', ['arquivo' => $arquivo]);
        $preview->assertOk();
        $this->assertTrue(collect($preview->json('erros'))->contains(
            fn ($erro) => ($erro['bloqueante'] ?? false) === true && str_contains((string) ($erro['mensagem'] ?? ''), 'Segmento desconhecido')
        ));

        $this->post('/api/importacoes/cursos/commit', [
            'arquivo' => $this->xlsxCursosAba('Saúde', 'Qualificação Profissional', 'Curso segmento', 'Segmento Inventado'),
        ])->assertStatus(422);

        $this->assertDatabaseMissing('cursos', ['titulo' => 'Curso segmento']);
    }

    public function test_import_cursos_programa_sem_eixo_bloqueia_sem_criar_eixo(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $arquivo = $this->xlsxCursosAba('60+', 'Qualificação Profissional', 'Cozinheiro 60+', '60+');

        $preview = $this->post('/api/importacoes/cursos/preview', ['arquivo' => $arquivo]);
        $preview->assertOk();
        $this->assertTrue(collect($preview->json('erros'))->contains(
            fn ($erro) => ($erro['bloqueante'] ?? false) === true
                && str_contains((string) ($erro['mensagem'] ?? ''), 'Não foi possível classificar o registro em um dos 5 Eixos.')
        ));

        $this->post('/api/importacoes/cursos/commit', [
            'arquivo' => $this->xlsxCursosAba('60+', 'Qualificação Profissional', 'Cozinheiro 60+', '60+'),
        ])->assertStatus(422);

        $this->assertDatabaseMissing('cursos', ['titulo' => 'Cozinheiro 60+']);
        $this->assertDatabaseMissing('eixos', ['nome' => '60+']);
    }

    public function test_import_cursos_programa_com_segmento_resolve_eixo_oficial(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $arquivo = $this->xlsxCursosAba('60+', 'Qualificação Profissional', 'Confeiteiro 60+', 'Confeitaria');

        $preview = $this->post('/api/importacoes/cursos/preview', ['arquivo' => $arquivo]);
        $preview->assertOk();
        $preview->assertJsonPath('linhas.0.eixo', 'Gastronomia e Turismo');
        $preview->assertJsonPath('linhas.0.segmento', 'Confeitaria');
        $preview->assertJsonPath('linhas.0.programa', '60+');
        $this->assertTrue(collect($preview->json('erros'))->every(fn ($erro) => empty($erro['bloqueante'])));

        $this->post('/api/importacoes/cursos/commit', [
            'arquivo' => $this->xlsxCursosAba('60+', 'Qualificação Profissional', 'Confeiteiro 60+', 'Confeitaria'),
        ])->assertOk();

        $this->assertDatabaseHas('cursos', [
            'titulo' => 'Confeiteiro 60+',
            'eixo' => 'Gastronomia e Turismo',
            'segmento' => 'Confeitaria',
            'programa' => '60+',
        ]);
        $this->assertDatabaseMissing('eixos', ['nome' => '60+']);
    }

    public function test_import_eixos_persiste_oferta_sem_curso_como_pendencia(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $preview = $this->post('/api/importacoes/eixos/preview', [
            'arquivo' => $this->uploadedFixture('eixos-sample.xlsx'),
        ]);
        $preview->assertOk();
        $this->assertTrue(collect($preview->json('erros'))->contains(
            fn ($erro) => ($erro['bloqueante'] ?? true) === false
                && str_contains((string) ($erro['mensagem'] ?? ''), 'Sem correspondência no catálogo de Cursos')
        ));
        $this->assertGreaterThan(0, (int) $preview->json('resumo_acoes.pendente'));

        $this->post('/api/importacoes/eixos/commit', [
            'arquivo' => $this->uploadedFixture('eixos-sample.xlsx'),
        ])->assertOk();

        $this->assertDatabaseCount('cursos', 0);
        $this->assertDatabaseCount('curso_por_eixos', 2);
        $this->assertDatabaseHas('curso_por_eixos', [
            'curso' => 'Cuidador',
            'curso_id' => null,
        ]);
        $this->assertDatabaseHas('curso_por_eixos', [
            'curso' => 'Primeiros Socorros',
            'curso_id' => null,
        ]);
    }

    private function xlsxCursosAba(string $aba, string $modalidade, string $titulo, ?string $segmento = null): UploadedFile
    {
        $ss = new Spreadsheet;
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle($aba);
        $headers = ['Status SIG', 'Segmento', 'Modalidade', 'Título - Nome do Curso', 'CH', 'Cód. SIG'];
        foreach ($headers as $i => $header) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1).'1', $header);
        }
        $sheet->setCellValue('A2', 'ATIVO');
        $sheet->setCellValue('B2', $segmento ?? $aba);
        $sheet->setCellValue('C2', $modalidade);
        $sheet->setCellValue('D2', $titulo);
        $sheet->setCellValue('E2', '40');
        $sheet->setCellValue('F2', 'SIG-IMP-1');

        $path = tempnam(sys_get_temp_dir(), 'siped-cursos-').'.xlsx';
        (new Xlsx($ss))->save($path);

        return new UploadedFile($path, 'cursos-teste.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
