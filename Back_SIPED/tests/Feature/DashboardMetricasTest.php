<?php

namespace Tests\Feature;

use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardMetricasTest extends TestCase
{
    use RefreshDatabase;

    public function test_resumo_conta_status_dos_registros_existentes_e_preserva_os_totais(): void
    {
        DB::table('hora_pedagogicas')->insert([
            ['status' => 'Em andamento'],
            ['status' => 'Pendente'],
            ['status' => 'Concluída'],
            ['status' => 'Cancelada'],
        ]);

        DB::table('acao_extensivas')->insert([
            ['priorizacao' => 'Alta'],
            ['priorizacao' => 'Média'],
            ['priorizacao' => 'Baixa'],
            ['priorizacao' => 'Resolvido'],
            ['priorizacao' => null],
        ]);

        DB::table('eventos')->insert([
            ['status' => 'Planejado'],
            ['status' => 'Realizado'],
            ['status' => 'Cancelado'],
        ]);

        DB::table('visita_tecnicas')->insert([
            ['status' => 'Pendente'],
            ['status' => 'Em andamento'],
            ['status' => 'Realizada'],
            ['status' => 'Cancelada'],
            ['status' => 'Atrasada'],
        ]);

        $regiaoId = DB::table('regioes_administrativas')->insertGetId(['nome' => 'Região de teste']);
        DB::table('unidades_oferta')->insert([
            ['regiao_administrativa_id' => $regiaoId, 'nome' => 'Unidade ativa', 'tipo' => 'unidade', 'ativo' => true],
            ['regiao_administrativa_id' => $regiaoId, 'nome' => 'Unidade inativa', 'tipo' => 'unidade', 'ativo' => false],
        ]);

        $resumo = app(DashboardService::class)->resumo(['ciclo_id' => 'todos']);

        $this->assertSame(4, $resumo['contagens']['horas']);
        $this->assertSame(5, $resumo['contagens']['acoes']);
        $this->assertSame(3, $resumo['contagens']['eventos']);
        $this->assertSame(5, $resumo['contagens']['visitas']);
        $this->assertSame(2, $resumo['contagens']['estruturas']);
        $this->assertSame(1, $resumo['contagens']['estruturas_faculdade'] + $resumo['contagens']['estruturas_polo'] + $resumo['contagens']['estruturas_unidade']);

        foreach (['horas', 'acoes', 'eventos', 'visitas'] as $grupo) {
            $this->assertSame(
                $resumo['contagens'][$grupo],
                array_sum($resumo['distribuicoes'][$grupo]),
                "A distribuição de {$grupo} deve incluir todos os registros contados."
            );
        }

        $this->assertSame(1, $resumo['distribuicoes']['acoes']['alta']);
        $this->assertSame(1, $resumo['distribuicoes']['acoes']['media']);
        $this->assertSame(1, $resumo['distribuicoes']['acoes']['baixa']);
        $this->assertSame(1, $resumo['distribuicoes']['acoes']['resolvido']);
        $this->assertSame(1, $resumo['distribuicoes']['acoes']['sem_classificacao']);
        $this->assertSame(1, $resumo['distribuicoes']['visitas']['atrasada']);
    }
}
