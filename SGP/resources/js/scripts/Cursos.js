import { podeEditarDados } from './auth';
import { UNIDADES } from './unidades';

export default {
  name: 'Cursos',
  data() {
    return {
      modo: 'lista',
      cursos: [],
      meta: {
        eixos: [],
        status: [],
        tipos: [],
        modalidades: [],
        sim_nao: ['SIM', 'NÃO'],
      },
      carregando: false,
      salvando: false,
      editandoId: null,
      abaForm: 'basico',
      mensagemSucesso: '',
      mensagemErro: '',
      erroFormulario: '',
      filtros: {
        busca: '',
        ano: '',
        eixo: '',
        status: '',
        tipo: '',
        unidade: '',
      },
      form: this.formVazio(),
      buscaTimeout: null,
      anosDisponiveis: ['2026', '2025', '2024', '2023'],
      unidades: UNIDADES,
      detalheAberto: false,
      cursoDetalhe: null,
      carregandoDetalhe: false,
      erroDetalhe: '',
    };
  },
  computed: {
    podeEditar() {
      return podeEditarDados();
    },
    temFiltro() {
      return Object.values(this.filtros).some(Boolean);
    },
    totalCursos() {
      return this.cursos.length;
    },
    abasForm() {
      return [
        { id: 'basico', label: 'Dados Básicos' },
        { id: 'tecnico', label: 'Informações Técnicas' },
        { id: 'comercial', label: 'Dados Comerciais' },
      ];
    },
  },
  mounted() {
    this.carregarCursos();
  },
  methods: {
    formVazio() {
      return {
        titulo: '',
        eixo: '',
        modalidade: '',
        tipo: '',
        status: 'ATIVO',
        unidade: '',
        unidades_oferta: [],
        carga_horaria: '',
        turmas: '',
        codigo_processo: '',
        alunos: '',
        instrutor: '',
        descricao: '',
        codigo_dn: '',
        codigo_sig: '',
        identificacao: '',
        ultima_revisao: '',
        processo_sei: '',
        data_inicio: '',
        data_fim: '',
        valores: '',
        compativel_bolsa: '',
        comercial: '',
        pcn: '',
        pcr: '',
        observacoes: '',
      };
    },

    async carregarCursos() {
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

          const { data } = await window.axios.get('/api/cursos', { params });
          this.cursos = data.data ?? [];
          this.meta = { ...this.meta, ...(data.meta ?? {}) };
        } catch (error) {
          this.mensagemErro = this.extrairErro(error, 'Não foi possível carregar os cursos.');
        } finally {
          this.carregando = false;
        }
      }, 200);
    },

    abrirNovo() {
      this.modo = 'novo';
      this.editandoId = null;
      this.abaForm = 'basico';
      this.form = this.formVazio();
      this.erroFormulario = '';
      this.fecharDetalhes();
    },

    abrirEdicao(curso) {
      const unidadesOferta = Array.isArray(curso.unidades_oferta) && curso.unidades_oferta.length
        ? [...curso.unidades_oferta]
        : curso.unidade
          ? [curso.unidade]
          : [];

      this.modo = 'editar';
      this.editandoId = curso.id;
      this.abaForm = 'basico';
      this.form = {
        titulo: curso.titulo ?? '',
        eixo: curso.eixo ?? '',
        modalidade: curso.modalidade ?? '',
        tipo: curso.tipo ?? '',
        status: curso.status ?? 'ATIVO',
        unidade: curso.unidade ?? '',
        unidades_oferta: unidadesOferta,
        carga_horaria: curso.carga_horaria ?? '',
        turmas: curso.turmas ?? '',
        codigo_processo: curso.codigo_processo ?? '',
        alunos: curso.alunos ?? '',
        instrutor: curso.instrutor ?? '',
        descricao: curso.descricao ?? '',
        codigo_dn: curso.codigo_dn ?? '',
        codigo_sig: curso.codigo_sig ?? '',
        identificacao: curso.identificacao ?? '',
        ultima_revisao: curso.ultima_revisao ?? '',
        processo_sei: curso.processo_sei ?? '',
        data_inicio: this.formatarDataInput(curso.data_inicio),
        data_fim: this.formatarDataInput(curso.data_fim),
        valores: curso.valores ?? '',
        compativel_bolsa: curso.compativel_bolsa ?? '',
        comercial: curso.comercial ?? '',
        pcn: curso.pcn ?? '',
        pcr: curso.pcr ?? '',
        observacoes: curso.observacoes ?? '',
      };
      this.erroFormulario = '';
      this.fecharDetalhes();
    },

    voltarLista() {
      this.modo = 'lista';
      this.editandoId = null;
      this.abaForm = 'basico';
      this.erroFormulario = '';
      this.form = this.formVazio();
    },

    toggleUnidade(unidade) {
      const lista = [...this.form.unidades_oferta];
      const indice = lista.indexOf(unidade);

      if (indice >= 0) {
        lista.splice(indice, 1);
      } else {
        lista.push(unidade);
      }

      this.form.unidades_oferta = lista;
      this.form.unidade = lista[0] ?? '';
    },

    unidadeSelecionada(unidade) {
      return this.form.unidades_oferta.includes(unidade);
    },

    validarFormulario() {
      if (!this.form.eixo) {
        this.abaForm = 'basico';
        return 'Selecione o segmento / área.';
      }

      if (!this.form.titulo?.trim()) {
        this.abaForm = 'basico';
        return 'O título do curso é obrigatório.';
      }

      if (!this.form.carga_horaria) {
        this.abaForm = 'basico';
        return 'Informe a carga horária.';
      }

      if (!this.form.status) {
        this.abaForm = 'tecnico';
        return 'Selecione o status.';
      }

      if (!this.form.modalidade) {
        this.abaForm = 'tecnico';
        return 'Selecione a modalidade.';
      }

      if (!this.form.codigo_sig?.trim()) {
        this.abaForm = 'tecnico';
        return 'Informe o código SIG.';
      }

      if (!this.form.tipo) {
        this.abaForm = 'tecnico';
        return 'Selecione o tipo de curso.';
      }

      if (this.form.data_inicio && this.form.data_fim && this.form.data_fim < this.form.data_inicio) {
        this.abaForm = 'tecnico';
        return 'A data de término deve ser igual ou posterior à data de início.';
      }

      return '';
    },

    async salvarCurso() {
      const erroValidacao = this.validarFormulario();

      if (erroValidacao) {
        this.erroFormulario = erroValidacao;
        return;
      }

      this.salvando = true;
      this.erroFormulario = '';
      this.mensagemSucesso = '';

      const payload = {
        titulo: this.form.titulo,
        eixo: this.form.eixo,
        modalidade: this.form.modalidade || null,
        tipo: this.form.tipo || null,
        status: this.form.status,
        unidade: this.form.unidades_oferta[0] || this.form.unidade || null,
        unidades_oferta: this.form.unidades_oferta.length ? this.form.unidades_oferta : null,
        carga_horaria: this.form.carga_horaria || null,
        turmas: this.form.turmas || null,
        codigo_processo: this.form.codigo_processo || null,
        alunos: this.form.alunos || null,
        instrutor: this.form.instrutor || null,
        descricao: this.form.descricao || null,
        codigo_dn: this.form.codigo_dn || null,
        codigo_sig: this.form.codigo_sig || null,
        identificacao: this.form.identificacao || null,
        ultima_revisao: this.form.ultima_revisao || null,
        processo_sei: this.form.processo_sei || null,
        data_inicio: this.form.data_inicio || null,
        data_fim: this.form.data_fim || null,
        valores: this.form.valores || null,
        compativel_bolsa: this.form.compativel_bolsa || null,
        comercial: this.form.comercial || null,
        pcn: this.form.pcn || null,
        pcr: this.form.pcr || null,
        observacoes: this.form.observacoes || null,
      };

      try {
        if (this.editandoId) {
          const { data } = await window.axios.put(`/api/cursos/${this.editandoId}`, payload);
          this.mensagemSucesso = data.message;
        } else {
          const { data } = await window.axios.post('/api/cursos', payload);
          this.mensagemSucesso = data.message;
        }

        this.voltarLista();
        await this.carregarCursos();
      } catch (error) {
        this.erroFormulario = this.extrairErro(error, 'Não foi possível salvar o curso.');
      } finally {
        this.salvando = false;
      }
    },

    badgeStatus(status) {
      const valor = String(status ?? '').toUpperCase();

      return {
        'badge-ativo': valor === 'ATIVO',
        'badge-revisao': valor === 'EM REVISÃO' || valor === 'EM REVISAO',
        'badge-inativo': valor === 'INATIVO',
        'badge-suspenso': valor === 'SUSPENSO',
      };
    },

    rotuloStatus(status) {
      return status || '—';
    },

    valorCampo(valor) {
      if (valor === null || valor === undefined || valor === '') {
        return 'Não informado';
      }

      return valor;
    },

    formatarDataInput(valor) {
      if (!valor) {
        return '';
      }

      const texto = String(valor);

      if (/^\d{4}-\d{2}-\d{2}$/.test(texto)) {
        return texto;
      }

      return texto.slice(0, 10);
    },

    formatarDataExibicao(valor) {
      const data = this.formatarDataInput(valor);

      if (!data) {
        return 'Não informado';
      }

      const [ano, mes, dia] = data.split('-');

      return `${dia}/${mes}/${ano}`;
    },

    textoUnidades(curso) {
      if (Array.isArray(curso?.unidades_oferta) && curso.unidades_oferta.length) {
        return curso.unidades_oferta.join(', ');
      }

      return curso?.unidade || '—';
    },

    async abrirDetalhes(curso) {
      this.detalheAberto = true;
      this.cursoDetalhe = null;
      this.carregandoDetalhe = true;
      this.erroDetalhe = '';

      try {
        const { data } = await window.axios.get(`/api/cursos/${curso.id}`);
        this.cursoDetalhe = data.curso ?? curso;
      } catch (error) {
        this.erroDetalhe = this.extrairErro(error, 'Não foi possível carregar os detalhes do curso.');
        this.cursoDetalhe = { ...curso };
      } finally {
        this.carregandoDetalhe = false;
      }
    },

    fecharDetalhes() {
      this.detalheAberto = false;
      this.cursoDetalhe = null;
      this.erroDetalhe = '';
    },

    editarDoDetalhe() {
      if (!this.cursoDetalhe) {
        return;
      }

      this.abrirEdicao(this.cursoDetalhe);
    },

    async excluirCurso(curso) {
      const confirmar = window.confirm(
        `Excluir o curso "${curso.titulo}"? Esta ação não pode ser desfeita.`
      );

      if (!confirmar) {
        return;
      }

      this.mensagemErro = '';
      this.mensagemSucesso = '';

      if (this.cursoDetalhe?.id === curso.id) {
        this.fecharDetalhes();
      }

      try {
        const { data } = await window.axios.delete(`/api/cursos/${curso.id}`);
        this.mensagemSucesso = data.message;
        await this.carregarCursos();
      } catch (error) {
        this.mensagemErro = this.extrairErro(error, 'Não foi possível excluir o curso.');
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
  },
};
