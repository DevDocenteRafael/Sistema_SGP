import { UNIDADES as UNIDADES_FALLBACK } from './unidades';

let cacheNomes = null;
let cacheOpcoes = null;
let pendingNomes = null;
let pendingOpcoes = null;

/**
 * Nomes ativos para selects (fonte: API de estruturas institucionais).
 * Em erro de rede, usa fallback estático; lista vazia da API é válida.
 */
export async function carregarUnidadesNomes({ forcar = false } = {}) {
  if (!forcar && cacheNomes !== null) {
    return cacheNomes;
  }

  if (!forcar && pendingNomes) {
    return pendingNomes;
  }

  pendingNomes = window.axios
    .get('/api/unidades-oferta/nomes')
    .then(({ data }) => {
      cacheNomes = Array.isArray(data.data) ? data.data : [];
      return cacheNomes;
    })
    .catch(() => {
      cacheNomes = [...UNIDADES_FALLBACK];
      return cacheNomes;
    })
    .finally(() => {
      pendingNomes = null;
    });

  return pendingNomes;
}

/** Opções { id, nome, tipo, ... } ativas — preferir em formulários novos. */
export async function carregarUnidadesOpcoes({ forcar = false } = {}) {
  if (!forcar && cacheOpcoes !== null) {
    return cacheOpcoes;
  }

  if (!forcar && pendingOpcoes) {
    return pendingOpcoes;
  }

  pendingOpcoes = window.axios
    .get('/api/unidades-oferta', { params: { ativo: 1, per_page: 200 } })
    .then(({ data }) => {
      cacheOpcoes = Array.isArray(data.data) ? data.data : [];
      return cacheOpcoes;
    })
    .catch(() => {
      cacheOpcoes = UNIDADES_FALLBACK.map((nome) => ({ id: null, nome, tipo: null }));
      return cacheOpcoes;
    })
    .finally(() => {
      pendingOpcoes = null;
    });

  return pendingOpcoes;
}

/**
 * Aplica nomes da API em uma ou mais props do componente (ex.: unidades, unidadesBase).
 * Se o componente tiver meta.unidades, atualiza também.
 */
export async function hidratarUnidadesSelect(vm, props = ['unidades'], { forcar = true } = {}) {
  const nomes = await carregarUnidadesNomes({ forcar });
  const alvos = Array.isArray(props) ? props : [props];

  alvos.forEach((prop) => {
    if (prop in vm) {
      vm[prop] = nomes;
    }
  });

  if (vm.meta && typeof vm.meta === 'object') {
    vm.meta.unidades = nomes;
  }

  return nomes;
}

export function limparCacheUnidadesNomes() {
  cacheNomes = null;
  cacheOpcoes = null;
  pendingNomes = null;
  pendingOpcoes = null;
}
