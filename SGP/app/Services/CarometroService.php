<?php

namespace App\Services;

use App\Models\CpedEquipe;

class CarometroService
{
    public function listar(): array
    {
        $membros = CpedEquipe::query()
            ->where('ativo', true)
            ->orderByRaw("CASE tipo
                WHEN 'ordenador' THEN 1
                WHEN 'assistente' THEN 2
                WHEN 'responsavel' THEN 3
                WHEN 'instrutor' THEN 4
                WHEN 'administrativo' THEN 5
                ELSE 6 END")
            ->orderBy('nome')
            ->get()
            ->map(fn (CpedEquipe $membro) => $this->formatar($membro))
            ->values()
            ->all();

        $tipos = collect($membros)
            ->groupBy('tipo')
            ->map->count()
            ->all();

        return [
            'membros' => $membros,
            'meta' => [
                'total' => count($membros),
                'por_tipo' => $tipos,
                'tipos_filtro' => config('cped_equipes.tipos_filtro', []),
                'tipos_labels' => config('cped_equipes.tipos_labels', []),
                'eixos' => config('cped_equipes.eixos', []),
                'setores' => config('cped_equipes.setores', []),
                'cores_tipo' => config('cped_equipes.cores_tipo', []),
                'cores_eixo' => config('cped_equipes.cores_eixo', []),
                'gerenciar_em' => '/app/cped',
            ],
        ];
    }

    private function formatar(CpedEquipe $membro): array
    {
        return [
            'id' => $membro->id,
            'nome' => $membro->nome,
            'cargo' => $membro->cargo,
            'setor' => $membro->setor,
            'contato' => $membro->contato,
            'tipo' => $membro->tipo,
            'eixo_vinculado' => $membro->eixo_vinculado,
            'iniciais' => $membro->iniciais,
            'foto' => $membro->foto,
            'cor' => $membro->cor,
        ];
    }
}
