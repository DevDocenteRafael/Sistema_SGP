<?php

namespace Database\Seeders;

use App\Models\AcaoExtensiva;
use Illuminate\Database\Seeder;

class AcaoExtensivaSeeder extends Seeder
{
    public function run(): void
    {
        $registros = [
            [
                'priorizacao' => 'Média',
                'atribuido' => 'ana.5041',
                'eixo' => 'Gastronomia e Turismo',
                'numero_processo_sei' => '2026.000001381-46',
                'tipo' => 'Ação Extensiva',
                'assunto' => 'Ação extensiva: Sabores da Culinária Regional Brasileira - Salão do Artesanato 2026',
                'objetivo' => "Promover conhecimentos sobre técnicas e ingredientes da culinária regional brasileira por meio de uma aula-show demonstrativa e interativa.\nEstimular o reconhecimento da identidade cultural por meio da gastronomia regional.",
                'status' => 'CPED',
                'ultima_atualizacao' => '2026-05-06',
            ],
            [
                'priorizacao' => 'Média',
                'atribuido' => 'barbara.6003',
                'eixo' => 'Gastronomia e Turismo',
                'numero_processo_sei' => '2026.000003012-33',
                'tipo' => 'Ação Extensiva',
                'assunto' => 'Como melhorar o cafezinho do dia a dia e conhecendo os diversos métodos de extração de café',
                'objetivo' => 'Proporcionar aos participantes uma experiência prática, abordando técnicas de degustação, de forma didática e aplicada.',
                'status' => 'DEP',
                'ultima_atualizacao' => '2026-06-10',
            ],
            [
                'priorizacao' => 'Alta',
                'atribuido' => 'hermesson.5559',
                'eixo' => 'Gastronomia e Turismo',
                'numero_processo_sei' => '2026.000003093-07',
                'tipo' => 'Ação Extensiva',
                'assunto' => 'Sabores regionais: Arroz carreteiro',
                'objetivo' => "Valorizar a culinária brasileira tradicional\nApresentar técnicas de preparo do arroz carreteiro\nPromover experiência sensorial e cultural",
                'status' => 'DEP',
                'ultima_atualizacao' => '2026-06-09',
            ],
            [
                'priorizacao' => 'Baixa',
                'atribuido' => 'barbara.6005',
                'eixo' => 'Gestão e Negócios',
                'numero_processo_sei' => '2026.000003011-52',
                'tipo' => 'Ação Extensiva',
                'assunto' => 'Coquetéis com café: Releitura da caipirinha e Orange Coffee',
                'objetivo' => 'Explorar técnicas de preparo, extração do café e degustação com harmonizações, com apelo comercial e formativo.',
                'status' => 'DIREG',
                'ultima_atualizacao' => '2026-06-09',
            ],
            [
                'priorizacao' => 'Resolvido',
                'atribuido' => 'barbara.6004',
                'eixo' => 'Saúde e Segurança',
                'numero_processo_sei' => '2026.000003013-14',
                'tipo' => 'Ação Extensiva',
                'assunto' => 'Coquetelaria com Cachaça: Releitura da caipirinha clássica',
                'objetivo' => 'Estimular o interesse pela área de gastronomia e bebidas; divulgar os cursos do Senac; proporcionar vivência prática.',
                'status' => 'NC',
                'ultima_atualizacao' => '2026-06-09',
            ],
        ];

        foreach ($registros as $registro) {
            AcaoExtensiva::query()->updateOrCreate(
                ['numero_processo_sei' => $registro['numero_processo_sei']],
                $registro
            );
        }
    }
}
