<?php

namespace Tests\Feature;

use App\Models\Ciclo;
use App\Models\Usuario;
use App\Models\VisitaTecnica;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CicloContextoApiTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): Usuario
    {
        return Usuario::create([
            'nome' => 'Editor Ciclo Contexto',
            'email' => 'editor-ciclo-contexto@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678977',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'DF',
            'area' => 'Portfolio',
            'telefone' => '61999990000',
        ]);
    }

    public function test_api_ciclos_lista_e_alias_legado_coincidem(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $novo = $this->getJson('/api/ciclos');
        $legado = $this->getJson('/api/portfolio-ciclos');

        $novo->assertOk();
        $legado->assertOk();
        $this->assertSame($novo->json('data'), $legado->json('data'));
        $this->assertNotNull($novo->json('meta.ciclo_atual_id'));
    }

    public function test_header_de_ciclo_inexistente_retorna_422(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->getJson('/api/visitas-tecnicas', [
            'X-SIPED-Ciclo-Id' => '99999',
        ])->assertStatus(422);
    }

    public function test_visitas_respeitam_header_de_ciclo(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $atual = Ciclo::atual();
        $outro = Ciclo::create([
            'nome' => '2030-2031',
            'atual' => false,
        ]);

        VisitaTecnica::create([
            'ciclo_id' => $atual->id,
            'unidade' => 'Taguatinga',
            'eixo' => 'Ambiente e Saúde',
            'processo_sei' => '0001',
            'data_solicitacao' => '2026-01-10',
            'data_visita_prevista' => '2026-01-20',
            'prazo_limite' => '2026-01-25',
            'status' => 'Pendente',
            'responsavel' => 'Ana',
            'relatorio' => 'Relatório atual',
            'observacao' => 'Obs atual',
        ]);
        VisitaTecnica::create([
            'ciclo_id' => $outro->id,
            'unidade' => 'Asa Norte',
            'eixo' => 'Gestão e Moda',
            'processo_sei' => '0002',
            'data_solicitacao' => '2030-01-10',
            'data_visita_prevista' => '2030-01-20',
            'prazo_limite' => '2030-01-25',
            'status' => 'Pendente',
            'responsavel' => 'Bruno',
            'relatorio' => 'Relatório outro',
            'observacao' => 'Obs outro',
        ]);

        $atualResp = $this->getJson('/api/visitas-tecnicas', [
            'X-SIPED-Ciclo-Id' => (string) $atual->id,
        ]);
        $atualResp->assertOk();
        $this->assertCount(1, $atualResp->json('data'));
        $this->assertSame('0001', $atualResp->json('data.0.processo_sei'));

        $outroResp = $this->getJson('/api/visitas-tecnicas', [
            'X-SIPED-Ciclo-Id' => (string) $outro->id,
        ]);
        $outroResp->assertOk();
        $this->assertCount(1, $outroResp->json('data'));
        $this->assertSame('0002', $outroResp->json('data.0.processo_sei'));
    }

    public function test_ciclo_id_explicito_prevalece_sobre_header(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $atual = Ciclo::atual();
        $outro = Ciclo::create([
            'nome' => '2032-2033',
            'atual' => false,
        ]);

        VisitaTecnica::create([
            'ciclo_id' => $atual->id,
            'unidade' => 'Taguatinga',
            'eixo' => 'Ambiente e Saúde',
            'processo_sei' => '1001',
            'data_solicitacao' => '2026-02-10',
            'data_visita_prevista' => '2026-02-20',
            'prazo_limite' => '2026-02-25',
            'status' => 'Pendente',
            'responsavel' => 'Ana',
            'relatorio' => 'Relatório atual',
            'observacao' => 'Obs atual',
        ]);
        VisitaTecnica::create([
            'ciclo_id' => $outro->id,
            'unidade' => 'Asa Norte',
            'eixo' => 'Gestão e Moda',
            'processo_sei' => '1002',
            'data_solicitacao' => '2032-02-10',
            'data_visita_prevista' => '2032-02-20',
            'prazo_limite' => '2032-02-25',
            'status' => 'Pendente',
            'responsavel' => 'Bruno',
            'relatorio' => 'Relatório outro',
            'observacao' => 'Obs outro',
        ]);

        $response = $this->getJson('/api/visitas-tecnicas?ciclo_id='.$outro->id, [
            'X-SIPED-Ciclo-Id' => (string) $atual->id,
        ]);

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('1002', $response->json('data.0.processo_sei'));
    }
}
