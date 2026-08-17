const STORAGE_GLOBAL = 'sgp_ciclo_contexto';
export const CICLO_CONTEXTO_EVENTO = 'sgp-ciclo-contexto';

const STORAGE_MODULO = {
  cursos: 'sgp_ciclo_contexto_cursos',
  metas: 'sgp_ciclo_contexto_metas',
  pca: 'sgp_ciclo_contexto_pca',
  eixos: 'sgp_ciclo_contexto_eixos',
};

const PATH_MODULO = {
  '/app/cursos': 'cursos',
  '/app/plano-de-metas': 'metas',
  '/app/pca': 'pca',
  '/app/eixos': 'eixos',
};

function chaveDoModulo(modulo) {
  return modulo && STORAGE_MODULO[modulo] ? STORAGE_MODULO[modulo] : null;
}

function lerChave(chave) {
  try {
    const raw = localStorage.getItem(chave);

    return raw ? JSON.parse(raw) : null;
  } catch {
    return null;
  }
}

export function moduloDoPath(path) {
  return PATH_MODULO[path] || null;
}

let ciclosCache = null;
let ciclosPromise = null;

export function invalidarCacheCiclos() {
  ciclosCache = null;
  ciclosPromise = null;
}

export async function buscarCiclosPortfolio() {
  if (ciclosCache) {
    return ciclosCache;
  }

  if (!ciclosPromise) {
    ciclosPromise = window.axios.get('/api/portfolio-ciclos')
      .then((response) => {
        ciclosCache = response.data?.data ?? [];
        return ciclosCache;
      })
      .finally(() => {
        ciclosPromise = null;
      });
  }

  return ciclosPromise;
}

export async function garantirCicloContexto(modulo, cicloId = null) {
  const ciclos = await buscarCiclosPortfolio();
  const existente = lerCicloContexto(modulo);
  const alvoId = cicloId || existente?.id;
  const ciclo = (alvoId
    ? ciclos.find((item) => String(item.id) === String(alvoId))
    : null)
    || ciclos.find((item) => item.atual)
    || ciclos[0]
    || null;

  if (ciclo && modulo) {
    salvarCicloContexto(ciclo, modulo);
  }

  return ciclo;
}

export function lerCicloContexto(modulo = null) {
  const chave = chaveDoModulo(modulo);

  if (chave) {
    return lerChave(chave);
  }

  return lerChave(STORAGE_GLOBAL);
}

export function salvarCicloContexto(ciclo, modulo = null) {
  if (!ciclo?.id) {
    limparCicloContexto(modulo);
    return;
  }

  const contexto = {
    id: ciclo.id,
    nome: ciclo.nome,
    atual: Boolean(ciclo.atual),
    anos: Array.isArray(ciclo.anos) ? ciclo.anos.map(String) : [],
    origem_nome: ciclo.origem_nome || null,
  };

  const chave = chaveDoModulo(modulo);
  localStorage.setItem(chave || STORAGE_GLOBAL, JSON.stringify(contexto));
  emitirCicloContexto({ modulo: modulo || null, ciclo: contexto });
}

export function limparCicloContexto(modulo = null) {
  const chave = chaveDoModulo(modulo);
  if (chave) {
    localStorage.removeItem(chave);
  } else {
    localStorage.removeItem(STORAGE_GLOBAL);
    Object.values(STORAGE_MODULO).forEach((item) => localStorage.removeItem(item));
  }

  emitirCicloContexto({ modulo: modulo || null, ciclo: null });
}

export function idCicloContexto(modulo = null) {
  const contexto = lerCicloContexto(modulo);

  return contexto?.id ? String(contexto.id) : '';
}

export function anoPrincipalDoCiclo(ciclo) {
  const anos = Array.isArray(ciclo?.anos) ? ciclo.anos.map(String) : [];

  return anos.length ? anos[anos.length - 1] : '';
}

function emitirCicloContexto(detalhe) {
  if (typeof window === 'undefined') {
    return;
  }

  window.dispatchEvent(new CustomEvent(CICLO_CONTEXTO_EVENTO, { detail: detalhe }));
}
