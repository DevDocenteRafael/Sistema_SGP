/**
 * Exportação confiável: redesenha o fluxograma no canvas a partir dos dados.
 * Não usa html-to-image (que distorce paths SVG do Vue Flow).
 */

const CORES = {
  fundo: '#f8fafc',
  linha: '#0f766e',
  texto: '#0f3d3a',
  rotuloAresta: '#0f766e',
  raias: [
    { fundo: '#f0fdfa', borda: '#99f6e4', faixa: '#0d9488' },
    { fundo: '#f0f9ff', borda: '#bae6fd', faixa: '#0284c7' },
    { fundo: '#fffbeb', borda: '#fde68a', faixa: '#d97706' },
    { fundo: '#f5f3ff', borda: '#ddd6fe', faixa: '#7c3aed' },
  ],
  simbolos: {
    inicio: { borda: '#0f766e', fundo: '#ccfbf1', texto: '#134e4a' },
    fim: { borda: '#475569', fundo: '#e2e8f0', texto: '#1e293b' },
    processo: { borda: '#1d4ed8', fundo: '#eff6ff', texto: '#1e3a8a' },
    decisao: { borda: '#c2410c', fundo: '#fff7ed', texto: '#9a3412' },
    documento: { borda: '#0369a1', fundo: '#e0f2fe', texto: '#0c4a6e' },
    predefinido: { borda: '#4338ca', fundo: '#eef2ff', texto: '#312e81' },
    manual: { borda: '#a16207', fundo: '#fefce8', texto: '#713f12' },
    espera: { borda: '#7c3aed', fundo: '#f5f3ff', texto: '#5b21b6' },
    conector: { borda: '#0f766e', fundo: '#f0fdfa', texto: '#134e4a' },
  },
};

const TAMANHOS = {
  inicio: { w: 150, h: 48 },
  fim: { w: 140, h: 48 },
  processo: { w: 160, h: 52 },
  decisao: { w: 120, h: 120 },
  documento: { w: 150, h: 58 },
  predefinido: { w: 160, h: 52 },
  manual: { w: 160, h: 52 },
  espera: { w: 150, h: 52 },
  conector: { w: 52, h: 52 },
};

function slugArquivo(titulo) {
  const base = String(titulo || 'fluxograma')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');

  return base || 'fluxograma';
}

function baixarDataUrl(dataUrl, nomeArquivo) {
  const link = document.createElement('a');
  link.download = nomeArquivo;
  link.href = dataUrl;
  link.click();
}

function posicaoAbsoluta(node, porId) {
  let x = Number(node.position?.x ?? 0);
  let y = Number(node.position?.y ?? 0);

  if (node.computedPosition) {
    return {
      x: Number(node.computedPosition.x ?? x),
      y: Number(node.computedPosition.y ?? y),
    };
  }

  const parentId = node.parentNode || node.data?.raiaId;
  if (parentId && porId.has(String(parentId))) {
    const pai = porId.get(String(parentId));
    const paiPos = posicaoAbsoluta(pai, porId);
    x += paiPos.x;
    y += paiPos.y;
  }

  return { x, y };
}

function tamanhoNo(node, ctx) {
  const base = { ...(TAMANHOS[node.type] || TAMANHOS.processo) };
  const label = String(node.data?.label || node.type || '');

  // Dimensões do Vue Flow às vezes incluem padding/handles — usar com teto.
  if (node.dimensions?.width && node.dimensions?.height) {
    const dw = Number(node.dimensions.width);
    const dh = Number(node.dimensions.height);
    if (dw >= 40 && dh >= 30 && dw <= 280 && dh <= 180) {
      base.w = Math.max(base.w, dw);
      base.h = Math.max(base.h, dh);
    }
  }

  if (node.type === 'decisao' || node.type === 'conector') {
    return base;
  }

  if (ctx && label) {
    ctx.font = '600 13px system-ui, Segoe UI, sans-serif';
    const textW = ctx.measureText(label).width;
    const needed = Math.ceil(textW + 36);
    base.w = Math.max(base.w, Math.min(230, needed));
  }

  return base;
}

function corSimbolo(tipo) {
  return CORES.simbolos[tipo] || CORES.simbolos.processo;
}

