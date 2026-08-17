<?php

namespace Tests\Feature;

use App\Models\AcaoExtensiva;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ImportacaoAcaoExtensivaTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): Usuario
    {
        return Usuario::create([
            'nome' => 'Editor Importacao',
            'email' => 'editor-import@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678911',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'DF',
            'area' => 'Portfolio',
            'telefone' => '11999999991',
        ]);
    }

    private function consultor(): Usuario
    {
        return Usuario::create([
            'nome' => 'Consultor Importacao',
            'email' => 'consultor-import@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678912',
            'perfil' => Usuario::PERFIL_CONSULTOR,
            'status' => true,
            'unidade' => 'DF',
            'area' => 'Portfolio',
            'telefone' => '11999999992',
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

    public function test_preview_ok_as_editor(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $response = $this->post('/api/importacoes/acoes-extensivas/preview', [
            'arquivo' => $this->uploadedFixture('acoes-extensivas-sample.xlsx'),
        ]);

        $response->assertOk();
        $response->assertJsonPath('total', 2);
        $response->assertJsonPath('linhas.0.assunto', 'Sabores Regionais');
        $response->assertJsonPath('linhas.0.numero_processo_sei', '2026.000001381-46');
        $response->assertJsonPath('linhas.0.objetivo', 'Objetivo teste');
        $response->assertJsonPath('linhas.0.ultima_atualizacao', '2026-05-06');
        $response->assertJsonPath('linhas.1.assunto', 'Cafezinho');
        $response->assertJsonPath('linhas.1.ultima_atualizacao', '2026-05-06');
    }

    public function test_commit_replaces_data(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        AcaoExtensiva::create([
            'priorizacao' => 'Baixa',
            'atribuido' => 'antigo.user',
            'eixo' => 'Antigo',
            'numero_processo_sei' => '2020.000000001-00',
            'tipo' => 'Ação Extensiva',
            'assunto' => 'Registro antigo',
            'objetivo' => null,
            'status' => 'NC',
            'ultima_atualizacao' => '2020-01-01',
        ]);

        $this->assertDatabaseCount('acao_extensivas', 1);

        $response = $this->post('/api/importacoes/acoes-extensivas/commit', [
            'arquivo' => $this->uploadedFixture('acoes-extensivas-sample.xlsx'),
        ]);

        $response->assertOk();
        $response->assertJsonPath('importados', 2);
        $response->assertJsonPath('backup.total', 1);
        $this->assertNotEmpty($response->json('backup.path'));
        $this->assertTrue(
            \Illuminate\Support\Facades\Storage::disk('local')->exists($response->json('backup.path'))
        );
        $backupJson = json_decode(
            \Illuminate\Support\Facades\Storage::disk('local')->get($response->json('backup.path')),
            true
        );
        $this->assertSame('Registro antigo', $backupJson['registros'][0]['assunto'] ?? null);
        $this->assertDatabaseCount('acao_extensivas', 2);
        $this->assertDatabaseMissing('acao_extensivas', ['assunto' => 'Registro antigo']);
        $this->assertDatabaseHas('acao_extensivas', [
            'assunto' => 'Sabores Regionais',
            'atribuido' => 'ana.5041',
            'status' => 'CPED',
        ]);
        $this->assertDatabaseHas('acao_extensivas', [
            'assunto' => 'Cafezinho',
            'atribuido' => 'barbara.6003',
            'status' => 'DEP',
        ]);
    }

    public function test_missing_sheet_returns_422(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $response = $this->post('/api/importacoes/acoes-extensivas/preview', [
            'arquivo' => $this->uploadedFixture('sem-aba-acoes.xlsx'),
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Aba "Ações extensivas" não encontrada no arquivo. Envie uma planilha que contenha essa aba.']);
    }

    public function test_consultor_gets_403(): void
    {
        $this->actingAs($this->consultor(), 'sanctum');

        $response = $this->post('/api/importacoes/acoes-extensivas/preview', [
            'arquivo' => $this->uploadedFixture('acoes-extensivas-sample.xlsx'),
        ]);

        $response->assertForbidden();
    }

    public function test_guest_gets_401(): void
    {
        $response = $this->postJson('/api/importacoes/acoes-extensivas/preview', []);

        $response->assertUnauthorized();
    }
}