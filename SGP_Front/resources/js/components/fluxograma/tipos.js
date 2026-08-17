/**
 * Símbolos de fluxograma de processos (padrão de qualidade).
 * Cores fixas por tipo — evita colorir sem critério.
 */
export const CORES_SIMBOLOS = {
  inicio: { borda: '#0f766e', fundo: '#ccfbf1', texto: '#134e4a' },
  fim: { borda: '#475569', fundo: '#e2e8f0', texto: '#1e293b' },
  processo: { borda: '#1d4ed8', fundo: '#eff6ff', texto: '#1e3a8a' },
  decisao: { borda: '#c2410c', fundo: '#fff7ed', texto: '#9a3412' },
  documento: { borda: '#0369a1', fundo: '#e0f2fe', texto: '#0c4a6e' },
  predefinido: { borda: '#4338ca', fundo: '#eef2ff', texto: '#312e81' },
  manual: { borda: '#a16207', fundo: '#fefce8', texto: '#713f12' },
  espera: { borda: '#7c3aed', fundo: '#f5f3ff', texto: '#5b21b6' },
  conector: { borda: '#0f766e', fundo: '#f0fdfa', texto: '#134e4a' },
};

export const TIPOS_PALETA = [
  {
    type: 'inicio',
    label: 'Início',
    defaultLabel: 'Início',
    descricao: 'Entrada do processo',
    grupo: 'essencial',
  },
  {
    type: 'processo',
    label: 'Processo',
    defaultLabel: 'Processo',
    descricao: 'Atividade ou etapa',
    grupo: 'essencial',
  },
  {
    type: 'decisao',
    label: 'Decisão',
    defaultLabel: 'Decisão?',
    descricao: 'Ramificação Sim/Não',
    grupo: 'essencial',
  },
  {
    type: 'documento',
    label: 'Documento',
    defaultLabel: 'Documento',
    descricao: 'Relatório, contrato, etc.',
    grupo: 'essencial',
  },
  {
    type: 'fim',
    label: 'Fim',
    defaultLabel: 'Fim',
    descricao: 'Saída do processo',
    grupo: 'essencial',
  },
  {
    type: 'predefinido',
    label: 'Pré-definido',
    defaultLabel: 'Subprocesso',
    descricao: 'Processo já mapeado',
    grupo: 'extra',
  },
  {
    type: 'manual',
    label: 'Manual',
    defaultLabel: 'Operação manual',
    descricao: 'Atividade feita à mão',
    grupo: 'extra',
  },
  {
    type: 'espera',
    label: 'Espera',
    defaultLabel: 'Espera',
    descricao: 'Tempo de espera',
    grupo: 'extra',
  },
  {
    type: 'conector',
    label: 'Conector',
    defaultLabel: 'A',
    descricao: 'Liga partes distantes',
    grupo: 'extra',
  },
];

export const LEGENDA_SIMBOLOS = TIPOS_PALETA.map((item) => ({
  type: item.type,
  label: item.label,
  descricao: item.descricao,
  cor: CORES_SIMBOLOS[item.type],
}));

export function paletaPorGrupo(grupo) {
  return TIPOS_PALETA.filter((item) => item.grupo === grupo);
}

let contadorNo = 1;

export function proximoIdNo() {
  contadorNo += 1;
  return `n-${Date.now()}-${contadorNo}`;
}

export function criarNo(type, position, extras = {}) {
  const item = TIPOS_PALETA.find((t) => t.type === type) || TIPOS_PALETA[1];
  const raiaId = extras.raiaId || '';

  const no = {
    id: proximoIdNo(),
    type: item.type,
    position: {
      x: position?.x ?? 0,
      y: position?.y ?? 0,
    },
    data: {
      label: item.defaultLabel,
      responsavel: '',
      observacao: '',
      raiaId,
    },
  };

  if (raiaId) {
    no.parentNode = raiaId;
    no.extent = 'parent';
    no.expandParent = false;
  }

  return no;
}

export function normalizarNos(nodes) {
  if (!Array.isArray(nodes)) {
    return [];
  }

  return nodes.map((node, index) => {
    const data = node.data && typeof node.data === 'object' ? node.data : {};

    return {
      id: String(node.id || `n-import-${index}`),
      type: node.type || 'processo',
      position: {
        x: Number(node.position?.x ?? 0),
        y: Number(node.position?.y ?? 0),
      },
      data: {
        label: data.label || node.label || 'Etapa',
        responsavel: data.responsavel || '',
        observacao: data.observacao || '',
        raiaId: data.raiaId || node.parentNode || '',
      },
    };
  });
}