function quebrarTexto(ctx, texto, maxWidth) {
  const palavras = String(texto || '').split(/\s+/).filter(Boolean);
  if (!palavras.length) return [''];

  const linhas = [];
  let atual = palavras[0];

  for (let i = 1; i < palavras.length; i += 1) {
    const tentativa = `${atual} ${palavras[i]}`;
    if (ctx.measureText(tentativa).width <= maxWidth) {
      atual = tentativa;
    } else {
      linhas.push(atual);
      atual = palavras[i];
    }
  }

  linhas.push(atual);
  return linhas.slice(0, 3);
}

function desenharOval(ctx, x, y, w, h, cor) {
  const rx = w / 2;
  const ry = h / 2;
  const cx = x + rx;
  const cy = y + ry;

  ctx.beginPath();
  ctx.ellipse(cx, cy, rx - 1, ry - 1, 0, 0, Math.PI * 2);
  ctx.fillStyle = cor.fundo;
  ctx.fill();
  ctx.strokeStyle = cor.borda;
  ctx.lineWidth = 2.5;
  ctx.stroke();
}

function desenharRetangulo(ctx, x, y, w, h, cor, raio = 6) {
  ctx.beginPath();
  ctx.roundRect(x, y, w, h, raio);
  ctx.fillStyle = cor.fundo;
  ctx.fill();
  ctx.strokeStyle = cor.borda;
  ctx.lineWidth = 2.5;
  ctx.stroke();
}

function desenharLosango(ctx, x, y, w, h, cor) {
  const cx = x + w / 2;
  const cy = y + h / 2;
  const m = 6;

  ctx.beginPath();
  ctx.moveTo(cx, y + m);
  ctx.lineTo(x + w - m, cy);
  ctx.lineTo(cx, y + h - m);
  ctx.lineTo(x + m, cy);
  ctx.closePath();
  ctx.fillStyle = cor.fundo;
  ctx.fill();
  ctx.strokeStyle = cor.borda;
  ctx.lineWidth = 2.5;
  ctx.stroke();
}

function desenharDocumento(ctx, x, y, w, h, cor) {
  const onda = 10;
  ctx.beginPath();
  ctx.moveTo(x, y);
  ctx.lineTo(x + w, y);
  ctx.lineTo(x + w, y + h - onda);
  ctx.quadraticCurveTo(x + w * 0.75, y + h - onda * 2.2, x + w * 0.5, y + h - onda);
  ctx.quadraticCurveTo(x + w * 0.25, y + h + 2, x, y + h - onda);
  ctx.closePath();
  ctx.fillStyle = cor.fundo;
  ctx.fill();
  ctx.strokeStyle = cor.borda;
  ctx.lineWidth = 2.5;
  ctx.stroke();
}

function desenharManual(ctx, x, y, w, h, cor) {
  ctx.beginPath();
  ctx.moveTo(x + 16, y);
  ctx.lineTo(x + w, y);
  ctx.lineTo(x + w - 16, y + h);
  ctx.lineTo(x, y + h);
  ctx.closePath();
  ctx.fillStyle = cor.fundo;
  ctx.fill();
  ctx.strokeStyle = cor.borda;
  ctx.lineWidth = 2.5;
  ctx.stroke();
}

function desenharEspera(ctx, x, y, w, h, cor) {
  const r = h / 2;
  ctx.beginPath();
  ctx.moveTo(x + 8, y);
  ctx.lineTo(x + w - r, y);
  ctx.arc(x + w - r, y + r, r, -Math.PI / 2, Math.PI / 2);
  ctx.lineTo(x + 8, y + h);
  ctx.closePath();
  ctx.fillStyle = cor.fundo;
  ctx.fill();
  ctx.strokeStyle = cor.borda;
  ctx.lineWidth = 2.5;
  ctx.stroke();
}

function desenharPredefinido(ctx, x, y, w, h, cor) {
  desenharRetangulo(ctx, x, y, w, h, cor, 4);
  ctx.beginPath();
  ctx.moveTo(x + 10, y + 6);
  ctx.lineTo(x + 10, y + h - 6);
  ctx.moveTo(x + w - 10, y + 6);
  ctx.lineTo(x + w - 10, y + h - 6);
  ctx.strokeStyle = cor.borda;
  ctx.lineWidth = 2;
  ctx.stroke();
}

