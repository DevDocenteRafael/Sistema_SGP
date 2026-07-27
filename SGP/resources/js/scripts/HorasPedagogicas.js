import { getPerfil, podeEditarDados, podeConsultarDados } from './auth';

const SEGMENTOS = [
  'Gastronomia',
  'Ambiente e Saúde',
  'Gestão e Moda',
  'Tecnologia e Economia Criativa',
  'Beleza e Cuidado Pessoal',
  'Turismo e Hospitalidade',
  'Comunicação e Audiovisual',
  'Artes e Design',
];

const EIXOS = [...SEGMENTOS];

const STATUS_LISTA = ['Pendente', 'Em andamento', 'Concluída', 'Cancelada'];

const ANOS = ['2024', '2025', '2026', '2027'];

export default {
  name: 'HorasPedagogicas',

  data() {
    return {
      modo: 'lista',
      horas: [],
      filtros: {
        busca: '',
        ano: '',
        eixo: '',
        status: '',
        ativo: '',
      },
      buscaTimeout: null,
      carregando: true,
      erro: '',
      mensagemSucesso: '',
      horaDetalhe: null,
      erroFormulario: '',
      salvando: false,
      editandoId: null,
      form: this.formVazio(),
      segmentos: SEGMENTOS,
      eixos: EIXOS,
      statusLista: STATUS_LISTA,
      anos: ANOS,
    };
  },

  computed: {
    perfilUsuario() {
      return getPerfil();
    },

    acessoBloqueado() {
      return !this.podeConsultarHoras;
    },

    podeEditarHoras() {
      return podeEditarDados();
    },

    podeConsultarHoras() {
      return podeConsultarDados();
    },

    temFiltro() {
      return Object.values(this.filtros).some((valor) => valor !== '' && valor != null);
    },

    horasFiltradas() {
      return this.horas;
    },

    totalHoras() {
      return this.horasFiltradas.length;
    },

    totalAtivos() {
      return this.horasFiltradas.filter((hora) => hora.ativo === true).length;
    },
  },

  mounted() {
    this.carregarHoras();
  },

  methods: {
    formVazio() {
      return {
        matricula: '',
        pessoa: '',
        segmento: '',
        eixo: '',
        processo_sei: '',
        ano: '',
        motivo: '',
        status: '',
        ativo: 'true',
        observacao: '',
      };
    },

    normalizarRegistro(registro) {
      return {
        ...registro,
        processo_sei: registro.processo_sei || registro.processo_SEI || '',
        ano: registro.ano != null ? Number(registro.ano) : null,
        ativo: registro.ativo !== false,
        motivo: registro.motivo || '',
        observacao: registro.observacao || '',
      };
    },

    aplicarFiltros() {
      clearTimeout(this.buscaTimeout);
      this.buscaTimeout = setTimeout(() => {
        this.carregarHoras();
      }, 200);
    },

    async carregarHoras() {
      this.carregando = true;
      this.erro = '';

      if (!this.podeConsultarHoras) {
        this.horas = [];
        this.carregando = false;
        this.erro = 'Seu perfil não possui acesso para consultar horas pedagógicas.';
        return;
      }

      try {
        const params = {};

        ['busca', 'ano', 'eixo', 'status', 'ativo'].forEach((chave) => {
          if (this.filtros[chave] !== '' && this.filtros[chave] != null) {
            params[chave] = this.filtros[chave];
          }
        });

        const { data } = await window.axios.get('/api/horas-pedagogicas', { params });
        this.horas = Array.isArray(data.data)
          ? data.data.map((item) => this.normalizarRegistro(item))
          : [];

        if (data.meta) {
          if (Array.isArray(data.meta.anos) && data.meta.anos.length) {
            this.anos = data.meta.anos.map(String);
          }

          if (Array.isArray(data.meta.eixos) && data.meta.eixos.length) {
            this.eixos = data.meta.eixos;
          }

          if (Array.isArray(data.meta.segmentos) && data.meta.segmentos.length) {
            this.segmentos = data.meta.segmentos;
          }

          if (Array.isArray(data.meta.status) && data.meta.status.length) {
            this.statusLista = data.meta.status;
          }
        }
      } catch (error) {
        this.erro = this.extrairErro(error, 'Não foi possível carregar as horas pedagógicas.');
        this.horas = [];
      } finally {
        this.carregando = false;
      }
    },

    bloquearSemPermissao(mensagem = 'Seu perfil só permite consultar horas pedagógicas.') {
      this.erro = mensagem;
      this.mensagemSucesso = '';
      this.erroFormulario = '';
      this.horaDetalhe = null;
      this.modo = 'lista';
    },

    abrirNovo() {
      if (!this.podeEditarHoras) {
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

    abrirEdicao(hora) {
      if (!this.podeEditarHoras) {
        this.bloquearSemPermissao();
        return;
      }

      this.modo = 'editar';
      this.editandoId = hora.id ?? null;
      this.form = {
        matricula: hora.matricula ?? '',
        pessoa: hora.pessoa ?? '',
        segmento: hora.segmento ?? '',
        eixo: hora.eixo ?? '',
        processo_sei: hora.processo_sei ?? '',
        ano: hora.ano != null ? String(hora.ano) : '',
        motivo: hora.motivo ?? '',
        status: hora.status ?? '',
        ativo: hora.ativo !== false ? 'true' : 'false',
        observacao: hora.observacao ?? '',
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
      if (!this.form.matricula?.trim()) return 'A matrícula é obrigatória.';
      if (!this.form.pessoa?.trim()) return 'O nome da pessoa é obrigatório.';
      if (!this.form.segmento) return 'O segmento é obrigatório.';
      if (!this.form.eixo) return 'O eixo é obrigatório.';
      if (!this.form.processo_sei?.trim()) return 'O processo SEI é obrigatório.';
      if (!this.form.ano) return 'O ano é obrigatório.';
      if (!this.form.motivo?.trim()) return 'O motivo é obrigatório.';
      if (!this.form.status) return 'O status é obrigatório.';
      return '';
    },

    async salvarHora() {
      if (!this.podeEditarHoras) {
        this.erroFormulario = 'Seu perfil não tem permissão para alterar horas pedagógicas.';
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
        matricula: this.form.matricula.trim(),
        pessoa: this.form.pessoa.trim(),
        segmento: this.form.segmento,
        eixo: this.form.eixo,
        processo_sei: this.form.processo_sei.trim(),
        ano: Number(this.form.ano),
        motivo: this.form.motivo.trim(),
        status: this.form.status,
        ativo: this.form.ativo === true || this.form.ativo === 'true',
        observacao: this.form.observacao?.trim() || null,
      };

      try {
        if (this.modo === 'editar' && this.editandoId) {
          const { data } = await window.axios.put(`/api/horas-pedagogicas/${this.editandoId}`, payload);
          this.mensagemSucesso = data.message;
        } else {
          const { data } = await window.axios.post('/api/horas-pedagogicas', payload);
          this.mensagemSucesso = data.message;
        }

        this.voltarLista();
        await this.carregarHoras();
      } catch (error) {
        this.erroFormulario = this.extrairErro(error, 'Não foi possível salvar a hora pedagógica.');
      } finally {
        this.salvando = false;
      }
    },

    async excluirHora(hora) {
      if (!this.podeEditarHoras) {
        this.bloquearSemPermissao();
        return;
      }

      if (!hora) {
        return;
      }

      const confirmar = window.confirm(
        `Deseja excluir o registro de ${hora.pessoa || hora.matricula || hora.id}?`
      );

      if (!confirmar) {
        return;
      }

      this.erro = '';
      this.mensagemSucesso = '';

      try {
        const { data } = await window.axios.delete(`/api/horas-pedagogicas/${hora.id}`);
        this.mensagemSucesso = data.message;
        this.fecharDetalhes();
        await this.carregarHoras();
      } catch (error) {
        this.erro = this.extrairErro(error, 'Não foi possível excluir a hora pedagógica.');
      }
    },

    async abrirDetalhes(hora) {
      this.horaDetalhe = this.normalizarRegistro(hora);

      try {
        const { data } = await window.axios.get(`/api/horas-pedagogicas/${hora.id}`);
        this.horaDetalhe = this.normalizarRegistro(data.horaPedagogica ?? hora);
      } catch (error) {
        this.erro = this.extrairErro(error, 'Não foi possível carregar os detalhes.');
      }
    },

    fecharDetalhes() {
      this.horaDetalhe = null;
    },

    rotuloAtivo(ativo) {
      return ativo ? 'Sim' : 'Não';
    },

    statusClass(status) {
      const mapa = {
        Concluída: 'badge-concluida',
        'Em andamento': 'badge-andamento',
        Pendente: 'badge-pendente',
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
