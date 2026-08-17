<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Models\VisitaTecnica;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class VisitaTecnicaApiTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): Usuario
    {
        return Usuario::create([
            'nome' => 'Editor Visitas',
            'email' => 'editor-visitas@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678002',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'Asa Norte',
            'area' => 'Portfólio',
            'telefone' => '61999990002',
        ]);
    }

    public function test_can_list_create_show_update_and_delete_visita_tecnica(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->getJson('/api/visitas-tecnicas')
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['total', 'total_geral', 'eixos', 'status', 'anos', 'unidades', 'prazos'],
            ]);

        $payload = [
            'unidade' => 'Asa Norte',
            'eixo' => 'Gastronomia',
            'processo_sei' => '2026.000011111-11',
            'data_solicitacao' => '2026-07-01',
            'data_visita_prevista' => '2026-07-10',
            'prazo_limite' => '2026-07-20',
            'status' => 'Pendente',
            'responsavel' => 'Equipe CPED',
            'relatorio' => null,
            'observacao' => 'Visita de teste',
        ];

        $create = $this->postJson('/api/visitas-tecnicas', $payload);
        $create->assertCreated();
        $create->assertJsonPath('visitaTecnica.processo_sei', '2026.000011111-11');

        $id = $create->json('visitaTecnica.id');

        $this->getJson("/api/visitas-tecnicas/{$id}")
            ->assertOk()
            ->assertJsonPath('visitaTecnica.responsavel', 'Equipe CPED');

        $this->putJson("/api/visitas-tecnicas/{$id}", [
            ...$payload,
            'status' => 'Realizada',
            'observacao' => 'Concluída',
        ])
            ->assertOk()
            ->assertJsonPath('visitaTecnica.status', 'Realizada');

        $this->deleteJson("/api/visitas-tecnicas/{$id}")->assertOk();
        $this->assertDatabaseMissing('visita_tecnicas', ['id' => $id]);
    }

    public function test_filters_visitas_by_status(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        VisitaTecnica::create([
            'unidade' => 'Asa Norte',
            'eixo' => 'Gastronomia',
            'processo_sei' => '2026.000000001-01',
            'data_solicitacao' => '2026-07-01',
            'data_visita_prevista' => '2026-07-05',
            'prazo_limite' => '2026-07-15',
            'status' => 'Pendente',
            'responsavel' => 'Ana',
        ]);
        VisitaTecnica::create([
            'unidade' => 'Asa Sul',
            'eixo' => 'Gestão e Moda',
            'processo_sei' => '2026.000000002-02',
            'data_solicitacao' => '2026-07-02',
            'data_visita_prevista' => '2026-07-06',
            'prazo_limite' => '2026-07-16',
            'status' => 'Cancelada',
            'responsavel' => 'Bia',
        ]);

        $filtered = $this->getJson('/api/visitas-tecnicas?status=Pendente');
        $filtered->assertOk();
        $filtered->assertJsonPath('meta.total', 1);
        $filtered->assertJsonPath('data.0.processo_sei', '2026.000000001-01');
    }
}
