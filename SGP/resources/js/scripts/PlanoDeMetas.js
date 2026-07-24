import { podeEditarDados } from './auth';

const FILTROS_VAZIOS = () => ({
  busca: '',
  ano: '',
  segmento: '',
  tipo: '',
  mes: '',
  status: '',
  situacao: '',
});

export default {
  name: 'PlanoDeMetas',
  data() {
    return {
      modo: 'lista',
      carregando: false,
      filtros: FILTROS_VAZIOS(),
      buscaTimeout: null,
      anosDisponiveis: ['2024', '2025', '2026', '2027'],
      segmentosDisponiveis: ['Infraestrutura', 'Educação'],
      tiposDisponiveis: ['QUALIFICAÇÃO', 'PRESENCIAL', 'HÍBRIDO'],
      mesesDisponiveis: [
        'Janeiro',
        'Fevereiro',
        'Março',
        'Abril',
        'Maio',
        'Junho',
        'Julho',
        'Agosto',
        'Setembro',
        'Outubro',
        'Novembro',
        'Dezembro',
      ],
      statusDisponiveis: ['PLANEJADO', 'EM ANÁLISE', 'EM ANDAMENTO', 'CONCLUÍDO'],
      situacoesDisponiveis: ['PENDENTE', 'EM ANALISE', 'ENTREGUE', 'PUBLICADO'],
      registros: [],
      mensagemSucesso: '',
      mensagemErro: '',
      salvando: false,
      detalheAberto: false,
      registroDetalhe: null,
      editandoId: null,
      form: this.formVazio(),
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
    formVazio() {
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
      clearTimeout(this.buscaTimeout);

      this.buscaTimeout = setTimeout(async () => {
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
          this.registros = Array.isArray(data.data)
            ? data.data.map((registro) => this.normalizarRegistro(registro))
            : [];

          if (data.meta) {
            if (Array.isArray(data.meta.anos) && data.meta.anos.length) {
              this.anosDisponiveis = data.meta.anos;
            }

            if (Array.isArray(data.meta.segmentos) && data.meta.segmentos.length) {
              this.segmentosDisponiveis = data.meta.segmentos;
            }

            if (Array.isArray(data.meta.tipos) && data.meta.tipos.length) {
              this.tiposDisponiveis = data.meta.tipos;
            }

            if (Array.isArray(data.meta.meses) && data.meta.meses.length) {
              this.mesesDisponiveis = data.meta.meses;
            }

            if (Array.isArray(data.meta.status) && data.meta.status.length) {
              this.statusDisponiveis = data.meta.status;
            }

            if (Array.isArray(data.meta.situacoes) && data.meta.situacoes.length) {
              this.situacoesDisponiveis = data.meta.situacoes;
            }
          }
        } catch (error) {
          this.mensagemErro = this.extrairErro(
            error,
            'Não foi possível carregar os registros de Plano de Metas.'
          );
          this.registros = [];
        } finally {
          this.carregando = false;
        }
      }, 200);
    },
    abrirNovo() {
      if (!this.podeEditar) {
        this.mensagemErro = 'Seu perfil só permite consultar registros de Plano de Metas.';
        return;
      }

      this.modo = 'novo';
      this.editandoId = null;
      this.form = this.formVazio();
      this.mensagemSucesso = '';
      this.mensagemErro = '';
      this.fecharDetalhes();
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

      this.modo = 'editar';
      this.editandoId = registro.id;
      this.form = {
        segmento: registro.segmento === '—' ? '' : registro.segmento || '',
        tipo: registro.tipo === '—' ? '' : registro.tipo || '',
        mes_entrega: registro.mesEntrega === '—' ? (registro.mes_entrega || '') : registro.mesEntrega || '',
        curso: registro.curso === '—' ? '' : registro.curso || '',
        numero_sei: registro.sei === '—' ? (registro.numero_sei || '') : registro.sei || '',
        codigo_sig: registro.sig === '—' ? (registro.codigo_sig || '') : registro.sig || '',
        status: registro.status === '—' ? '' : registro.status || '',
        origem: registro.origem || 'Plano de Metas',
        status_final: registro.statusFinal === '—'
          ? (registro.status_final || '')
          : registro.statusFinal || '',
        observacao: registro.observacao === '—' ? '' : registro.observacao || '',
      };
      this.mensagemSucesso = '';
      this.mensagemErro = '';
      this.fecharDetalhes();
    },
    voltarLista() {
      this.modo = 'lista';
      this.editandoId = null;
      this.form = this.formVazio();
      this.salvando = false;
    },
    validarFormulario() {
      if (!this.form.segmento?.trim()) {
        return 'O segmento é obrigatório.';
      }

      if (!this.form.curso?.trim()) {
        return 'O curso é obrigatório.';
      }

      if (!this.form.tipo?.trim()) {
        return 'O tipo é obrigatório.';
      }

      if (!this.form.numero_sei?.trim()) {
        return 'Informe o número SEI.';
      }

      if (!this.form.codigo_sig?.trim()) {
        return 'Informe o código SIG.';
      }

      if (!this.form.mes_entrega?.trim()) {
        return 'Informe o mês de entrega.';
      }

      if (!this.form.status?.trim()) {
        return 'Informe o status do registro.';
      }

      if (!this.form.status_final?.trim()) {
        return 'Informe o status final.';
      }

      return '';
    },
    async salvarRegistro() {
      if (!this.podeEditar) {
        this.mensagemErro = 'Seu perfil só permite consultar registros de Plano de Metas.';
        return;
      }

      const erroValidacao = this.validarFormulario();

      if (erroValidacao) {
        this.mensagemErro = erroValidacao;
        return;
      }

      this.salvando = true;
      this.mensagemSucesso = '';
      this.mensagemErro = '';

      const payload = {
        segmento: this.form.segmento,
        curso: this.form.curso,
        tipo: this.form.tipo,
        numero_sei: this.form.numero_sei,
        codigo_sig: this.form.codigo_sig,
        mes_entrega: this.form.mes_entrega,
        status: this.form.status,
        origem: this.form.origem,
        observacao: this.form.observacao,
        status_final: this.form.status_final,
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

        this.voltarLista();
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
