<?php

namespace Database\Seeders;

use App\Models\PortfolioCiclo;
use App\Support\CatalogoOficial;
use Database\Seeders\Concerns\GeraMassa;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MassaDadosSeeder extends Seeder
{
    use GeraMassa;

    public function run(): void
    {
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
        for ($i = 1; $i <= 800; $i++) {
            $par = $this->item($pares ?: [['eixo' => 'Gestão e Moda']], $i);
            $ciclo = $this->item($ciclos->all(), $i);
            $visitas[] = array_merge($origem, [
                'ciclo_id' => $ciclo->id ?? $cicloAtualId,
                'unidade' => $this->item($unidades, $i),
                'eixo' => $par['eixo'],
                'processo_sei' => sprintf('00001.%06d/2026-%02d', $i, $i % 90 + 1),
                'data_solicitacao' => sprintf('2026-%02d-%02d', ($i % 12) + 1, ($i % 27) + 1),
                'data_visita_prevista' => sprintf('2026-%02d-%02d', (($i + 1) % 12) + 1, ($i % 27) + 1),
                'prazo_limite' => sprintf('2026-%02d-%02d', (($i + 2) % 12) + 1, min(28, ($i % 27) + 1)),
                'status' => $this->statusPorPeso(['Pendente' => 20, 'Em andamento' => 25, 'Realizada' => 40, 'Atrasada' => 10, 'Cancelada' => 5], $i),
                'responsavel' => $this->item(['Ana Souza', 'Bruno Lima', 'Carla Mendes', 'Daniel Rego'], $i),
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
                'status' => $this->statusPorPeso(['Pendente' => 20, 'Em andamento' => 30, 'Concluída' => 45, 'Cancelada' => 5], $i),
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
                'status' => $this->statusPorPeso(['CPED' => 40, 'Em andamento' => 30, 'Concluída' => 25, 'Cancelada' => 5], $i),
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
                'ano' => (string) (2024 + ($i % 4)),
                'data' => sprintf('2026-%02d-%02d', ($i % 12) + 1, ($i % 27) + 1),
                'unidade' => $this->item($unidades, $i),
                'eixo' => $par['eixo'],
                'quantidade_pessoas' => 20 + ($i % 300),
                'equipe' => 'Equipe CPED',
                'possui_acao_extensiva' => $i % 3 === 0 ? 'Sim' : 'Não',
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
                'status' => $this->statusPorPeso(['vigente' => 55, 'vencida' => 20, 'em_atencao' => 15, 'concluida' => 10], $i),
                'observacoes' => 'Documento fictício.',
                'created_at' => $agora,
                'updated_at' => $agora,
            ]);
        }
        $this->inserirLotes('resolucoes', $resolucoes);

        $termos = [];
        for ($i = 1; $i <= 500; $i++) {
            $par = $this->item($pares ?: [['eixo' => 'Gestão e Moda']], $i);
            $termos[] = array_merge($origem, [
                'nome' => 'TR — Documento pedagógico '.$i,
                'eixo' => $par['eixo'],
                'processo_sei' => sprintf('2026.%02d.%05d-01', ($i % 12) + 1, $i),
                'prazo_deadline' => sprintf('2026-%02d-%02d', ($i % 12) + 1, min(28, ($i % 27) + 1)),
                'status' => $this->statusPorPeso(['Planejamento' => 25, 'Em Andamento' => 40, 'Em tramitação (fora da CPED)' => 20, 'Concluído' => 15], $i),
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
