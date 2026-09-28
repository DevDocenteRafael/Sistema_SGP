<?php

namespace Tests\Feature;

use App\Models\Ciclo;
use App\Models\Resolucao;
use App\Models\TermoReferencia;
use App\Models\Usuario;
use App\Models\VisitaTecnica;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NotificacaoApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-09-28'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function editor(): Usuario
    {
        return Usuario::create([
            'nome' => 'Editor Notificações',
            'email' => 'editor-notif@teste.com',
            'senha' => Hash::make('senha123'),
            'cpf' => '12345678151',
            'perfil' => Usuario::PERFIL_EDITOR,
            'status' => true,
            'unidade' => 'Asa Norte',
            'area' => 'Portfólio',
            'telefone' => '61999991051',
        ]);
    }

    public function test_lista_alertas_de_prazo_e_permite_marcar_como_lida(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $ciclo = Ciclo::query()->first() ?? Ciclo::create(['nome' => '2025-2026', 'atual' => true]);

        Resolucao::create([
            'numero' => 'RES/2021/001',
            'curso_relacionado' => 'Administração',
            'resumo' => 'Resolução vencida para o sininho.',
            'status' => 'vigente',
            'data_inicio_vigencia' => '2021-01-01',
            'data_fim_vigencia' => '2026-09-01',
        ]);

        TermoReferencia::create([
            'nome' => 'TR vencido de teste',
            'eixo' => 'Gestão e Moda',
            'processo_sei' => '123.001/2026-01',
            'prazo_deadline' => '2026-09-01',
            'status' => 'Em Andamento',
        ]);

        VisitaTecnica::create([
            'ciclo_id' => $ciclo->id,
            'unidade' => 'Asa Norte',
            'eixo' => 'Gestão e Moda',
            'processo_sei' => '123.002/2026-01',
            'data_solicitacao' => '2026-09-01',
            'data_visita_prevista' => '2026-09-10',
            'prazo_limite' => '2026-09-20',
            'status' => 'Pendente',
            'responsavel' => 'Equipe',
            'relatorio' => 'Relatório',
            'observacao' => 'Observação',
        ]);

        Resolucao::create([
            'numero' => 'RES/2026/OK',
            'resumo' => 'Ainda no prazo.',
            'status' => 'vigente',
            'data_inicio_vigencia' => '2026-01-01',
            'data_fim_vigencia' => '2031-01-01',
        ]);

        $lista = $this->getJson('/api/notificacoes');
        $lista->assertOk();
        $lista->assertJsonPath('meta.nao_lidas', 3);
        $chaves = collect($lista->json('itens'))->pluck('chave');
        $this->assertTrue($chaves->contains(fn ($chave) => str_starts_with((string) $chave, 'resolucoes:')));
        $this->assertTrue($chaves->contains(fn ($chave) => str_starts_with((string) $chave, 'termos-referencia:')));
        $this->assertTrue($chaves->contains(fn ($chave) => str_starts_with((string) $chave, 'visitas-tecnicas:')));
        $this->assertFalse($chaves->contains('RES/2026/OK'));

        $marcar = $this->postJson('/api/notificacoes/marcar-lidas', [
            'chaves' => $chaves->all(),
        ]);
        $marcar->assertOk();
        $marcar->assertJsonPath('gravadas', 3);

        $this->getJson('/api/notificacoes')
            ->assertOk()
            ->assertJsonPath('meta.nao_lidas', 0);
    }

    public function test_lista_pagina_alertas_quando_ha_mais_do_que_o_limite(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        foreach (range(1, 3) as $indice) {
            TermoReferencia::create([
                'nome' => "TR paginado {$indice}",
                'eixo' => 'Gestão e Moda',
                'processo_sei' => '123.02'.str_pad((string) $indice, 2, '0', STR_PAD_LEFT).'/2026-01',
                'prazo_deadline' => '2026-09-0'.$indice,
                'status' => 'Em Andamento',
            ]);
        }

        $primeira = $this->getJson('/api/notificacoes?limit=2&offset=0');
        $primeira->assertOk();
        $primeira->assertJsonPath('meta.total', 3);
        $primeira->assertJsonPath('meta.has_more', true);
        $this->assertCount(2, $primeira->json('itens'));

        $segunda = $this->getJson('/api/notificacoes?limit=2&offset=2');
        $segunda->assertOk();
        $segunda->assertJsonPath('meta.has_more', false);
        $this->assertCount(1, $segunda->json('itens'));
    }

    public function test_listas_aceitam_filtro_pelo_id_do_alerta(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $alvo = TermoReferencia::create([
            'nome' => 'TR do alerta',
            'eixo' => 'Gestão e Moda',
            'processo_sei' => '123.010/2026-01',
            'prazo_deadline' => '2026-09-01',
            'status' => 'Em Andamento',
        ]);
        TermoReferencia::create([
            'nome' => 'Outro TR',
            'eixo' => 'Gestão e Moda',
            'processo_sei' => '123.011/2026-01',
            'prazo_deadline' => '2026-09-02',
            'status' => 'Em Andamento',
        ]);

        $this->getJson('/api/termos-referencia?id='.$alvo->id)
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $alvo->id)
            ->assertJsonPath('data.0.nome', 'TR do alerta');
    }

    public function test_lista_filtra_alertas_por_busca_modulo_e_nivel(): void
    {
        $this->actingAs($this->editor(), 'sanctum');

        $alvo = TermoReferencia::create([
            'nome' => 'TR Documento pedagógico 108',
            'eixo' => 'Gastronomia e Turismo',
            'processo_sei' => '2026.01.00108-01',
            'prazo_deadline' => '2026-09-01',
            'status' => 'Em Andamento',
        ]);
        TermoReferencia::create([
            'nome' => 'TR Documento pedagógico 999',
            'eixo' => 'Gestão e Moda',
            'processo_sei' => '2026.01.00999-01',
            'prazo_deadline' => '2026-09-02',
            'status' => 'Em Andamento',
        ]);
        Resolucao::create([
            'numero' => 'RES/2021/001',
            'curso_relacionado' => 'Administração',
            'resumo' => 'Resolução vencida.',
            'status' => 'vigente',
            'data_inicio_vigencia' => '2021-01-01',
            'data_fim_vigencia' => '2026-09-01',
        ]);

        $busca = $this->getJson('/api/notificacoes?q=108');
        $busca->assertOk();
        $busca->assertJsonPath('meta.total', 1);
        $busca->assertJsonPath('meta.filtrado', true);
        $busca->assertJsonPath('itens.0.registro_id', $alvo->id);
        $this->assertStringContainsString('108', (string) $busca->json('itens.0.titulo'));

        $modulo = $this->getJson('/api/notificacoes?modulo=resolucoes');
        $modulo->assertOk();
        $modulo->assertJsonPath('meta.total', 1);
        $modulo->assertJsonPath('itens.0.modulo', 'resolucoes');

        $nivel = $this->getJson('/api/notificacoes?nivel=vencido');
        $nivel->assertOk();
        $this->assertGreaterThanOrEqual(1, $nivel->json('meta.total'));
        foreach ($nivel->json('itens') as $item) {
            $this->assertSame('vencido', $item['nivel']);
        }
    }

    public function test_visitante_nao_consulta_notificacoes(): void
    {
        $this->getJson('/api/notificacoes')->assertUnauthorized();
    }
}
