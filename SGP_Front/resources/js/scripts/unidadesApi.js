import { UNIDADES as UNIDADES_FALLBACK } from './unidades';

let cacheNomes = null;
let carregandoPromise = null;

/** Lista flat de nomes ativos (API), com fallback estático. */
export async function carregarUnidadesNomes({ forcar = false } = {}) {
  if (!forcar && Array.isArray(cacheNomes) && cacheNomes.length) {
    return cacheNomes;
  }

  if (!forcar && carregandoPromise) {
    return carregandoPromise;
  }

  carregandoPromise = window.axios
    .get('/api/unidades-oferta/nomes')
    .then(({ data }) => {
      const nomes = Array.isArray(data.data) ? data.data : [];
      cacheNomes = nomes.length ? nomes : [...UNIDADES_FALLBACK];
      return cacheNomes;
    })
    .catch(() => {
      cacheNomes = [...UNIDADES_FALLBACK];
      return cacheNomes;
    })
    .finally(() => {
      carregandoPromise = null;
    });

  return carregandoPromise;
}

export function limparCacheUnidadesNomes() {
  cacheNomes = null;
}

/** Árvore RA → grupos CEP/Polo/Faculdade para o formulário de Cursos. */
export async function carregarUnidadesOpcoes() {
  try {
    const { data } = await window.axios.get('/api/unidades-oferta/opcoes');
    return Array.isArray(data.data) ? data.data : [];
  } catch {
    return [];
  }
}
