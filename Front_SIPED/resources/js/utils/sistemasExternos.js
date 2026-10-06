import { reactive } from 'vue';

/**
 * URLs dos sistemas externos (SEI, SIG, SIGIN, site Senac), vindas do back
 * (config/sistemas_externos.php via .env). Nenhuma URL institucional fica fixa no front.
 */
export const sistemasExternos = reactive({
  carregado: false,
  sei: { base_url: null, processo_url: null },
  sig: { base_url: null, curso_url: null },
  sigin: { base_url: null },
  senac: { base_url: null },
});

let carregamento = null;

export function carregarSistemasExternos() {
  if (sistemasExternos.carregado) return Promise.resolve(sistemasExternos);
  if (carregamento) return carregamento;

  carregamento = window.axios
    .get('/api/sistemas-externos')
    .then(({ data }) => {
      Object.assign(sistemasExternos, data?.data || {}, { carregado: true });
      return sistemasExternos;
    })
    .catch(() => {
      // Sem configuração: os códigos aparecem como texto, sem link.
      carregamento = null;
      return sistemasExternos;
    });

  return carregamento;
}

/** Usado nos testes e quando a configuração já vem por outra resposta. */
export function definirSistemasExternos(config) {
  Object.assign(sistemasExternos, config || {}, { carregado: true });
}
