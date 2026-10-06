<?php

namespace Tests\Feature;

use App\Models\ImportacaoHistorico;
use App\Models\Usuario;
use App\Models\VisitaTecnica;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * SPEC 07 — Visitas Técnicas preparadas para virem do SVT.
 */
class Spec07IntegracaoSvtTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'token-de-teste-svt-1234567890';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'svt.token' => self::TOKEN,
            'svt.modo' => 'local',
            'svt.visita_url' => 'https://svt.exemplo/visitas/{id}',
            'sistemas_externos.svt.base_url' => 'https://svt.exemplo/',
        ]);
    }

    private function editor(): Usuario
    {
        return Usuario::create([
            'nome' => 'Editor Spec 07', 'email' => 'editor-spec07@teste.com', 'senha' => Hash::make('senha123'),
            'cpf' => '52998224725', 'perfil' => Usuario::PERFIL_EDITOR, 'status' => true,
            'unidade' => 'Asa Norte', 'area' => 'CPED', 'telefone' => '61999990707',
        ]);
    }

    private function enviar(array $visitas, ?string $token = self::TOKEN)
    {
        return $this->withHeaders(array_filter(['Authorization' => $token ? 'Bearer '.$token : null]))
            ->postJson('/api/integracoes/svt/visitas', ['visitas' => $visitas]);
    }

    private function visita(string $id, array $extra = []): array
    {
        return array_merge([
            'id' => $id, 'unidade' => 'Asa Norte', 'eixo' => 'gestao e moda', 'processo_sei' => '0001.123456/2026-01',
            'data_solicitacao' => '2026-10-01', 'data_visita_prevista' => '2026-10-20', 'prazo_limite' => '2026-10-15',
            'status' => 'em andamento', 'etapa' => 'CPAD', 'responsavel' => 'Coordenação', 'observacao' => 'Visita SVT',
        ], $extra);
    }

    public function test_endpoint_exige_token_e_fica_desligado_sem_configuracao(): void
    {
        $this->enviar([$this->visita('A')], null)->assertStatus(401);
        $this->enviar([$this->visita('A')], 'errado')->assertStatus(401);

        config(['svt.token' => null]);
        $this->enviar([$this->visita('A')])->assertStatus(503);
        $this->assertSame(0, VisitaTecnica::count());
    }

    public function test_svt_cria_atualiza_e_exclui_visitas_por_id_externo(): void
    {
        $this->enviar([$this->visita('SVT-1'), $this->visita('SVT-2'), ['eixo' => 'sem id']])->assertOk()
            ->assertJsonPath('novos', 2)->assertJsonPath('ignorados', 1);

        $v1 = VisitaTecnica::where('external_id', 'SVT-1')->firstOrFail();
        $this->assertSame(['svt', 'integration', 'Gestão e Moda', 'Em andamento', 'CPAD'],
            [$v1->source_system, $v1->source_type, $v1->eixo, $v1->status, $v1->etapa_svt]);
        $this->assertNotNull($v1->synced_at);

        $this->enviar([['id' => 'SVT-1', 'status' => 'Realizada', 'etapa' => 'NULOG'], ['id' => 'SVT-2', 'excluida' => true]])
            ->assertOk()->assertJsonPath('atualizados', 1)->assertJsonPath('excluidos', 1);

        $v1->refresh();
        $this->assertSame(['Realizada', 'NULOG', 'Visita SVT'], [$v1->status, $v1->etapa_svt, $v1->observacao]);
        $this->assertSoftDeleted('visita_tecnicas', ['external_id' => 'SVT-2']);
        $this->assertSame(1, VisitaTecnica::where('external_id', 'SVT-1')->count());

        $this->assertSame(2, ImportacaoHistorico::where('tipo', 'integracao')->where('modulo', 'visitas-tecnicas')->count());
        $this->withHeader('Authorization', 'Bearer '.self::TOKEN)->getJson('/api/integracoes/svt/situacao')->assertOk()
            ->assertJsonPath('data.visitas_do_svt', 1)
            ->assertJsonPath('data.integracao_ativa', true);
    }

    public function test_visita_do_svt_nao_e_editada_no_siped_mas_aparece_na_pagina(): void
    {
        $this->enviar([$this->visita('SVT-9')])->assertOk();
        $visita = VisitaTecnica::where('external_id', 'SVT-9')->firstOrFail();
        $this->actingAs($this->editor(), 'sanctum');

        $this->putJson('/api/visitas-tecnicas/'.$visita->id, ['observacao' => 'tentativa'])->assertStatus(409);
        $this->deleteJson('/api/visitas-tecnicas/'.$visita->id)->assertStatus(409);
        $this->assertNotSoftDeleted('visita_tecnicas', ['id' => $visita->id]);

        $lista = $this->getJson('/api/visitas-tecnicas?ciclo_id=todos&origem=svt')->assertOk();
        $lista->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.origem_svt', true)
            ->assertJsonPath('data.0.editavel_no_siped', false)
            ->assertJsonPath('data.0.url_svt', 'https://svt.exemplo/visitas/SVT-9')
            ->assertJsonPath('meta.origem.modo', 'local');
    }

    public function test_modo_svt_deixa_a_pagina_so_para_consulta_e_bloqueia_planilha(): void
    {
        config(['svt.modo' => 'svt']);
        $this->actingAs($this->editor(), 'sanctum');
        $local = VisitaTecnica::create(['unidade' => 'Asa Norte', 'eixo' => 'Gestão e Moda', 'processo_sei' => '0001.1/2026', 'status' => 'Pendente']);

        $this->postJson('/api/visitas-tecnicas', [])->assertStatus(409);
        $this->deleteJson('/api/visitas-tecnicas/'.$local->id)->assertStatus(409);
        $this->post('/api/importacoes/visitas-tecnicas/commit', [], ['Accept' => 'application/json'])->assertStatus(409);

        $this->getJson('/api/visitas-tecnicas?ciclo_id=todos')->assertOk()
            ->assertJsonPath('meta.origem.modo', 'svt')
            ->assertJsonPath('data.0.editavel_no_siped', false);
    }

    public function test_svt_aparece_em_sistemas_de_apoio_quando_configurado(): void
    {
        $this->actingAs($this->editor(), 'sanctum');
        $links = collect($this->getJson('/api/sistemas-apoio')->assertOk()->json('data'))->keyBy('key');
        $this->assertSame('https://svt.exemplo/', $links['svt']['url']);

        config(['sistemas_externos.svt.base_url' => null]);
        $links = collect($this->getJson('/api/sistemas-apoio')->assertOk()->json('data'))->keyBy('key');
        $this->assertArrayNotHasKey('svt', $links->all());
    }
}
