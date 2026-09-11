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
      cicloContextoId: lerCicloContexto()?.id || '',
      resumo: {
        ciclo_id: null,
        ciclo_nome: '',
        totais: { eixos: 5, cursos_classificados: 0, cursos: 0, turmas: 0, alunos: 0 },
        eixos: [],
      },
    };
  },

  computed: {
    cicloId() {
      return this.$route.query.ciclo_id || this.cicloContextoId || '';
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
      const id = this.$route.query.ciclo_id || this.cicloContextoId || lerCicloContexto()?.id || '';
      return id ? { ciclo_id: id } : {};
    },

    async carregarResumo() {
      this.carregando = true;
      this.erro = '';
      try {
        const { data } = await window.axios.get('/api/eixos/resumo', { params: this.queryCiclo() });
        this.resumo = {
          ciclo_id: null,
          ciclo_nome: '',
          totais: { eixos: 5, cursos_classificados: 0, cursos: 0, turmas: 0, alunos: 0 },
          eixos: [],
          ...(data.data || {}),
        };
      } catch (error) {
        this.erro = error.response?.data?.message || 'Não foi possível carregar o resumo dos eixos.';
        this.resumo = {
          ciclo_id: null,
          ciclo_nome: '',
          totais: { eixos: 5, cursos_classificados: 0, cursos: 0, turmas: 0, alunos: 0 },
          eixos: [],
        };
      } finally {
        this.carregando = false;
      }
    },

    abrirDetalhe(eixo) {
      this.$router.push({
        name: 'eixos-detalhe',
        params: { id: String(eixo.id) },
        query: this.queryCiclo(),
      });
    },

    aoTrocarCiclo(evento) {
      this.cicloContextoId = evento?.detail?.ciclo?.id || lerCicloContexto()?.id || '';
      this.carregarResumo();
    },
  },
};
