/**
 * Helpers de conexão entre símbolos (handles em 4 lados).
 */

const ALIAS_HANDLE = {
  in: 'top',
  out: 'bottom',
  'in-top': 'top',
  'in-right': 'right',
  'in-bottom': 'bottom',
  'in-left': 'left',
};

export function normalizarHandleId(handleId, fallback = null) {
  if (handleId == null || handleId === '') return fallback;
  const id = String(handleId);
  return ALIAS_HANDLE[id] || id;
}

export function escolherHandlesPorPosicao(origem, destino) {
  const ox = Number(origem?.positionAbsolute?.x ?? origem?.position?.x ?? 0);
  const oy = Number(origem?.positionAbsolute?.y ?? origem?.position?.y ?? 0);
  const ow = Number(origem?.dimensions?.width ?? origem?.width ?? 120);
  const oh = Number(origem?.dimensions?.height ?? origem?.height ?? 48);

  const dx = Number(destino?.positionAbsolute?.x ?? destino?.position?.x ?? 0);
  const dy = Number(destino?.positionAbsolute?.y ?? destino?.position?.y ?? 0);
  const dw = Number(destino?.dimensions?.width ?? destino?.width ?? 120);
  const dh = Number(destino?.dimensions?.height ?? destino?.height ?? 48);

  const centroOrigem = { x: ox + ow / 2, y: oy + oh / 2 };
  const centroDestino = { x: dx + dw / 2, y: dy + dh / 2 };
  const deltaX = centroDestino.x - centroOrigem.x;
  const deltaY = centroDestino.y - centroOrigem.y;

  if (Math.abs(deltaX) >= Math.abs(deltaY)) {
    if (deltaX >= 0) {
      return { sourceHandle: 'right', targetHandle: 'left' };
    }
    return { sourceHandle: 'left', targetHandle: 'right' };
  }

  if (deltaY >= 0) {
    return { sourceHandle: 'bottom', targetHandle: 'top' };
  }

  return { sourceHandle: 'top', targetHandle: 'bottom' };
}

export function handleSaidaDecisao(origem, saidaEscolhida, edges = []) {
  if (origem?.type !== 'decisao') {
    return null;
  }

  if (saidaEscolhida === 'sim' || saidaEscolhida === 'nao') {
    return saidaEscolhida;
  }

  const saidas = edges.filter((e) => e.source === origem.id);
  const temSim = saidas.some((e) => normalizarHandleId(e.sourceHandle) === 'sim');
  const temNao = saidas.some((e) => normalizarHandleId(e.sourceHandle) === 'nao');
  if (!temSim) return 'sim';
  if (!temNao) return 'nao';
  return 'sim';
}
