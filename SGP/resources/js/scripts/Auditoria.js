const ACAO_LABEL = {
  criar: 'Criou',
  editar: 'Editou',
  excluir: 'Excluiu',
  importar: 'Importou',
};

export default {
  name: 'Auditoria',
  data() {
    return {
      registros: [],
      meta: {
        total: 0,
        per_page: 50,
        current_page: 1,
        last_page: 1,
        acoes: ['criar', 'editar', 'excluir', 'importar'],
        modulos: [],
      },
      carregando: false,
      mensagemErro: '',
      detalhe: null,
      filtros: {
        busca: '',
        modulo: '',
        acao: '',
        data_inicio: '',
        data_fim: '',
      },
      buscaTimeout: null,
    };
  },
  mounted() {
    this.carregar();
  },
  methods: {
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
            if (valor !== '' && valor !== null) {
              params[chave] = valor;
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
  },
};
