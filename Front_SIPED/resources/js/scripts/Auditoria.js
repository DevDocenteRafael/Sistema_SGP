import PageTableCard from '../components/crud/PageTableCard.vue';
import CrudPageHeader from '../components/crud/CrudPageHeader.vue';
import Pagination from '../components/crud/Pagination.vue';
import HistoricoImportacoes from '../components/importacoes/HistoricoImportacoes.vue';

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
  components: { PageTableCard, CrudPageHeader, Pagination, HistoricoImportacoes },
  data() {
    return {
      aba: 'atividades',
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
    this.sincronizarAbaDaRota();
  },
  watch: {
    '$route.query.aba'() {
      this.sincronizarAbaDaRota();
    },
    '$route.query.historico'() {
      this.abrirHistoricoDaRota();
    },
  },
  computed: {
    temFiltro() {
      return Object.values(this.filtros).some((valor) => valor !== '' && valor != null && valor !== false);
    },
    opcoesModulos() {
      return this.meta.modulos.map((modulo) => ({
        value: modulo,
        label: this.labelModulo(modulo),
      }));
    },
    subtituloAba() {
      return this.aba === 'importacoes'
        ? 'Histórico e rastreabilidade das importações realizadas no SIPED'
        : 'Histórico de cadastros, edições, exclusões, restaurações, importações e acessos';
    },
    infoAba() {
      return this.aba === 'importacoes'
        ? 'Cada envio de planilha ou PDF fica registrado com arquivo, usuário, data/hora, ciclo, contagens e situação. Disponível para Root e Administrador.'
        : 'Registro automático de quem fez o quê no SIPED. Exclusões são lógicas: enquanto o registro estiver excluído, o evento mostra o botão Restaurar. Disponível para Root e Administrador.';
    },
  },
  methods: {
    abaDaRota() {
      return this.$route?.query?.aba === 'importacoes' ? 'importacoes' : 'atividades';
    },

    sincronizarAbaDaRota() {
      const aba = this.abaDaRota();
      const mudou = this.aba !== aba;
      this.aba = aba;
      if (aba === 'atividades') {
        this.carregar(this.meta.current_page || 1);
        return;
      }
      if (mudou || this.$route.query.historico) {
        this.$nextTick(() => this.abrirHistoricoDaRota());
      }
    },

    selecionarAba(aba) {
      const query = { ...this.$route.query };
      if (aba === 'importacoes') {
        query.aba = 'importacoes';
      } else {
        delete query.aba;
        delete query.historico;
      }
      this.$router.replace({ query }).catch(() => {});
    },

    abrirHistoricoDaRota() {
      if (this.aba !== 'importacoes') {
        return;
      }
      const id = Number(this.$route.query.historico);
      if (!id) {
        return;
      }
      this.$refs.historico?.abrirPorId(id);
    },

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

    labelModulo(modulo) {
      return modulo === 'autenticacao' ? 'Autenticação' : modulo;
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
