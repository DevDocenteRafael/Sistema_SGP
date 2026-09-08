<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cadastros', function (Blueprint $table) {
            if (! Schema::hasColumn('cadastros', 'usuario_id')) {
                $table->foreignId('usuario_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('usuarios')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('cadastros', 'acao')) {
                $table->string('acao', 30)->default('')->after('usuario_id');
            }

            if (! Schema::hasColumn('cadastros', 'modulo')) {
                $table->string('modulo', 50)->default('')->after('acao');
            }

            if (! Schema::hasColumn('cadastros', 'registro_tipo')) {
                $table->string('registro_tipo', 120)->nullable()->after('modulo');
            }

            if (! Schema::hasColumn('cadastros', 'registro_id')) {
                $table->unsignedBigInteger('registro_id')->nullable()->after('registro_tipo');
            }

            if (! Schema::hasColumn('cadastros', 'resumo')) {
                $table->string('resumo', 500)->nullable()->after('registro_id');
            }

            if (! Schema::hasColumn('cadastros', 'dados')) {
                $table->json('dados')->nullable()->after('resumo');
            }

            if (! Schema::hasColumn('cadastros', 'ip')) {
                $table->string('ip', 45)->nullable()->after('dados');
            }

            if (! Schema::hasColumn('cadastros', 'user_agent')) {
                $table->string('user_agent', 500)->nullable()->after('ip');
            }
        });

        Schema::table('cadastros', function (Blueprint $table) {
            $table->index(['modulo', 'created_at']);
            $table->index(['usuario_id', 'created_at']);
            $table->index(['registro_tipo', 'registro_id']);
        });
    }

    public function down(): void
    {
        Schema::table('cadastros', function (Blueprint $table) {
            if (Schema::hasColumn('cadastros', 'usuario_id')) {
                $table->dropConstrainedForeignId('usuario_id');
            }

            $cols = ['acao', 'modulo', 'registro_tipo', 'registro_id', 'resumo', 'dados', 'ip', 'user_agent'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('cadastros', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