function desenharRotulo(ctx, x, y, w, h, texto, corTexto) {
  ctx.fillStyle = corTexto;
  ctx.font = '600 13px system-ui, Segoe UI, sans-serif';
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';

  const linhas = quebrarTexto(ctx, texto, w - 20);
  const lineHeight = 16;
  const startY = y + h / 2 - ((linhas.length - 1) * lineHeight) / 2;

  linhas.forEach((linha, i) => {
    ctx.fillText(linha, x + w / 2, startY + i * lineHeight);
  });
}

function desenharNo(ctx, node, pos, tamanho) {
  const { x, y } = pos;
  const { w, h } = tamanho;
  const cor = corSimbolo(node.type);
  const label = node.data?.label || node.type;

  switch (node.type) {
    case 'inicio':
    case 'fim':
    case 'conector':
      desenharOval(ctx, x, y, w, h, cor);
      break;
    case 'decisao':
      desenharLosango(ctx, x, y, w, h, cor);
      break;
    case 'documento':
      desenharDocumento(ctx, x, y, w, h, cor);
      break;
    case 'manual':
      desenharManual(ctx, x, y, w, h, cor);
      break;
    case 'espera':
      desenharEspera(ctx, x, y, w, h, cor);
      break;
    case 'predefinido':
      desenharPredefinido(ctx, x, y, w, h, cor);
      break;
    default:
      desenharRetangulo(ctx, x, y, w, h, cor);
      break;
  }

  // Losango: texto um pouco menor para não estourar
  if (node.type === 'decisao') {
    ctx.fillStyle = cor.texto;
    ctx.font = '700 12px system-ui, Segoe UI, sans-serif';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    const linhas = quebrarTexto(ctx, label, w * 0.55);
    const lineHeight = 14;
    const startY = y + h / 2 - ((linhas.length - 1) * lineHeight) / 2;
    linhas.forEach((linha, i) => {
      ctx.fillText(linha, x + w / 2, startY + i * lineHeight);
    });
    return;
  }

  desenharRotulo(ctx, x, y, w, h, label, cor.texto);
}

function normalizarDirecaoHandle(handleId, fallback = 'top') {
  const handle = String(handleId || fallback);
  if (handle === 'sim' || handle === 'right') return 'right';
  if (handle === 'left') return 'left';
  if (handle === 'nao' || handle === 'bottom' || handle === 'out') return 'bottom';
  return 'top';
}

function pontoNoHandle(pos, tamanho, handleId, fallback = 'top') {
  const { x, y } = pos;
  const { w, h } = tamanho;
  const dir = normalizarDirecaoHandle(handleId, fallback);

  if (dir === 'right') return { x: x + w, y: y + h / 2 };
  if (dir === 'left') return { x, y: y + h / 2 };
  if (dir === 'bottom') return { x: x + w / 2, y: y + h };
  return { x: x + w / 2, y };
}

function pontoOffset(p, dir, dist) {
  if (dir === 'right') return { x: p.x + dist, y: p.y };
  if (dir === 'left') return { x: p.x - dist, y: p.y };
  if (dir === 'bottom') return { x: p.x, y: p.y + dist };
  return { x: p.x, y: p.y - dist };
}

function segmentosIguais(a, b) {
  return Math.abs(a.x - b.x) < 0.5 && Math.abs(a.y - b.y) < 0.5;
}

function limparPontos(pontos) {
  const limpos = [];
  pontos.forEach((p) => {
    if (!limpos.length || !segmentosIguais(limpos[limpos.length - 1], p)) {
      limpos.push(p);
    }
  });

  // Remove colineares intermediários
  const out = [];
  for (let i = 0; i < limpos.length; i += 1) {
    if (i === 0 || i === limpos.length - 1) {
      out.push(limpos[i]);
      continue;
    }
    const prev = out[out.length - 1];
    const cur = limpos[i];
    const next = limpos[i + 1];
    const colinearH = Math.abs(prev.y - cur.y) < 0.5 && Math.abs(cur.y - next.y) < 0.5;
    const colinearV = Math.abs(prev.x - cur.x) < 0.5 && Math.abs(cur.x - next.x) < 0.5;
    if (!colinearH && !colinearV) {
      out.push(cur);
    }
  }
  return out;
}

