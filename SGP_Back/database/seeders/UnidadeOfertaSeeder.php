<?php

namespace Database\Seeders;

use App\Models\RegiaoAdministrativa;
use App\Models\UnidadeOferta;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UnidadeOfertaSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ...array_values(config('unidades', [])),
            'Candangolândia',
            'Planaltina',
            'Recanto das Emas',
            'Pátio Brasil',
            'Setor Comercial Sul',
        ] as $localidade) {
            RegiaoAdministrativa::query()->firstOrCreate(
                ['nome' => $localidade],
                ['ativo' => true],
            );
        }

        $estruturas = [
            [
                'localidade' => 'Asa Sul',
                'nome' => 'Faculdade de Tecnologia e Inovação Senac-DF — Campus 913 Sul',
                'tipo' => UnidadeOferta::TIPO_FACULDADE,
                'codigo' => '713/913',
                'endereco' => 'SEP Sul, trecho 713/913',
                'responsavel' => null,
                'aliases' => ['Asa Sul 713/913'],
            ],
            [
                'localidade' => 'Taguatinga',
                'nome' => 'Faculdade de Tecnologia e Inovação Senac-DF — Campus Taguatinga',
                'tipo' => UnidadeOferta::TIPO_FACULDADE,
                'endereco' => 'Centro de Educação Profissional Jó Rufino e Carlos Aguiar',
                'responsavel' => null,
                'aliases' => ['Taguatinga Norte', 'Faculdade Taguatinga'],
            ],
            [
                'localidade' => 'Asa Norte',
                'nome' => 'Faculdade de Tecnologia e Inovação Senac-DF — Campus 712/912 Norte',
                'tipo' => UnidadeOferta::TIPO_FACULDADE,
                'codigo' => '712/912',
                'endereco' => null,
                'responsavel' => null,
                'aliases' => ['Asa Norte 712/912'],
            ],
            [
                'localidade' => 'Brazlândia',
                'nome' => 'Senac Brazlândia',
                'tipo' => UnidadeOferta::TIPO_POLO,
                'aliases' => ['Brazlândia'],
            ],
            [
                'localidade' => 'Candangolândia',
                'nome' => 'Polo de Educação Profissional Senac Israel Pinheiro — Candangolândia',
                'tipo' => UnidadeOferta::TIPO_POLO,
                'responsavel' => 'Israel Pinheiro',
                'aliases' => ['Candangolândia', 'Israel Pinheiro'],
            ],
            [
                'localidade' => 'Pátio Brasil',
                'nome' => 'Senac Pátio Brasil Shopping',
                'tipo' => UnidadeOferta::TIPO_POLO,
                'aliases' => ['Pátio Brasil Shopping'],
            ],
            [
                'localidade' => 'Planaltina',
                'nome' => 'Polo de Educação Profissional Senac — Planaltina',
                'tipo' => UnidadeOferta::TIPO_POLO,
                'aliases' => ['Planaltina'],
            ],
            [
                'localidade' => 'Recanto das Emas',
                'nome' => 'Polo de Educação Profissional Senac — Recanto das Emas',
                'tipo' => UnidadeOferta::TIPO_POLO,
                'aliases' => ['Recanto das Emas'],
            ],
            [
                'localidade' => 'Santa Maria',
                'nome' => 'Senac Santa Maria',
                'tipo' => UnidadeOferta::TIPO_POLO,
                'aliases' => ['Santa Maria'],
            ],
            [
                'localidade' => 'São Sebastião',
                'nome' => 'Senac São Sebastião',
                'tipo' => UnidadeOferta::TIPO_POLO,
                'aliases' => ['São Sebastião'],
            ],
            [
                'localidade' => 'Setor Comercial Sul',
                'nome' => 'Centro de Educação Profissional Ennius Marcus de Moraes Muniz',
                'tipo' => UnidadeOferta::TIPO_UNIDADE,
                'endereco' => 'Setor Comercial Sul',
                'aliases' => ['Ennius Muniz'],
            ],
            [
                'localidade' => 'Sobradinho',
                'nome' => 'Centro de Educação Profissional Sobradinho',
                'tipo' => UnidadeOferta::TIPO_UNIDADE,
                'aliases' => ['Sobradinho'],
            ],
            [
                'localidade' => 'Gama',
                'nome' => 'Centro de Educação Profissional Joaquim Loiola — Gama',
                'tipo' => UnidadeOferta::TIPO_UNIDADE,
                'responsavel' => 'Joaquim Loiola',
                'aliases' => ['Gama'],
            ],
            [
                'localidade' => 'Ceilândia',
                'nome' => 'Centro de Educação Profissional Talal Abu-Allan — Ceilândia',
                'tipo' => UnidadeOferta::TIPO_UNIDADE,
                'responsavel' => 'Talal Abu-Allan',
                'aliases' => ['Ceilândia'],
            ],
            [
                'localidade' => 'Setor Comercial Sul',
                'nome' => 'Centro de Educação Profissional Miguel Setembrino — Setor Comercial Sul',
                'tipo' => UnidadeOferta::TIPO_UNIDADE,
                'codigo' => '106/136',
                'endereco' => 'SCS Quadra 4, Bloco A, Lote 106/136',
                'aliases' => ['Miguel Setembrino'],
            ],
        ];

        foreach ($estruturas as $item) {
            $this->sincronizarEstrutura($item);
        }

        UnidadeOferta::query()->where('tipo', 'cep')->update(['tipo' => UnidadeOferta::TIPO_UNIDADE]);

        UnidadeOferta::query()
            ->where('nome', 'like', '%Campus 712/912 Norte%')
            ->where('tipo', UnidadeOferta::TIPO_FACULDADE)
            ->update(['responsavel' => null]);

        $this->removerEstruturasLegadasGenericas();
    }

    private function removerEstruturasLegadasGenericas(): void
    {
        $mapa = [
            'Asa Norte' => 'Faculdade de Tecnologia e Inovação Senac-DF — Campus 712/912 Norte',
            'Asa Sul' => 'Faculdade de Tecnologia e Inovação Senac-DF — Campus 913 Sul',
            'Taguatinga' => 'Faculdade de Tecnologia e Inovação Senac-DF — Campus Taguatinga',
            'Jessé Freire' => null,
        ];

        foreach ($mapa as $legado => $destino) {
            if ($destino) {
                $this->atualizarReferenciasPorNome($legado, $destino);
            } else {
                $this->limparReferenciasPorNome($legado);
            }

            UnidadeOferta::query()
                ->where('nome', $legado)
                ->where('tipo', UnidadeOferta::TIPO_UNIDADE)
                ->delete();
        }
    }

    private function limparReferenciasPorNome(string $nome): void
    {
        foreach ([
            'usuarios' => 'unidade',
            'eventos' => 'unidade',
            'visita_tecnicas' => 'unidade',
            'curso_por_eixos' => 'unidade',
            'pcas' => 'unidade',
            'cursos' => 'unidade',
        ] as $tabela => $coluna) {
            if (! Schema::hasTable($tabela) || ! Schema::hasColumn($tabela, $coluna)) {
                continue;
            }
            DB::table($tabela)->where($coluna, $nome)->update([$coluna => null]);
        }

        if (Schema::hasTable('cursos') && Schema::hasColumn('cursos', 'unidades_oferta')) {
            foreach (DB::table('cursos')->whereNotNull('unidades_oferta')->select('id', 'unidades_oferta')->get() as $curso) {
                $lista = json_decode($curso->unidades_oferta, true);
                if (! is_array($lista)) {
                    continue;
                }
                $nova = array_values(array_filter($lista, fn ($item) => $item !== $nome));
                if ($nova !== $lista) {
                    DB::table('cursos')->where('id', $curso->id)->update([
                        'unidades_oferta' => $nova === [] ? null : json_encode($nova, JSON_UNESCAPED_UNICODE),
                    ]);
                }
            }
        }
    }

    private function sincronizarEstrutura(array $item): void
    {
        $regiao = RegiaoAdministrativa::query()->firstOrCreate(
            ['nome' => $item['localidade']],
            ['ativo' => true],
        );

        $aliases = array_values(array_unique([$item['nome'], ...($item['aliases'] ?? [])]));

        $registro = UnidadeOferta::query()
            ->whereIn('nome', $aliases)
            ->orderByRaw('CASE WHEN nome = ? THEN 0 ELSE 1 END', [$item['nome']])
            ->first() ?? new UnidadeOferta;

        $nomeAnterior = $registro->exists ? $registro->nome : null;

        $registro->fill([
            'regiao_administrativa_id' => $regiao->id,
            'nome' => $item['nome'],
            'tipo' => $item['tipo'],
            'codigo' => $item['codigo'] ?? null,
            'endereco' => $item['endereco'] ?? null,
            'responsavel' => array_key_exists('responsavel', $item) ? $item['responsavel'] : $registro->responsavel,
            'ativo' => true,
        ]);
        $registro->save();

        if ($nomeAnterior && $nomeAnterior !== $item['nome']) {
            $this->atualizarReferenciasPorNome($nomeAnterior, $item['nome']);
        }

        UnidadeOferta::query()
            ->whereIn('nome', $item['aliases'] ?? [])
            ->where('id', '!=', $registro->id)
            ->each(function (UnidadeOferta $duplicata) use ($item) {
                $this->atualizarReferenciasPorNome($duplicata->nome, $item['nome']);
                $duplicata->ativo = false;
                $duplicata->save();
            });
    }

    private function atualizarReferenciasPorNome(string $antigo, string $novo): void
    {
        if ($antigo === $novo) {
            return;
        }

        foreach ([
            'usuarios' => 'unidade',
            'eventos' => 'unidade',
            'visita_tecnicas' => 'unidade',
            'curso_por_eixos' => 'unidade',
            'pcas' => 'unidade',
            'cursos' => 'unidade',
        ] as $tabela => $coluna) {
            if (! Schema::hasTable($tabela) || ! Schema::hasColumn($tabela, $coluna)) {
                continue;
            }
            DB::table($tabela)->where($coluna, $antigo)->update([$coluna => $novo]);
        }

        if (Schema::hasTable('cursos') && Schema::hasColumn('cursos', 'unidades_oferta')) {
            foreach (DB::table('cursos')->whereNotNull('unidades_oferta')->select('id', 'unidades_oferta')->get() as $curso) {
                $lista = json_decode($curso->unidades_oferta, true);
                if (! is_array($lista)) {
                    continue;
                }
                $alterou = false;
                $nova = array_map(function ($nome) use ($antigo, $novo, &$alterou) {
                    if ($nome === $antigo) {
                        $alterou = true;
                        return $novo;
                    }
                    return $nome;
                }, $lista);
                if ($alterou) {
                    DB::table('cursos')->where('id', $curso->id)->update([
                        'unidades_oferta' => json_encode(array_values(array_unique($nova)), JSON_UNESCAPED_UNICODE),
                    ]);
                }
            }
        }
    }
}
