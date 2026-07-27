import { UNIDADES } from './unidades';
import { getPerfil, podeEditarDados, podeConsultarDados } from './auth';

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

const STATUS_LISTA = ['Pendente', 'Em andamento', 'Realizada', 'Cancelada', 'Atrasada'];

const ANOS = ['2024', '2025', '2026', '2027'];

const PRAZO_LISTA = [
  { value: 'dentro', label: 'Dentro do prazo' },
  { value: 'fora', label: 'Fora do prazo' },
];

export default {
  name: 'VisitasTecnicas',

  data() {
    return {
      modo: 'lista',
      visitas: [],
      filtros: {
        busca: '',
        ano: '',
        unidade: '',
        eixo: '',
        status: '',
        prazo: '',
      },
      buscaTimeout: null,
      carregando: true,
      erro: '',
      mensagemSucesso: '',
      visitaDetalhe: null,
      erroFormulario: '',
      salvando: false,
      editandoId: null,
      form: this.formVazio(),
      unidades: UNIDADES,
      eixos: EIXOS,
      statusLista: STATUS_LISTA,
      anosDisponiveis: ANOS,
      prazoLista: PRAZO_LISTA,
    };
  },

  computed: {
    perfilUsuario() {
      return getPerfil();
    },

    acessoBloqueado() {
      return !this.podeConsultarVisita;
    },

    podeEditarVisita() {
      return podeEditarDados();
    },

    podeConsultarVisita() {
      return podeConsultarDados();
    },

    temFiltro() {
      return Object.values(this.filtros).some(Boolean);
    },

    visitasFiltradas() {
      return this.visitas;
    },

    totalVisitas() {
      return this.visitasFiltradas.length;
    },
  },

  mounted() {
    this.carregarVisitas();
  },

  methods: {
    formVazio() {
      return {
        unidade: '',
        eixo: '',
        processo_sei: '',
        data_solicitacao: '',
        data_visita_prevista: '',
        prazo_limite: '',
        status: '',
        responsavel: '',
        relatorio: '',
        observacao: '',
      };
    },

    normalizarRegistro(registro) {
      return {
        ...registro,
        processo_sei: registro.processo_sei || registro.processo_SEI || '',
        data_solicitacao: this.normalizarData(registro.data_solicitacao),
        data_visita_prevista: this.normalizarData(registro.data_visita_prevista),
        prazo_limite: this.normalizarData(registro.prazo_limite),
        relatorio: registro.relatorio || '',
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
        this.carregarVisitas();
      }, 200);
    },

    async carregarVisitas() {
      this.carregando = true;
      this.erro = '';

      if (!this.podeConsultarVisita) {
        this.visitas = [];
        this.carregando = false;
        this.erro = 'Seu perfil não possui acesso para consultar visitas técnicas.';
        return;
      }

      try {
        const params = {};

        ['busca', 'ano', 'unidade', 'eixo', 'status', 'prazo'].forEach((chave) => {
          if (this.filtros[chave]) {
            params[chave] = this.filtros[chave];
          }
        });

        const { data } = await window.axios.get('/api/visitas-tecnicas', { params });
        this.visitas = Array.isArray(data.data)
          ? data.data.map((item) => this.normalizarRegistro(item))
          : [];

        if (data.meta) {
          if (Array.isArray(data.meta.anos) && data.meta.anos.length) {
            this.anosDisponiveis = data.meta.anos;
          }

          if (Array.isArray(data.meta.eixos) && data.meta.eixos.length) {
            this.eixos = data.meta.eixos;
          }

          if (Array.isArray(data.meta.status) && data.meta.status.length) {
            this.statusLista = data.meta.status;
          }

          if (Array.isArray(data.meta.unidades) && data.meta.unidades.length) {
            this.unidades = data.meta.unidades;
          }

          if (Array.isArray(data.meta.prazos) && data.meta.prazos.length) {
            this.prazoLista = data.meta.prazos;
          }
        }
      } catch (error) {
        this.erro = this.extrairErro(error, 'Não foi possível carregar as visitas técnicas.');
        this.visitas = [];
      } finally {
        this.carregando = false;
      }
    },

    bloquearSemPermissao(mensagem = 'Seu perfil só permite consultar visitas técnicas.') {
      this.erro = mensagem;
      this.mensagemSucesso = '';
      this.erroFormulario = '';
      this.visitaDetalhe = null;
      this.modo = 'lista';
    },

    abrirNovo() {
      if (!this.podeEditarVisita) {
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

    abrirEdicao(visita) {
      if (!this.podeEditarVisita) {
        this.bloquearSemPermissao();
        return;
      }

      this.modo = 'editar';
      this.editandoId = visita.id ?? null;
      this.form = {
        unidade: visita.unidade ?? '',
        eixo: visita.eixo ?? '',
        processo_sei: visita.processo_sei ?? '',
        data_solicitacao: this.normalizarData(visita.data_solicitacao),
        data_visita_prevista: this.normalizarData(visita.data_visita_prevista),
        prazo_limite: this.normalizarData(visita.prazo_limite),
        status: visita.status ?? '',
        responsavel: visita.responsavel ?? '',
        relatorio: visita.relatorio ?? '',
        observacao: visita.observacao ?? '',
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
      if (!this.form.unidade) return 'A unidade é obrigatória.';
      if (!this.form.eixo) return 'O eixo é obrigatório.';
      if (!this.form.processo_sei?.trim()) return 'O processo SEI é obrigatório.';
      if (!this.form.data_solicitacao) return 'A data de solicitação é obrigatória.';
      if (!this.form.data_visita_prevista) return 'A data prevista da visita é obrigatória.';
      if (!this.form.prazo_limite) return 'O prazo limite é obrigatório.';
      if (!this.form.status) return 'O status é obrigatório.';
      if (!this.form.responsavel?.trim()) return 'O responsável é obrigatório.';
      return '';
    },

    async salvarVisita() {
      if (!this.podeEditarVisita) {
        this.erroFormulario = 'Seu perfil não tem permissão para alterar visitas técnicas.';
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
        unidade: this.form.unidade,
        eixo: this.form.eixo,
        processo_sei: this.form.processo_sei.trim(),
        data_solicitacao: this.form.data_solicitacao,
        data_visita_prevista: this.form.data_visita_prevista,
        prazo_limite: this.form.prazo_limite,
        status: this.form.status,
        responsavel: this.form.responsavel.trim(),
        relatorio: this.form.relatorio?.trim() || null,
        observacao: this.form.observacao?.trim() || null,
      };

      try {
        if (this.modo === 'editar' && this.editandoId) {
          const { data } = await window.axios.put(`/api/visitas-tecnicas/${this.editandoId}`, payload);
          this.mensagemSucesso = data.message;
        } else {
          const { data } = await window.axios.post('/api/visitas-tecnicas', payload);
          this.mensagemSucesso = data.message;
        }

        this.voltarLista();
        await this.carregarVisitas();
      } catch (error) {
        this.erroFormulario = this.extrairErro(error, 'Não foi possível salvar a visita técnica.');
      } finally {
        this.salvando = false;
      }
    },

    async excluirVisita(visita) {
      if (!this.podeEditarVisita) {
        this.bloquearSemPermissao();
        return;
      }

      if (!visita) {
        return;
      }

      const confirmar = window.confirm(
        `Deseja excluir a visita do processo ${visita.processo_sei || visita.id}?`
      );

      if (!confirmar) {
        return;
      }

      this.erro = '';
      this.mensagemSucesso = '';

      try {
        const { data } = await window.axios.delete(`/api/visitas-tecnicas/${visita.id}`);
        this.mensagemSucesso = data.message;
        this.fecharDetalhes();
        await this.carregarVisitas();
      } catch (error) {
        this.erro = this.extrairErro(error, 'Não foi possível excluir a visita técnica.');
      }
    },

    async abrirDetalhes(visita) {
      this.visitaDetalhe = this.normalizarRegistro(visita);

      try {
        const { data } = await window.axios.get(`/api/visitas-tecnicas/${visita.id}`);
        this.visitaDetalhe = this.normalizarRegistro(data.visitaTecnica ?? visita);
      } catch (error) {
        this.erro = this.extrairErro(error, 'Não foi possível carregar os detalhes da visita.');
      }
    },

    fecharDetalhes() {
      this.visitaDetalhe = null;
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

    statusClass(status) {
      const mapa = {
        Realizada: 'badge-realizada',
        'Em andamento': 'badge-andamento',
        Pendente: 'badge-pendente',
        Atrasada: 'badge-atrasada',
        Cancelada: 'badge-cancelada',
      };

      return mapa[status] || 'badge-pendente';
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
