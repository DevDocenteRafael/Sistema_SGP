<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * foto passa a guardar só o caminho relativo no disco (ex.: cped/abc.jpg),
 * não mais base64 / binário.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cped_equipes') || ! Schema::hasColumn('cped_equipes', 'foto')) {
            return;
        }

        DB::table('cped_equipes')
            ->whereNotNull('foto')
            ->orderBy('id')
            ->each(function (object $row): void {
                $foto = (string) $row->foto;

                if (str_starts_with($foto, 'data:') || strlen($foto) > 255) {
                    DB::table('cped_equipes')->where('id', $row->id)->update(['foto' => null]);
                }
            });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE cped_equipes MODIFY foto VARCHAR(255) NULL');
        } elseif ($driver !== 'sqlite') {
            Schema::table('cped_equipes', function (Blueprint $table) {
                $table->string('foto', 255)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('cped_equipes') || ! Schema::hasColumn('cped_equipes', 'foto')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE cped_equipes MODIFY foto TEXT NULL');
        } elseif ($driver !== 'sqlite') {
            Schema::table('cped_equipes', function (Blueprint $table) {
                $table->text('foto')->nullable()->change();
            });
        }
    }
};
