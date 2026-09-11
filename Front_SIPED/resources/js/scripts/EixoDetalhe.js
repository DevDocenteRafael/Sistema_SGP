import { CICLO_CONTEXTO_EVENTO, lerCicloContexto } from './cicloContexto';
import { podeEditarDados } from './auth';

export default {
  name: 'EixoDetalhe',

  data() {
    return {
      carregando: false,
      carregandoCursos: false,
      erro: '',
      cicloContextoId: lerCicloContexto()?.id || '',
      detalhes: { eixo: null, ciclo_nome: '', segmentos: [] },
      cursos: [],
      busca: '',
      buscaTimeout: null,
      meta: {
        total: 0,
        per_page: 25,
        current_page: 1,
        last_page: 1,
        from: 0,
        to: 0,
      },
    };
  },

  computed: {
    podeEditar() {
      return podeEditarDados();
    },
    eixoId() {
      return this.$route.params.id;
    },
  },

  watch: {
    eixoId() {
      this.carregarTudo();
    },
  },

  async created() {
    window.addEventListener(CICLO_CONTEXTO_EVENTO, this.aoTrocarCiclo);
    await this.carregarTudo();
  },

  beforeUnmount() {
    window.removeEventListener(CICLO_CONTEXTO_EVENTO, this.aoTrocarCiclo);
    clearTimeout(this.buscaTimeout);
  },

  methods: {
    queryCiclo() {
      const id = this.$route.query.ciclo_id || this.cicloContextoId || lerCicloContexto()?.id || '';
      return id ? { ciclo_id: id } : {};
    },

    async carregarTudo() {
      await this.carregarDetalhe();
      await this.carregarCursos(1);
    },

    async carregarDetalhe() {
      this.carregando = true;
      this.erro = '';
      try {
        const { data } = await window.axios.get(`/api/eixos/${this.eixoId}/detalhes`, { params: this.queryCiclo() });
        this.detalhes = data.data || { eixo: null, ciclo_nome: '', segmentos: [] };
      } catch (error) {
        this.erro = error.response?.data?.message || 'Não foi possível carregar o detalhe do eixo.';
        this.detalhes = { eixo: null, ciclo_nome: '', segmentos: [] };
      } finally {
        this.carregando = false;
      }
    },

    async carregarCursos(pagina = 1) {
      this.carregandoCursos = true;
      try {
        const { data } = await window.axios.get(`/api/eixos/${this.eixoId}/cursos`, {
          params: {
            ...this.queryCiclo(),
            busca: this.busca || undefined,
            page: pagina,
            per_page: 25,
          },
        });
        this.cursos = data.data || [];
        this.meta = {
          total: 0,
          per_page: 25,
          current_page: 1,
          last_page: 1,
          from: 0,
          to: 0,
          ...(data.meta || {}),
        };
      } catch (error) {
        this.erro = error.response?.data?.message || 'Não foi possível carregar os cursos do eixo.';
        this.cursos = [];
      } finally {
        this.carregandoCursos = false;
      }
    },

    agendarBusca() {
      clearTimeout(this.buscaTimeout);
      this.buscaTimeout = setTimeout(() => this.carregarCursos(1), 250);
    },

    irPagina(pagina) {
      this.carregarCursos(pagina);
    },

    voltar() {
      this.$router.push({ name: 'eixos', query: this.queryCiclo() });
    },

    verCurso(curso) {
      this.$router.push({
        name: 'cursos',
        query: {
          curso_id: String(curso.id),
          ...this.queryCiclo(),
        },
      });
    },

    cadastrarCurso() {
      this.$router.push({
        name: 'cursos',
        query: {
          novo: '1',
          eixo: this.detalhes.eixo?.nome || '',
          ...this.queryCiclo(),
        },
      });
    },

    aoTrocarCiclo(evento) {
      this.cicloContextoId = evento?.detail?.ciclo?.id || lerCicloContexto()?.id || '';
      this.carregarTudo();
    },
  },
};
