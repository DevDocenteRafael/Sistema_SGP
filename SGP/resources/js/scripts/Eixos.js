import { podeEditarDados } from './auth';
import { UNIDADES } from './unidades';

const FILTROS_VAZIOS = () => ({
  busca: '',
  ano: '',
  eixo: '',
  unidade: '',
  status: '',
});

const FORM_VAZIO = () => ({
  curso: '',
  eixo: '',
  unidade: '',
  ano: '2025',
  ch: '',
  turmas: '',
  codigo: '',
  alunos: '',
  instrutores: '',
  status: 'Ativo',
  observacao: '',
});

export default {
  name: 'Eixos',
  data() {
    return {
      modo: 'lista',
      registros: [],
      meta: {
        eixos: [],
        status: ['Ativo', 'Suspenso', 'Inativo'],
        anos: ['2026', '2025', '2024', '2023'],
        unidades: UNIDADES,
      },
      carregando: false,
      unidades: UNIDADES,
      filtros: FILTROS_VAZIOS(),
      buscaTimeout: null,
      detalheAberto: false,
      registroDetalhe: null,
      editandoId: null,
      form: FORM_VAZIO(),
      salvando: false,
      erroFormulario: '',
      mensagemSucesso: '',
      mensagemErro: '',
    };
  },
  computed: {
    podeEditar() {
      return podeEditarDados();
    },
    statusLista() {
      return this.meta.status?.length ? this.meta.status : ['Ativo', 'Suspenso', 'Inativo'];
    },
    anosDisponiveis() {
      return this.meta.anos?.length ? this.meta.anos : ['2026', '2025', '2024', '2023'];
    },
    eixosDisponiveis() {
      if (this.meta.eixos?.length) {
        return [...this.meta.eixos].sort((a, b) => a.localeCompare(b, 'pt-BR'));
      }

      const set = new Set(this.registros.map((r) => r.eixo).filter(Boolean));

      return [...set].sort((a, b) => a.localeCompare(b, 'pt-BR'));
    },
    eixosFormulario() {
      return this.eixosDisponiveis;
    },
    totalFiltrado() {
      return this.registros.length;
    },
    totalGeral() {
      return this.meta.total_geral ?? this.registros.length;
    },
    temFiltro() {
      return Object.values(this.filtros).some(Boolean);
    },
  },
  mounted() {
    this.carregarRegistros();
  },
  methods: {
    aplicarFiltros() {
      clearTimeout(this.buscaTimeout);

      this.buscaTimeout = setTimeout(() => {
        this.carregarRegistros();
      }, 200);
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

        const { data } = await window.axios.get('/api/curso-por-eixos', { params });
        this.registros = data.data ?? [];
        this.meta = { ...this.meta, ...(data.meta ?? {}) };

        if (Array.isArray(this.meta.unidades) && this.meta.unidades.length) {
          this.unidades = this.meta.unidades;
        }
      } catch (error) {
        this.mensagemErro = this.extrairErro(error, 'Não foi possível carregar os cursos por eixo.');
        this.registros = [];
      } finally {
        this.carregando = false;
      }
    },

    abrirNovo() {
      if (!this.podeEditar) {
        this.mensagemErro = 'Seu perfil só permite consultar cursos por eixo.';
        return;
      }

      this.modo = 'novo';
      this.editandoId = null;
      this.form = FORM_VAZIO();
      this.erroFormulario = '';
      this.mensagemSucesso = '';
      this.mensagemErro = '';
      this.fecharDetalhes();
    },

    abrirEdicao(registro) {
      if (!this.podeEditar) {
        this.mensagemErro = 'Seu perfil só permite consultar cursos por eixo.';
        return;
      }

      this.modo = 'editar';
      this.editandoId = registro.id;
      this.form = {
        curso: registro.curso ?? '',
        eixo: registro.eixo ?? '',
        unidade: registro.unidade ?? '',
        ano: registro.ano ?? '2025',
        ch: registro.ch ?? '',
        turmas: registro.turmas ?? '',
        codigo: registro.codigo ?? '',
        alunos: registro.alunos ?? '',
        instrutores: registro.instrutores ?? '',
        status: registro.status ?? 'Ativo',
        observacao: registro.observacao ?? '',
      };
      this.erroFormulario = '';
      this.mensagemSucesso = '';
      this.mensagemErro = '';
      this.fecharDetalhes();
    },

    editarDoDetalhe() {
      if (!this.registroDetalhe) {
        return;
      }

      this.abrirEdicao(this.registroDetalhe);
    },

    voltarLista() {
      this.modo = 'lista';
      this.editandoId = null;
      this.form = FORM_VAZIO();
      this.erroFormulario = '';
      this.salvando = false;
    },

    fecharFormulario() {
      this.voltarLista();
    },

    validarFormulario() {
      if (!this.form.curso?.trim()) {
        return 'Informe o nome do curso.';
      }

      if (!this.form.eixo) {
        return 'Selecione o eixo tecnológico.';
      }

      if (!this.form.ano) {
        return 'Selecione o ano.';
      }

      if (!this.form.status) {
        return 'Selecione o status.';
      }

      return '';
    },

    async salvarRegistro() {
      const erro = this.validarFormulario();

      if (erro) {
        this.erroFormulario = erro;
        return;
      }

      this.salvando = true;
      this.erroFormulario = '';
      this.mensagemSucesso = '';
      this.mensagemErro = '';

      const payload = {
        curso: this.form.curso.trim(),
        eixo: this.form.eixo,
        unidade: this.form.unidade || null,
        ano: this.form.ano,
        ch: this.form.ch || null,
        turmas: this.form.turmas || null,
        codigo: this.form.codigo || null,
        alunos: this.form.alunos || null,
        instrutores: this.form.instrutores || null,
        status: this.form.status,
        observacao: this.form.observacao || null,
        is_novo: false,
      };

      try {
        if (this.editandoId) {
          const { data } = await window.axios.put(`/api/curso-por-eixos/${this.editandoId}`, payload);
          this.mensagemSucesso = data.message;
        } else {
          const { data } = await window.axios.post('/api/curso-por-eixos', payload);
          this.mensagemSucesso = data.message;
        }

        this.voltarLista();
        await this.carregarRegistros();
      } catch (error) {
        this.erroFormulario = this.extrairErro(error, 'Não foi possível salvar o registro.');
      } finally {
        this.salvando = false;
      }
    },

    async excluirRegistro(registro) {
      const confirmar = window.confirm(
        `Excluir o curso "${registro.curso}" (${registro.ano})? Esta ação não pode ser desfeita.`
      );

      if (!confirmar) {
        return;
      }

      this.mensagemErro = '';
      this.mensagemSucesso = '';

      if (this.registroDetalhe?.id === registro.id) {
        this.fecharDetalhes();
      }

      try {
        const { data } = await window.axios.delete(`/api/curso-por-eixos/${registro.id}`);
        this.mensagemSucesso = data.message;
        await this.carregarRegistros();
      } catch (error) {
        this.mensagemErro = this.extrairErro(error, 'Não foi possível excluir o registro.');
      }
    },

    async abrirDetalhes(registro) {
      this.detalheAberto = true;
      this.registroDetalhe = { ...registro };

      try {
        const { data } = await window.axios.get(`/api/curso-por-eixos/${registro.id}`);
        this.registroDetalhe = data.cursoPorEixo ?? registro;
      } catch (error) {
        this.mensagemErro = this.extrairErro(error, 'Não foi possível carregar os detalhes.');
      }
    },

    fecharDetalhes() {
      this.detalheAberto = false;
      this.registroDetalhe = null;
    },

    formatarCh(valor) {
      if (!valor) {
        return '—';
      }

      const texto = String(valor).trim();

      if (/h$/i.test(texto)) {
        return texto;
      }

      return `${texto}h`;
    },

    valorCampo(valor) {
      if (valor === null || valor === undefined || valor === '') {
        return '—';
      }

      return valor;
    },

    badgeStatus(status) {
      const valor = String(status ?? '').toLowerCase();

      return {
        'badge-ativo': valor === 'ativo',
        'badge-suspenso': valor === 'suspenso',
        'badge-inativo': valor === 'inativo',
      };
    },

    classeEixo(eixo) {
      const nome = String(eixo || '').toLowerCase();

      if (nome.includes('gastronomia') || nome.includes('bebidas') || nome.includes('panif') || nome.includes('confeit')) {
        return 'eixo-tag--laranja';
      }

      if (nome.includes('turismo') || nome.includes('hospitalidade')) {
        return 'eixo-tag--azul';
      }

      if (nome.includes('tecnologia') || nome.includes('informação') || nome.includes('infoma')) {
        return 'eixo-tag--roxo';
      }

      if (nome.includes('enfermagem') || nome.includes('saúde') || nome.includes('nutri') || nome.includes('farm') || nome.includes('radio') || nome.includes('estética')) {
        return 'eixo-tag--verde';
      }

      if (nome.includes('beleza') || nome.includes('moda')) {
        return 'eixo-tag--rosa';
      }

      if (nome.includes('gestão') || nome.includes('educação') || nome.includes('vendas') || nome.includes('segurança')) {
        return 'eixo-tag--cinza';
      }

      return 'eixo-tag--padrao';
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
