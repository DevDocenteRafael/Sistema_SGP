<?php

namespace Database\Seeders;

use App\Models\Evento;
use Illuminate\Database\Seeder;

class EventoSeeder extends Seeder
{
    public function run(): void
    {
        $registros = [
            [
                'nome' => 'Semana Pedagógica CPED 2025',
                'ano' => '2025',
                'data' => '2025-08-12',
                'unidade' => 'Sobradinho',
                'eixo' => 'Tecnologia e Economia Criativa',
                'quantidade_pessoas' => 85,
                'equipe' => 'Equipe CPED',
                'possui_acao_extensiva' => 'Sim',
                'acao_vinculada' => 'Palestra: Inteligência Artificial Aplicada a Negócios',
                'status' => 'Realizado',
                'observacao' => 'Evento realizado com boa adesão do público interno.',
            ],
            [
                'nome' => 'Feira de Profissões SENAC DF',
                'ano' => '2025',
                'data' => '2025-09-25',
                'unidade' => 'Taguatinga',
                'eixo' => 'Ambiente e Saúde',
                'quantidade_pessoas' => 320,
                'equipe' => 'Portfólio e Comunicação',
                'possui_acao_extensiva' => 'Não',
                'acao_vinculada' => null,
                'status' => 'Planejado',
                'observacao' => 'Aguardando definição final da programação por eixo.',
            ],
            [
                'nome' => 'Mostra Gastronômica de Fim de Ano',
                'ano' => '2025',
                'data' => '2025-12-05',
                'unidade' => 'Faculdade de Tecnologia e Inovação Senac-DF — Campus Taguatinga',
                'eixo' => 'Gastronomia',
                'quantidade_pessoas' => 120,
                'equipe' => 'Gastronomia e Turismo',
                'possui_acao_extensiva' => 'Sim',
                'acao_vinculada' => 'Oficina de Boas Práticas em Manipulação de Alimentos',
                'status' => 'Planejado',
                'observacao' => null,
            ],
        ];

        foreach ($registros as $registro) {
            Evento::query()->updateOrCreate(
                ['nome' => $registro['nome'], 'data' => $registro['data']],
                $registro
            );
        }
    }
}
