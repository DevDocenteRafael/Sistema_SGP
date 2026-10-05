<?php

namespace Tests\Feature;

use App\Models\Curso;
use App\Models\Documento;
use App\Models\Evento;
use App\Models\ImportacaoHistorico;
use App\Models\Resolucao;
use App\Models\ResolucaoHistorico;
use App\Models\TermoReferencia;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Critérios de aceite da SPEC 01 V2 — Prazos, Semáforo, Importações e PDF.
 */
class Spec01PrazosImportacoesPdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-05 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function editor(): Usuario
    {
        return Usuario::create([
            'nome' => 'Editor Spec 01',
            'email' => 'editor-spec01@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678931',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'DF',
            'area' => 'Portfolio',
            'telefone' => '11999990031',
        ]);
    }

    private function resolucao(string $numero, string $fim): Resolucao
    {
        return Resolucao::create([
            'numero' => $numero,
            'curso_relacionado' => 'Curso '.$numero,
            'categoria' => 'Normativa',
            'resumo' => 'Resumo',
            'relator' => 'Relator',
            'setor' => 'CPED',
            'data_inicio_vigencia' => Carbon::parse($fim)->subYears(5)->toDateString(),
            'data_fim_vigencia' => $fim,
            'status' => 'vigente',
        ]);
    }

    // ---------- Semáforo de Resoluções ----------

    public function test_resolucoes_oferecem_somente_vigente_atencao_e_vencida(): void
    {
        $this->actingAs($this->editor(), 'sanctum');
        $this->resolucao('R-VIG', '2028-01-01');
        $this->resolucao('R-ATE-PROXIMA', '2026-10-20'); // faltam 15 dias: antes era "crítico"
        $this->resolucao('R-ATE', '2027-02-01');
        $this->resolucao('R-VEN', '2026-09-01');

        $lista = $this->getJson('/api/resolucoes')->assertOk();
        $this->assertSame(['vigente', 'atencao', 'vencida'], $lista->json('meta.status'));
        $this->assertSame(['no_prazo' => 1, 'atencao' => 2, 'vencidos' => 1], $lista->json('meta.contagens'));

        $porNumero = collect($lista->json('data'))->keyBy('numero');
        $this->assertSame('amarelo', $porNumero['R-ATE-PROXIMA']['semaforo']);
        $this->assertSame('atencao', $porNumero['R-ATE-PROXIMA']['status']);
        $this->assertSame('vermelho', $porNumero['R-VEN']['semaforo']);
        $this->assertSame('verde', $porNumero['R-VIG']['semaforo']);
    }

    public function test_filtro_de_prazo_de_resolucoes_e_calculado_no_servidor(): void
    {
        $this->actingAs($this->editor(), 'sanctum');
        $this->resolucao('R-VIG', '2028-01-01');
        $this->resolucao('R-ATE', '2026-10-20');
        $this->resolucao('R-VEN', '2026-09-01');

        $this->getJson('/api/resolucoes?prazo=atencao')->assertOk()
            ->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.numero', 'R-ATE');
        $this->getJson('/api/resolucoes?prazo=vencida')->assertOk()
            ->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.numero', 'R-VEN');
        $this->getJson('/api/resolucoes?prazo=vigente')->assertOk()
            ->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.numero', 'R-VIG');
    }

    public function test_status_legados_sao_normalizados_e_preservados_no_historico(): void
    {
        $concluida = $this->resolucao('R-LEGADA-CONCLUIDA', '2026-09-01');
        $concluida->forceFill(['status' => 'concluida'])->save();
        $critica = $this->resolucao('R-LEGADA-CRITICA', '2026-10-20');
        $critica->forceFill(['status' => 'critico'])->save();

        $migration = require database_path('migrations/2026_10_05_100000_normalize_resolucoes_semaforo_status.php');
        $migration->up();

        $this->assertSame('vencida', $concluida->fresh()->status);
        $this->assertSame('atencao', $critica->fresh()->status);
        $this->assertDatabaseHas('resolucao_historicos', [
            'resolucao_id' => $concluida->id,
            'evento' => 'Status legado normalizado',
            'status_anterior' => 'concluida',
            'status_novo' => 'vencida',
        ]);
        $this->assertSame(2, Resolucao::count());
        $this->assertSame(2, ResolucaoHistorico::where('evento', 'Status legado normalizado')->count());
    }

    // ---------- Termos de Referência / Ata ----------

    private function payloadTr(array $extra = []): array
    {
        return array_merge([
            'nome' => 'TR Spec 01',
            'eixo' => 'Saúde',
            'processo_sei' => '2026.00.00001-0',
            'prazo_deadline' => '2027-06-01',
            'status' => 'Em Andamento',
            'observacao' => 'Observação.',
            'data_inicio' => '2026-03-01',
            'data_fim' => '2026-11-01',
        ], $extra);
    }

    public function test_campos_da_ata_persistem_e_dirigem_o_semaforo(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $criado = $this->postJson('/api/termos-referencia', $this->payloadTr([
            'numero_tr' => 'TR-12/2026',
            'numero_ata' => 'ATA-77/2026',
            'data_vencimento_ata' => '2026-10-25',
            'ata_renovada' => true,
        ]))->assertCreated();

        $criado->assertJsonPath('termo.numero_tr', 'TR-12/2026')
            ->assertJsonPath('termo.numero_ata', 'ATA-77/2026')
            ->assertJsonPath('termo.data_vencimento_ata', '2026-10-25')
            ->assertJsonPath('termo.ata_renovada', true)
            ->assertJsonPath('termo.origem_prazo', 'ata')
            // Faltam 20 dias para o vencimento da Ata: amarelo (antes seria "crítico").
            ->assertJsonPath('termo.status_prazo', 'atencao')
            ->assertJsonPath('termo.semaforo', 'amarelo');

        $id = $criado->json('termo.id');
        $this->putJson('/api/termos-referencia/'.$id, $this->payloadTr([
            'numero_ata' => 'ATA-77/2026',
            'data_vencimento_ata' => '2026-09-30',
            'ata_renovada' => false,
        ]))->assertOk()
            ->assertJsonPath('termo.semaforo', 'vermelho')
            ->assertJsonPath('termo.ata_renovada', false);

        $this->assertDatabaseHas('termos_referencia_historicos', [
            'termo_referencia_id' => $id,
            'acao' => 'Dados da Ata alterados',
        ]);

        $this->getJson('/api/termos-referencia?busca=ATA-77')->assertOk()->assertJsonPath('meta.total', 1);
        $this->getJson('/api/termos-referencia?prazo=vencido')->assertOk()->assertJsonPath('meta.total', 1);
        $this->getJson('/api/termos-referencia?prazo=atencao')->assertOk()->assertJsonPath('meta.total', 0);
    }

    public function test_sem_ata_o_semaforo_usa_o_prazo_do_tr_com_tres_estados(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $proximo = $this->postJson('/api/termos-referencia', $this->payloadTr(['prazo_deadline' => '2026-10-10']))
            ->assertCreated();
        $proximo->assertJsonPath('termo.origem_prazo', 'tr')->assertJsonPath('termo.semaforo', 'amarelo');

        $lista = $this->getJson('/api/termos-referencia')->assertOk();
        $this->assertSame(['no_prazo' => 0, 'atencao' => 1, 'vencidos' => 0], $lista->json('meta.contagens'));
    }

    // ---------- Importação parcial ----------

    /**
     * @param  list<array<int, string|null>>  $linhas
     */
    private function xlsxCursos(array $linhas): UploadedFile
    {
        $ss = new Spreadsheet;
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Saúde');
        $headers = ['Status SIG', 'Segmento', 'Modalidade', 'Título - Nome do Curso', 'CH', 'Cód. SIG'];
        foreach ($headers as $i => $header) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1).'1', $header);
        }
        foreach ($linhas as $r => $valores) {
            foreach ($valores as $c => $valor) {
                if ($valor !== null) {
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($c + 1).($r + 2), $valor);
                }
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'siped-spec01-').'.xlsx';
        (new Xlsx($ss))->save($path);

        return new UploadedFile($path, 'cursos-spec01.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    private function linhasParciais(): array
    {
        return [
            ['ATIVO', 'Saúde', 'Qualificação Profissional', 'Curso completo o bastante', '40', 'SIG-P-1'],
            ['ATIVO', 'Saúde', null, 'Curso sem modalidade nem CH', null, null],
            ['ATIVO', 'Saúde', 'Qualificação Profissional', null, '20', 'SIG-SEM-TITULO'],
        ];
    }

    public function test_xlsx_incompleto_importa_linhas_identificaveis_e_registra_historico(): void
    {
        Storage::fake('local');
        $this->actingAs($this->editor(), 'sanctum');
        $existente = Curso::create(['titulo' => 'Curso que já existia', 'status' => 'ATIVO', 'eixo' => 'Gestão e Moda']);

        $preview = $this->post('/api/importacoes/cursos/preview', ['arquivo' => $this->xlsxCursos($this->linhasParciais())])
            ->assertOk();
        $this->assertSame(2, $preview->json('incompletos'));
        $this->assertTrue(collect($preview->json('colunas_preview'))->contains('key', 'pendencias'));

        $commit = $this->post('/api/importacoes/cursos/commit', ['arquivo' => $this->xlsxCursos($this->linhasParciais())])
            ->assertOk();
        $commit->assertJsonPath('resumo_acoes.novo', 2)->assertJsonPath('resumo_acoes.incompleto', 2);

        // Linha sem título (identidade mínima) foi ignorada sem derrubar o arquivo.
        $this->assertDatabaseHas('cursos', ['titulo' => 'Curso sem modalidade nem CH', 'modalidade' => null]);
        $this->assertDatabaseMissing('cursos', ['codigo_sig' => 'SIG-SEM-TITULO']);
        // Nenhum registro existente apagado.
        $this->assertDatabaseHas('cursos', ['id' => $existente->id]);
        $this->assertSame(3, Curso::count());

        $historico = ImportacaoHistorico::query()->findOrFail($commit->json('historico_id'));
        $this->assertSame('planilha', $historico->tipo);
        $this->assertSame('concluida', $historico->situacao);
        $this->assertSame(2, $historico->novos);
        $this->assertSame(2, $historico->incompletos);
        $this->assertSame('cursos-spec01.xlsx', $historico->arquivo_nome);
        Storage::disk('local')->assertExists($historico->arquivo_path);

        $detalhe = $this->getJson('/api/importacoes/historico/'.$historico->id)->assertOk();
        $incompletos = collect($detalhe->json('data.detalhes.incompletos'));
        $this->assertCount(2, $incompletos);
        $semModalidade = $incompletos->firstWhere('rotulo', 'Curso sem modalidade nem CH');
        $this->assertNotNull($semModalidade['id']);
        $this->assertContains('Modalidade', $semModalidade['campos']);

        $this->getJson('/api/importacoes/historico')->assertOk()->assertJsonPath('data.0.id', $historico->id);
    }

    public function test_arquivo_ilegivel_e_bloqueado_e_registrado_sem_alterar_dados(): void
    {
        Storage::fake('local');
        $this->actingAs($this->editor(), 'sanctum');
        Curso::create(['titulo' => 'Intocado', 'status' => 'ATIVO', 'eixo' => 'Gestão e Moda']);

        $ss = new Spreadsheet;
        $ss->getActiveSheet()->setTitle('Aba qualquer');
        $path = tempnam(sys_get_temp_dir(), 'siped-spec01-').'.xlsx';
        (new Xlsx($ss))->save($path);
        $arquivo = new UploadedFile($path, 'sem-aba.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $this->post('/api/importacoes/cursos/commit', ['arquivo' => $arquivo])->assertStatus(422);

        $this->assertSame(1, Curso::count());
        $this->assertDatabaseHas('importacao_historicos', ['modulo' => 'cursos', 'situacao' => 'bloqueada']);
    }

    // ---------- PDF como documento ----------

    private function pdf(string $nome = 'oficio-sei.pdf'): UploadedFile
    {
        $conteudo = "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\ntrailer << /Root 1 0 R >>\n%%EOF\n";
        $path = tempnam(sys_get_temp_dir(), 'siped-pdf-');
        file_put_contents($path, $conteudo);

        return new UploadedFile($path, $nome, 'application/pdf', null, true);
    }

    public function test_pdf_nao_e_interpretado_como_planilha(): void
    {
        $this->actingAs($this->editor(), 'sanctum');
        $antes = Curso::count();

        $this->post('/api/importacoes/cursos/preview', ['arquivo' => $this->pdf()])
            ->assertStatus(422)->assertJsonPath('tipo', 'documento');
        $this->post('/api/importacoes/cursos/commit', ['arquivo' => $this->pdf()])
            ->assertStatus(422)->assertJsonPath('tipo', 'documento');

        $this->assertSame($antes, Curso::count());
    }

    public function test_pdf_e_guardado_com_seguranca_e_vinculado_ao_registro(): void
    {
        Storage::fake('local');
        $this->actingAs($this->editor(), 'sanctum');
        $evento = Evento::create(['nome' => 'Feira de Profissões', 'status' => 'Planejado']);
        $totalEventos = Evento::count();

        $resposta = $this->post('/api/documentos', [
            'arquivo' => $this->pdf(),
            'modulo' => 'eventos',
            'registro_id' => $evento->id,
            'processo_sei' => '0001.000001/2026-01',
        ])->assertCreated();

        $resposta->assertJsonPath('documento.registro_id', $evento->id)
            ->assertJsonPath('documento.registro_titulo', 'Feira de Profissões')
            ->assertJsonPath('documento.titulo', 'oficio-sei')
            ->assertJsonMissingPath('documento.arquivo_path');

        $documento = Documento::query()->firstOrFail();
        $this->assertStringStartsWith('documentos/eventos/', $documento->arquivo_path);
        Storage::disk('local')->assertExists($documento->arquivo_path);
        $this->assertDatabaseHas('importacao_historicos', ['tipo' => 'documento', 'modulo' => 'eventos']);
        // Nenhum registro criado a partir do PDF.
        $this->assertSame($totalEventos, Evento::count());

        $this->getJson('/api/documentos?modulo=eventos&registro_id='.$evento->id)
            ->assertOk()->assertJsonCount(1, 'data');
        $this->get('/api/documentos/'.$documento->id.'/arquivo')->assertOk();
    }

    public function test_pdf_sem_registro_exige_metadados_e_pode_ser_vinculado_depois(): void
    {
        Storage::fake('local');
        $this->actingAs($this->editor(), 'sanctum');

        $this->post('/api/documentos', ['arquivo' => $this->pdf(), 'modulo' => 'resolucoes'], ['Accept' => 'application/json'])
            ->assertStatus(422);

        $criado = $this->post('/api/documentos', [
            'arquivo' => $this->pdf(),
            'modulo' => 'resolucoes',
            'titulo' => 'Resolução ainda não cadastrada',
        ], ['Accept' => 'application/json'])->assertCreated();
        $this->assertNull($criado->json('documento.registro_id'));

        $resolucao = $this->resolucao('R-PDF', '2028-01-01');
        $this->putJson('/api/documentos/'.$criado->json('documento.id'), ['registro_id' => $resolucao->id])
            ->assertOk()->assertJsonPath('documento.registro_id', $resolucao->id);
    }

    public function test_arquivo_que_nao_e_pdf_de_verdade_e_recusado(): void
    {
        Storage::fake('local');
        $this->actingAs($this->editor(), 'sanctum');
        $evento = Evento::create(['nome' => 'Evento', 'status' => 'Planejado']);

        $path = tempnam(sys_get_temp_dir(), 'siped-falso-');
        file_put_contents($path, 'isto não é um pdf');
        $falso = new UploadedFile($path, 'falso.pdf', 'application/pdf', null, true);

        $this->post('/api/documentos', ['arquivo' => $falso, 'modulo' => 'eventos', 'registro_id' => $evento->id], ['Accept' => 'application/json'])
            ->assertStatus(422);
        $this->assertSame(0, Documento::count());
    }
}