function retanguloInflado(item, pad = 14) {
  return {
    x: item.pos.x - pad,
    y: item.pos.y - pad,
    w: item.tamanho.w + pad * 2,
    h: item.tamanho.h + pad * 2,
    id: item.node.id,
  };
}

function pontoEmRet(p, r) {
  return p.x >= r.x && p.x <= r.x + r.w && p.y >= r.y && p.y <= r.y + r.h;
}

function segmentoCruzaRet(a, b, r) {
  // Amostra ao longo do segmento (suficiente p/ ortogonais)
  const steps = 12;
  for (let i = 1; i < steps; i += 1) {
    const t = i / steps;
    const p = { x: a.x + (b.x - a.x) * t, y: a.y + (b.y - a.y) * t };
    if (pontoEmRet(p, r)) return true;
  }
  return false;
}

function caminhoCruzaObstaculos(pontos, obstaculos, ignorarIds = []) {
  const ignore = new Set(ignorarIds.map(String));
  for (let i = 0; i < pontos.length - 1; i += 1) {
    for (const obs of obstaculos) {
      if (ignore.has(String(obs.id))) continue;
      if (segmentoCruzaRet(pontos[i], pontos[i + 1], obs)) return true;
    }
  }
  return false;
}

function caminhoOrtogonalBasico(s1, t1, sourceDir, targetDir) {
  const horizontalOut = sourceDir === 'left' || sourceDir === 'right';
  const horizontalIn = targetDir === 'left' || targetDir === 'right';

  if (horizontalOut && horizontalIn) {
    const midX = (s1.x + t1.x) / 2;
    return [s1, { x: midX, y: s1.y }, { x: midX, y: t1.y }, t1];
  }
  if (!horizontalOut && !horizontalIn) {
    const midY = (s1.y + t1.y) / 2;
    return [s1, { x: s1.x, y: midY }, { x: t1.x, y: midY }, t1];
  }
  if (horizontalOut) {
    return [s1, { x: t1.x, y: s1.y }, t1];
  }
  return [s1, { x: s1.x, y: t1.y }, t1];
}

function caminhoComDesvio(s1, t1, sourceDir, targetDir, obstaculos, ignorarIds) {
  const candidatos = [];
  const base = caminhoOrtogonalBasico(s1, t1, sourceDir, targetDir);
  candidatos.push(base);

  // Desvios externos (evitam atravessar símbolos)
  const spanX = Math.max(80, Math.abs(t1.x - s1.x) * 0.35);
  const spanY = Math.max(60, Math.abs(t1.y - s1.y) * 0.35);
  const left = Math.min(s1.x, t1.x) - spanX;
  const right = Math.max(s1.x, t1.x) + spanX;
  const top = Math.min(s1.y, t1.y) - spanY;
  const bottom = Math.max(s1.y, t1.y) + spanY;

  candidatos.push(
    [s1, { x: left, y: s1.y }, { x: left, y: t1.y }, t1],
    [s1, { x: right, y: s1.y }, { x: right, y: t1.y }, t1],
    [s1, { x: s1.x, y: top }, { x: t1.x, y: top }, t1],
    [s1, { x: s1.x, y: bottom }, { x: t1.x, y: bottom }, t1],
    [s1, { x: left, y: s1.y }, { x: left, y: top }, { x: t1.x, y: top }, t1],
    [s1, { x: right, y: s1.y }, { x: right, y: bottom }, { x: t1.x, y: bottom }, t1],
  );

  let melhor = base;
  let melhorScore = Infinity;

  candidatos.forEach((pts) => {
    const cruza = caminhoCruzaObstaculos(pts, obstaculos, ignorarIds);
    let comprimento = 0;
    for (let i = 0; i < pts.length - 1; i += 1) {
      comprimento += Math.hypot(pts[i + 1].x - pts[i].x, pts[i + 1].y - pts[i].y);
    }
    const score = comprimento + (cruza ? 10000 : 0);
    if (score < melhorScore) {
      melhorScore = score;
      melhor = pts;
    }
  });

  return melhor;
}

