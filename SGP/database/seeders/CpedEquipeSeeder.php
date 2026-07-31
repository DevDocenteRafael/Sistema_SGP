<?php

namespace Database\Seeders;

use App\Models\CpedEquipe;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CpedEquipeSeeder extends Seeder
{
    public function run(): void
    {
        $registros = [
            [
                'nome' => 'João Carlos Mendes Silva',
                'cargo' => 'Coordenador Geral / Ordenador',
                'setor' => 'CPED',
                'contato' => 'ceped@senac.df.br',
                'tipo' => 'ordenador',
                'eixo_vinculado' => null,
                'iniciais' => 'JC',
                'cor' => '#003F7D',
            ],
            [
                'nome' => 'Maria Paula Rodrigues',
                'cargo' => 'Assistente Administrativa',
                'setor' => 'Secretaria Geral',
                'contato' => 'mpaula@senac.df.br',
                'tipo' => 'assistente',
                'eixo_vinculado' => null,
                'iniciais' => 'MP',
                'cor' => '#5C6BC0',
            ],
            [
                'nome' => 'Carlos Eduardo Lima',
                'cargo' => 'Assistente Administrativo',
                'setor' => 'Secretaria Geral',
                'contato' => 'clima@senac.df.br',
                'tipo' => 'assistente',
                'eixo_vinculado' => null,
                'iniciais' => 'CE',
                'cor' => '#5C6BC0',
            ],
            [
                'nome' => 'Ana Beatriz Fonseca',
                'cargo' => 'Responsável de Eixo',
                'setor' => 'Gastronomia',
                'contato' => 'abfonseca@senac.df.br',
                'tipo' => 'responsavel',
                'eixo_vinculado' => 'Gastronomia',
                'iniciais' => 'AB',
                'cor' => '#E65100',
            ],
            [
                'nome' => 'Fernanda Cristina Borges',
                'cargo' => 'Responsável de Eixo',
                'setor' => 'Beleza e Cuidado Pessoal',
                'contato' => 'fcborges@senac.df.br',
                'tipo' => 'responsavel',
                'eixo_vinculado' => 'Beleza e Cuidado Pessoal',
                'iniciais' => 'FC',
                'cor' => '#AD1457',
            ],
            [
                'nome' => 'Roberto Augusto Pinto',
                'cargo' => 'Responsável de Eixo',
                'setor' => 'Gestão e Negócios',
                'contato' => 'rapinto@senac.df.br',
                'tipo' => 'responsavel',
                'eixo_vinculado' => 'Gestão e Negócios',
                'iniciais' => 'RA',
                'cor' => '#1565C0',
            ],
            [
                'nome' => 'Juliana Moraes Cardoso',
                'cargo' => 'Responsável de Eixo',
                'setor' => 'Tecnologia e Economia Criativa',
                'contato' => 'jcardoso@senac.df.br',
                'tipo' => 'responsavel',
                'eixo_vinculado' => 'Tecnologia e Economia Criativa',
                'iniciais' => 'JM',
                'cor' => '#6A1B9A',
            ],
            [
                'nome' => 'Marcos Vinícius Alves',
                'cargo' => 'Responsável de Eixo',
                'setor' => 'Ambiente e Saúde',
                'contato' => 'mvalves@senac.df.br',
                'tipo' => 'responsavel',
                'eixo_vinculado' => 'Ambiente e Saúde',
                'iniciais' => 'MV',
                'cor' => '#2E7D32',
            ],
            [
                'nome' => 'Priscila Torres Melo',
                'cargo' => 'Responsável de Eixo',
                'setor' => 'Gestão e Moda',
                'contato' => 'ptmelo@senac.df.br',
                'tipo' => 'responsavel',
                'eixo_vinculado' => 'Gestão e Moda',
                'iniciais' => 'PT',
                'cor' => '#B71C1C',
            ],
            [
                'nome' => 'Chef André Luiz Santos',
                'cargo' => 'Instrutor',
                'setor' => 'Gastronomia',
                'contato' => 'alsantos@senac.df.br',
                'tipo' => 'instrutor',
                'eixo_vinculado' => 'Gastronomia',
                'iniciais' => 'AL',
                'cor' => '#F57C00',
            ],
            [
                'nome' => 'Patrícia Cavalcante',
                'cargo' => 'Instrutora',
                'setor' => 'Gastronomia',
                'contato' => 'pcavalcante@senac.df.br',
                'tipo' => 'instrutor',
                'eixo_vinculado' => 'Gastronomia',
                'iniciais' => 'PC',
                'cor' => '#F57C00',
            ],
            [
                'nome' => 'Renata Souza Costa',
                'cargo' => 'Instrutora',
                'setor' => 'Beleza e Cuidado Pessoal',
                'contato' => 'rscosta@senac.df.br',
                'tipo' => 'instrutor',
                'eixo_vinculado' => 'Beleza e Cuidado Pessoal',
                'iniciais' => 'RS',
                'cor' => '#E91E63',
            ],
            [
                'nome' => 'Diego Ferreira Ramos',
                'cargo' => 'Instrutor',
                'setor' => 'Beleza e Cuidado Pessoal',
                'contato' => 'dframos@senac.df.br',
                'tipo' => 'instrutor',
                'eixo_vinculado' => 'Beleza e Cuidado Pessoal',
                'iniciais' => 'DF',
                'cor' => '#E91E63',
            ],
            [
                'nome' => 'Luciana Peixoto Tavares',
                'cargo' => 'Instrutora',
                'setor' => 'Gestão e Negócios',
                'contato' => 'lptavares@senac.df.br',
                'tipo' => 'instrutor',
                'eixo_vinculado' => 'Gestão e Negócios',
                'iniciais' => 'LP',
                'cor' => '#1976D2',
            ],
            [
                'nome' => 'Alexandre Cunha Freitas',
                'cargo' => 'Instrutor',
                'setor' => 'Gestão e Negócios',
                'contato' => 'acfreitas@senac.df.br',
                'tipo' => 'instrutor',
                'eixo_vinculado' => 'Gestão e Negócios',
                'iniciais' => 'AC',
                'cor' => '#1976D2',
            ],
            [
                'nome' => 'Thiago Mendonça Pereira',
                'cargo' => 'Instrutor',
                'setor' => 'Tecnologia e Economia Criativa',
                'contato' => 'tmpereira@senac.df.br',
                'tipo' => 'instrutor',
                'eixo_vinculado' => 'Tecnologia e Economia Criativa',
                'iniciais' => 'TM',
                'cor' => '#7B1FA2',
            ],
            [
                'nome' => 'Camila Rocha Andrade',
                'cargo' => 'Instrutora',
                'setor' => 'Tecnologia e Economia Criativa',
                'contato' => 'crandrade@senac.df.br',
                'tipo' => 'instrutor',
                'eixo_vinculado' => 'Tecnologia e Economia Criativa',
                'iniciais' => 'CR',
                'cor' => '#7B1FA2',
            ],
            [
                'nome' => 'Enf.ª Cristiane Barbosa',
                'cargo' => 'Instrutora',
                'setor' => 'Ambiente e Saúde',
                'contato' => 'cbarbosa@senac.df.br',
                'tipo' => 'instrutor',
                'eixo_vinculado' => 'Ambiente e Saúde',
                'iniciais' => 'CB',
                'cor' => '#388E3C',
            ],
            [
                'nome' => 'Dr. Paulo Henrique Neves',
                'cargo' => 'Instrutor',
                'setor' => 'Ambiente e Saúde',
                'contato' => 'phneves@senac.df.br',
                'tipo' => 'instrutor',
                'eixo_vinculado' => 'Ambiente e Saúde',
                'iniciais' => 'PH',
                'cor' => '#388E3C',
            ],
            [
                'nome' => 'Isabela Guimarães Costa',
                'cargo' => 'Instrutora',
                'setor' => 'Gestão e Moda',
                'contato' => 'igcosta@senac.df.br',
                'tipo' => 'instrutor',
                'eixo_vinculado' => 'Gestão e Moda',
                'iniciais' => 'IG',
                'cor' => '#C62828',
            ],
            [
                'nome' => 'Sônia Aparecida Cruz',
                'cargo' => 'Técnica Administrativa',
                'setor' => 'Secretaria',
                'contato' => 'sacruz@senac.df.br',
                'tipo' => 'administrativo',
                'eixo_vinculado' => null,
                'iniciais' => 'SA',
                'cor' => '#00796B',
            ],
            [
                'nome' => 'Gabriel Oliveira Santos',
                'cargo' => 'Analista de TI',
                'setor' => 'TI / Sistemas',
                'contato' => 'gosantos@senac.df.br',
                'tipo' => 'administrativo',
                'eixo_vinculado' => null,
                'iniciais' => 'GO',
                'cor' => '#00796B',
            ],
            [
                'nome' => 'Vanessa Lima Martins',
                'cargo' => 'Técnica Administrativa',
                'setor' => 'Financeiro',
                'contato' => 'vlmartins@senac.df.br',
                'tipo' => 'administrativo',
                'eixo_vinculado' => null,
                'iniciais' => 'VL',
                'cor' => '#00796B',
            ],
            [
                'nome' => 'Henrique Castro Dias',
                'cargo' => 'Técnico Administrativo',
                'setor' => 'Patrimônio',
                'contato' => 'hcdias@senac.df.br',
                'tipo' => 'administrativo',
                'eixo_vinculado' => null,
                'iniciais' => 'HC',
                'cor' => '#00796B',
            ],
            [
                'nome' => 'Eduardo Ferreira de Lima',
                'cargo' => 'Supervisor',
                'setor' => 'CPED',
                'contato' => 'Edu@gmail.com',
                'tipo' => 'ordenador',
                'eixo_vinculado' => null,
                'iniciais' => 'EL',
                'cor' => '#003F7D',
                'foto_arquivo' => 'eduardo-ferreira-de-lima.jpg',
            ],
            [
                'nome' => 'Lucas Leal',
                'cargo' => 'Desenvolvedor',
                'setor' => 'TI / Sistemas',
                'contato' => 'lucaseduleal@gmail.com',
                'tipo' => 'administrativo',
                'eixo_vinculado' => null,
                'iniciais' => 'LL',
                'cor' => '#00796B',
                'foto_arquivo' => 'lucas-leal.png',
            ],
        ];

        foreach ($registros as $registro) {
            $fotoArquivo = $registro['foto_arquivo'] ?? null;
            unset($registro['foto_arquivo']);

            $dados = [
                ...$registro,
                'ativo' => true,
                'observacao' => null,
            ];

            if ($fotoArquivo) {
                $caminho = $this->instalarFotoSeed($fotoArquivo);
                if ($caminho) {
                    $dados['foto'] = $caminho;
                }
            }

            CpedEquipe::query()->updateOrCreate(
                ['contato' => $registro['contato']],
                $dados
            );
        }
    }

    /**
     * Copia foto versionada em database/data/cped para o storage público.
     * Assim qualquer máquina com git pull + seed passa a ter as imagens.
     */
    private function instalarFotoSeed(string $nomeArquivo): ?string
    {
        $origem = database_path('data/cped/'.$nomeArquivo);

        if (! File::isFile($origem)) {
            return null;
        }

        $destino = 'cped/'.$nomeArquivo;
        Storage::disk('public')->put($destino, File::get($origem));

        return $destino;
    }
}
