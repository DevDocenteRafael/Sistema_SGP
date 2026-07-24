import { getPerfil, podeEditarDados, podeConsultarDados } from './auth';

const PRIORIZACOES = ['Baixa', 'Média', 'Alta', 'Resolvido'];
const STATUS_LISTA = ['CPED', 'DEP', 'DIREG', 'NC'];
const TIPOS = ['Ação Extensiva'];
const EIXOS = [
  'Gastronomia e Turismo',
  'Gestão e Negócios',
  'Gestão e Comércio',
  'Saúde e Segurança',
  'Segurança',
];

export default {
  name: 'AcoesExtensivas',

  data() {
    return {
      modo: 'lista',
      registros: [],
      filtros: {
        busca: '',
        priorizacao: '',
        eixo: '',
        status: '',
        tipo: '',
      },
      buscaTimeout: null,
      carregando: true,
      erro: '',
      mensagemSucesso: '',
      registroDetalhe: null,
      erroFormulario: '',
      salvando: false,
      editandoId: null,
      form: this.formVazio(),
      priorizacoes: PRIORIZACOES,
      statusLista: STATUS_LISTA,
      tipos: TIPOS,
      eixos: EIXOS,
    };
  },

  computed: {
    perfilUsuario() {
      return getPerfil();
    },

    acessoBloqueado() {
      return !this.podeConsultar;
    },

    podeEditar() {
      return podeEditarDados();
    },

    podeConsultar() {
      return podeConsultarDados();
    },

    temFiltro() {
      return Object.values(this.filtros).some(Boolean);
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
        priorizacao: '',
        atribuido: '',
        eixo: '',
        numero_processo_sei: '',
        tipo: 'Ação Extensiva',
        assunto: '',
        objetivo: '',
        status: '',
        ultima_atualizacao: '',
      };
    },

    normalizarRegistro(registro) {
      return {
        ...registro,
        priorizacao: registro.priorizacao || '',
        atribuido: registro.atribuido || '',
        eixo: registro.eixo || '',
        numero_processo_sei: registro.numero_processo_sei || '',
        tipo: registro.tipo || 'Ação Extensiva',
        assunto: registro.assunto || '',
        objetivo: registro.objetivo || '',
        status: registro.status || '',
        ultima_atualizacao: this.normalizarData(registro.ultima_atualizacao),
      };
    },

    normalizarData(valor) {
      if (!valor) {
        return '';
      }

      return String(valor).slice(0, 10);
    },

    aplicarFiltros() {
      clearTimeout(this.buscaTimeout);
      this.buscaTimeout = setTimeout(() => {
        this.carregarRegistros();
      }, 200);
    },

    async carregarRegistros() {
      this.carregando = true;
      this.erro = '';

      if (!this.podeConsultar) {
        this.registros = [];
        this.carregando = false;
        this.erro = 'Seu perfil não possui acesso para consultar ações extensivas.';
        return;
      }

      try {
        const params = {};

        Object.entries(this.filtros).forEach(([chave, valor]) => {
          if (valor) {
            params[chave] = valor;
          }
        });

        const { data } = await window.axios.get('/api/acoes-extensivas', { params });
        this.registros = Array.isArray(data.data)
          ? data.data.map((item) => this.normalizarRegistro(item))
          : [];

        if (data.meta) {
          if (Array.isArray(data.meta.priorizacoes) && data.meta.priorizacoes.length) {
            this.priorizacoes = data.meta.priorizacoes;
          }

          if (Array.isArray(data.meta.status) && data.meta.status.length) {
            this.statusLista = data.meta.status;
          }

          if (Array.isArray(data.meta.tipos) && data.meta.tipos.length) {
            this.tipos = data.meta.tipos;
          }

          if (Array.isArray(data.meta.eixos) && data.meta.eixos.length) {
            this.eixos = data.meta.eixos;
          }
        }
      } catch (error) {
        this.erro = this.extrairErro(error, 'Não foi possível carregar as ações extensivas.');
        this.registros = [];
      } finally {
        this.carregando = false;
      }
    },

    bloquearSemPermissao(mensagem = 'Seu perfil só permite consultar ações extensivas.') {
      this.erro = mensagem;
      this.mensagemSucesso = '';
      this.erroFormulario = '';
      this.registroDetalhe = null;
      this.modo = 'lista';
    },

    abrirNovo() {
      if (!this.podeEditar) {
        this.bloquearSemPermissao();
        return;
      }

      this.modo = 'novo';
      this.editandoId = null;
      this.form = this.formVazio();
      this.erroFormulario = '';
      this.mensagemSucesso = '';
      this.erro = '';
      this.fecharDetalhes();
    },

    abrirEdicao(registro) {
      if (!this.podeEditar) {
        this.bloquearSemPermissao();
        return;
      }

      this.modo = 'editar';
      this.editandoId = registro.id ?? null;
      this.form = {
        priorizacao: registro.priorizacao ?? '',
        atribuido: registro.atribuido ?? '',
        eixo: registro.eixo ?? '',
        numero_processo_sei: registro.numero_processo_sei ?? '',
        tipo: registro.tipo || 'Ação Extensiva',
        assunto: registro.assunto ?? '',
        objetivo: registro.objetivo ?? '',
        status: registro.status ?? '',
        ultima_atualizacao: this.normalizarData(registro.ultima_atualizacao),
      };
      this.erroFormulario = '';
      this.mensagemSucesso = '';
      this.erro = '';
      this.fecharDetalhes();
    },

    voltarLista() {
      this.modo = 'lista';
      this.editandoId = null;
      this.erroFormulario = '';
      this.form = this.formVazio();
      this.salvando = false;
    },

    validarFormulario() {
      if (!this.form.priorizacao) return 'A priorização é obrigatória.';
      if (!this.form.atribuido?.trim()) return 'Informe o responsável atribuído.';
      if (!this.form.eixo) return 'O eixo é obrigatório.';
      if (!this.form.numero_processo_sei?.trim()) return 'O número do processo SEI é obrigatório.';
      if (!this.form.tipo) return 'O tipo é obrigatório.';
      if (!this.form.assunto?.trim()) return 'O assunto é obrigatório.';
      if (!this.form.status) return 'O status é obrigatório.';
      return '';
    },

    async salvarRegistro() {
      if (!this.podeEditar) {
        this.erroFormulario = 'Seu perfil não tem permissão para alterar ações extensivas.';
        this.modo = 'lista';
        return;
      }

      const erroValidacao = this.validarFormulario();

      if (erroValidacao) {
        this.erroFormulario = erroValidacao;
        return;
      }

      this.salvando = true;
      this.erroFormulario = '';
      this.mensagemSucesso = '';
      this.erro = '';

      const payload = {
        priorizacao: this.form.priorizacao,
        atribuido: this.form.atribuido.trim(),
        eixo: this.form.eixo,
        numero_processo_sei: this.form.numero_processo_sei.trim(),
        tipo: this.form.tipo || 'Ação Extensiva',
        assunto: this.form.assunto.trim(),
        objetivo: this.form.objetivo?.trim() || null,
        status: this.form.status,
        ultima_atualizacao: this.form.ultima_atualizacao || null,
      };

      try {
        if (this.modo === 'editar' && this.editandoId) {
          const { data } = await window.axios.put(`/api/acoes-extensivas/${this.editandoId}`, payload);
          this.mensagemSucesso = data.message;
        } else {
          const { data } = await window.axios.post('/api/acoes-extensivas', payload);
          this.mensagemSucesso = data.message;
        }

        this.voltarLista();
        await this.carregarRegistros();
      } catch (error) {
        this.erroFormulario = this.extrairErro(error, 'Não foi possível salvar a ação extensiva.');
      } finally {
        this.salvando = false;
      }
    },

    async excluirRegistro(registro) {
      if (!this.podeEditar) {
        this.bloquearSemPermissao();
        return;
      }

      if (!registro) {
        return;
      }

      const confirmar = window.confirm(
        `Deseja excluir a ação do processo ${registro.numero_processo_sei || registro.id}?`
      );

      if (!confirmar) {
        return;
      }

      this.erro = '';
      this.mensagemSucesso = '';

      try {
        const { data } = await window.axios.delete(`/api/acoes-extensivas/${registro.id}`);
        this.mensagemSucesso = data.message;
        this.fecharDetalhes();
        await this.carregarRegistros();
      } catch (error) {
        this.erro = this.extrairErro(error, 'Não foi possível excluir a ação extensiva.');
      }
    },

    async abrirDetalhes(registro) {
      this.registroDetalhe = this.normalizarRegistro(registro);

      try {
        const { data } = await window.axios.get(`/api/acoes-extensivas/${registro.id}`);
        this.registroDetalhe = this.normalizarRegistro(data.acaoExtensiva ?? registro);
      } catch (error) {
        this.erro = this.extrairErro(error, 'Não foi possível carregar os detalhes.');
      }
    },

    fecharDetalhes() {
      this.registroDetalhe = null;
    },

    formatarData(data) {
      const normalizada = this.normalizarData(data);

      if (!normalizada) {
        return '—';
      }

      const [ano, mes, dia] = normalizada.split('-');

      if (!ano || !mes || !dia) {
        return data;
      }

      return `${dia}/${mes}/${ano}`;
    },

    badgePriorizacao(valor) {
      const mapa = {
        Baixa: 'badge-baixa',
        Média: 'badge-media',
        Alta: 'badge-alta',
        Resolvido: 'badge-resolvido',
      };

      return mapa[valor] || 'badge-media';
    },

    badgeStatus(valor) {
      const mapa = {
        CPED: 'badge-cped',
        DEP: 'badge-dep',
        DIREG: 'badge-direg',
        NC: 'badge-nc',
      };

      return mapa[valor] || 'badge-cped';
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
  },
};
