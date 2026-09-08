<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Planilhas do portfólio usam "status"/origem/tipo/unidade como texto livre longo.
 * VARCHAR curto derruba a importação (1406 Data too long).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        $this->alterIfExists('plano_de_metas', [
            'MODIFY status TEXT NULL',
            'MODIFY status_final TEXT NULL',
            'MODIFY origem TEXT NULL',
            'MODIFY observacao TEXT NULL',
            'MODIFY segmento VARCHAR(255) NULL',
            'MODIFY tipo VARCHAR(150) NULL',
            'MODIFY mes_entrega VARCHAR(100) NULL',
            'MODIFY curso VARCHAR(500) NULL',
        ]);

        $this->alterIfExists('pcas', [
            'MODIFY status TEXT NULL',
            'MODIFY unidade VARCHAR(500) NULL',
            'MODIFY observacao TEXT NULL',
        ]);

        $this->alterIfExists('visita_tecnicas', [
            'MODIFY status TEXT NULL',
            'MODIFY unidade VARCHAR(500) NULL',
        ]);

        $this->alterIfExists('hora_pedagogicas', [
            'MODIFY status TEXT NULL',
        ]);

        $this->alterIfExists('acao_extensivas', [
            'MODIFY status TEXT NULL',
        ]);

        $this->alterIfExists('eventos', [
            'MODIFY status TEXT NULL',
            'MODIFY unidade VARCHAR(500) NULL',
        ]);

        // unidade tem índice em curso_por_eixos — não pode virar TEXT sem prefixo
        $this->alterIfExists('curso_por_eixos', [
            'MODIFY status VARCHAR(100) NULL',
            'MODIFY unidade VARCHAR(255) NULL',
        ]);
    }

    public function down(): void
    {
        // Irreversível de forma segura após dados longos; mantém colunas amplas.
    }

    /**
     * @param  list<string>  $modifies
     */
    private function alterIfExists(string $table, array $modifies): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        foreach ($modifies as $modify) {
            DB::statement("ALTER TABLE {$table} {$modify}");
        }
    }
};
