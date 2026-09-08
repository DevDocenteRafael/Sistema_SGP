export const ALTURA_RAIA_PADRAO = 220;
export const ALTURA_RAIA_MIN = 160;
export const ALTURA_RAIA_MAX = 480;
export const LARGURA_RAIA_PADRAO = 1900;
export const GAP_RAIAS = 16;

export const CORES_RAIAS = [
  { fundo: 'rgba(204, 251, 241, 0.55)', borda: '#99f6e4', faixa: '#0d9488' },
  { fundo: 'rgba(224, 242, 254, 0.55)', borda: '#bae6fd', faixa: '#0284c7' },
  { fundo: 'rgba(254, 243, 199, 0.55)', borda: '#fde68a', faixa: '#d97706' },
  { fundo: 'rgba(237, 233, 254, 0.55)', borda: '#ddd6fe', faixa: '#7c3aed' },
];

let contadorRaia = 1;

export function proximoIdRaia() {
  contadorRaia += 1;
  return `raia-${Date.now()}-${contadorRaia}`;
}

export function limitarAlturaRaia(altura) {
  const n = Number(altura);
  if (!Number.isFinite(n)) return ALTURA_RAIA_PADRAO;
  return Math.min(ALTURA_RAIA_MAX, Math.max(ALTURA_RAIA_MIN, n));
}

export function criarRaiasPadrao() {
  return [
    { id: 'raia-1', nome: 'Área / Setor 1', ordem: 0, altura: ALTURA_RAIA_PADRAO },
    { id: 'raia-2', nome: 'Área / Setor 2', ordem: 1, altura: ALTURA_RAIA_PADRAO },
  ];
}

export function normalizarRaias(raias) {
  if (!Array.isArray(raias) || !raias.length) {
    return criarRaiasPadrao();
  }

  return raias.map((raia, index) => ({
    id: String(raia.id || `raia-${index + 1}`),
    nome: String(raia.nome || `Raia ${index + 1}`).slice(0, 80),
    ordem: Number.isFinite(Number(raia.ordem)) ? Number(raia.ordem) : index,
    altura: limitarAlturaRaia(raia.altura || ALTURA_RAIA_PADRAO),
  })).sort((a, b) => a.ordem - b.ordem);
}

export function raiaParaNoPai(raia, index = 0) {
  const cor = CORES_RAIAS[index % CORES_RAIAS.length];
  const altura = limitarAlturaRaia(raia.altura || ALTURA_RAIA_PADRAO);

  return {
    id: raia.id,
    type: 'raia',
    position: { x: 0, y: 0 },
    draggable: false,
    selectable: true,
    connectable: false,
    deletable: false,
    focusable: false,
    style: {
      width: `${LARGURA_RAIA_PADRAO}px`,
      height: `${altura}px`,
      overflow: 'visible',
      zIndex: 0,
    },
    data: {
      label: raia.nome,
      altura,
      cor,
    },
  };
}

/**
 * Empilha as raias em Y fixo e impede que filhos alterem o tamanho/posição delas.
 * Corrige layouts “bagunçados” causados por expandParent.
 */
