<?php

namespace Tests\Unit;

use App\Models\Curso;
use App\Support\ConciliadorCursoOferta;
use Tests\TestCase;

class ConciliadorCursoOfertaTest extends TestCase
{
    public function test_titulo_unico_vincula_mesmo_com_hifen_diferente(): void
    {
        $catalogo = collect([
            new Curso([
                'titulo' => 'Lógica de Programação',
                'eixo' => 'Tecnologia e Economia Criativa',
                'segmento' => 'Tecnologia da Informação - Desenvolvimento',
                'carga_horaria' => '40',
            ]),
        ]);

        $escolha = ConciliadorCursoOferta::escolherComMotivo($catalogo, [
            'titulo' => 'Logica de Programacao',
            'eixo' => 'Tecnologia e Economia Criativa',
        ]);

        $this->assertSame('titulo_unico', $escolha['motivo']);
        $this->assertSame('Lógica de Programação', $escolha['curso']?->titulo);
    }

    public function test_codigo_operacional_nao_e_tratado_como_sig(): void
    {
        $catalogo = collect([
            new Curso([
                'titulo' => 'Outro Curso',
                'codigo_sig' => '2025.08.159',
                'eixo' => 'Gestão e Moda',
            ]),
        ]);

        $escolha = ConciliadorCursoOferta::escolherComMotivo($catalogo, [
            'titulo' => 'Açougueiro',
            'codigo' => '2025.08.159',
        ]);

        $this->assertSame('sem_correspondencia', $escolha['motivo']);
        $this->assertNull($escolha['curso']);
    }

    public function test_duplicata_no_mesmo_eixo_nao_vincula_sozinha(): void
    {
        $catalogo = collect([
            new Curso([
                'titulo' => 'Cozinheiro',
                'eixo' => 'Gastronomia e Turismo',
                'segmento' => 'Gastronomia',
                'carga_horaria' => '160',
            ]),
            new Curso([
                'titulo' => 'Cozinheiro',
                'eixo' => 'Gastronomia e Turismo',
                'segmento' => 'Gastronomia',
                'carga_horaria' => '80',
                'programa' => '60+',
            ]),
        ]);

        $escolha = ConciliadorCursoOferta::escolherComMotivo($catalogo, [
            'titulo' => 'Cozinheiro',
            'eixo' => 'Gastronomia e Turismo',
            'segmento' => 'Gastronomia',
        ]);

        $this->assertSame('ambiguo', $escolha['motivo']);
        $this->assertNull($escolha['curso']);
    }

    public function test_duplicata_em_eixos_diferentes_desambigua_pelo_eixo(): void
    {
        $catalogo = collect([
            new Curso([
                'titulo' => 'Atendente',
                'eixo' => 'Gastronomia e Turismo',
                'segmento' => 'Hospitalidade',
            ]),
            new Curso([
                'titulo' => 'Atendente',
                'eixo' => 'Gestão e Moda',
                'segmento' => 'Vendas e Marketing',
            ]),
        ]);

        $escolha = ConciliadorCursoOferta::escolherComMotivo($catalogo, [
            'titulo' => 'Atendente',
            'eixo' => 'Gestão e Moda',
        ]);

        $this->assertSame('titulo_eixo', $escolha['motivo']);
        $this->assertSame('Gestão e Moda', $escolha['curso']?->eixo);
    }

    public function test_duplicata_desambigua_pela_carga_horaria(): void
    {
        $catalogo = collect([
            new Curso([
                'titulo' => 'Barista',
                'eixo' => 'Gastronomia e Turismo',
                'segmento' => 'Bebidas',
                'carga_horaria' => '40',
            ]),
            new Curso([
                'titulo' => 'Barista',
                'eixo' => 'Gastronomia e Turismo',
                'segmento' => 'Bebidas',
                'carga_horaria' => '80',
            ]),
        ]);

        $escolha = ConciliadorCursoOferta::escolherComMotivo($catalogo, [
            'titulo' => 'Barista',
            'eixo' => 'Gastronomia e Turismo',
            'segmento' => 'Bebidas',
            'ch' => '80',
        ]);

        $this->assertSame('titulo_ch', $escolha['motivo']);
        $this->assertSame('80', $escolha['curso']?->carga_horaria);
    }
}
