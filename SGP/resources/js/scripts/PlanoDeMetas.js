import { podeEditarDados } from './auth';

export default {
  name: 'PlanoDeMetas',
  data() {
    return {
      carregando: false,
      filtros: {
        busca: '',
        ano: '',
        segmento: '',
        tipo: '',
        mes: '',
        status: '',
        situacao: '',
      },
      anosDisponiveis: ['2024', '2025', '2026', '2027'],
      registros: [],
      mensagemSucesso: '',
      mensagemErro: '',
      salvando: false,
      mostrarModalNovo: false,
      detalheAberto: false,
      registroDetalhe: null,
      editandoId: null,
      modalModo: 'novo',
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
  },
  mounted() {
    this.carregarRegistros();
  },
  methods: {
    formNovoVazio() {
      return {
        segmento: '',
        tipo: '',
        mes_entrega: '',
        curso: '',
        numero_sei: '',
        codigo_sig: '',
        status: '',
        origem: 'Plano de Metas',
        status_final: '',
        observacao: '',
      };
    },
    normalizarRegistro(registro) {
      return {
        id: registro.id,
        segmento: registro.segmento || '—',
        curso: registro.curso || '—',
        tipo: registro.tipo || '—',
        sei: registro.numero_sei || registro.sei || '—',
        sig: registro.codigo_sig || registro.sig || '—',
        mesEntrega: registro.mes_entrega || registro.mesEntrega || '—',
        status: registro.status || '—',
        origem: registro.origem || 'Plano de Metas',
        observacao: registro.observacao || '—',
        statusFinal: registro.status_final || registro.statusFinal || '—',
        numero_sei: registro.numero_sei || registro.sei || '',
        codigo_sig: registro.codigo_sig || registro.sig || '',
        mes_entrega: registro.mes_entrega || registro.mesEntrega || '',
        status_final: registro.status_final || registro.statusFinal || '',
      };
    },
    async carregarRegistros() {
      this.carregando = true;
      this.mensagemErro = '';

      try {
        const params = {};

        Object.entries(this.filtros).forEach(([chave, valor]) => {
          if (valor) {
            params[chave] = valor;
          }
        });

        const { data } = await window.axios.get('/api/plano-de-metas', { params });
        this.registros = Array.isArray(data.data) ? data.data.map((registro) => this.normalizarRegistro(registro)) : [];
      } catch (error) {
        this.mensagemErro = this.extrairErro(error, 'Não foi possível carregar os registros de Plano de Metas.');
        this.registros = [];
      } finally {
        this.carregando = false;
      }
    },
    abrirModalNovo() {
      if (!this.podeEditar) {
        this.mensagemErro = 'Seu perfil só permite consultar registros de Plano de Metas.';
        return;
      }

      this.modalModo = 'novo';
      this.editandoId = null;
      this.novoRegistroForm = this.formNovoVazio();
      this.mostrarModalNovo = true;
      this.mensagemSucesso = '';
      this.mensagemErro = '';
    },
    async abrirDetalhes(registro) {
      this.detalheAberto = true;
      this.registroDetalhe = this.normalizarRegistro(registro);

      try {
        const { data } = await window.axios.get(`/api/plano-de-metas/${registro.id}`);
        this.registroDetalhe = this.normalizarRegistro(data.planoDeMeta ?? data);
      } catch (error) {
        this.mensagemErro = this.extrairErro(error, 'Não foi possível carregar os detalhes do registro.');
      }
    },
    fecharDetalhes() {
      this.detalheAberto = false;
      this.registroDetalhe = null;
    },
    abrirEdicao(registro) {
      if (!this.podeEditar) {
        this.mensagemErro = 'Seu perfil só permite consultar registros de Plano de Metas.';
        return;
      }

      this.modalModo = 'editar';
      this.editandoId = registro.id;
      this.novoRegistroForm = {
        segmento: registro.segmento || '',
        tipo: registro.tipo || 'QUALIFICAÇÃO',
        mes_entrega: registro.mesEntrega || '',
        curso: registro.curso || '',
        numero_sei: registro.sei || '',
        codigo_sig: registro.sig || '',
        status: registro.status || 'EM ANÁLISE',
        origem: registro.origem || 'Plano de Metas',
        status_final: registro.statusFinal || 'PENDENTE',
        observacao: registro.observacao || '',
      };
      this.mostrarModalNovo = true;
    },
    fecharModalNovo() {
      this.mostrarModalNovo = false;
      this.editandoId = null;
      this.modalModo = 'novo';
      this.novoRegistroForm = this.formNovoVazio();
    },
    limparFiltros() {
      this.filtros = {
        busca: '',
        ano: '',
        segmento: '',
        tipo: '',
        mes: '',
        status: '',
        situacao: '',
      };
      this.carregarRegistros();
    },
    aplicarFiltros() {
      this.mensagemSucesso = '';
      this.mensagemErro = '';
      this.carregarRegistros();
    },
    validarFormularioPlanoDeMeta() {
      if (!this.novoRegistroForm.segmento?.trim()) {
        return 'O segmento é obrigatório.';
      }

      if (!this.novoRegistroForm.curso?.trim()) {
        return 'O curso é obrigatório.';
      }

      if (!this.novoRegistroForm.tipo?.trim()) {
        return 'O tipo é obrigatório.';
      }

      if (!this.novoRegistroForm.numero_sei?.trim()) {
        return 'Informe o número SEI.';
      }

      if (!this.novoRegistroForm.codigo_sig?.trim()) {
        return 'Informe o código SIG.';
      }

      if (!this.novoRegistroForm.mes_entrega?.trim()) {
        return 'Informe o mês de entrega.';
      }

      if (!this.novoRegistroForm.status?.trim()) {
        return 'Informe o status do registro.';
      }

      if (!this.novoRegistroForm.status_final?.trim()) {
        return 'Informe o status final.';
      }

      return '';
    },
    async salvarNovoRegistro() {
      if (!this.podeEditar) {
        this.mensagemErro = 'Seu perfil só permite consultar registros de Plano de Metas.';
        return;
      }

      const erroValidacao = this.validarFormularioPlanoDeMeta();

      if (erroValidacao) {
        this.mensagemErro = erroValidacao;
        return;
      }

      this.salvando = true;
      this.mensagemSucesso = '';
      this.mensagemErro = '';

      const payload = {
        segmento: this.novoRegistroForm.segmento,
        curso: this.novoRegistroForm.curso,
        tipo: this.novoRegistroForm.tipo,
        numero_sei: this.novoRegistroForm.numero_sei,
        codigo_sig: this.novoRegistroForm.codigo_sig,
        mes_entrega: this.novoRegistroForm.mes_entrega,
        status: this.novoRegistroForm.status,
        origem: this.novoRegistroForm.origem,
        observacao: this.novoRegistroForm.observacao,
        status_final: this.novoRegistroForm.status_final,
        ano: Number(this.filtros.ano || new Date().getFullYear()),
      };

      try {
        if (this.editandoId) {
          const { data } = await window.axios.put(`/api/plano-de-metas/${this.editandoId}`, payload);
          this.mensagemSucesso = data.message;
        } else {
          const { data } = await window.axios.post('/api/plano-de-metas', payload);
          this.mensagemSucesso = data.message;
        }

        this.fecharModalNovo();
        await this.carregarRegistros();
      } catch (error) {
        this.mensagemErro = this.extrairErro(error, 'Não foi possível salvar o registro de Plano de Metas.');
      } finally {
        this.salvando = false;
      }
    },
    async excluirRegistro(registro) {
      if (!this.podeEditar) {
        this.mensagemErro = 'Seu perfil só permite consultar registros de Plano de Metas.';
        return;
      }

      const confirmar = window.confirm(`Excluir o registro "${registro.curso}"?`);

      if (!confirmar) {
        return;
      }

      try {
        const { data } = await window.axios.delete(`/api/plano-de-metas/${registro.id}`);
        this.mensagemSucesso = data.message;
        await this.carregarRegistros();
      } catch (error) {
        this.mensagemErro = this.extrairErro(error, 'Não foi possível excluir o registro.');
      }
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
    statusClass(status) {
      const mapa = {
        PUBLICADO: 'badge-ativo',
        ENTREGUE: 'badge-revisao',
        'EM ANALISE': 'badge-suspenso',
        PENDENTE: 'badge-inativo',
      };

      return mapa[status] || 'badge-inativo';
    },
  },
};
