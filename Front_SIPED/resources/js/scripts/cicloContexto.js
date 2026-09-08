const STORAGE_GLOBAL = 'sgp_ciclo_contexto';
export const CICLO_CONTEXTO_EVENTO = 'sgp-ciclo-contexto';

/** Chaves antigas por módulo — migradas para o storage global na primeira leitura. */
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
  Object.values(STORAGE_MODULO_LEGADO).forEach((chave) => {
    localStorage.removeItem(chave);
  });
}

function migrarLegadoSeNecessario() {
  const global = lerChave(STORAGE_GLOBAL);
  if (global?.id) {
    return global;
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

/**
 * Garante um ciclo global. O parâmetro `modulo` é ignorado (compatibilidade).
 */
export async function garantirCicloContexto(_modulo = null, cicloId = null) {
  const ciclos = await buscarCiclosPortfolio();
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
  }

  return ciclo;
}

/** Lê o ciclo global. O parâmetro `modulo` é ignorado (compatibilidade). */
export function lerCicloContexto(_modulo = null) {
  return migrarLegadoSeNecessario() || lerChave(STORAGE_GLOBAL);
}

/**
 * Define o ciclo global único (Cursos, Metas, PCA e Eixos).
 * O parâmetro `modulo` é ignorado (compatibilidade).
 */
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
}