export function estabilizarLayoutRaias(nodes) {
  const lista = Array.isArray(nodes) ? [...nodes] : [];
  const raias = lista
    .filter((n) => n.type === 'raia')
    .sort((a, b) => (a.position?.y ?? 0) - (b.position?.y ?? 0));

  if (!raias.length) {
    return lista.map((node) => (
      node.type === 'raia'
        ? node
        : { ...node, expandParent: false }
    ));
  }

  let yAtual = 0;
  const meta = {};

  raias.forEach((raia, index) => {
    const altura = limitarAlturaRaia(raia.data?.altura || raia.style?.height || ALTURA_RAIA_PADRAO);
    meta[raia.id] = {
      x: 0,
      y: yAtual,
      altura,
      cor: raia.data?.cor || CORES_RAIAS[index % CORES_RAIAS.length],
      label: raia.data?.label || `Raia ${index + 1}`,
    };
    yAtual += altura + GAP_RAIAS;
  });

  const primeiraId = raias[0].id;

  return lista.map((node) => {
    if (node.type === 'raia') {
      const info = meta[node.id];
      if (!info) return node;

      return {
        ...node,
        position: { x: info.x, y: info.y },
        draggable: false,
        selectable: true,
        connectable: false,
        deletable: false,
        focusable: false,
        style: {
          width: `${LARGURA_RAIA_PADRAO}px`,
          height: `${info.altura}px`,
          overflow: 'visible',
          zIndex: 0,
        },
        data: {
          ...node.data,
          label: info.label,
          altura: info.altura,
          cor: info.cor,
        },
      };
    }

    let parentId = node.parentNode || node.data?.raiaId || '';
    if (parentId && !meta[parentId]) {
      parentId = primeiraId;
    }
    if (!parentId) {
      parentId = primeiraId;
    }

    const alturaPai = meta[parentId]?.altura || ALTURA_RAIA_PADRAO;
    const x = Math.max(24, Number(node.position?.x ?? 24));
    const y = Math.max(24, Math.min(Number(node.position?.y ?? 24), Math.max(24, alturaPai - 72)));

    return {
      ...node,
      parentNode: parentId,
      extent: 'parent',
      expandParent: false,
      position: { x, y },
      data: {
        ...node.data,
        raiaId: parentId,
      },
    };
  });
}

export function montarNosComRaias(etapas, raias) {
  const raiasNorm = normalizarRaias(raias);
  const pais = raiasNorm.map((raia, index) => raiaParaNoPai(raia, index));
  const primeira = raiasNorm[0]?.id || null;

  const filhos = (etapas || []).map((etapa) => {
    const raiaId = etapa.data?.raiaId && raiasNorm.some((r) => r.id === etapa.data.raiaId)
      ? etapa.data.raiaId
      : primeira;

    return {
      ...etapa,
      parentNode: raiaId || undefined,
      extent: raiaId ? 'parent' : undefined,
      expandParent: false,
      data: {
        ...etapa.data,
        raiaId: raiaId || '',
      },
    };
  });

  return estabilizarLayoutRaias([...pais, ...filhos]);
}

export function extrairRaiasDosNos(nodes) {
  return (nodes || [])
    .filter((n) => n.type === 'raia')
    .sort((a, b) => (a.position?.y ?? 0) - (b.position?.y ?? 0))
    .map((n, index) => ({
      id: n.id,
      nome: n.data?.label || `Raia ${index + 1}`,
      ordem: index,
      altura: limitarAlturaRaia(n.data?.altura || n.style?.height || ALTURA_RAIA_PADRAO),
    }));
}

export function apenasEtapas(nodes) {
  return (nodes || []).filter((n) => n.type !== 'raia');
}

/** Qual raia contém a coordenada Y absoluta do canvas. */
export function raiaEmY(nodes, yAbsoluto) {
  const raias = (nodes || [])
    .filter((n) => n.type === 'raia')
    .sort((a, b) => (a.position?.y ?? 0) - (b.position?.y ?? 0));

  for (const raia of raias) {
    const top = Number(raia.position?.y ?? 0);
    const altura = limitarAlturaRaia(raia.data?.altura || ALTURA_RAIA_PADRAO);
    if (yAbsoluto >= top && yAbsoluto <= top + altura) {
      return raia.id;
    }
  }

  return raias[0]?.id || null;
}

export function criarDiagramaTemplateFuncional() {
  const raias = criarRaiasPadrao();

  return {
    raias,
    nodes: [
      {
        id: 'tpl-inicio',
        type: 'inicio',
        position: { x: 220, y: 55 },
        data: {
          label: 'Início',
          responsavel: '',
          observacao: '',
          raiaId: raias[0].id,
        },
      },
      {
        id: 'tpl-processo',
        type: 'processo',
        position: { x: 200, y: 50 },
        data: {
          label: 'Processo',
          responsavel: '',
          observacao: '',
          raiaId: raias[1].id,
        },
      },
      {
        id: 'tpl-fim',
        type: 'fim',
        position: { x: 480, y: 55 },
        data: {
          label: 'Fim',
          responsavel: '',
          observacao: '',
          raiaId: raias[1].id,
        },
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
    viewport: { x: 0, y: 0, zoom: 0.85 },
  };
}
