import PageTableCard from '../components/crud/PageTableCard.vue';
import CrudPageHeader from '../components/crud/CrudPageHeader.vue';
import Pagination from '../components/crud/Pagination.vue';

const ACAO_LABEL = {
  criar: 'Criou',
  editar: 'Editou',
  excluir: 'Excluiu',
  restaurar: 'Restaurou',
  importar: 'Importou',
  inativar: 'Inativou',
  reativar: 'Reativou',
  login: 'Entrou',
  logout: 'Saiu',
  login_falha: 'Login recusado',
};

export default {
  name: 'Auditoria',
  components: { PageTableCard, CrudPageHeader, Pagination },
  data() {
    return {
      registros: [],
      meta: {
        total: 0,
        per_page: 50,
        current_page: 1,
        last_page: 1,
        acoes: Object.keys(ACAO_LABEL),
        modulos: [],
      },
      carregando: false,
      mensagemErro: '',
      mensagemSucesso: '',
      restaurando: null,
      detalhe: null,
      filtros: {
        busca: '',
        modulo: '',
        acao: '',
        data_inicio: '',
        data_fim: '',
        a_restaurar: false,
      },
      buscaTimeout: null,
    };
  },
  mounted() {
    this.carregar();
  },
  computed: {
    temFiltro() {
      return Object.values(this.filtros).some((valor) => valor !== '' && valor != null && valor !== false);
    },
  },
  methods: {
    limparFiltros() {
      this.filtros = {
        busca: '',
        modulo: '',
        acao: '',
        data_inicio: '',
        data_fim: '',
        a_restaurar: false,
      };
      this.carregar(1);
    },

    labelAcao(acao) {
      return ACAO_LABEL[acao] ?? acao;
    },

    formatarData(valor) {
      if (!valor) {
        return '—';
      }

      const data = new Date(valor);

      if (Number.isNaN(data.getTime())) {
        return valor;
      }

      return data.toLocaleString('pt-BR');
    },

    async carregar(page = 1) {
      clearTimeout(this.buscaTimeout);

      this.buscaTimeout = setTimeout(async () => {
        this.carregando = true;
        this.mensagemErro = '';

        try {
          const params = { page, per_page: this.meta.per_page };

          Object.entries(this.filtros).forEach(([chave, valor]) => {
            if (valor !== '' && valor !== null && valor !== false) {
              params[chave] = valor === true ? 1 : valor;
            }
          });

          const { data } = await window.axios.get('/api/cadastros', { params });
          this.registros = data.data ?? [];
          this.meta = {
            ...this.meta,
            ...(data.meta ?? {}),
          };
        } catch (error) {
          this.mensagemErro = error?.response?.data?.message
            ?? 'Não foi possível carregar a auditoria.';
          this.registros = [];
        } finally {
          this.carregando = false;
        }
      }, 200);
    },

    async restaurar(item) {
      if (!window.confirm(`Restaurar este registro?\n\n${item.resumo}\n\nEle volta a aparecer na tela de origem.`)) {
        return;
      }
      this.restaurando = item.id;
      this.mensagemErro = '';
      this.mensagemSucesso = '';
      try {
        const { data } = await window.axios.post(`/api/lixeira/${item.modulo_lixeira}/${item.registro_id}/restaurar`);
        this.mensagemSucesso = data.message || 'Registro restaurado.';
        this.carregar(this.meta.current_page);
      } catch (error) {
        this.mensagemErro = error?.response?.data?.message ?? 'Não foi possível restaurar o registro.';
      } finally {
        this.restaurando = null;
      }
    },

    abrirDetalhe(registro) {
      this.detalhe = registro;
    },

    fecharDetalhe() {
      this.detalhe = null;
    },

    paginaAnterior() {
      if (this.meta.current_page > 1) {
        this.carregar(this.meta.current_page - 1);
      }
    },

    paginaProxima() {
      if (this.meta.current_page < this.meta.last_page) {
        this.carregar(this.meta.current_page + 1);
      }
    },

    irParaPagina(page) {
      if (!this.carregando && page >= 1 && page <= this.meta.last_page) {
        this.carregar(page);
      }
    },

    alterarRegistrosPorPagina(perPage) {
      const quantidade = Number(perPage);
      if (!Number.isInteger(quantidade) || quantidade < 1 || this.carregando) return;
      this.meta.per_page = quantidade;
      this.meta.current_page = 1;
      this.carregar(1);
    },
  },
};
