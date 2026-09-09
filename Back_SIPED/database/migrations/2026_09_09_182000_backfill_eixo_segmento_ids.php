<?php

use App\Support\CatalogoOficial;
use App\Support\CatalogoInstitucional;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        CatalogoInstitucional::sincronizar();

        if (Schema::hasTable('cursos') && Schema::hasColumn('cursos', 'eixo_id')) {
            $linhas = DB::table('cursos')->select('id', 'eixo', 'segmento')->get();
            foreach ($linhas as $linha) {
                $resolvido = CatalogoOficial::resolverEixoESegmento(
                    $linha->eixo,
                    $linha->segmento ?? null,
                );
                if ($resolvido['erro'] !== null && $resolvido['eixo'] === null && $resolvido['segmento'] === null) {
                    continue;
                }

                $ids = CatalogoInstitucional::ids($resolvido['eixo'], $resolvido['segmento']);
                DB::table('cursos')->where('id', $linha->id)->update([
                    'eixo' => $resolvido['eixo'] ?? $linha->eixo,
                    'segmento' => $resolvido['segmento'],
                    'eixo_id' => $ids['eixo_id'],
                    'segmento_id' => $ids['segmento_id'],
                ]);
            }
        }

        if (Schema::hasTable('curso_por_eixos') && Schema::hasColumn('curso_por_eixos', 'eixo_id')) {
            $linhas = DB::table('curso_por_eixos')->select('id', 'eixo', 'segmento', 'curso', 'ciclo_id')->get();
            foreach ($linhas as $linha) {
                $resolvido = CatalogoOficial::resolverEixoESegmento(
                    $linha->eixo,
                    $linha->segmento ?? null,
                );
                if ($resolvido['erro'] !== null && $resolvido['eixo'] === null && $resolvido['segmento'] === null) {
                    continue;
                }

                $ids = CatalogoInstitucional::ids($resolvido['eixo'], $resolvido['segmento']);
                $cursoId = $this->encontrarCursoId($linha->curso ?? null, $linha->ciclo_id ?? null);

                DB::table('curso_por_eixos')->where('id', $linha->id)->update([
                    'eixo' => $resolvido['eixo'] ?? $linha->eixo,
                    'segmento' => $resolvido['segmento'],
                    'eixo_id' => $ids['eixo_id'],
                    'segmento_id' => $ids['segmento_id'],
                    'curso_id' => $cursoId,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cursos') && Schema::hasColumn('cursos', 'eixo_id')) {
            DB::table('cursos')->update([
                'eixo_id' => null,
                'segmento_id' => null,
            ]);
        }

        if (Schema::hasTable('curso_por_eixos') && Schema::hasColumn('curso_por_eixos', 'eixo_id')) {
            DB::table('curso_por_eixos')->update([
                'eixo_id' => null,
                'segmento_id' => null,
                'curso_id' => null,
            ]);
        }
    }

    private function encontrarCursoId(mixed $titulo, mixed $cicloId): ?int
    {
        $titulo = mb_strtolower(trim((string) $titulo));
        if ($titulo === '' || ! Schema::hasTable('cursos')) {
            return null;
        }

        $query = DB::table('cursos')->whereRaw('LOWER(titulo) = ?', [$titulo]);
        if ($cicloId) {
            $query->where('ciclo_id', $cicloId);
        }

        $id = $query->value('id');

        return $id ? (int) $id : null;
    }
};
