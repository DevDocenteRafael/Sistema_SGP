import { CICLO_CONTEXTO_EVENTO, lerCicloContexto } from './cicloContexto';
import { podeEditarDados } from './auth';
import SearchableSelect from '../components/SearchableSelect.vue';

export default {
  name: 'RevisaoDados',
  components: { SearchableSelect },

  data() {
    return {
      tipo: 'sem_eixo',
      carregando: false,
      salvando: false,
      erro: '',
      mensagem: '',
      busca: '',
      buscaTimeout: null,
      cicloContextoId: lerCicloContexto()?.id || '',
      itens: [],
      cursosCiclo: [],
      eixos: [],
      segmentosPorEixo: {},
      totais: { total: 0, sem_eixo: 0, sem_vinculo: 0 },
      meta: { total: 0, per_page: 25, current_page: 1, last_page: 1, from: 0, to: 0 },
      classificando: null,
      vinculando: null,
      formClassificacao: { eixo: '', segmento: '' },
      formVinculo: { curso_id: '' },
    };
  },

  computed: {
    podeEditar() {
      return podeEditarDados();
    },
    cicloNome() {
      return lerCicloContexto()?.nome || 'atual';
    },
    segmentosDoEixo() {
      return this.segmentosPorEixo[this.formClassificacao.eixo] || [];
    },
    opcoesCursos() {
      return this.cursosCiclo.map((curso) => ({
        value: String(curso.id),
        label: `${curso.titulo}${curso.eixo ? ` — ${curso.eixo}` : ''}`,
      }));
    },
  },

  watch: {
    'formClassificacao.eixo'() {
      const lista = this.segmentosDoEixo;
      if (this.formClassificacao.segmento && !lista.includes(this.formClassificacao.segmento)) {
        this.formClassificacao.segmento = '';
      }
    },
  },

  async created() {
    window.addEventListener(CICLO_CONTEXTO_EVENTO, this.aoTrocarCiclo);
    await this.carregar(1);
  },

  beforeUnmount() {
    window.removeEventListener(CICLO_CONTEXTO_EVENTO, this.aoTrocarCiclo);
    clearTimeout(this.buscaTimeout);
  },

  methods: {
    queryCiclo() {
      const id = this.cicloContextoId || lerCicloContexto()?.id || '';
      return id ? { ciclo_id: id } : {};
    },

    async carregar(pagina = 1) {
      this.carregando = true;
      this.erro = '';
      try {
        const { data } = await window.axios.get('/api/revisao-dados', {
          params: {
            ...this.queryCiclo(),
            tipo: this.tipo,
            busca: this.busca || undefined,
            page: pagina,
            per_page: 25,
          },
        });
        this.itens = data.data || [];
        this.totais = data.totais || { total: 0, sem_eixo: 0, sem_vinculo: 0 };
        this.eixos = data.eixos || [];
        this.segmentosPorEixo = data.segmentos_por_eixo || {};
        this.meta = {
          total: 0, per_page: 25, current_page: 1, last_page: 1, from: 0, to: 0,
          ...(data.meta || {}),
        };
        if (
          this.tipo === 'sem_eixo'
          && Number(this.totais.sem_eixo || 0) === 0
          && Number(this.totais.sem_vinculo || 0) > 0
          && pagina === 1
        ) {
          this.tipo = 'sem_vinculo';
          await this.carregar(1);
          return;
        }
      } catch (error) {
        this.erro = error.response?.data?.message || 'Não foi possível carregar a revisão de dados.';
        this.itens = [];
      } finally {
        this.carregando = false;
      }
    },

    async carregarCursosCiclo() {
      const { data } = await window.axios.get('/api/cursos', { params: this.queryCiclo() });
      this.cursosCiclo = data.data || [];
    },

    trocarTipo(tipo) {
      this.tipo = tipo;
      this.carregar(1);
    },

    agendarBusca() {
      clearTimeout(this.buscaTimeout);
      this.buscaTimeout = setTimeout(() => this.carregar(1), 250);
    },

    irPagina(pagina) {
      this.carregar(pagina);
    },

    abrirClassificacao(item) {
      this.classificando = item;
      this.formClassificacao = {
        eixo: this.eixos.includes(item.eixo_original) ? item.eixo_original : '',
        segmento: item.segmento || '',
      };
    },

    async abrirVinculo(item) {
      this.vinculando = item;
      this.formVinculo = { curso_id: item.sugestoes?.[0] ? String(item.sugestoes[0].id) : '' };
      if (!this.cursosCiclo.length) {
        await this.carregarCursosCiclo();
      }
    },

    async salvarClassificacao() {
      if (!this.classificando || !this.formClassificacao.eixo) {
        this.erro = 'Selecione um dos cinco eixos oficiais.';
        return;
      }
      this.salvando = true;
      this.erro = '';
      try {
        await window.axios.post(`/api/revisao-dados/cursos/${this.classificando.id}/classificar`, this.formClassificacao);
        this.mensagem = 'Curso classificado no eixo oficial.';
        this.classificando = null;
        await this.carregar(this.meta.current_page);
      } catch (error) {
        this.erro = error.response?.data?.message || 'Não foi possível classificar o curso.';
      } finally {
        this.salvando = false;
      }
    },

    async salvarVinculo() {
      if (!this.vinculando || !this.formVinculo.curso_id) return;
      this.salvando = true;
      this.erro = '';
      try {
        await window.axios.post(`/api/revisao-dados/execucoes/${this.vinculando.id}/vincular`, {
          curso_id: Number(this.formVinculo.curso_id),
        });
        this.mensagem = 'Registro vinculado ao curso oficial.';
        this.vinculando = null;
        await this.carregar(this.meta.current_page);
      } catch (error) {
        this.erro = error.response?.data?.message || 'Não foi possível vincular o registro.';
      } finally {
        this.salvando = false;
      }
    },

    aoTrocarCiclo(evento) {
      this.cicloContextoId = evento?.detail?.ciclo?.id || lerCicloContexto()?.id || '';
      this.cursosCiclo = [];
      this.carregar(1);
    },
  },
};
