<?php

use App\Support\CatalogoOficial;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cped_equipes') || ! Schema::hasColumn('cped_equipes', 'setor')) {
            return;
        }

        $temLog = Schema::hasTable('eixo_backfill_log');
        $agora = now();

        $linhas = DB::table('cped_equipes')
            ->select('id', 'setor')
            ->whereNotNull('setor')
            ->where('setor', '!=', '')
            ->get();

        foreach ($linhas as $linha) {
            $atual = trim((string) $linha->setor);
            if ($atual === '' || CatalogoOficial::eAbaEspecial($atual)) {
                continue;
            }

            $canon = CatalogoOficial::canonicalizarEixo($atual);
            if ($canon === null || $canon === $atual) {
                continue;
            }

            DB::table('cped_equipes')->where('id', $linha->id)->update(['setor' => $canon]);

            if ($temLog) {
                DB::table('eixo_backfill_log')->insert([
                    'tabela' => 'cped_equipes',
                    'registro_id' => $linha->id,
                    'coluna' => 'setor',
                    'valor_anterior' => $atual,
                    'valor_novo' => $canon,
                    'created_at' => $agora,
                    'updated_at' => $agora,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('eixo_backfill_log') || ! Schema::hasTable('cped_equipes')) {
            return;
        }

        $logs = DB::table('eixo_backfill_log')
            ->where('tabela', 'cped_equipes')
            ->where('coluna', 'setor')
            ->orderByDesc('id')
            ->get();

        foreach ($logs as $log) {
            DB::table('cped_equipes')
                ->where('id', $log->registro_id)
                ->where('setor', $log->valor_novo)
                ->update(['setor' => $log->valor_anterior]);
        }

        DB::table('eixo_backfill_log')
            ->where('tabela', 'cped_equipes')
            ->where('coluna', 'setor')
            ->delete();
    }
};
