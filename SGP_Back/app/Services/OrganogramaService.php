<?php

namespace App\Services;

use App\Models\CpedEquipe;

class OrganogramaService
{
    public function montar(): array
    {
        $membros = CpedEquipe::query()
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();

        $ordenador = $membros->firstWhere('tipo', 'ordenador');
        $assistentes = $membros->where('tipo', 'assistente')->values();
        $responsaveis = $membros->where('tipo', 'responsavel')->values();
        $instrutores = $membros->where('tipo', 'instrutor')->values();
        $administrativos = $membros->where('tipo', 'administrativo')->values();

        $eixosConfig = config('cped_equipes.eixos', []);
        $coresEixo = config('cped_equipes.cores_eixo', []);
        $coresTipo = config('cped_equipes.cores_tipo', []);
        $labels = config('cped_equipes.tipos_labels', []);

        $eixosNomes = $responsaveis
            ->pluck('eixo_vinculado')
            ->filter()
            ->merge($eixosConfig)
            ->unique()
            ->values();

        $ramos = $eixosNomes->map(function (string $eixo) use ($responsaveis, $instrutores, $coresEixo) {
            $responsavel = $responsaveis->firstWhere('eixo_vinculado', $eixo);
            $equipe = $instrutores
                ->filter(fn (CpedEquipe $item) => $item->eixo_vinculado === $eixo)
                ->map(fn (CpedEquipe $item) => $this->formatarMembro($item))
                ->values()
                ->all();

            return [
                'eixo' => $eixo,
                'cor' => $coresEixo[$eixo] ?? '#003F7D',
                'responsavel' => $responsavel ? $this->formatarMembro($responsavel) : null,
                'equipe' => $equipe,
                'total' => ($responsavel ? 1 : 0) + count($equipe),
            ];
        })->filter(fn (array $ramo) => $ramo['responsavel'] || count($ramo['equipe']) > 0)
            ->values()
            ->all();

        return [
            'ordenador' => $ordenador ? $this->formatarMembro($ordenador) : null,
            'assistentes' => $assistentes->map(fn (CpedEquipe $item) => $this->formatarMembro($item))->values()->all(),
            'ramos' => $ramos,
            'administrativos' => $administrativos->map(fn (CpedEquipe $item) => $this->formatarMembro($item))->values()->all(),
            'meta' => [
                'total' => $membros->count(),
                'total_eixos' => count($ramos),
                'total_instrutores' => $instrutores->count(),
                'total_administrativos' => $administrativos->count(),
                'tipos_labels' => $labels,
                'cores_tipo' => $coresTipo,
                'cores_eixo' => $coresEixo,
            ],
        ];
    }

    private function formatarMembro(CpedEquipe $membro): array
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
