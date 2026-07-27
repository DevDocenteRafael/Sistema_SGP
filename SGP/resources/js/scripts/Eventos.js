import { getPerfil, podeEditarDados, podeConsultarDados } from './auth';

const STATUS_LISTA = ['Planejado', 'Realizado', 'Cancelado'];
const ANOS = ['2024', '2025', '2026', '2027'];
const EIXOS = [
  'Gastronomia',
  'Ambiente e Saúde',
  'Gestão e Moda',
  'Tecnologia e Economia Criativa',
  'Beleza e Cuidado Pessoal',
  'Turismo e Hospitalidade',
  'Comunicação e Audiovisual',
  'Artes e Design',
];
const UNIDADES = [
  'Asa Norte',
  'Asa Sul',
  'Taguatinga',
  'Gama',
  'Ceilândia',
  'Sobradinho',
  'Jessé Freire',
  'Santa Maria',
  'São Sebastião',
  'Brazlândia',
];
const OPCOES_ACAO = ['Sim', 'Não'];

export default {
  name: 'Eventos',

  data() {
    return {
      modo: 'lista',
      registros: [],
      filtros: {
        busca: '',
        ano: '',
        eixo: '',
        unidade: '',
        status: '',
        possui_acao_extensiva: '',
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
      statusLista: STATUS_LISTA,
      anos: ANOS,
      eixos: EIXOS,
      unidades: UNIDADES,
      opcoesAcao: OPCOES_ACAO,
      acoesVinculaveis: [],
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
        nome: '',
        ano: '2025',
        data: '',
        unidade: '',
        eixo: '',
        quantidade_pessoas: null,
        equipe: '',
        possui_acao_extensiva: 'Não',
        acao_vinculada: '',
        status: 'Planejado',
        observacao: '',
      };
    },

    normalizarRegistro(registro) {
      return {
        ...registro,
        nome: registro.nome || '',
        ano: registro.ano ? String(registro.ano) : '',
        data: this.normalizarData(registro.data),
        unidade: registro.unidade || '',
        eixo: registro.eixo || '',
        quantidade_pessoas: registro.quantidade_pessoas ?? null,
        equipe: registro.equipe || '',
        possui_acao_extensiva: registro.possui_acao_extensiva || 'Não',
        acao_vinculada: registro.acao_vinculada || '',
        status: registro.status || '',
        observacao: registro.observacao || '',
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
        this.erro = 'Seu perfil não possui acesso para consultar eventos.';
        return;
      }

      try {
        const params = {};

        Object.entries(this.filtros).forEach(([chave, valor]) => {
          if (valor) {
            params[chave] = valor;
          }
        });

        const { data } = await window.axios.get('/api/eventos', { params });
        this.registros = Array.isArray(data.data)
          ? data.data.map((item) => this.normalizarRegistro(item))
          : [];

        if (data.meta) {
          if (Array.isArray(data.meta.status) && data.meta.status.length) {
            this.statusLista = data.meta.status;
          }

          if (Array.isArray(data.meta.anos) && data.meta.anos.length) {
            this.anos = data.meta.anos.map(String);
          }

          if (Array.isArray(data.meta.eixos) && data.meta.eixos.length) {
            this.eixos = data.meta.eixos;
          }

          if (Array.isArray(data.meta.unidades) && data.meta.unidades.length) {
            this.unidades = data.meta.unidades;
          }

          if (Array.isArray(data.meta.possui_acao_extensiva) && data.meta.possui_acao_extensiva.length) {
            this.opcoesAcao = data.meta.possui_acao_extensiva;
          }

          if (Array.isArray(data.meta.acoes_vinculaveis)) {
            this.acoesVinculaveis = data.meta.acoes_vinculaveis;
          }
        }
      } catch (error) {
        this.erro = this.extrairErro(error, 'Não foi possível carregar os eventos.');
        this.registros = [];
      } finally {
        this.carregando = false;
      }
    },

    bloquearSemPermissao(mensagem = 'Seu perfil só permite consultar eventos.') {
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
        nome: registro.nome ?? '',
        ano: registro.ano ? String(registro.ano) : '',
        data: this.normalizarData(registro.data),
        unidade: registro.unidade ?? '',
        eixo: registro.eixo ?? '',
        quantidade_pessoas: registro.quantidade_pessoas ?? null,
        equipe: registro.equipe ?? '',
        possui_acao_extensiva: registro.possui_acao_extensiva || 'Não',
        acao_vinculada: registro.acao_vinculada ?? '',
        status: registro.status || 'Planejado',
        observacao: registro.observacao ?? '',
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

    preencherAnoDaData() {
      if (this.form.data && !this.form.ano) {
        this.form.ano = this.form.data.slice(0, 4);
      }
    },

    onMudarAcao() {
      if (this.form.possui_acao_extensiva !== 'Sim') {
        this.form.acao_vinculada = '';
      }
    },

    validarFormulario() {
      if (!this.form.nome?.trim() || !this.form.data) {
        return 'Preencha o nome e a data do evento.';
      }

      if (!this.form.unidade) return 'A unidade é obrigatória.';
      if (!this.form.eixo) return 'O eixo é obrigatório.';
      if (!this.form.status) return 'O status é obrigatório.';
      if (!this.form.possui_acao_extensiva) return 'Informe se possui ação extensiva.';

      return '';
    },

    async salvarRegistro() {
      if (!this.podeEditar) {
        this.erroFormulario = 'Seu perfil não tem permissão para alterar eventos.';
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
        nome: this.form.nome.trim(),
        ano: this.form.ano || this.form.data.slice(0, 4),
        data: this.form.data,
        unidade: this.form.unidade,
        eixo: this.form.eixo,
        quantidade_pessoas: this.form.quantidade_pessoas === '' || this.form.quantidade_pessoas === null
          ? null
          : Number(this.form.quantidade_pessoas),
        equipe: this.form.equipe?.trim() || null,
        possui_acao_extensiva: this.form.possui_acao_extensiva,
        acao_vinculada: this.form.possui_acao_extensiva === 'Sim'
          ? (this.form.acao_vinculada?.trim() || null)
          : null,
        status: this.form.status,
        observacao: this.form.observacao?.trim() || null,
      };

      try {
        if (this.modo === 'editar' && this.editandoId) {
          const { data } = await window.axios.put(`/api/eventos/${this.editandoId}`, payload);
          this.mensagemSucesso = data.message;
        } else {
          const { data } = await window.axios.post('/api/eventos', payload);
          this.mensagemSucesso = data.message;
        }

        this.voltarLista();
        await this.carregarRegistros();
      } catch (error) {
        this.erroFormulario = this.extrairErro(error, 'Não foi possível salvar o evento.');
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

      const confirmar = window.confirm(`Deseja excluir o evento "${registro.nome || registro.id}"?`);

      if (!confirmar) {
        return;
      }

      this.erro = '';
      this.mensagemSucesso = '';

      try {
        const { data } = await window.axios.delete(`/api/eventos/${registro.id}`);
        this.mensagemSucesso = data.message;
        this.fecharDetalhes();
        await this.carregarRegistros();
      } catch (error) {
        this.erro = this.extrairErro(error, 'Não foi possível excluir o evento.');
      }
    },

    async abrirDetalhes(registro) {
      this.registroDetalhe = this.normalizarRegistro(registro);

      try {
        const { data } = await window.axios.get(`/api/eventos/${registro.id}`);
        this.registroDetalhe = this.normalizarRegistro(data.evento ?? registro);
      } catch (error) {
        this.erro = this.extrairErro(error, 'Não foi possível carregar os detalhes.');
      }
    },

    fecharDetalhes() {
      this.registroDetalhe = null;
    },

    textoAcaoExtensiva(item) {
      if (item.possui_acao_extensiva === 'Sim') {
        return item.acao_vinculada
          ? `Sim - ${item.acao_vinculada}`
          : 'Sim';
      }

      return item.possui_acao_extensiva || 'Não';
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

    badgeStatus(valor) {
      const mapa = {
        Planejado: 'badge-planejado',
        Realizado: 'badge-realizado',
        Cancelado: 'badge-cancelado',
      };

      return mapa[valor] || 'badge-planejado';
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
