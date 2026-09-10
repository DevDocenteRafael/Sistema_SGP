import { CICLO_CONTEXTO_EVENTO, lerCicloContexto } from './cicloContexto';

const CLASSES_EIXO = [
  { teste: /gastronomia/i, classe: 'eixo-card--laranja' },
  { teste: /saúde|saude/i, classe: 'eixo-card--verde' },
  { teste: /gestão|gestao|moda/i, classe: 'eixo-card--azul' },
  { teste: /tecnologia|economia/i, classe: 'eixo-card--roxo' },
  { teste: /beleza/i, classe: 'eixo-card--rosa' },
];

export default {
  name: 'Eixos',

  data() {
    return {
      carregando: false,
      erro: '',
      resumo: {
        ciclo_id: null,
        ciclo_nome: '',
        eixos: [],
        pendentes: { cursos: 0, ofertas: 0, sem_correspondencia: 0, amostra: [], amostra_sem_correspondencia: [] },
      },
      detalhes: null,
      detalheAberto: false,
      eixoSelecionadoId: null,
    };
  },

  computed: {
    cicloId() {
      return this.$route.query.ciclo_id || lerCicloContexto()?.id || '';
    },
    totalPendentes() {
      const pendentes = this.resumo.pendentes || {};
      return (pendentes.cursos || 0) + (pendentes.ofertas || 0);
    },
    totalSemCorrespondencia() {
      return this.resumo.pendentes?.sem_correspondencia || 0;
    },
  },

  watch: {
    cicloId() {
      this.carregarResumo();
    },
  },

  async created() {
    window.addEventListener(CICLO_CONTEXTO_EVENTO, this.aoTrocarCiclo);
    await this.carregarResumo();
  },

  beforeUnmount() {
    window.removeEventListener(CICLO_CONTEXTO_EVENTO, this.aoTrocarCiclo);
  },

  methods: {
    classeEixo(nome) {
      const encontrado = CLASSES_EIXO.find((item) => item.teste.test(String(nome || '')));
      return encontrado?.classe || '';
    },

    queryCiclo() {
      return this.cicloId ? { ciclo_id: this.cicloId } : {};
    },

    async carregarResumo() {
      this.carregando = true;
      this.erro = '';

      try {
        const { data } = await window.axios.get('/api/eixos/resumo', { params: this.queryCiclo() });
        this.resumo = data.data || { ciclo_id: null, ciclo_nome: '', eixos: [], pendentes: { cursos: 0, ofertas: 0, sem_correspondencia: 0, amostra: [], amostra_sem_correspondencia: [] } };
      } catch (error) {
        this.erro = error.response?.data?.message || 'Não foi possível carregar o resumo dos eixos.';
        this.resumo = { ciclo_id: null, ciclo_nome: '', eixos: [], pendentes: { cursos: 0, ofertas: 0, sem_correspondencia: 0, amostra: [], amostra_sem_correspondencia: [] } };
      } finally {
        this.carregando = false;
      }
    },

    async abrirDetalhes(eixo) {
      this.eixoSelecionadoId = eixo.id;
      this.erro = '';

      try {
        const { data } = await window.axios.get(`/api/eixos/${eixo.id}/detalhes`, { params: this.queryCiclo() });
        this.detalhes = data.data || null;
        this.detalheAberto = true;
      } catch (error) {
        this.erro = error.response?.data?.message || 'Não foi possível carregar o detalhe do eixo.';
      }
    },

    fecharDetalhes() {
      this.detalheAberto = false;
      this.detalhes = null;
      this.eixoSelecionadoId = null;
    },

    aoTrocarCiclo() {
      this.fecharDetalhes();
      this.carregarResumo();
    },
  },
};
