<?php

namespace Tests\Feature;

use App\Models\Curso;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
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
        $preview->assertJsonPath('linhas.0.eixo', 'Saúde');
        $preview->assertJsonPath('linhas.2.eixo', 'Gestão e Moda');

        $commit = $this->post('/api/importacoes/cursos/commit', [
            'arquivo' => $this->uploadedFixture('cursos-sample.xlsx'),
        ]);
        $commit->assertOk();
        $this->assertDatabaseCount('cursos', 3);
        $this->assertDatabaseMissing('cursos', ['titulo' => 'Antigo']);
        $this->assertDatabaseHas('cursos', [
            'titulo' => 'Cuidador de Idosos',
            'codigo_sig' => 'SIG-001',
            'eixo' => 'Saúde',
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

        $preview = $this->post('/api/importacoes/eixos/preview', [
            'arquivo' => $this->uploadedFixture('eixos-sample.xlsx'),
        ]);
        $preview->assertOk();
        $preview->assertJsonPath('total', 2);
        // forward-fill do eixo
        $preview->assertJsonPath('linhas.1.eixo', 'Saúde');

        $this->post('/api/importacoes/eixos/commit', [
            'arquivo' => $this->uploadedFixture('eixos-sample.xlsx'),
        ])->assertOk();

        $this->assertDatabaseCount('curso_por_eixos', 2);
        $this->assertDatabaseHas('curso_por_eixos', [
            'curso' => 'Primeiros Socorros',
            'eixo' => 'Saúde',
            'codigo' => 'EIX-2',
        ]);
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
            'eixo' => 'Saúde',
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
}
