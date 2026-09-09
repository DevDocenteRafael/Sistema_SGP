<?php

use App\Support\CatalogoOficial;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @return array<string, list<string>>
     */
    private function colunasPorTabela(): array
    {
        return [
            'cursos' => ['eixo'],
            'pcas' => ['eixo'],
            'eventos' => ['eixo'],
            'visita_tecnicas' => ['eixo'],
            'hora_pedagogicas' => ['eixo', 'segmento'],
            'acao_extensivas' => ['eixo'],
            'termos_referencia' => ['eixo'],
            'cped_equipes' => ['eixo_vinculado', 'setor'],
            'plano_de_metas' => ['segmento'],
        ];
    }

    public function up(): void
    {
        Schema::create('eixo_backfill_log', function (Blueprint $table) {
            $table->id();
            $table->string('tabela', 80);
            $table->unsignedBigInteger('registro_id');
            $table->string('coluna', 80);
            $table->string('valor_anterior', 150);
            $table->string('valor_novo', 150);
            $table->timestamps();
            $table->index(['tabela', 'registro_id']);
        });

        $agora = now();

        foreach ($this->colunasPorTabela() as $tabela => $colunas) {
            if (! Schema::hasTable($tabela)) {
                continue;
            }

            foreach ($colunas as $coluna) {
                if (! Schema::hasColumn($tabela, $coluna)) {
                    continue;
                }

                $linhas = DB::table($tabela)
                    ->select('id', $coluna)
                    ->whereNotNull($coluna)
                    ->where($coluna, '!=', '')
                    ->get();

                foreach ($linhas as $linha) {
                    $atual = trim((string) $linha->{$coluna});
                    if ($atual === '' || CatalogoOficial::eAbaEspecial($atual)) {
                        continue;
                    }

                    $canon = CatalogoOficial::canonicalizarEixo($atual);
                    if ($canon === null || $canon === $atual) {
                        continue;
                    }

                    DB::table($tabela)->where('id', $linha->id)->update([$coluna => $canon]);
                    DB::table('eixo_backfill_log')->insert([
                        'tabela' => $tabela,
                        'registro_id' => $linha->id,
                        'coluna' => $coluna,
                        'valor_anterior' => $atual,
                        'valor_novo' => $canon,
                        'created_at' => $agora,
                        'updated_at' => $agora,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('eixo_backfill_log')) {
            $logs = DB::table('eixo_backfill_log')->orderByDesc('id')->get();

            foreach ($logs as $log) {
                if (! Schema::hasTable($log->tabela) || ! Schema::hasColumn($log->tabela, $log->coluna)) {
                    continue;
                }

                DB::table($log->tabela)
                    ->where('id', $log->registro_id)
                    ->where($log->coluna, $log->valor_novo)
                    ->update([$log->coluna => $log->valor_anterior]);
            }

            Schema::dropIfExists('eixo_backfill_log');
        }
    }
};
