<?php

use App\Services\ResolucaoVigenciaService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Semáforo de Resoluções com exatamente três estados (Vigente / Atenção / Vencida).
 *
 * Status legados ("critico", "concluida", "em_atencao"...) passam para o estado calculado
 * pela vigência. O valor antigo fica preservado no histórico da resolução.
 * Nenhuma resolução é apagada.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('resolucoes')) {
            return;
        }

        $agora = now();
        $vigenciaAnos = (int) config('resolucoes.vigencia_anos', 5);
        $temHistorico = Schema::hasTable('resolucao_historicos');

        DB::table('resolucoes')
            ->whereNull('data_fim_vigencia')
            ->whereNotNull('data_inicio_vigencia')
            ->orderBy('id')
            ->each(function ($resolucao) use ($vigenciaAnos) {
                DB::table('resolucoes')->where('id', $resolucao->id)->update([
                    'data_fim_vigencia' => \Carbon\Carbon::parse($resolucao->data_inicio_vigencia)
                        ->addYears($vigenciaAnos)->toDateString(),
                ]);
            });

        DB::table('resolucoes')
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhereNotIn('status', ResolucaoVigenciaService::estados());
            })
            ->orderBy('id')
            ->each(function ($resolucao) use ($agora, $temHistorico) {
                $novo = ResolucaoVigenciaService::statusAutomatico(
                    $resolucao->data_inicio_vigencia,
                    $resolucao->data_fim_vigencia,
                );

                DB::table('resolucoes')->where('id', $resolucao->id)->update(['status' => $novo]);

                if ($temHistorico && $resolucao->status !== null) {
                    DB::table('resolucao_historicos')->insert([
                        'resolucao_id' => $resolucao->id,
                        'evento' => 'Status legado normalizado',
                        'status_anterior' => $resolucao->status,
                        'status_novo' => $novo,
                        'observacao' => 'Semáforo de Resoluções passou a ter três estados (Vigente, Atenção, Vencida). Status anterior preservado neste histórico.',
                        'created_at' => $agora,
                        'updated_at' => $agora,
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Os status anteriores ficam no histórico (evento "Status legado normalizado").
    }
};
