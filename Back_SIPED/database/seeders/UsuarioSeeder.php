<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = [
            [
                'email' => 'administrador@df.senac.br',
                'nome' => 'Administrador do Sistema',
                'senha' => 'senac2025',
                'perfil' => Usuario::PERFIL_ADMINISTRADOR,
                'unidade' => 'Faculdade de Tecnologia e Inovação Senac-DF — Campus 712/912 Norte',
                'area' => 'Coordenação Pedagógica',
            ],
            [
                'email' => 'editor@df.senac.br',
                'nome' => 'Editor de Portfólio',
                'senha' => 'editor2025',
                'perfil' => Usuario::PERFIL_EDITOR,
                'unidade' => 'Faculdade de Tecnologia e Inovação Senac-DF — Campus 712/912 Norte',
                'area' => 'Equipe Pedagógica',
            ],
            [
                'email' => 'consultor@df.senac.br',
                'nome' => 'Consultor de Portfólio',
                'senha' => 'consultor2025',
                'perfil' => Usuario::PERFIL_CONSULTOR,
                'unidade' => 'Faculdade de Tecnologia e Inovação Senac-DF — Campus 712/912 Norte',
                'area' => 'Gestão',
            ],
        ];

        foreach ($usuarios as $dados) {
            Usuario::updateOrCreate(
                ['email' => $dados['email']],
                [
                    'nome' => $dados['nome'],
                    'senha' => Hash::make($dados['senha']),
                    'perfil' => $dados['perfil'],
                    'status' => true,
                    'unidade' => $dados['unidade'],
                    'area' => $dados['area'],
                ]
            );
        }

        if ($this->command) {
            $this->command->warn('Usuários demo: administrador@df.senac.br / editor@df.senac.br / consultor@df.senac.br');
            $this->command->warn('Troque as senhas padrão antes de qualquer ambiente compartilhado ou homologação.');
        }
    }
}
