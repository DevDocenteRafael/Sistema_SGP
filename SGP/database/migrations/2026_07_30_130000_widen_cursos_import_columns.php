<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A planilha de portfólio traz textos longos em "unidade que pode ser rodado"
 * (ex.: resolução + vários CEPs). VARCHAR(100) truncava e derrubava a importação.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cursos') || ! Schema::hasColumn('cursos', 'unidade')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE cursos MODIFY unidade TEXT NULL');
            DB::statement('ALTER TABLE cursos MODIFY valores TEXT NULL');
            DB::statement('ALTER TABLE cursos MODIFY tipo VARCHAR(150) NULL');
            DB::statement('ALTER TABLE cursos MODIFY codigo_dn VARCHAR(100) NULL');
            DB::statement('ALTER TABLE cursos MODIFY codigo_sig VARCHAR(100) NULL');
        } elseif ($driver === 'sqlite') {
            // SQLite não aplica o mesmo limite rígido de VARCHAR; nada a fazer.
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('cursos') || ! Schema::hasColumn('cursos', 'unidade')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE cursos MODIFY unidade VARCHAR(100) NULL');
            DB::statement('ALTER TABLE cursos MODIFY valores VARCHAR(255) NULL');
            DB::statement('ALTER TABLE cursos MODIFY tipo VARCHAR(100) NULL');
            DB::statement('ALTER TABLE cursos MODIFY codigo_dn VARCHAR(50) NULL');
            DB::statement('ALTER TABLE cursos MODIFY codigo_sig VARCHAR(50) NULL');
        }
    }
};