export function normalizarEdges(edges) {
  if (!Array.isArray(edges)) {
    return [];
  }

  const alias = {
    in: 'top',
    out: 'bottom',
    'in-top': 'top',
    'in-right': 'right',
    'in-bottom': 'bottom',
    'in-left': 'left',
  };

  return edges.map((edge, index) => {
    const sourceHandle = edge.sourceHandle == null || edge.sourceHandle === ''
      ? 'bottom'
      : (alias[edge.sourceHandle] || String(edge.sourceHandle));
    const targetHandle = edge.targetHandle == null || edge.targetHandle === ''
      ? 'top'
      : (alias[edge.targetHandle] || String(edge.targetHandle));

    return {
      id: String(edge.id || `e-import-${index}`),
      source: String(edge.source),
      target: String(edge.target),
      sourceHandle,
      targetHandle,
      label: edge.label || '',
      type: edge.type || 'smoothstep',
      animated: Boolean(edge.animated),
      selectable: true,
      focusable: true,
      updatable: true,
      interactionWidth: 48,
      markerEnd: edge.markerEnd || { type: 'arrowclosed' },
    };
  });
}

function serializarMarkerEnd(markerEnd) {
  if (!markerEnd) {
    return { type: 'arrowclosed' };
  }

  if (typeof markerEnd === 'string') {
    return { type: markerEnd };
  }

  return {
    type: markerEnd.type || 'arrowclosed',
  };
}

export function criarDiagramaTemplate() {
  return {
    raias: [],
    nodes: [
      {
        id: 'tpl-inicio',
        type: 'inicio',
        position: { x: 250, y: 0 },
        data: { label: 'Início', responsavel: '', observacao: '', raiaId: '' },
      },
      {
        id: 'tpl-processo',
        type: 'processo',
        position: { x: 235, y: 120 },
        data: { label: 'Processo', responsavel: '', observacao: '', raiaId: '' },
      },
      {
        id: 'tpl-fim',
        type: 'fim',
        position: { x: 250, y: 250 },
        data: { label: 'Fim', responsavel: '', observacao: '', raiaId: '' },
      },
    ],
    edges: [
      {
        id: 'tpl-e1',
        source: 'tpl-inicio',
        target: 'tpl-processo',
        sourceHandle: 'bottom',
        targetHandle: 'top',
        label: '',
        type: 'smoothstep',
        markerEnd: { type: 'arrowclosed' },
      },
      {
        id: 'tpl-e2',
        source: 'tpl-processo',
        target: 'tpl-fim',
        sourceHandle: 'bottom',
        targetHandle: 'top',
        label: '',
        type: 'smoothstep',
        markerEnd: { type: 'arrowclosed' },
      },
    ],
    viewport: { x: 0, y: 0, zoom: 1 },
  };
}

export function serializarDiagrama(nodes, edges, viewport, raias = []) {
  const etapas = (nodes || []).filter((node) => node.type !== 'raia');

  return {
    raias: Array.isArray(raias) ? raias : [],
    nodes: etapas.map((node) => ({
      id: node.id,
      type: node.type,
      position: {
        x: node.position?.x ?? 0,
        y: node.position?.y ?? 0,
      },
      data: {
        label: node.data?.label || '',
        responsavel: node.data?.responsavel || '',
        observacao: node.data?.observacao || '',
        raiaId: node.data?.raiaId || node.parentNode || '',
      },
    })),
    edges: (edges || []).map((edge) => ({
      id: edge.id,
      source: edge.source,
      target: edge.target,
      sourceHandle: edge.sourceHandle ?? null,
      targetHandle: edge.targetHandle ?? null,
      label: edge.label || '',
      type: edge.type || 'smoothstep',
      markerEnd: serializarMarkerEnd(edge.markerEnd),
    })),
    viewport: {
      x: Number(viewport?.x ?? 0),
      y: Number(viewport?.y ?? 0),
      zoom: Number(viewport?.zoom ?? 1),
    },
  };
}
