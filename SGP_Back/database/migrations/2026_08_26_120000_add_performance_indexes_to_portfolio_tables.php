<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Índices de performance. Colunas TEXT (ampliadas nas importações) usam
 * índice com prefixo no MySQL — VARCHAR/DATE usam índice normal.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->indexar('cursos', [
            'status' => 50,
            'eixo' => 150,
            // unidade é TEXT longo demais e pouco seletivo para filtro exato
        ]);

        $this->indexar('resolucoes', [
            'status' => 50,
            'categoria' => 100,
            'setor' => 100,
            'data_fim_vigencia' => null,
        ]);

        $this->indexar('visita_tecnicas', [
            'status' => 100,
            'eixo' => 150,
            'unidade' => 191,
            'data_solicitacao' => null,
        ]);

        $this->indexar('hora_pedagogicas', [
            'status' => 100,
            'eixo' => 150,
            'ano' => null,
        ]);

        $this->indexar('acao_extensivas', [
            'status' => 100,
            'eixo' => 150,
        ]);

        $this->indexar('eventos', [
            'status' => 100,
            'eixo' => 150,
            'ano' => null,
            'unidade' => 191,
        ]);

        $this->indexar('plano_de_metas', [
            'status' => 100,
            'ano' => null,
        ]);

        $this->indexar('pcas', [
            'status' => 100,
            'ano' => null,
            'eixo' => 150,
            'unidade' => 191,
        ]);
    }

    public function down(): void
    {
        foreach ([
            'cursos' => ['cursos_status_index', 'cursos_eixo_index'],
            'resolucoes' => [
                'resolucoes_status_index',
                'resolucoes_categoria_index',
                'resolucoes_setor_index',
                'resolucoes_data_fim_vigencia_index',
            ],
            'visita_tecnicas' => [
                'visita_tecnicas_status_index',
                'visita_tecnicas_eixo_index',
                'visita_tecnicas_unidade_index',
                'visita_tecnicas_data_solicitacao_index',
            ],
            'hora_pedagogicas' => [
                'hora_pedagogicas_status_index',
                'hora_pedagogicas_eixo_index',
                'hora_pedagogicas_ano_index',
            ],
            'acao_extensivas' => [
                'acao_extensivas_status_index',
                'acao_extensivas_eixo_index',
            ],
            'eventos' => [
                'eventos_status_index',
                'eventos_eixo_index',
                'eventos_ano_index',
                'eventos_unidade_index',
            ],
            'plano_de_metas' => [
                'plano_de_metas_status_index',
                'plano_de_metas_ano_index',
            ],
            'pcas' => [
                'pcas_status_index',
                'pcas_ano_index',
                'pcas_eixo_index',
                'pcas_unidade_index',
            ],
        ] as $tabela => $indices) {
            foreach ($indices as $indice) {
                $this->droparIndiceSeExistir($tabela, $indice);
            }
        }
    }

    /**
     * @param  array<string, int|null>  $colunas  coluna => prefixo (null = índice completo)
     */
    private function indexar(string $tabela, array $colunas): void
    {
        if (! Schema::hasTable($tabela)) {
            return;
        }

        foreach ($colunas as $coluna => $prefixo) {
            if (! Schema::hasColumn($tabela, $coluna)) {
                continue;
            }

            $indice = "{$tabela}_{$coluna}_index";
            if (Schema::hasIndex($tabela, $indice)) {
                continue;
            }

            $tipo = $this->tipoColuna($tabela, $coluna);
            $precisaPrefixo = $tipo !== null && (
                str_contains($tipo, 'text')
                || str_contains($tipo, 'blob')
            );

            if ($precisaPrefixo) {
                $tamanho = $prefixo ?? 100;
                DB::statement("CREATE INDEX `{$indice}` ON `{$tabela}` (`{$coluna}`({$tamanho}))");
                continue;
            }

            Schema::table($tabela, function ($table) use ($coluna, $indice) {
                $table->index($coluna, $indice);
            });
        }
    }

    private function tipoColuna(string $tabela, string $coluna): ?string
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            $row = DB::selectOne(
                'SELECT DATA_TYPE as data_type
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = ?
                   AND COLUMN_NAME = ?
                 LIMIT 1',
                [$tabela, $coluna]
            );

            return isset($row->data_type) ? strtolower((string) $row->data_type) : null;
        }

        return null;
    }

    private function droparIndiceSeExistir(string $tabela, string $indice): void
    {
        if (! Schema::hasTable($tabela) || ! Schema::hasIndex($tabela, $indice)) {
            return;
        }

        Schema::table($tabela, function ($table) use ($indice) {
            $table->dropIndex($indice);
        });
    }
};
