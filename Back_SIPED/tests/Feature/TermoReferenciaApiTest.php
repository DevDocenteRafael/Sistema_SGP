<?php

namespace Tests\Feature;

use App\Models\TermoReferencia;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TermoReferenciaApiTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): Usuario
    {
        return Usuario::create([
            'nome' => 'Editor TR',
            'email' => 'editor-tr@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678011',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'Asa Norte',
            'area' => 'Portfólio',
            'telefone' => '61999990011',
        ]);
    }

    public function test_lista_inclui_status_de_tramitacao_fora_da_cped(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $response = $this->getJson('/api/termos-referencia');
        $response->assertOk();
        $this->assertContains(
            'Em tramitação (fora da CPED)',
            $response->json('meta.status')
        );
    }

    public function test_pode_acompanhar_tr_depois_de_sair_da_cped(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $payload = [
            'nome' => 'TR de teste',
            'eixo' => 'Saúde',
            'processo_sei' => '2026.00.00001-0',
            'prazo_deadline' => '2026-12-01',
            'status' => 'Planejamento',
        ];

        $create = $this->postJson('/api/termos-referencia', $payload);
        $create->assertCreated();
        $id = $create->json('termo.id');

        $update = $this->putJson("/api/termos-referencia/{$id}", [
            ...$payload,
            'status' => 'Em tramitação (fora da CPED)',
            'observacao' => 'Encaminhado para outra área',
            'data_inicio' => '2026-03-01',
        ]);

        $update->assertOk();
        $update->assertJsonPath('termo.status', 'Em tramitação (fora da CPED)');

        $show = $this->getJson("/api/termos-referencia/{$id}");
        $show->assertOk();
        $show->assertJsonPath('termo.status', 'Em tramitação (fora da CPED)');

        $acoes = collect($show->json('termo.historicos'))->pluck('acao')->all();
        $this->assertContains('TR em tramitação fora da CPED', $acoes);
    }

    public function test_filtra_por_status_fora_da_cped(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        TermoReferencia::create([
            'nome' => 'TR fora',
            'eixo' => 'Saúde',
            'processo_sei' => '2026.00.00002-0',
            'prazo_deadline' => '2026-11-01',
            'status' => 'Em tramitação (fora da CPED)',
        ]);
        TermoReferencia::create([
            'nome' => 'TR interno',
            'eixo' => 'Saúde',
            'processo_sei' => '2026.00.00003-0',
            'prazo_deadline' => '2026-11-01',
            'status' => 'Em Andamento',
        ]);

        $filtered = $this->getJson('/api/termos-referencia?status='.urlencode('Em tramitação (fora da CPED)'));
        $filtered->assertOk();
        $filtered->assertJsonPath('meta.total', 1);
        $filtered->assertJsonPath('data.0.nome', 'TR fora');
    }
}