/**
 * Roteamento estilo smoothstep: sai na direção do handle, desvia obstáculos e entra no destino.
 */
function caminhoOrtogonal(a, b, sourceHandle, targetHandle, obstaculos = [], ignorarIds = []) {
  const sourceDir = normalizarDirecaoHandle(sourceHandle, 'bottom');
  const targetDir = normalizarDirecaoHandle(targetHandle, 'top');
  const offset = 28;

  const dx = b.x - a.x;
  const dy = b.y - a.y;

  if (Math.abs(dx) < 4 && (sourceDir === 'top' || sourceDir === 'bottom')) {
    return limparPontos([a, b]);
  }
  if (Math.abs(dy) < 4 && (sourceDir === 'left' || sourceDir === 'right')) {
    return limparPontos([a, b]);
  }

  const s1 = pontoOffset(a, sourceDir, offset);
  const t1 = pontoOffset(b, targetDir, offset);
  const meio = caminhoComDesvio(s1, t1, sourceDir, targetDir, obstaculos, ignorarIds);

  return limparPontos([a, ...meio, b]);
}

function desenharSeta(ctx, x, y, angulo) {
  const t = 10;
  ctx.beginPath();
  ctx.moveTo(x, y);
  ctx.lineTo(x - t * Math.cos(angulo - Math.PI / 6), y - t * Math.sin(angulo - Math.PI / 6));
  ctx.lineTo(x - t * Math.cos(angulo + Math.PI / 6), y - t * Math.sin(angulo + Math.PI / 6));
  ctx.closePath();
  ctx.fillStyle = CORES.linha;
  ctx.fill();
}

function encolherUltimoSegmento(pontos, recuo = 10) {
  if (pontos.length < 2) return pontos;
  const out = pontos.map((p) => ({ ...p }));
  const p0 = out[out.length - 2];
  const p1 = out[out.length - 1];
  const dx = p1.x - p0.x;
  const dy = p1.y - p0.y;
  const len = Math.hypot(dx, dy) || 1;
  const t = Math.min(recuo, len * 0.45);
  out[out.length - 1] = {
    x: p1.x - (dx / len) * t,
    y: p1.y - (dy / len) * t,
  };
  return out;
}

function desenharLinhaAresta(ctx, pontos, { comSeta = true } = {}) {
  if (pontos.length < 2) return;

  const linha = comSeta ? encolherUltimoSegmento(pontos, 9) : pontos;

  ctx.strokeStyle = CORES.linha;
  ctx.lineWidth = 2.25;
  ctx.lineCap = 'round';
  ctx.lineJoin = 'round';
  ctx.beginPath();
  ctx.moveTo(linha[0].x, linha[0].y);
  for (let i = 1; i < linha.length; i += 1) {
    ctx.lineTo(linha[i].x, linha[i].y);
  }
  ctx.stroke();

  if (comSeta) {
    const p0 = pontos[pontos.length - 2];
    const p1 = pontos[pontos.length - 1];
    const angulo = Math.atan2(p1.y - p0.y, p1.x - p0.x);
    desenharSeta(ctx, p1.x, p1.y, angulo);
  }
}

function pontoRotuloAresta(pontos) {
  if (pontos.length < 2) {
    return { x: 0, y: 0 };
  }

  // Preferir o segmento mais longo (melhor leitura)
  let melhor = { a: pontos[0], b: pontos[1], len: 0 };
  for (let i = 0; i < pontos.length - 1; i += 1) {
    const a = pontos[i];
    const b = pontos[i + 1];
    const len = Math.hypot(b.x - a.x, b.y - a.y);
    if (len > melhor.len) {
      melhor = { a, b, len };
    }
  }

  return {
    x: (melhor.a.x + melhor.b.x) / 2,
    y: (melhor.a.y + melhor.b.y) / 2 - 12,
  };
}

