import { podeEditarDados } from './auth';

export default {
  name: 'Pca',
  data() {
    return {
      carregando: false,
      salvando: false,
      filtros: {
        busca: '',
        ano: '',
        status: '',
        tipo: '',
        unidade: '',
      },
      anosDisponiveis: ['2026', '2025', '2024', '2023'],
      registros: [],
      registroDetalhe: null,
      detalheAberto: false,
      mostrarModalNovo: false,
      modalModo: 'novo',
      editandoId: null,
      mensagemSucesso: '',
      mensagemErro: '',
      novoRegistroForm: this.formNovoVazio(),
    };
  },
  computed: {
    podeEditar() {
      return podeEditarDados();
    },
    temFiltro() {
      return Object.values(this.filtros).some((valor) => Boolean(valor));
    },
    totalRegistros() {
      return this.registros.length;
    },
    unidadesDisponiveis() {
      return Array.from(new Set(this.registros
        .map((registro) => registro.unidade)
        .filter((valor) => valor && valor.toString().trim() !== ''))).sort();
    },
    tiposDisponiveis() {
      return Array.from(new Set(this.registros
        .map((registro) => registro.tipo)
        .filter((valor) => valor && valor.toString().trim() !== ''))).sort();
    },
    registrosExibidos() {
      return this.registros.filter((registro) => {
        if (this.filtros.ano && String(registro.ano) !== String(this.filtros.ano)) {
          return false;
        }

        if (this.filtros.tipo && registro.tipo !== this.filtros.tipo) {
          return false;
        }

        if (this.filtros.unidade && registro.unidade !== this.filtros.unidade) {
          return false;
        }

        if (this.filtros.status && registro.status !== this.filtros.status) {
          return false;
        }

        if (this.filtros.busca) {
          const busca = this.filtros.busca.toLowerCase();
          const campos = [registro.curso, registro.unidade, registro.sei, registro.sig, registro.observacao];
          return campos.some((campo) => String(campo).toLowerCase().includes(busca));
        }

        return true;
      });
    },
  },
  mounted() {
    this.carregarRegistros();
  },
  methods: {
    formNovoVazio() {
      return {
        unidade: '',
        curso: '',
        tipo: '',
        periodo: '',
        sei: '',
        sig: '',
        status: '',
        responsavel: '',
        objetivo: '',
        data_inicio: '',
        data_fim: '',
        observacao: '',
        ano: new Date().getFullYear(),
      };
    },
    carregarRegistros() {
      this.carregando = true;
      this.mensagemErro = '';

      window.axios.get('/api/pcas')
        .then(({ data }) => {
          this.registros = Array.isArray(data.data) ? data.data : [];
        })
        .catch((error) => {
          this.mensagemErro = this.extrairErro(error, 'Não foi possível carregar os registros de PCA.');
          this.registros = [];
        })
        .finally(() => {
          this.carregando = false;
        });
    },
    abrirDetalhes(registro) {
      this.detalheAberto = true;
      this.registroDetalhe = registro;
    },
    fecharDetalhes() {
      this.detalheAberto = false;
      this.registroDetalhe = null;
    },
    abrirModalNovo() {
      if (!this.podeEditar) {
        this.mensagemErro = 'Seu perfil só permite consultar registros.';
        return;
      }

      this.editandoId = null;
      this.modalModo = 'novo';
      this.novoRegistroForm = this.formNovoVazio();
      this.mostrarModalNovo = true;
      this.mensagemErro = '';
      this.mensagemSucesso = '';
    },
    abrirEdicao(registro) {
      if (!this.podeEditar) {
        this.mensagemErro = 'Seu perfil só permite consultar registros.';
        return;
      }

      this.editandoId = registro.id;
      this.modalModo = 'editar';
      this.novoRegistroForm = {
        unidade: registro.unidade || '',
        curso: registro.curso || '',
        tipo: registro.tipo || '',
        periodo: registro.periodo || '',
        sei: registro.sei || '',
        sig: registro.sig || '',
        status: registro.status || '',
        responsavel: registro.responsavel || '',
        objetivo: registro.objetivo || '',
        data_inicio: registro.data_inicio || '',
        data_fim: registro.data_fim || '',
        observacao: registro.observacao || '',
        ano: registro.ano || new Date().getFullYear(),
      };
      this.mostrarModalNovo = true;
      this.mensagemErro = '';
      this.mensagemSucesso = '';
    },
    fecharModalNovo() {
      this.mostrarModalNovo = false;
      this.editandoId = null;
      this.modalModo = 'novo';
      this.novoRegistroForm = this.formNovoVazio();
    },
    aplicarFiltros() {
      // A filtragem já é feita automaticamente por registrosExibidos.
    },
    limparFiltros() {
      this.filtros = {
        busca: '',
        ano: '',
        status: '',
        tipo: '',
        unidade: '',
      };
    },
    validarFormulario() {
      if (!this.novoRegistroForm.ano) {
        return 'O ano é obrigatório.';
      }
      if (!this.novoRegistroForm.unidade.trim()) {
        return 'A unidade é obrigatória.';
      }
      if (!this.novoRegistroForm.curso.trim()) {
        return 'O curso é obrigatório.';
      }
      if (!this.novoRegistroForm.status.trim()) {
        return 'O status é obrigatório.';
      }
      return '';
    },
    salvarNovoRegistro() {
      if (!this.podeEditar) {
        this.mensagemErro = 'Seu perfil só permite consultar registros.';
        return;
      }

      const erroValidacao = this.validarFormulario();
      if (erroValidacao) {
        this.mensagemErro = erroValidacao;
        return;
      }

      this.salvando = true;
      this.mensagemErro = '';
      this.mensagemSucesso = '';

      const payload = { ...this.novoRegistroForm };
      if (payload.sei) payload.numero_sei = payload.sei;
      if (payload.sig) payload.codigo_sig = payload.sig;
      if (payload.responsavel) payload.responsavel = payload.responsavel;
      if (payload.objetivo) payload.objetivo = payload.objetivo;
      if (payload.data_inicio) payload.data_inicio = payload.data_inicio;
      if (payload.data_fim) payload.data_fim = payload.data_fim;

      const requisicao = this.editandoId
        ? window.axios.put(`/api/pcas/${this.editandoId}`, payload)
        : window.axios.post('/api/pcas', payload);

      requisicao
        .then(({ data }) => {
          this.mensagemSucesso = data.message || 'Registro salvo com sucesso.';
          this.fecharModalNovo();
          this.carregarRegistros();
        })
        .catch((error) => {
          this.mensagemErro = this.extrairErro(error, 'Não foi possível salvar o registro de PCA.');
        })
        .finally(() => {
          this.salvando = false;
        });
    },
    excluirRegistro(registro) {
      if (!this.podeEditar) {
        this.mensagemErro = 'Seu perfil só permite consultar registros.';
        return;
      }

      if (!window.confirm(`Excluir o registro do curso "${registro.curso}"?`)) {
        return;
      }

      window.axios.delete(`/api/pcas/${registro.id}`)
        .then(({ data }) => {
          this.mensagemSucesso = data.message || 'Registro excluído com sucesso.';
          this.carregarRegistros();
        })
        .catch((error) => {
          this.mensagemErro = this.extrairErro(error, 'Não foi possível excluir o registro.');
        });
    },
    extrairErro(error, fallback) {
      if (error.response?.data?.message) {
        return error.response.data.message;
      }

      const errors = error.response?.data?.errors;
      if (errors) {
        const primeiro = Object.values(errors)[0];
        return Array.isArray(primeiro) ? primeiro[0] : fallback;
      }

      return fallback;
    },
    badgeStatus(status) {
      return {
        'badge-ativo': status === 'Concluído',
        'badge-revisao': status === 'Em andamento',
        'badge-inativo': status === 'Planejado',
      }[status] || 'badge-inativo';
    },
  },
};
