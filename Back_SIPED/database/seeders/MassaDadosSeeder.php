<?php

namespace Database\Seeders;

use App\Models\PortfolioCiclo;
use App\Support\CatalogoOficial;
use Database\Seeders\Concerns\GeraMassa;
use Database\Seeders\Concerns\SomenteForaDeProducao;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MassaDadosSeeder extends Seeder
{
    use GeraMassa;
    use SomenteForaDeProducao;

    public function run(): void
    {
        $this->bloquearEmProducao();

        $ciclos = PortfolioCiclo::query()->orderByDesc('atual')->orderBy('id')->get();
        $cicloAtualId = PortfolioCiclo::atual()?->id ?? $ciclos->first()?->id;
        $unidades = DB::table('unidades_oferta')->orderBy('id')->pluck('nome')->all();
        if ($unidades === []) {
            $unidades = ['Faculdade de Tecnologia e Inovação Senac-DF — Campus 712/912 Norte'];
        }

        $eixosMapa = CatalogoOficial::segmentosPorEixo();
        $pares = [];
        foreach ($eixosMapa as $eixo => $segmentos) {
            $eixoRow = DB::table('eixos')->where('nome', $eixo)->first();
            foreach ($segmentos as $segmento) {
                $segRow = DB::table('segmentos')->where('nome', $segmento)->where('eixo_id', $eixoRow?->id)->first();
                $pares[] = [
                    'eixo' => $eixo,
                    'eixo_id' => $eixoRow?->id,
                    'segmento' => $segmento,
                    'segmento_id' => $segRow?->id,
                ];
            }
        }

        $modalidades = CatalogoOficial::modalidades() ?: ['Qualificação Profissional'];
        $statusCurso = ['ATIVO' => 60, 'EM REVISÃO' => 15, 'SUSPENSO' => 15, 'INATIVO' => 10];
        $agora = $this->agora();
        $origem = $this->origemSeeder();

        $cursos = [];
        $cursoId = 1;
        $distribuicaoCiclo = [];
        foreach ($ciclos as $ciclo) {
            $qtd = (int) $ciclo->id === (int) $cicloAtualId ? 900 : 150;
            $distribuicaoCiclo[(int) $ciclo->id] = $qtd;
            for ($i = 0; $i < $qtd; $i++) {
                $par = $this->item($pares ?: [['eixo' => 'Gestão e Moda', 'eixo_id' => null, 'segmento' => 'Gestão e Comércio', 'segmento_id' => null]], $cursoId);
                $mod = $this->item($modalidades, $cursoId);
                $unidade = $this->item($unidades, $cursoId);
                $semClassificacao = $cursoId % 17 === 0;
                    $cursos[] = array_merge($origem, [
                        'ciclo_id' => $ciclo->id,
                        'titulo' => $par['segmento'].' — '.$mod.' '.str_pad((string) $cursoId, 4, '0', STR_PAD_LEFT),
                        'eixo' => $semClassificacao ? null : $par['eixo'],
                        'eixo_id' => $semClassificacao ? null : $par['eixo_id'],
                        'segmento' => $semClassificacao ? null : $par['segmento'],
                        'segmento_id' => $semClassificacao ? null : $par['segmento_id'],
                    'modalidade' => $mod,
                    'carga_horaria' => (string) (40 + (($cursoId * 8) % 360)),
                    'status' => $this->statusPorPeso($statusCurso, $cursoId),
                    'codigo_sig' => 'SIG-'.str_pad((string) $cursoId, 5, '0', STR_PAD_LEFT),
                    'codigo_dn' => (string) (10000 + $cursoId),
                    'identificacao' => 'CUR-'.$cursoId,
                    'tipo' => $this->item(['Técnico', 'Livre', 'Qualificação', 'Aperfeiçoamento'], $cursoId),
                    'ultima_revisao' => (string) (2023 + ($cursoId % 4)),
                    'processo_sei' => sprintf('2026.%05d/%04d', $cursoId, $cursoId % 99 + 1),
                    'unidade' => $unidade,
                    'unidades_oferta' => json_encode([$unidade]),
                    'compativel_bolsa' => $cursoId % 4 === 0 ? 'NÃO' : 'SIM',
                    'comercial' => $cursoId % 5 === 0 ? 'NÃO' : 'SIM',
                    'programa' => $cursoId % 10 === 0 ? 'Ensino Médio' : ($cursoId % 23 === 0 ? '60+' : null),
                    'turmas' => (string) (1 + $cursoId % 6),
                    'alunos' => (string) (15 + $cursoId % 25),
                    'codigo_processo' => 'PROC-'.str_pad((string) $cursoId, 5, '0', STR_PAD_LEFT),
                    'instrutor' => $this->item(['Ana Souza', 'Bruno Lima', 'Carla Mendes', 'Daniel Rego', 'Elena Prado'], $cursoId),
                    'descricao' => 'Curso fictício de '.$par['segmento'].' para homologação do portfólio.',
                    'data_inicio' => sprintf('2026-%02d-01', ($cursoId % 12) + 1),
                    'data_fim' => sprintf('2026-%02d-28', ($cursoId % 12) + 1),
                    'observacoes' => 'Curso fictício gerado pelo seeder.',
                    'valores' => 'R$ '.number_format(300 + ($cursoId % 40) * 50, 2, ',', '.'),
                    'pcn' => 'PCN-'.str_pad((string) ($cursoId % 300), 3, '0', STR_PAD_LEFT),
                    'pcr' => 'PCR-'.str_pad((string) ($cursoId % 300), 3, '0', STR_PAD_LEFT),
                    'created_at' => $agora,
                    'updated_at' => $agora,
                ]);
                $cursoId++;
            }
        }
        $this->inserirLotes('cursos', $cursos);
        $cursosDb = DB::table('cursos')->select('id', 'titulo', 'eixo', 'eixo_id', 'segmento', 'segmento_id', 'ciclo_id', 'carga_horaria')->orderBy('id')->get();

        $ofertas = [];
        $totalOfertas = 1800;
        for ($i = 1; $i <= $totalOfertas; $i++) {
            $semVinculo = $i > 1500;
            $curso = $semVinculo ? null : $cursosDb[$i % $cursosDb->count()];
            $par = $this->item($pares ?: [['eixo' => 'Gestão e Moda', 'eixo_id' => null, 'segmento' => 'Gestão e Comércio', 'segmento_id' => null]], $i);
            $cicloId = $curso->ciclo_id ?? $cicloAtualId;
            $ofertas[] = array_merge($origem, [
                'ciclo_id' => $cicloId,
                'curso_id' => $semVinculo ? null : $curso->id,
                'curso' => $semVinculo ? 'Oferta importada pendente '.$i : $curso->titulo,
                'eixo' => $curso->eixo ?? $par['eixo'],
                'eixo_id' => $curso->eixo_id ?? $par['eixo_id'],
                'segmento' => $curso->segmento ?? $par['segmento'],
                'segmento_id' => $curso->segmento_id ?? $par['segmento_id'],
                'unidade' => $this->item($unidades, $i),
                'ano' => (string) (2024 + ($i % 4)),
                'ch' => (string) ($curso->carga_horaria ?? (80 + ($i % 200))),
                'turmas' => (string) ($i % 6),
                'codigo' => sprintf('2025.%02d.%02d', ($i % 40) + 1, ($i % 90) + 1),
                'alunos' => (string) ($i % 35),
                'instrutores' => $this->item(['Ana Souza', 'Bruno Lima', 'Carla Mendes', 'Daniel Rego', 'Elena Prado'], $i),
                'status' => $this->statusPorPeso(['Ativo' => 60, 'Pendente' => 15, 'Inativo' => 15, 'Concluído' => 10], $i),
                'is_novo' => $i % 9 === 0,
                'programa' => $i % 10 === 0 ? 'Ensino Médio' : null,
                'observacao' => $semVinculo ? 'Sem curso oficial correspondente.' : null,
                'created_at' => $agora,
                'updated_at' => $agora,
            ]);
        }
        $this->inserirLotes('curso_por_eixos', $ofertas);

        $this->gerarPlanejamento($cursosDb, $unidades, $cicloAtualId, $origem, $agora);
        $this->gerarProcessos($pares, $unidades, $ciclos, $cicloAtualId, $origem, $agora);
        $this->gerarDocumentos($pares, $origem, $agora);
        $this->gerarAuditoria($cicloAtualId, $agora);
        $this->preencherAutoria();
    }

    /**
     * Registros fictícios aparecem como cadastrados pelo Administrador e alterados pelo Editor demo.
     */
    private function preencherAutoria(): void
    {
        $admin = DB::table('usuarios')->where('email', 'administrador@df.senac.br')->value('id')
            ?? DB::table('usuarios')->orderBy('id')->value('id');
        $editor = DB::table('usuarios')->where('email', 'editor@df.senac.br')->value('id') ?? $admin;
        if (! $admin) {
            return;
        }

        foreach (['cursos', 'curso_por_eixos', 'plano_de_metas', 'pcas', 'visita_tecnicas', 'hora_pedagogicas', 'acao_extensivas',
            'eventos', 'jornadas_pedagogicas', 'resolucoes', 'termos_referencia', 'cped_equipes', 'fluxogramas', 'kanban_cartoes'] as $tabela) {
            if (Schema::hasColumn($tabela, 'criado_por')) {
                DB::table($tabela)->whereNull('criado_por')->update(['criado_por' => $admin]);
            }
            if (Schema::hasColumn($tabela, 'atualizado_por')) {
                DB::table($tabela)->whereNull('atualizado_por')->update(['atualizado_por' => $editor]);
            }
        }
    }

    private function gerarPlanejamento($cursosDb, array $unidades, ?int $cicloAtualId, array $origem, string $agora): void
    {
        $planos = [];
        $meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        for ($i = 1; $i <= 600; $i++) {
            $curso = $cursosDb[$i % max(1, $cursosDb->count())];
            $planos[] = array_merge($origem, [
                'ciclo_id' => $curso->ciclo_id ?? $cicloAtualId,
                'segmento' => $curso->segmento,
                'curso' => $curso->titulo,
                'tipo' => $this->item(['QUALIFICAÇÃO', 'PRESENCIAL', 'HÍBRIDO', 'EAD'], $i),
                'numero_sei' => 'SEI-PM-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'codigo_sig' => 'SIG-PM-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'mes_entrega' => $this->item($meses, $i),
                'status' => $this->statusPorPeso(['EM ANÁLISE' => 20, 'EM ANDAMENTO' => 40, 'CONCLUÍDO' => 30, 'CANCELADO' => 10], $i),
                'origem' => 'Plano de Metas',
                'area_planejamento' => $this->item(['Planejamento Estratégico', 'DN', 'DEF', 'CPED'], $i),
                'status_final' => $this->item(['PENDENTE', 'ENTREGUE', 'PUBLICADO'], $i),
                'observacao' => 'Registro fictício de planejamento.',
                'ano' => 2024 + ($i % 4),
                'created_at' => $agora,
                'updated_at' => $agora,
            ]);
        }
        $this->inserirLotes('plano_de_metas', $planos);

        $pcas = [];
        for ($i = 1; $i <= 600; $i++) {
            $curso = $cursosDb[$i % max(1, $cursosDb->count())];
            $pcas[] = array_merge($origem, [
                'ciclo_id' => $curso->ciclo_id ?? $cicloAtualId,
                'titulo' => $curso->titulo,
                'semestre' => (2024 + ($i % 3)).'/'.(($i % 2) + 1),
                'numero_sei' => 'SEI-PCA-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'codigo_sig' => 'SIG-PCA-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'eixo' => $curso->eixo,
                'unidade' => $this->item($unidades, $i),
                'carga_horaria' => (string) (400 + ($i % 800)),
                'valor' => 'R$ '.(1200 + ($i * 15)),
                'precificacao' => $this->item(['Valor cheio', 'Modular', 'Promocional'], $i),
                'valor_primeiro_modulo' => 'R$ '.(300 + ($i % 50) * 10),
                'parcelas_boleto' => 1 + $i % 10,
                'valor_parcela_boleto' => 'R$ '.round((1200 + $i * 15) / (1 + $i % 10), 2),
                'parcelas_cartao' => 1 + $i % 12,
                'valor_cartao' => 'R$ '.round((1200 + $i * 15) / (1 + $i % 12), 2),
                'parcela_desc_20' => 'R$ '.round((1200 + $i * 15) * 0.8, 2),
                'parcela_desc_15' => 'R$ '.round((1200 + $i * 15) * 0.85, 2),
                'status' => $this->statusPorPeso(['Vigente' => 55, 'Em análise' => 25, 'Encerrado' => 15, 'Cancelado' => 5], $i),
                'observacao' => 'PCA fictício para homologação.',
                'ano' => 2024 + ($i % 4),
                'created_at' => $agora,
                'updated_at' => $agora,
            ]);
        }
        $this->inserirLotes('pcas', $pcas);
    }

    private function gerarProcessos(array $pares, array $unidades, $ciclos, ?int $cicloAtualId, array $origem, string $agora): void
    {
        $visitas = [];
        $limites = ['Curso Técnico' => 4, 'Qualificação' => 2, 'Aperfeiçoamento' => 1, 'Aprendizagem' => 4];
        $etapas = ['Núcleo Pedagógico da unidade', 'Coordenação/área DEP/CPED', 'Direção Pedagógica'];
        for ($i = 1; $i <= 800; $i++) {
            $par = $this->item($pares ?: [['eixo' => 'Gestão e Moda']], $i);
            $ciclo = $this->item($ciclos->all(), $i);
            $status = $this->statusPorPeso(['Pendente' => 15, 'Em andamento' => 20, 'Aprovada' => 15, 'Recusada' => 5, 'Realizada' => 35, 'Atrasada' => 5, 'Cancelada' => 5], $i);
            $tipoCurso = $this->item(array_keys($limites), $i);
            $limite = $limites[$tipoCurso];
            $usadas = 1 + $i % ($limite + 2);
            $aprovadasAte = match ($status) {
                'Pendente' => 0,
                'Em andamento' => 1 + $i % 2,
                default => 3,
            };
            $decisoes = [];
            foreach (array_slice($etapas, 0, $aprovadasAte) as $n => $etapa) {
                $decisoes[] = ['etapa' => $etapa, 'decisao' => 'aprovada', 'responsavel' => $this->item(['Fernanda Alves', 'Gustavo Rocha', 'Helena Costa'], $i + $n),
                    'motivo' => null, 'data' => sprintf('2026-%02d-%02d', ($i % 12) + 1, min(28, ($i % 20) + 2 + $n))];
            }
            if ($status === 'Recusada') {
                $decisoes[count($decisoes) - 1]['decisao'] = 'recusada';
                $decisoes[count($decisoes) - 1]['motivo'] = 'Sem transporte disponível na data solicitada.';
            }
            $comTransporte = in_array($status, ['Aprovada', 'Realizada'], true);
            $visitas[] = array_merge($origem, [
                'turma' => sprintf('T%02d-2026-%02d', $i % 60, $i % 4 + 1),
                'instrutor' => $this->item(['Ana Souza', 'Bruno Lima', 'Carla Mendes', 'Daniel Rego', 'Elena Prado'], $i),
                'curso' => 'Curso de '.($par['segmento'] ?? $par['eixo']),
                'tipo_curso' => $tipoCurso,
                'local_visita' => $this->item(['Hospital Regional da Asa Norte', 'Hotel Nacional', 'Indústria Alimentícia DF', 'Centro de Convenções', 'Empresa de Tecnologia SIA'], $i),
                'visitas_utilizadas' => $usadas,
                'visitas_limite' => $limite,
                'justificativa_excedente' => $usadas > $limite ? 'Visita necessária para o projeto integrador da turma.' : null,
                'motivo_recusa' => $status === 'Recusada' ? 'Sem transporte disponível na data solicitada.' : null,
                'decisoes' => json_encode($decisoes, JSON_UNESCAPED_UNICODE),
                'transporte' => $comTransporte ? json_encode([
                    'tipo' => $this->item(['Micro-ônibus', 'Ônibus', 'Van'], $i),
                    'veiculo' => $this->item(['Micro-ônibus 24 lugares', 'Ônibus 44 lugares', 'Van 15 lugares'], $i),
                    'placa' => sprintf('JK%s%d%s%02d', chr(65 + $i % 26), $i % 10, chr(65 + ($i * 3) % 26), $i % 100),
                    'data_hora' => sprintf('2026-%02d-%02dT07:30:00-03:00', (($i + 1) % 12) + 1, ($i % 27) + 1),
                    'motorista' => $this->item(['José Pereira', 'Marcos Silva', 'Paulo Henrique'], $i),
                    'observacao' => 'Saída em frente à unidade.',
                ], JSON_UNESCAPED_UNICODE) : null,
                'relatorio' => $status === 'Realizada' ? 'Visita realizada conforme o planejado; turma participou das atividades previstas.' : null,
                'ciclo_id' => $ciclo->id ?? $cicloAtualId,
                'unidade' => $this->item($unidades, $i),
                'eixo' => $par['eixo'],
                'processo_sei' => sprintf('00001.%06d/2026-%02d', $i, $i % 90 + 1),
                'data_solicitacao' => sprintf('2026-%02d-%02d', ($i % 12) + 1, ($i % 27) + 1),
                'data_visita_prevista' => sprintf('2026-%02d-%02d', (($i + 1) % 12) + 1, ($i % 27) + 1),
                'prazo_limite' => sprintf('2026-%02d-%02d', (($i + 2) % 12) + 1, min(28, ($i % 27) + 1)),
                'status' => $status,
                'responsavel' => $this->item(['Coordenação de Saúde (DEP/CPED)', 'Coordenação de Gestão (DEP/CPED)', 'Coordenação de Tecnologia (DEP/CPED)', 'Coordenação de Hospitalidade (DEP/CPED)'], $i),
                'observacao' => 'Visita técnica fictícia.',
                'created_at' => $agora,
                'updated_at' => $agora,
            ]);
        }
        $this->inserirLotes('visita_tecnicas', $visitas);

        $horas = [];
        for ($i = 1; $i <= 800; $i++) {
            $par = $this->item($pares ?: [['eixo' => 'Gestão e Moda', 'segmento' => 'Gestão e Comércio']], $i);
            $ciclo = $this->item($ciclos->all(), $i);
            $horas[] = array_merge($origem, [
                'ciclo_id' => $ciclo->id ?? $cicloAtualId,
                'matricula' => (string) (2026000 + $i),
                'pessoa' => $this->item(['Ana Souza', 'Bruno Lima', 'Carla Mendes', 'Daniel Rego', 'Elena Prado'], $i),
                'segmento' => $par['segmento'] ?? $par['eixo'],
                'eixo' => $par['eixo'],
                'processo_sei' => sprintf('00002.%06d/2026-01', $i),
                'ano' => 2024 + ($i % 4),
                'motivo' => 'Atividade pedagógica fictícia '.$i,
                'status' => $this->statusPorPeso(['Pendente' => 25, 'Em andamento' => 30, 'Concluída' => 45], $i),
                'ativo' => $i % 12 !== 0,
                'observacao' => 'Hora pedagógica fictícia.',
                'created_at' => $agora,
                'updated_at' => $agora,
            ]);
        }
        $this->inserirLotes('hora_pedagogicas', $horas);

        $acoes = [];
        for ($i = 1; $i <= 600; $i++) {
            $par = $this->item($pares ?: [['eixo' => 'Gestão e Moda']], $i);
            $ciclo = $this->item($ciclos->all(), $i);
            $acoes[] = array_merge($origem, [
                'ciclo_id' => $ciclo->id ?? $cicloAtualId,
                'priorizacao' => $this->item(['Alta', 'Média', 'Baixa'], $i),
                'atribuido' => 'equipe.'.(1000 + $i),
                'eixo' => $par['eixo'],
                'numero_processo_sei' => sprintf('2026.%09d-%02d', $i, $i % 90 + 1),
                'tipo' => 'Ação Extensiva',
                'assunto' => 'Ação extensiva fictícia '.$i,
                'objetivo' => 'Objetivo pedagógico de homologação.',
                'setor_atual' => $this->item(['CPED', 'DEP', 'DIREG', 'NC'], $i),
                'ultima_atualizacao' => sprintf('2026-%02d-%02d', ($i % 12) + 1, ($i % 27) + 1),
                'created_at' => $agora,
                'updated_at' => $agora,
            ]);
        }
        $this->inserirLotes('acao_extensivas', $acoes);

        $eventos = [];
        for ($i = 1; $i <= 600; $i++) {
            $par = $this->item($pares ?: [['eixo' => 'Gestão e Moda']], $i);
            $ciclo = $this->item($ciclos->all(), $i);
            $eventos[] = array_merge($origem, [
                'ciclo_id' => $ciclo->id ?? $cicloAtualId,
                'nome' => 'Evento pedagógico '.$i,
                'processo_sei' => sprintf('00003.%06d/2026-01', $i),
                'tipo_evento' => $this->item(['Palestra', 'Oficina', 'Seminário', 'Feira'], $i),
                'ano' => (string) (2024 + ($i % 4)),
                'data' => sprintf('2026-%02d-%02d', ($i % 12) + 1, ($i % 27) + 1),
                'unidade' => $this->item($unidades, $i),
                'eixo' => $par['eixo'],
                'quantidade_pessoas' => 20 + ($i % 300),
                'equipe' => 'Equipe CPED',
                'possui_acao_extensiva' => $i % 3 === 0 ? 'Sim' : 'Não',
                'acao_vinculada' => $i % 3 === 0 ? 'Ação extensiva fictícia '.$i : null,
                'status' => $this->statusPorPeso(['Planejado' => 30, 'Realizado' => 50, 'Cancelado' => 20], $i),
                'observacao' => 'Evento fictício.',
                'created_at' => $agora,
                'updated_at' => $agora,
            ]);
        }
        $this->inserirLotes('eventos', $eventos);

        $jornadas = [];
        for ($i = 1; $i <= 500; $i++) {
            $ciclo = $this->item($ciclos->all(), $i);
            $jornadas[] = array_merge($origem, [
                'ciclo_id' => $ciclo->id ?? $cicloAtualId,
                'titulo' => 'Jornada Pedagógica '.$i,
                'data_inicio' => sprintf('2026-%02d-%02d', ($i % 12) + 1, 10),
                'data_fim' => sprintf('2026-%02d-%02d', ($i % 12) + 1, 12),
                'tem_pre_jornada' => $i % 4 === 0 ? 'Sim' : 'Não',
                'data_pre_jornada' => $i % 4 === 0 ? sprintf('2026-%02d-%02d', ($i % 12) + 1, 5) : null,
                'custos' => 'Coffee break, material gráfico e certificados.',
                'observacoes' => 'Jornada fictícia para homologação.',
                'local' => $this->item($unidades, $i),
                'espaco' => $this->item(['Auditório', 'Sala híbrida', 'Laboratório'], $i),
                'verba' => 'R$ '.(3000 + $i * 20),
                'programacao' => 'Programação fictícia de alinhamento pedagógico.',
                'setores' => 'CPED',
                'status' => $this->statusPorPeso(['Planejamento' => 25, 'Enviado' => 25, 'Consolidado' => 40, 'Cancelado' => 10], $i),
                'created_at' => $agora,
                'updated_at' => $agora,
            ]);
        }
        $this->inserirLotes('jornadas_pedagogicas', $jornadas);
    }

    private function gerarDocumentos(array $pares, array $origem, string $agora): void
    {
        $resolucoes = [];
        for ($i = 1; $i <= 500; $i++) {
            $inicio = sprintf('202%1d-%02d-15', 4 + ($i % 3), ($i % 12) + 1);
            $resolucoes[] = array_merge($origem, [
                'numero' => sprintf('MEC/%d/%03d', 2020 + ($i % 7), $i),
                'curso_relacionado' => 'Curso técnico fictício '.$i,
                'categoria' => $this->item(['Normativa', 'Regulamentação', 'Operacional', 'Interna'], $i),
                'resumo' => 'Resolução fictícia para acompanhamento de vigência.',
                'relator' => $this->item(['Maria Souza', 'Carlos Mendes', 'Ana Paula Lima', 'Bruno Lima'], $i),
                'setor' => $this->item(['CPED', 'Diretoria', 'Gabinete', 'Coordenação'], $i),
                'data_inicio_vigencia' => $inicio,
                'data_fim_vigencia' => date('Y-m-d', strtotime($inicio.' +5 years')),
                'status' => \App\Services\ResolucaoVigenciaService::statusAutomatico($inicio, date('Y-m-d', strtotime($inicio.' +5 years'))),
                'observacoes' => 'Documento fictício.',
                'created_at' => $agora,
                'updated_at' => $agora,
            ]);
        }
        $this->inserirLotes('resolucoes', $resolucoes);

        $termos = [];
        for ($i = 1; $i <= 500; $i++) {
            $par = $this->item($pares ?: [['eixo' => 'Gestão e Moda']], $i);
            $status = $this->statusPorPeso(['Planejamento' => 25, 'Em Andamento' => 40, 'Em tramitação (fora da CPED)' => 20, 'Concluído' => 15], $i);
            $comAta = $i % 3 === 0;
            $termos[] = array_merge($origem, [
                'nome' => 'TR — Documento pedagógico '.$i,
                'numero_tr' => sprintf('TR-%03d/2026', $i),
                'numero_ata' => $comAta ? sprintf('ATA-%03d/2026', $i) : null,
                'data_vencimento_ata' => $comAta ? sprintf('202%d-%02d-%02d', 6 + $i % 2, ($i % 12) + 1, min(28, ($i % 27) + 1)) : null,
                'ata_renovada' => $comAta && $i % 2 === 0,
                'data_fim' => sprintf('2026-%02d-28', ($i % 12) + 1),
                'concluido_em' => $status === 'Concluído' ? sprintf('2026-%02d-20 10:00:00', ($i % 12) + 1) : null,
                'eixo' => $par['eixo'],
                'processo_sei' => sprintf('2026.%02d.%05d-01', ($i % 12) + 1, $i),
                'prazo_deadline' => sprintf('2026-%02d-%02d', ($i % 12) + 1, min(28, ($i % 27) + 1)),
                'status' => $status,
                'observacao' => 'Termo de referência fictício.',
                'data_inicio' => sprintf('2026-%02d-01', ($i % 12) + 1),
                'created_at' => $agora,
                'updated_at' => $agora,
            ]);
        }
        $this->inserirLotes('termos_referencia', $termos);
    }

    private function gerarAuditoria(?int $cicloAtualId, string $agora): void
    {
        $usuarioId = DB::table('usuarios')->orderBy('id')->value('id');
        $linhas = [];
        for ($i = 1; $i <= 1200; $i++) {
            $linhas[] = [
                'usuario_id' => $usuarioId,
                'acao' => $this->item(['criar', 'editar', 'consultar'], $i),
                'modulo' => $this->item(['cursos', 'eixos', 'visitas-tecnicas', 'plano-de-metas', 'pcas'], $i),
                'registro_tipo' => 'seeder',
                'registro_id' => $i,
                'resumo' => 'Evento de auditoria fictício '.$i.' (ciclo '.$cicloAtualId.').',
                'dados' => json_encode(['origem' => 'seeder']),
                'created_at' => $agora,
                'updated_at' => $agora,
            ];
        }
        $this->inserirLotes('cadastros', $linhas);

        $sincronizacoes = [];
        for ($i = 1; $i <= 600; $i++) {
            $sincronizacoes[] = [
                'sistema' => $this->item(['SIG', 'SGN', 'SGA'], $i),
                'entidade' => $this->item(['cursos', 'ofertas', 'pcas', 'visitas-tecnicas', 'horas-pedagogicas'], $i),
                'iniciado_em' => $agora,
                'finalizado_em' => $agora,
                'recebidos' => 200 + ($i % 1400),
                'criados' => $i % 40,
                'atualizados' => $i % 180,
                'ignorados' => $i % 60,
                'erros' => $i % 17 === 0 ? 3 : 0,
                'status' => $this->statusPorPeso(['Concluído' => 70, 'Concluído com alerta' => 22, 'Erro' => 8], $i),
                'observacao' => 'Histórico fictício de sincronização para homologação.',
                'created_at' => $agora,
                'updated_at' => $agora,
            ];
        }
        $this->inserirLotes('sincronizacao_historicos', $sincronizacoes);
    }
}