function desenharRotuloAresta(ctx, pontos, label) {
  const texto = String(label || '').trim();
  if (!texto) return;

  const p = pontoRotuloAresta(pontos);
  ctx.font = '700 12px system-ui, Segoe UI, sans-serif';
  const tw = ctx.measureText(texto).width;
  const padX = 8;
  const padY = 5;
  const bw = tw + padX * 2;
  const bh = 18;

  ctx.beginPath();
  ctx.roundRect(p.x - bw / 2, p.y - bh / 2, bw, bh, 5);
  ctx.fillStyle = '#ffffff';
  ctx.fill();
  ctx.strokeStyle = '#99f6e4';
  ctx.lineWidth = 1.25;
  ctx.stroke();

  ctx.fillStyle = CORES.rotuloAresta;
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  ctx.fillText(texto, p.x, p.y);
}

function desenharRaias(ctx, raias, largura, offsetX, offsetY) {
  let y = 0;
  const faixaW = 168;

  raias.forEach((raia, index) => {
    const cor = CORES.raias[index % CORES.raias.length];
    const altura = Number(raia.altura) || 200;
    const rx = offsetX;
    const ry = offsetY + y;

    ctx.beginPath();
    ctx.roundRect(rx, ry, largura, altura, 12);
    ctx.fillStyle = cor.fundo;
    ctx.fill();
    ctx.strokeStyle = cor.borda;
    ctx.lineWidth = 2;
    ctx.stroke();

    ctx.fillStyle = 'rgba(255,255,255,0.65)';
    ctx.fillRect(rx + 1, ry + 1, faixaW - 1, altura - 2);

    ctx.strokeStyle = cor.borda;
    ctx.beginPath();
    ctx.moveTo(rx + faixaW, ry);
    ctx.lineTo(rx + faixaW, ry + altura);
    ctx.stroke();

    ctx.fillStyle = cor.faixa;
    ctx.font = '700 13px system-ui, Segoe UI, sans-serif';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';

    const linhas = quebrarTexto(ctx, raia.nome || `Raia ${index + 1}`, faixaW - 24);
    const start = ry + altura / 2 - ((linhas.length - 1) * 16) / 2;
    linhas.forEach((linha, i) => {
      ctx.fillText(linha, rx + faixaW / 2, start + i * 16);
    });

    y += altura + 16;
  });
}

function prepararModelo(nodes = [], edges = [], raias = [], ctx = null) {
  const porId = new Map();
  nodes.forEach((n) => porId.set(String(n.id), n));

  const etapas = nodes.filter((n) => n.type && n.type !== 'raia');
  const raiasNorm = Array.isArray(raias) ? raias : [];

  const itens = etapas.map((node) => {
    const pos = posicaoAbsoluta(node, porId);
    const tamanho = tamanhoNo(node, ctx);
    return { node, pos, tamanho };
  });

  let minX = Infinity;
  let minY = Infinity;
  let maxX = -Infinity;
  let maxY = -Infinity;

  if (raiasNorm.length) {
    let y = 0;
    raiasNorm.forEach((raia) => {
      const altura = Number(raia.altura) || 200;
      maxY = Math.max(maxY, y + altura);
      y += altura + 16;
    });
    minX = 0;
    minY = 0;
    maxX = 1200;
    itens.forEach(({ pos, tamanho }) => {
      maxX = Math.max(maxX, pos.x + tamanho.w + 80);
      maxY = Math.max(maxY, pos.y + tamanho.h + 32);
    });
  } else {
    itens.forEach(({ pos, tamanho }) => {
      minX = Math.min(minX, pos.x);
      minY = Math.min(minY, pos.y);
      maxX = Math.max(maxX, pos.x + tamanho.w);
      maxY = Math.max(maxY, pos.y + tamanho.h);
    });
  }

  if (!Number.isFinite(minX)) {
    minX = 0;
    minY = 0;
    maxX = 400;
    maxY = 300;
  }

  const padding = 64;
  const width = Math.ceil(maxX - minX + padding * 2);
  const height = Math.ceil(maxY - minY + padding * 2);

  return {
    itens,
    edges,
    raias: raiasNorm,
    minX,
    minY,
    width: Math.max(640, width),
    height: Math.max(420, height),
    padding,
    porId,
  };
}

/**
 * Exporta PNG (ou SVG embutindo PNG) redesenhando o diagrama.
 */
