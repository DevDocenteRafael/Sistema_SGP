<?php

namespace Tests\Unit;

use App\Support\CatalogoOficial;
use App\Support\ClassificadorLegado;
use Tests\TestCase;

class CatalogoOficialClassificacaoTest extends TestCase
{
    public function test_typo_de_segmento_resolve_eixo_oficial(): void
    {
        $resolvido = CatalogoOficial::resolverEixoESegmento(
            'Tecnologia da Infomação - Desenvolvimento',
            null,
        );

        $this->assertNull($resolvido['erro']);
        $this->assertSame('Tecnologia e Economia Criativa', $resolvido['eixo']);
        $this->assertSame('Tecnologia da Informação - Desenvolvimento', $resolvido['segmento']);
    }

    public function test_gastronomia_vira_eixo_oficial_e_preserva_segmento(): void
    {
        $resolvido = CatalogoOficial::resolverEixoESegmento('Gastronomia', null);

        $this->assertNull($resolvido['erro']);
        $this->assertSame('Gastronomia e Turismo', $resolvido['eixo']);
        $this->assertSame('Gastronomia', $resolvido['segmento']);
    }

    public function test_programa_60_mais_sem_segmento_nao_vira_eixo(): void
    {
        $resolvido = CatalogoOficial::resolverEixoESegmento('60+', null);

        $this->assertSame('60+', $resolvido['programa']);
        $this->assertNull($resolvido['eixo']);
        $this->assertNotNull($resolvido['erro']);
    }

    public function test_legado_60_mais_infere_eixo_pelo_titulo(): void
    {
        $resolvido = ClassificadorLegado::classificar('60+', null, 'Cozinheiro');

        $this->assertNull($resolvido['erro']);
        $this->assertSame('60+', $resolvido['programa']);
        $this->assertSame('Gastronomia e Turismo', $resolvido['eixo']);
        $this->assertSame('Gastronomia', $resolvido['segmento']);
    }

    public function test_beleza_oficial_preenche_segmento_homonimo(): void
    {
        $resolvido = CatalogoOficial::resolverEixoESegmento('Beleza e Cuidado Pessoal', null);

        $this->assertNull($resolvido['erro']);
        $this->assertSame('Beleza e Cuidado Pessoal', $resolvido['eixo']);
        $this->assertSame('Beleza e cuidado pessoal', $resolvido['segmento']);
    }

    public function test_gastronomia_e_turismo_sem_segmento_usa_padrao_no_legado(): void
    {
        $resolvido = ClassificadorLegado::classificar('Gastronomia e Turismo', null, 'Cozinheiro Internacional');

        $this->assertSame('Gastronomia e Turismo', $resolvido['eixo']);
        $this->assertSame('Gastronomia', $resolvido['segmento']);
    }

    public function test_ensino_medio_infere_eixo_pelo_titulo(): void
    {
        $resolvido = ClassificadorLegado::classificar('Ensino Médio 2025', null, 'Técnico em Administração SEEDF');

        $this->assertSame('Ensino Médio 2025', $resolvido['programa']);
        $this->assertSame('Gestão e Moda', $resolvido['eixo']);
        $this->assertSame('Gestão e Comércio', $resolvido['segmento']);
    }
}
