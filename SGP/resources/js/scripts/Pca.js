import { podeEditarDados, podeConsultarDados } from './auth';

const STATUS_LISTA = ['Vigente', 'Em análise', 'Suspenso', 'Previsto', 'Publicado', 'Ativo', 'Aprovado'];
const ANOS = ['2025', '2026'];
const SEMESTRES = ['2025/1', '2025/2', '2026/1', '2026/2'];
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

export default {
  name: 'Pca',

  data() {
    return {
      modo: 'lista',
      carregando: false,
      salvando: false,
      buscaTimeout: null,
      filtros: {
        busca: '',
        ano: '',
        semestre: '',
        unidade: '',
        eixo: '',
        status: '',
      },
      anos: ANOS,
      semestres: SEMESTRES,
      unidades: UNIDADES,
      eixos: EIXOS,
      statusLista: STATUS_LISTA,
      registros: [],
      registroDetalhe: null,
      editandoId: null,
      form: this.formVazio(),
      mensagemSucesso: '',
      erro: '',
      erroFormulario: '',
    };
  },

  computed: {
    podeEditar() {
      return podeEditarDados();
    },
    podeConsultar() {
      return podeConsultarDados();
    },
    acessoBloqueado() {
      return !this.podeConsultar;
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
        ano: '2025',
        semestre: '',
        numero_sei: '',
        codigo_sig: '',
        titulo: '',
        eixo: '',
        unidade: '',
        carga_horaria: '',
        precificacao: '',
        valor_primeiro_modulo: '',
        valor: '',
        parcelas_boleto: '',
        valor_parcela_boleto: '',
        parcelas_cartao: '',
        valor_cartao: '',
        parcela_desc_20: '',
        parcela_desc_15: '',
        status: 'Vigente',
        observacao: '',
      };
    },

    normalizarRegistro(registro) {
      return {
        id: registro.id,
        ano: registro.ano ? String(registro.ano) : '',
        semestre: registro.semestre || '',
        numero_sei: registro.numero_sei || registro.sei || '',
        codigo_sig: registro.codigo_sig || registro.sig || '',
        sei: registro.numero_sei || registro.sei || '',
        sig: registro.codigo_sig || registro.sig || '',
        titulo: registro.titulo || registro.curso || '',
        eixo: registro.eixo || '',
        unidade: registro.unidade || '',
        carga_horaria: registro.carga_horaria || registro.ch || '',
        ch: registro.carga_horaria || registro.ch || '',
        precificacao: registro.precificacao || '',
        valor_primeiro_modulo: registro.valor_primeiro_modulo || '',
        valor: registro.valor || '',
        parcelas_boleto: registro.parcelas_boleto || '',
        valor_parcela_boleto: registro.valor_parcela_boleto || '',
        parcelas_cartao: registro.parcelas_cartao || '',
        valor_cartao: registro.valor_cartao || '',
        parcela_desc_20: registro.parcela_desc_20 || '',
        parcela_desc_15: registro.parcela_desc_15 || '',
        status: registro.status || '',
        observacao: registro.observacao || '',
      };
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
        this.erro = 'Seu perfil não possui acesso para consultar PCA.';
        return;
      }

      try {
        const params = {};

        Object.entries(this.filtros).forEach(([chave, valor]) => {
          if (valor) {
            params[chave] = valor;
          }
        });

        const { data } = await window.axios.get('/api/pcas', { params });
        this.registros = Array.isArray(data.data)
          ? data.data.map((item) => this.normalizarRegistro(item))
          : [];

        if (data.meta) {
          if (Array.isArray(data.meta.anos) && data.meta.anos.length) {
            this.anos = data.meta.anos.map(String);
          }
          if (Array.isArray(data.meta.semestres) && data.meta.semestres.length) {
            this.semestres = data.meta.semestres;
          }
          if (Array.isArray(data.meta.unidades) && data.meta.unidades.length) {
            this.unidades = data.meta.unidades;
          }
          if (Array.isArray(data.meta.eixos) && data.meta.eixos.length) {
            this.eixos = data.meta.eixos;
          }
          if (Array.isArray(data.meta.status) && data.meta.status.length) {
            this.statusLista = data.meta.status;
          }
        }
      } catch (error) {
        this.erro = this.extrairErro(error, 'Não foi possível carregar os registros de PCA.');
        this.registros = [];
      } finally {
        this.carregando = false;
      }
    },

    bloquearSemPermissao(mensagem = 'Seu perfil só permite consultar registros.') {
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

      const item = this.normalizarRegistro(registro);

      this.modo = 'editar';
      this.editandoId = item.id ?? null;
      this.form = {
        ano: item.ano || '2025',
        semestre: item.semestre || '',
        numero_sei: item.numero_sei || '',
        codigo_sig: item.codigo_sig || '',
        titulo: item.titulo || '',
        eixo: item.eixo || '',
        unidade: item.unidade || '',
        carga_horaria: item.carga_horaria || '',
        precificacao: item.precificacao || '',
        valor_primeiro_modulo: item.valor_primeiro_modulo || '',
        valor: item.valor || '',
        parcelas_boleto: item.parcelas_boleto || '',
        valor_parcela_boleto: item.valor_parcela_boleto || '',
        parcelas_cartao: item.parcelas_cartao || '',
        valor_cartao: item.valor_cartao || '',
        parcela_desc_20: item.parcela_desc_20 || '',
        parcela_desc_15: item.parcela_desc_15 || '',
        status: item.status || 'Vigente',
        observacao: item.observacao || '',
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
      if (!this.form.titulo?.trim()) {
        return 'O título / curso é obrigatório.';
      }

      if (!this.form.status?.trim()) {
        return 'O status é obrigatório.';
      }

      return '';
    },

    async salvarRegistro() {
      if (!this.podeEditar) {
        this.erroFormulario = 'Seu perfil não tem permissão para alterar PCA.';
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
        ano: this.form.ano || null,
        semestre: this.form.semestre?.trim() || null,
        numero_sei: this.form.numero_sei?.trim() || null,
        codigo_sig: this.form.codigo_sig?.trim() || null,
        titulo: this.form.titulo.trim(),
        eixo: this.form.eixo || null,
        unidade: this.form.unidade || null,
        carga_horaria: this.form.carga_horaria?.trim() || null,
        precificacao: this.form.precificacao?.trim() || null,
        valor_primeiro_modulo: this.form.valor_primeiro_modulo?.trim() || null,
        valor: this.form.valor?.trim() || null,
        parcelas_boleto: this.form.parcelas_boleto?.trim() || null,
        valor_parcela_boleto: this.form.valor_parcela_boleto?.trim() || null,
        parcelas_cartao: this.form.parcelas_cartao?.trim() || null,
        valor_cartao: this.form.valor_cartao?.trim() || null,
        parcela_desc_20: this.form.parcela_desc_20?.trim() || null,
        parcela_desc_15: this.form.parcela_desc_15?.trim() || null,
        status: this.form.status,
        observacao: this.form.observacao?.trim() || null,
      };

      try {
        if (this.modo === 'editar' && this.editandoId) {
          const { data } = await window.axios.put(`/api/pcas/${this.editandoId}`, payload);
          this.mensagemSucesso = data.message;
        } else {
          const { data } = await window.axios.post('/api/pcas', payload);
          this.mensagemSucesso = data.message;
        }

        this.voltarLista();
        await this.carregarRegistros();
      } catch (error) {
        this.erroFormulario = this.extrairErro(error, 'Não foi possível salvar o registro de PCA.');
      } finally {
        this.salvando = false;
      }
    },

    async excluirRegistro(registro) {
      if (!this.podeEditar) {
        this.bloquearSemPermissao();
        return;
      }

      if (!window.confirm(`Excluir o registro "${registro.titulo}"?`)) {
        return;
      }

      this.erro = '';
      this.mensagemSucesso = '';

      try {
        const { data } = await window.axios.delete(`/api/pcas/${registro.id}`);
        this.mensagemSucesso = data.message;
        this.fecharDetalhes();
        await this.carregarRegistros();
      } catch (error) {
        this.erro = this.extrairErro(error, 'Não foi possível excluir o registro.');
      }
    },

    async abrirDetalhes(registro) {
      this.registroDetalhe = this.normalizarRegistro(registro);

      try {
        const { data } = await window.axios.get(`/api/pcas/${registro.id}`);
        this.registroDetalhe = this.normalizarRegistro(data.pca ?? registro);
      } catch (error) {
        this.erro = this.extrairErro(error, 'Não foi possível carregar os detalhes.');
      }
    },

    fecharDetalhes() {
      this.registroDetalhe = null;
    },

    badgeStatus(status) {
      const valor = String(status || '').toUpperCase();

      if (valor.includes('VIGENTE') || valor.includes('ATIVO') || valor.includes('PUBLICADO') || valor.includes('APROVADO')) {
        return 'badge-vigente';
      }

      if (valor.includes('ANALISE') || valor.includes('ANÁLISE') || valor.includes('AGUARD')) {
        return 'badge-analise';
      }

      if (valor.includes('SUSPENS') || valor.includes('CANCEL') || valor.includes('INATIV')) {
        return 'badge-suspenso';
      }

      return 'badge-analise';
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
