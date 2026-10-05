<?php

namespace Tests\Feature;

use App\Models\Curso;
use App\Models\PortfolioCiclo;
use App\Models\RegiaoAdministrativa;
use App\Models\UnidadeOferta;
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

    private function payloadValido(array $sobrescreve = []): array
    {
        $ciclo = PortfolioCiclo::query()->first() ?? PortfolioCiclo::create(['nome' => '2025-2026', 'atual' => true]);
        $regiao = RegiaoAdministrativa::query()->firstOrCreate(['nome' => 'Asa Norte'], ['ativo' => true]);
        UnidadeOferta::query()->firstOrCreate(
            ['nome' => 'Asa Norte'],
            ['tipo' => UnidadeOferta::TIPO_UNIDADE, 'ativo' => true, 'regiao_administrativa_id' => $regiao->id],
        );

        return array_merge([
            'ciclo_id' => $ciclo->id,
            'titulo' => 'Curso cadastrado no SIPED',
            'eixo' => 'Gestão e Moda',
            'segmento' => 'Gestão e Comércio',
            'programa' => '60+',
            'modalidade' => 'Qualificação Profissional',
            'status' => 'ATIVO',
            'codigo_sig' => 'SIG-ORIGEM-001',
            'codigo_dn' => 'DN-ORIGEM-001',
            'carga_horaria' => '40',
            'turmas' => '1',
            'codigo_processo' => 'PROC-ORIGEM-001',
            'alunos' => '10',
            'instrutor' => 'Instrutor Origem',
            'descricao' => 'Cadastro local.',
            'identificacao' => '2026',
            'ultima_revisao' => '2026',
            'processo_sei' => '123.456/2026-01',
            'data_inicio' => '2026-01-01',
            'data_fim' => '2026-12-31',
            'unidade' => 'Asa Norte',
            'unidades_oferta' => ['Asa Norte'],
            'observacoes' => 'Observação de origem.',
            'valores' => '0',
            'compativel_bolsa' => 'NÃO',
            'comercial' => 'NÃO',
            'pcn' => 'PCN.',
            'pcr' => 'PCR.',
        ], $sobrescreve);
    }

    public function test_cadastro_local_expoe_origem_e_permite_crud(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $payload = $this->payloadValido();
        $create = $this->postJson('/api/cursos', $payload);
        $create->assertCreated();
        $id = $create->json('curso.id');
        $this->assertNotNull($id);

        $this->getJson('/api/cursos/'.$id)
            ->assertOk()
            ->assertJsonPath('curso.titulo', 'Curso cadastrado no SIPED')
            ->assertJsonPath('curso.origem.source_type', 'local');

        $this->putJson('/api/cursos/'.$id, [
            ...$payload,
            'titulo' => 'Curso alterado',
            'status' => 'INATIVO',
        ])->assertOk()
            ->assertJsonPath('curso.titulo', 'Curso alterado');

        $this->deleteJson('/api/cursos/'.$id)->assertOk();
        $this->assertSoftDeleted('cursos', ['id' => $id]);
    }

    public function test_registro_de_seeder_continua_identificado_e_pode_ser_editado(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $payload = $this->payloadValido([
            'titulo' => 'Curso da massa',
            'codigo_sig' => 'SIG-SEEDER-001',
            'source_type' => 'seeder',
        ]);
        $curso = Curso::create($payload);

        $this->getJson('/api/cursos/'.$curso->id)
            ->assertOk()
            ->assertJsonPath('curso.origem.source_type', 'seeder');

        $this->putJson('/api/cursos/'.$curso->id, [
            ...$payload,
            'titulo' => 'Curso da massa revisado',
        ])->assertOk()
            ->assertJsonPath('curso.titulo', 'Curso da massa revisado');
    }
}