export async function exportarFluxograma(_elemento, {
  formato = 'png',
  titulo = 'fluxograma',
  backgroundColor = CORES.fundo,
  nodes = [],
  edges = [],
  raias = [],
} = {}) {
  const scale = 2;
  const probe = document.createElement('canvas').getContext('2d');
  const modelo = prepararModelo(nodes, edges, raias, probe);

  let ox = modelo.padding - modelo.minX;
  let oy = modelo.padding - modelo.minY;

  const itensAbs = modelo.itens.map(({ node, pos, tamanho }) => ({
    node,
    pos: { x: pos.x + ox, y: pos.y + oy },
    tamanho,
  }));
  const obstaculos = itensAbs.map((item) => retanguloInflado(item, 16));

  const caminhos = modelo.edges.map((edge) => {
    const sourceItem = itensAbs.find((i) => String(i.node.id) === String(edge.source));
    const targetItem = itensAbs.find((i) => String(i.node.id) === String(edge.target));
    if (!sourceItem || !targetItem) return null;

    const a = pontoNoHandle(sourceItem.pos, sourceItem.tamanho, edge.sourceHandle, 'bottom');
    const b = pontoNoHandle(targetItem.pos, targetItem.tamanho, edge.targetHandle, 'top');
    const pontos = caminhoOrtogonal(
      a,
      b,
      edge.sourceHandle,
      edge.targetHandle,
      obstaculos,
      [edge.source, edge.target]
    );
    return { pontos, label: edge.label || '' };
  }).filter(Boolean);

  let width = modelo.width;
  let height = modelo.height;
  caminhos.forEach(({ pontos }) => {
    pontos.forEach((p) => {
      width = Math.max(width, Math.ceil(p.x + 48));
      height = Math.max(height, Math.ceil(p.y + 48));
    });
  });
  itensAbs.forEach(({ pos, tamanho }) => {
    width = Math.max(width, Math.ceil(pos.x + tamanho.w + 48));
    height = Math.max(height, Math.ceil(pos.y + tamanho.h + 48));
  });

  const canvas = document.createElement('canvas');
  canvas.width = width * scale;
  canvas.height = height * scale;
  const ctx = canvas.getContext('2d');
  if (!ctx) {
    throw new Error('Não foi possível criar o canvas de exportação.');
  }

  ctx.scale(scale, scale);
  ctx.fillStyle = backgroundColor;
  ctx.fillRect(0, 0, width, height);
  ctx.imageSmoothingEnabled = true;
  ctx.imageSmoothingQuality = 'high';

  if (modelo.raias.length) {
    const larguraRaias = Math.max(
      1200,
      ...itensAbs.map(({ pos, tamanho }) => pos.x + tamanho.w - ox + 120)
    );
    desenharRaias(ctx, modelo.raias, larguraRaias, ox, oy);
  }

  // 1) Linhas atrás dos símbolos
  caminhos.forEach(({ pontos }) => {
    desenharLinhaAresta(ctx, pontos, { comSeta: false });
  });

  // 2) Símbolos
  itensAbs.forEach(({ node, pos, tamanho }) => {
    desenharNo(ctx, node, pos, tamanho);
  });

  // 3) Pontas das setas por cima
  caminhos.forEach(({ pontos }) => {
    if (pontos.length < 2) return;
    const p0 = pontos[pontos.length - 2];
    const p1 = pontos[pontos.length - 1];
    const angulo = Math.atan2(p1.y - p0.y, p1.x - p0.x);
    desenharSeta(ctx, p1.x, p1.y, angulo);
  });

  // 4) Rótulos por cima de tudo
  caminhos.forEach(({ pontos, label }) => {
    desenharRotuloAresta(ctx, pontos, label);
  });

  const pngData = canvas.toDataURL('image/png');

  if (formato === 'svg') {
    const svg = `<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}">
  <image href="${pngData}" width="${width}" height="${height}" />
</svg>`;
    const svgUrl = `data:image/svg+xml;charset=utf-8,${encodeURIComponent(svg)}`;
    baixarDataUrl(svgUrl, `${slugArquivo(titulo)}.svg`);
    return svgUrl;
  }

  baixarDataUrl(pngData, `${slugArquivo(titulo)}.png`);
  return pngData;
}
