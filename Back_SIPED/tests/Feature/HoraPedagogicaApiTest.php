<?php

namespace Tests\Feature;

use App\Models\HoraPedagogica;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HoraPedagogicaApiTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): Usuario
    {
        return Usuario::create([
            'nome' => 'Editor Horas',
            'email' => 'editor-horas@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678003',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'Asa Norte',
            'area' => 'Portfólio',
            'telefone' => '61999990003',
        ]);
    }

    public function test_can_list_create_show_update_and_delete_hora_pedagogica(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $this->getJson('/api/horas-pedagogicas')
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['total', 'total_geral', 'total_ativos', 'eixos', 'segmentos', 'status', 'anos'],
            ]);

        $payload = [
            'matricula' => '5041',
            'pessoa' => 'Ana Teste',
            'segmento' => 'Gastronomia',
            'eixo' => 'Gastronomia',
            'processo_sei' => '2026.000022222-22',
            'ano' => 2026,
            'motivo' => 'Planejamento pedagógico',
            'status' => 'Pendente',
            'ativo' => true,
            'observacao' => 'Registro de teste',
        ];

        $create = $this->postJson('/api/horas-pedagogicas', $payload);
        $create->assertCreated();
        $create->assertJsonPath('horaPedagogica.pessoa', 'Ana Teste');
        $create->assertJsonPath('horaPedagogica.ativo', true);

        $id = $create->json('horaPedagogica.id');

        $this->getJson("/api/horas-pedagogicas/{$id}")
            ->assertOk()
            ->assertJsonPath('horaPedagogica.matricula', '5041');

        $this->putJson("/api/horas-pedagogicas/{$id}", [
            ...$payload,
            'status' => 'Concluída',
            'ativo' => false,
        ])
            ->assertOk()
            ->assertJsonPath('horaPedagogica.status', 'Concluída')
            ->assertJsonPath('horaPedagogica.ativo', false);

        $this->deleteJson("/api/horas-pedagogicas/{$id}")->assertOk();
        $this->assertDatabaseMissing('hora_pedagogicas', ['id' => $id]);
    }

    public function test_filters_horas_by_ano_and_ativo(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        HoraPedagogica::create([
            'matricula' => '1001',
            'pessoa' => 'Ativa',
            'segmento' => 'Gastronomia',
            'eixo' => 'Gastronomia',
            'processo_sei' => '2026.1',
            'ano' => 2026,
            'motivo' => 'Motivo A',
            'status' => 'Pendente',
            'ativo' => true,
        ]);
        HoraPedagogica::create([
            'matricula' => '1002',
            'pessoa' => 'Inativa',
            'segmento' => 'Gestão e Moda',
            'eixo' => 'Gestão e Moda',
            'processo_sei' => '2026.2',
            'ano' => 2025,
            'motivo' => 'Motivo B',
            'status' => 'Cancelada',
            'ativo' => false,
        ]);

        $filtered = $this->getJson('/api/horas-pedagogicas?ano=2026&ativo=true');
        $filtered->assertOk();
        $filtered->assertJsonPath('meta.total', 1);
        $filtered->assertJsonPath('data.0.pessoa', 'Ativa');
    }
}
