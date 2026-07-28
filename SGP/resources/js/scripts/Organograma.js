import { podeConsultarDados, podeEditarDados } from './auth';

function textoBusca(pessoa) {
  return [
    pessoa?.nome,
    pessoa?.cargo,
    pessoa?.setor,
    pessoa?.eixo_vinculado,
    pessoa?.contato,
  ].filter(Boolean).join(' ').toLowerCase();
}

export default {
  name: 'Organograma',

  data() {
    return {
      ordenador: null,
      assistentes: [],
      ramos: [],
      administrativos: [],
      meta: {},
      carregando: true,
      erro: '',
      busca: '',
      selecionado: null,
      eixosAbertos: [],
      podeEditarApi: false,
    };
  },

  computed: {
    acessoBloqueado() {
      return !podeConsultarDados();
    },

    podeEditar() {
      return podeEditarDados() && this.podeEditarApi;
    },

    termo() {
      return this.busca.trim().toLowerCase();
    },

    temDados() {
      return Boolean(
        this.ordenador
        || this.assistentes.length
        || this.ramos.length
        || this.administrativos.length
      );
    },

    ordenadorVisivel() {
      if (!this.ordenador) {
        return false;
      }

      if (!this.termo) {
        return true;
      }

      return this.destaca(this.ordenador) || this.assistentesVisiveis.length || this.ramosVisiveis.length;
    },

    assistentesVisiveis() {
      if (!this.termo) {
        return this.assistentes;
      }

      return this.assistentes.filter((pessoa) => this.destaca(pessoa));
    },

    ramosVisiveis() {
      if (!this.termo) {
        return this.ramos;
      }

      return this.ramos.filter((ramo) => {
        if (ramo.eixo.toLowerCase().includes(this.termo)) {
          return true;
        }

        if (ramo.responsavel && this.destaca(ramo.responsavel)) {
          return true;
        }

        return this.equipeFiltrada(ramo).length > 0;
      });
    },

    administrativosVisiveis() {
      if (!this.termo) {
        return this.administrativos;
      }

      return this.administrativos.filter((pessoa) => this.destaca(pessoa));
    },
  },

  watch: {
    termo(valor) {
      if (!valor) {
        return;
      }

      const abrir = this.ramos
        .filter((ramo) => {
          if (ramo.eixo.toLowerCase().includes(valor)) {
            return true;
          }

          if (ramo.responsavel && this.destaca(ramo.responsavel)) {
            return true;
          }

          return this.equipeFiltrada(ramo).length > 0;
        })
        .map((ramo) => ramo.eixo);

      this.eixosAbertos = [...new Set([...this.eixosAbertos, ...abrir])];
    },
  },

  mounted() {
    this.carregar();
  },

  methods: {
    async carregar() {
      this.carregando = true;
      this.erro = '';

      if (!podeConsultarDados()) {
        this.carregando = false;
        return;
      }

      try {
        const { data } = await window.axios.get('/api/organograma');
        this.ordenador = data.data?.ordenador ?? null;
        this.assistentes = Array.isArray(data.data?.assistentes) ? data.data.assistentes : [];
        this.ramos = Array.isArray(data.data?.ramos) ? data.data.ramos : [];
        this.administrativos = Array.isArray(data.data?.administrativos) ? data.data.administrativos : [];
        this.meta = data.meta || {};
        this.podeEditarApi = Boolean(data.meta?.pode_editar);
        this.eixosAbertos = this.ramos.slice(0, 2).map((ramo) => ramo.eixo);
      } catch (error) {
        this.erro = error.response?.data?.message
          || 'Não foi possível carregar o organograma.';
      } finally {
        this.carregando = false;
      }
    },

    labelTipo(tipo) {
      return this.meta.tipos_labels?.[tipo] || tipo;
    },

    avatarStyle(pessoa) {
      const cor = pessoa?.cor
        || this.meta.cores_eixo?.[pessoa?.eixo_vinculado]
        || this.meta.cores_tipo?.[pessoa?.tipo]
        || '#003F7D';

      return {
        background: cor,
        color: '#fff',
      };
    },

    destaca(pessoa) {
      if (!this.termo || !pessoa) {
        return false;
      }

      return textoBusca(pessoa).includes(this.termo);
    },

    destacaFiltro(pessoa, eixo = '') {
      if (!this.termo) {
        return true;
      }

      return this.destaca(pessoa) || eixo.toLowerCase().includes(this.termo);
    },

    equipeFiltrada(ramo) {
      const equipe = Array.isArray(ramo.equipe) ? ramo.equipe : [];

      if (!this.termo) {
        return equipe;
      }

      if (ramo.eixo.toLowerCase().includes(this.termo)) {
        return equipe;
      }

      return equipe.filter((pessoa) => this.destaca(pessoa));
    },

    toggleEixo(eixo) {
      if (this.eixosAbertos.includes(eixo)) {
        this.eixosAbertos = this.eixosAbertos.filter((item) => item !== eixo);
        return;
      }

      this.eixosAbertos = [...this.eixosAbertos, eixo];
    },

    selecionar(pessoa) {
      this.selecionado = pessoa;
    },
  },
};
