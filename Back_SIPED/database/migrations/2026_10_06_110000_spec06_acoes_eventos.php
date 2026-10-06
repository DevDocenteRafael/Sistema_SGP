<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SPEC 06.
 *
 * Ações Extensivas: separa prioridade, setor/etapa e status.
 * - setor_atual recebe CPED/DEP/DIREG/NC, que estavam gravados em "status";
 * - prioridade "Resolvido" (que era um estado, não prioridade) vai para situacao_legada.
 * Nada é apagado: os valores apenas mudam de coluna.
 *
 * Eventos: Processo SEI e Tipo do evento.
 */
return new class extends Migration
{
    private const SETORES = ['CPED', 'DEP', 'DIREG', 'NC'];

    public function up(): void
    {
        Schema::table('acao_extensivas', function (Blueprint $table) {
            if (! Schema::hasColumn('acao_extensivas', 'setor_atual')) {
                $table->string('setor_atual', 50)->nullable()->after('status');
            }
            if (! Schema::hasColumn('acao_extensivas', 'situacao_legada')) {
                $table->string('situacao_legada', 50)->nullable()->after('setor_atual');
            }
        });

        DB::table('acao_extensivas')
            ->whereIn(DB::raw('UPPER(status)'), self::SETORES)
            ->orderBy('id')
            ->each(function ($acao) {
                DB::table('acao_extensivas')->where('id', $acao->id)->update([
                    'setor_atual' => strtoupper((string) $acao->status),
                    'status' => null,
                ]);
            });

        DB::table('acao_extensivas')
            ->where('priorizacao', 'Resolvido')
            ->update(['situacao_legada' => 'Resolvido', 'priorizacao' => null]);

        Schema::table('eventos', function (Blueprint $table) {
            if (! Schema::hasColumn('eventos', 'processo_sei')) {
                $table->string('processo_sei', 100)->nullable()->after('nome');
            }
            if (! Schema::hasColumn('eventos', 'tipo_evento')) {
                $table->string('tipo_evento', 100)->nullable()->after('processo_sei');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('acao_extensivas', 'setor_atual')) {
            DB::table('acao_extensivas')
                ->whereNotNull('setor_atual')
                ->whereNull('status')
                ->update(['status' => DB::raw('setor_atual')]);
        }
        if (Schema::hasColumn('acao_extensivas', 'situacao_legada')) {
            DB::table('acao_extensivas')
                ->where('situacao_legada', 'Resolvido')
                ->whereNull('priorizacao')
                ->update(['priorizacao' => 'Resolvido']);
        }

        Schema::table('acao_extensivas', function (Blueprint $table) {
            foreach (['setor_atual', 'situacao_legada'] as $coluna) {
                if (Schema::hasColumn('acao_extensivas', $coluna)) {
                    $table->dropColumn($coluna);
                }
            }
        });

        Schema::table('eventos', function (Blueprint $table) {
            foreach (['processo_sei', 'tipo_evento'] as $coluna) {
                if (Schema::hasColumn('eventos', $coluna)) {
                    $table->dropColumn($coluna);
                }
            }
        });
    }
};
