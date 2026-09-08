/**
 * Validação leve de fluxograma de processos (avisos, não bloqueia salvamento).
 */
export function validarFluxograma(nodes = [], edges = []) {
  const avisos = [];

  if (!nodes.length) {
    return {
      ok: true,
      avisos: [
        {
          codigo: 'vazio',
          nivel: 'info',
          mensagem: 'Canvas vazio. Adicione Início, etapas e Fim para mapear o processo.',
        },
      ],
    };
  }

  const inicios = nodes.filter((n) => n.type === 'inicio');
  const fins = nodes.filter((n) => n.type === 'fim');

  if (!inicios.length) {
    avisos.push({
      codigo: 'sem-inicio',
      nivel: 'aviso',
      mensagem: 'Defina a fronteira do processo: falta o símbolo de Início.',
    });
  }

  if (!fins.length) {
    avisos.push({
      codigo: 'sem-fim',
      nivel: 'aviso',
      mensagem: 'Defina a fronteira do processo: falta o símbolo de Fim.',
    });
  }

  const conectados = new Set();
  edges.forEach((edge) => {
    if (edge.source) conectados.add(String(edge.source));
    if (edge.target) conectados.add(String(edge.target));
  });

  const isolados = nodes.filter((n) => !conectados.has(String(n.id)));

  if (isolados.length) {
    const nomes = isolados
      .slice(0, 3)
      .map((n) => n.data?.label || rotuloTipo(n.type))
      .join(', ');
    const mais = isolados.length > 3 ? ` e mais ${isolados.length - 3}` : '';
    avisos.push({
      codigo: 'isolados',
      nivel: 'aviso',
      mensagem: `${isolados.length} etapa(s) sem conexão: ${nomes}${mais}. Toda etapa deve ter fluxo (seta).`,
      ids: isolados.map((n) => n.id),
    });
  }

  const decisoes = nodes.filter((n) => n.type === 'decisao');
  decisoes.forEach((decisao) => {
    const saidas = edges.filter((e) => String(e.source) === String(decisao.id));
    const temSim = saidas.some((e) => e.sourceHandle === 'sim' || /^sim$/i.test(String(e.label || '')));
    const temNao = saidas.some((e) => e.sourceHandle === 'nao' || /^n[aã]o$/i.test(String(e.label || '')));

    if (saidas.length < 2 || !temSim || !temNao) {
      avisos.push({
        codigo: 'decisao-incompleta',
        nivel: 'aviso',
        mensagem: `A decisão "${decisao.data?.label || 'Decisão'}" deve ter saídas Sim e Não.`,
        ids: [decisao.id],
      });
    }
  });

  return {
    ok: avisos.every((a) => a.nivel === 'info'),
    avisos,
  };
}

function rotuloTipo(type) {
  const mapa = {
    inicio: 'Início',
    fim: 'Fim',
    processo: 'Processo',
    decisao: 'Decisão',
    documento: 'Documento',
    predefinido: 'Pré-definido',
    manual: 'Manual',
    espera: 'Espera',
    conector: 'Conector',
  };

  return mapa[type] || type;
}
