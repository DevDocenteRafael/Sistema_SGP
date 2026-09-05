/**
 * Glossário central do SGP — textos curtos para tooltips/ajuda contextual.
 * Chaves normalizadas (minúsculas, sem acento) para busca flexível.
 */

function normalizar(texto) {
  return String(texto || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim();
}

/** Indicadores e métricas do Dashboard / resumos */
export const INDICADORES = {
  resolucoes: 'Atos normativos que regulamentam cursos e procedimentos do SENAC DF.',
  'termos de referencia': 'Documentos que definem escopo e requisitos de cursos ou projetos em elaboração.',
  'termos de referência': 'Documentos que definem escopo e requisitos de cursos ou projetos em elaboração.',
  'total de cursos': 'Quantidade de cursos do portfólio no ciclo e filtros atuais.',
  estruturas: 'Faculdades, polos e unidades do Senac DF cadastrados em Estruturas Institucionais (apenas ativas).',
  unidades: 'Faculdades, polos e unidades do Senac DF cadastrados em Estruturas Institucionais (apenas ativas).',
  'estrutura institucional': 'Local de oferta: Faculdade, Polo ou Unidade vinculados a uma localidade/região.',
  'estruturas institucionais': 'Cadastro de faculdades, polos e unidades usados nos formulários e filtros do sistema.',
  'horas pedagogicas': 'Registros de horas pedagógicas solicitadas ou realizadas por docentes/instrutores.',
  'horas pedagógicas': 'Registros de horas pedagógicas solicitadas ou realizadas por docentes/instrutores.',
  'acoes extensivas': 'Ações de extensão e projetos acompanhados pela CPED fora da oferta regular.',
  'ações extensivas': 'Ações de extensão e projetos acompanhados pela CPED fora da oferta regular.',
  eventos: 'Eventos acadêmicos e institucionais vinculados a eixos e unidades.',
  'visitas tecnicas': 'Solicitações e realização de visitas técnicas às unidades.',
  'visitas técnicas': 'Solicitações e realização de visitas técnicas às unidades.',
  'eixos tecnologicos': 'Agrupamento dos cursos por área tecnológica do SENAC.',
  'eixos tecnológicos': 'Agrupamento dos cursos por área tecnológica do SENAC.',
  'tipos de curso': 'Distribuição dos cursos por tipo de oferta (técnico, qualificação etc.).',
  'faixas de carga horaria': 'Cursos agrupados por faixa de carga horária total.',
  'faixas de carga horária': 'Cursos agrupados por faixa de carga horária total.',
  'total no periodo': 'Total de registros no grupo de indicadores selecionado.',
  'total no período': 'Total de registros no grupo de indicadores selecionado.',
  realizadas: 'Visitas já concluídas com sucesso.',
  'no prazo': 'Itens dentro do prazo esperado (semáforo verde).',
  'fora do prazo': 'Itens com prazo ultrapassado ou status de atraso.',
  devolvidas: 'Visitas canceladas, recusadas ou devolvidas.',
  concluidas: 'Registros finalizados com sucesso.',
  'concluídas': 'Registros finalizados com sucesso.',
  aprovadas: 'Solicitações de horas pedagógicas aprovadas.',
  'em analise': 'Itens em avaliação pela área responsável.',
  'em análise': 'Itens em avaliação pela área responsável.',
  solicitadas: 'Solicitações registradas e ainda não finalizadas.',
  recusadas: 'Solicitações negadas ou canceladas.',
  inativas: 'Registros marcados como inativos.',
};

/** Status e situações usados em vários módulos */
export const STATUS = {
  // Cursos
  ativo: 'Curso disponível para oferta no portfólio.',
  inativo: 'Curso fora de oferta no momento.',
  suspenso: 'Curso temporariamente suspenso.',
  'em revisao': 'Curso em atualização ou revisão de conteúdo.',
  'em revisão': 'Curso em atualização ou revisão de conteúdo.',

  // Resoluções / prazos
  vigente: 'Resolução dentro do período de vigência.',
  atencao: 'Vigência próxima do vencimento — atenção preventiva.',
  atenção: 'Vigência próxima do vencimento — atenção preventiva.',
  critico: 'Prazo crítico: vencimento iminente.',
  crítico: 'Prazo crítico: vencimento iminente.',
  vencida: 'Vigência expirada — necessita regularização.',
  vencidos: 'Itens com prazo ou vigência já expirados.',
  concluida: 'Processo de resolução encerrado.',
  'concluída': 'Processo de resolução encerrado.',

  // Termos de Referência
  planejamento: 'TR em fase inicial de elaboração.',
  'em andamento': 'Trabalho em curso na CPED ou área responsável.',
  'em tramitacao (fora da cped)': 'TR encaminhado a outra área; ainda acompanhado no SGP.',
  'em tramitação (fora da cped)': 'TR encaminhado a outra área; ainda acompanhado no SGP.',
  concluido: 'Documento finalizado.',
  'concluído': 'Documento finalizado.',
  arquivado: 'Registro encerrado e arquivado.',

  // Visitas / Horas / Eventos
  pendente: 'Aguardando início ou análise.',
  pendentes: 'Registros aguardando início ou análise.',
  realizada: 'Visita técnica já realizada.',
  cancelada: 'Registro cancelado.',
  cancelado: 'Registro cancelado.',
  atrasada: 'Prazo ultrapassado sem conclusão.',
  planejado: 'Evento previsto, ainda não realizado.',
  realizado: 'Evento já ocorrido.',

  // PCA / metas
  'em analise': 'Em análise pela gestão.',
  previsto: 'Incluído no planejamento, ainda não vigente.',
  publicado: 'Já publicado para acompanhamento.',
  aprovado: 'Aprovado pela área responsável.',
  planejado: 'Planejado para execução futura.',
  'em andamento': 'Execução em andamento.',

  // Situação final metas
  entregue: 'Entrega registrada.',
  'em analise': 'Situação em análise.',

  // Jornada
  rascunho: 'Plano ainda em elaboração.',
  consolidado: 'Plano revisado e consolidado.',
  enviado: 'Plano já encaminhado.',

  // Ações extensivas (setores)
  cped: 'Ação sob responsabilidade da CPED.',
  dep: 'Ação sob responsabilidade do DEP.',
  direg: 'Ação sob responsabilidade da DIREG.',
  nc: 'Não classificado / sem setor definido.',

  // Curso por eixo
  // ativo/inativo/suspenso já cobertos
};

/** Siglas e termos técnicos */
export const TERMOS = {
  sei: 'Sistema Eletrônico de Informações — número do processo oficial.',
  'processo sei': 'Número do processo no SEI usado para tramitação oficial.',
  sig: 'Código interno SIG do curso no portfólio.',
  'codigo sig': 'Código interno SIG do curso no portfólio.',
  'código sig': 'Código interno SIG do curso no portfólio.',
  dn: 'Código DN (Departamento Nacional) do curso.',
  'codigo dn': 'Código DN (Departamento Nacional) do curso.',
  pca: 'Planejamento de Cursos Abertos — oferta e precificação do período.',
  cped: 'Coordenação Pedagógica — área gestora do portfólio no SGP.',
  tr: 'Termo de Referência.',
  'termo de referencia': 'Documento que define escopo e requisitos de elaboração.',
  'termo de referência': 'Documento que define escopo e requisitos de elaboração.',
  ch: 'Carga horária total do curso, em horas.',
  'carga horaria': 'Carga horária total do curso, em horas.',
  'carga horária': 'Carga horária total do curso, em horas.',
  eixo: 'Eixo tecnológico que agrupa cursos da mesma área.',
  ciclo: 'Período do portfólio (ex.: 2025-2026) que delimita os registros exibidos.',
  'ciclo de portfolio': 'Período do portfólio que delimita os registros exibidos.',
  'ciclo': 'Período ativo do sistema que filtra Cursos, Metas, PCA e Eixos. Troque pelo seletor no topo.',
  'ciclo de portfólio': 'Período do portfólio que delimita os registros exibidos (mesmo conceito de ciclo).',
  portfolio: 'Conjunto de cursos e planejamentos do período.',
  portfólio: 'Conjunto de cursos e planejamentos do período.',
  'plano de metas': 'Mapeamento de produção e entregas planejadas por ano.',
  'jornada pedagogica': 'Planejamento documental do evento de jornada pedagógica.',
  'jornada pedagógica': 'Planejamento documental do evento de jornada pedagógica.',
  'semáforo': 'Indicador visual de prazo: verde (ok), amarelo (atenção), vermelho (crítico/vencido).',
  semaforo: 'Indicador visual de prazo: verde (ok), amarelo (atenção), vermelho (crítico/vencido).',
  vigencia: 'Período em que a resolução permanece válida (padrão: 5 anos).',
  vigência: 'Período em que a resolução permanece válida (padrão: 5 anos).',
};

const FONTES = [INDICADORES, STATUS, TERMOS];

/**
 * Busca explicação para um rótulo ou chave.
 * @param {string} chave
 * @returns {string|null}
 */
export function explicar(chave) {
  if (!chave) return null;
  const bruto = String(chave).trim();
  if (!bruto) return null;

  for (const mapa of FONTES) {
    if (mapa[bruto]) return mapa[bruto];
  }

  const chaveNorm = normalizar(bruto);
  for (const mapa of FONTES) {
    for (const [k, v] of Object.entries(mapa)) {
      if (normalizar(k) === chaveNorm) return v;
    }
  }

  return null;
}

/**
 * Retorna texto de ajuda ou string vazia (para props).
 */
export function explicarOuVazio(chave) {
  return explicar(chave) || '';
}
