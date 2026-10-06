<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Models\VisitaTecnica;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Visitas Técnicas alinhadas à ATA de 30/09/2026: o SIPED recebe do SVT os dados da
 * solicitação, o limite por turma, as decisões de cada etapa, o transporte e o relatório.
 */
class AtaVisitasTecnicasSvtTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'token-de-teste-ata-svt-1234567890';

    protected function setUp(): void
    {
        parent::setUp();
        config(['svt.token' => self::TOKEN, 'svt.modo' => 'svt']);
    }

    private function enviar(array $visitas)
    {
        return $this->withHeaders(['Authorization' => 'Bearer '.self::TOKEN])
            ->postJson('/api/integracoes/svt/visitas', ['visitas' => $visitas]);
    }

    private function consultor(): Usuario
    {
        return Usuario::create([
            'nome' => 'Consultor ATA', 'email' => 'consultor-ata@teste.com', 'senha' => Hash::make('senha123'),
            'cpf' => '52998224725', 'perfil' => Usuario::PERFIL_CONSULTOR, 'status' => true,
            'unidade' => 'Asa Norte', 'area' => 'CPED', 'telefone' => '61999990808',
        ]);
    }

    public function test_svt_envia_dados_da_ata_e_o_siped_mostra_com_historico_da_turma(): void
    {
        $base = ['unidade' => 'Asa Norte', 'eixo' => 'Saúde', 'curso' => 'Técnico em Enfermagem',
            'tipo_curso' => 'Curso Técnico', 'turma' => 'TE-2026-01'];

        $this->enviar([
            $base + [
                'id' => 'A1', 'status' => 'aprovada', 'etapa' => 'NULOG', 'instrutor' => 'Ana',
                'local_visita' => 'Hospital Regional', 'data_visita_prevista' => '2026-10-20',
                'visitas_utilizadas' => 5, 'visitas_limite' => 4, 'justificativa_excedente' => 'Projeto integrador',
                'decisoes' => [
                    ['etapa' => 'Núcleo Pedagógico da unidade', 'decisao' => 'aprovada', 'responsavel' => 'Bia', 'data' => '2026-10-02'],
                    ['etapa' => 'Direção Pedagógica', 'decisao' => 'aprovada', 'responsavel' => 'Caio'],
                    'lixo',
                ],
                'transporte' => ['tipo' => 'Micro-ônibus', 'placa' => 'ABC1D23', 'motorista' => 'Davi', 'data_hora' => '2026-10-20T07:30'],
                'relatorio_url' => 'https://svt.exemplo/relatorios/A1.pdf', 'relatorio_arquivo_nome' => 'relatorio-A1.pdf',
            ],
            $base + ['id' => 'A0', 'status' => 'Realizada', 'local_visita' => 'Laboratório X', 'data_visita_prevista' => '2026-08-10'],
            array_merge($base, ['id' => 'R1', 'status' => 'Recusada', 'turma' => 'OUTRA', 'motivo_recusa' => 'Sem transporte disponível',
                'relatorio_url' => 'javascript:alert(1)']),
        ])->assertOk()->assertJsonPath('novos', 3);

        $a1 = VisitaTecnica::where('external_id', 'A1')->firstOrFail();
        $this->assertSame('Aprovada', $a1->status);
        $this->assertSame([5, 4], [$a1->visitas_utilizadas, $a1->visitas_limite]);
        $this->assertCount(2, $a1->decisoes);
        $this->assertSame('Davi', $a1->transporte['motorista']);
        $this->assertNull(VisitaTecnica::where('external_id', 'R1')->value('relatorio_url'));

        $this->actingAs($this->consultor(), 'sanctum');

        $this->getJson("/api/visitas-tecnicas/{$a1->id}")->assertOk()
            ->assertJsonPath('visitaTecnica.curso', 'Técnico em Enfermagem')
            ->assertJsonPath('visitaTecnica.transporte.placa', 'ABC1D23')
            ->assertJsonCount(1, 'visitaTecnica.historico_turma')
            ->assertJsonPath('visitaTecnica.historico_turma.0.local', 'Laboratório X');

        $this->getJson('/api/visitas-tecnicas?busca=Hospital Regional')->assertOk()->assertJsonCount(1, 'data');
        $this->assertContains('Recusada', $this->getJson('/api/visitas-tecnicas')->json('meta.status'));
        $this->assertContains('Direção Pedagógica', $this->getJson('/api/visitas-tecnicas')->json('meta.etapas_svt'));
    }
}
