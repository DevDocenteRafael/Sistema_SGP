const STORAGE_GLOBAL = 'siped_ciclo_contexto';
const STORAGE_GLOBAL_LEGADO = 'sgp_ciclo_contexto';
export const CICLO_CONTEXTO_EVENTO = 'siped-ciclo-contexto';
export const CICLO_CONTEXTO_EVENTO_LEGADO = 'sgp-ciclo-contexto';

const STORAGE_MODULO_LEGADO = {
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

function lerChave(chave) {
  try {
    const raw = localStorage.getItem(chave);

    return raw ? JSON.parse(raw) : null;
  } catch {
    return null;
  }
}

function limparChavesLegadas() {
  localStorage.removeItem(STORAGE_GLOBAL_LEGADO);
  Object.values(STORAGE_MODULO_LEGADO).forEach((chave) => {
    localStorage.removeItem(chave);
  });
}

function migrarLegadoSeNecessario() {
  const global = lerChave(STORAGE_GLOBAL);
  if (global?.id) {
    return global;
  }

  const legadoGlobal = lerChave(STORAGE_GLOBAL_LEGADO);
  if (legadoGlobal?.id) {
    localStorage.setItem(STORAGE_GLOBAL, JSON.stringify(legadoGlobal));
    limparChavesLegadas();
    return legadoGlobal;
  }

  for (const chave of Object.values(STORAGE_MODULO_LEGADO)) {
    const legado = lerChave(chave);
    if (legado?.id) {
      localStorage.setItem(STORAGE_GLOBAL, JSON.stringify(legado));
      limparChavesLegadas();
      return legado;
    }
  }

  return null;
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

export async function buscarCiclos() {
  if (ciclosCache) {
    return ciclosCache;
  }

  if (!ciclosPromise) {
    ciclosPromise = window.axios.get('/api/ciclos')
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

/** @deprecated Use buscarCiclos() */
export async function buscarCiclosPortfolio() {
  return buscarCiclos();
}

export async function garantirCicloContexto(_modulo = null, cicloId = null) {
  const ciclos = await buscarCiclos();
  const existente = lerCicloContexto();
  const alvoId = cicloId || existente?.id;
  const ciclo = (alvoId
    ? ciclos.find((item) => String(item.id) === String(alvoId))
    : null)
    || ciclos.find((item) => item.atual)
    || ciclos[0]
    || null;

  if (ciclo) {
    salvarCicloContexto(ciclo);
  } else if (existente?.id) {
    limparCicloContexto();
  }

  return ciclo;
}

export function lerCicloContexto(_modulo = null) {
  return migrarLegadoSeNecessario() || lerChave(STORAGE_GLOBAL);
}

export function salvarCicloContexto(ciclo, _modulo = null) {
  if (!ciclo?.id) {
    limparCicloContexto();
    return;
  }

  const contexto = {
    id: ciclo.id,
    nome: ciclo.nome,
    atual: Boolean(ciclo.atual),
    anos: Array.isArray(ciclo.anos) ? ciclo.anos.map(String) : [],
    origem_nome: ciclo.origem_nome || null,
  };

  localStorage.setItem(STORAGE_GLOBAL, JSON.stringify(contexto));
  limparChavesLegadas();
  emitirCicloContexto({ ciclo: contexto });
}

export function limparCicloContexto(_modulo = null) {
  localStorage.removeItem(STORAGE_GLOBAL);
  limparChavesLegadas();
  emitirCicloContexto({ ciclo: null });
}

export function idCicloContexto(_modulo = null) {
  const contexto = lerCicloContexto();

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
  window.dispatchEvent(new CustomEvent(CICLO_CONTEXTO_EVENTO_LEGADO, { detail: detalhe }));
}
