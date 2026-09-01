import { podeEditarDados } from './auth';
import { lerCicloContexto, salvarCicloContexto } from './cicloContexto';
import { UNIDADES } from './unidades';
import PageTableCard from '../components/crud/PageTableCard.vue';
import CrudPageHeader from '../components/crud/CrudPageHeader.vue';
import CicloContextoBanner from '../components/crud/CicloContextoBanner.vue';
import { mixinHistoricoFormulario } from './formularioHistorico';
import {
  combinarValidacoes,
  extrairErroApi,
  formatarInteiroInput,
  formatarProcessoSeiInput,
  somenteAlfanumericoProcesso,
  tamanhoMaximo,
  textoObrigatorio,
  validarData,
  validarInteiro,
  validarOrdemDatas,
  validarProcessoSei,
} from '../utils/validacao';

export default {
  name: 'Cursos',
  mixins: [mixinHistoricoFormulario],
  components: { PageTableCard, CrudPageHeader, CicloContextoBanner },
  data() {
    return {
      modo: 'lista',
      cursos: [],
      ciclos: [],
      cicloInicializado: false,
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
        ciclo_id: '',
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
      duplicidadeAberta: false,
      duplicidadeSimilares: [],
      justificativaDuplicidade: '',
      erroDuplicidade: '',
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
    cicloAberto() {
      if (this.filtros.ciclo_id === 'todos') {
        return null;
      }

      if (this.filtros.ciclo_id) {
        return this.ciclos.find((ciclo) => String(ciclo.id) === String(this.filtros.ciclo_id))
          || lerCicloContexto('cursos');
      }

      return lerCicloContexto('cursos');
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
    this.aplicarCicloInicial();
    this.carregarCursos();
  },
  watch: {
    '$route.query.ciclo_id'(id) {
      if (!id || String(id) === String(this.filtros.ciclo_id)) {
        return;
      }

      this.cicloInicializado = true;
      this.filtros.ciclo_id = String(id);
      this.lembrarCicloSelecionado();
      this.carregarCursos();
    },
  },
  methods: {
    formVazio() {
      return {
        ciclo_id: '',
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
        justificativa_duplicidade: '',
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
          this.ciclos = Array.isArray(data.meta?.ciclos) ? data.meta.ciclos : this.ciclos;

          if (!this.cicloInicializado && data.meta?.ciclo_atual_id) {
            this.cicloInicializado = true;
            this.filtros.ciclo_id = String(data.meta.ciclo_atual_id);
          }

          this.lembrarCicloSelecionado();
        } catch (error) {
          this.mensagemErro = extrairErroApi(error, 'Não foi possível carregar os cursos.');
        } finally {
          this.carregando = false;
        }
      }, 200);
    },

    abrirNovo() {
      if (!this.podeEditar) {
        this.mensagemErro = 'Seu perfil não tem permissão para criar cursos.';
        return;
      }

      this.aplicarEstadoNovoLocal();
      this.empilharHistoricoFormulario('novo');
    },

    aplicarEstadoNovoLocal() {
      this.modo = 'novo';
      this.editandoId = null;
      this.abaForm = 'basico';
      this.form = this.formVazio();
      this.form.ciclo_id = this.cicloFormPadrao();
      this.erroFormulario = '';
      this.fecharDetalhes();
    },

    abrirEdicao(curso) {
      this.aplicarEstadoEdicaoLocal(curso);
      this.empilharHistoricoFormulario('editar', curso.id);
    },

    aplicarEstadoEdicaoLocal(curso) {
      const unidadesOferta = Array.isArray(curso.unidades_oferta) && curso.unidades_oferta.length
        ? [...curso.unidades_oferta]
        : curso.unidade
          ? [curso.unidade]
          : [];

      this.modo = 'editar';
      this.editandoId = curso.id;
      this.abaForm = 'basico';
      this.form = {
        ciclo_id: curso.ciclo_id ? String(curso.ciclo_id) : this.cicloFormPadrao(),
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

    async aplicarEstadoEdicaoPorId(id) {
      let curso = this.cursos.find((item) => String(item.id) === String(id));

      if (!curso) {
        try {
          const { data } = await window.axios.get(`/api/cursos/${id}`);
          curso = data.curso || data.data || null;
        } catch {
          curso = null;
        }
      }

      if (!curso) {
        this.aplicarEstadoListaLocal();
        this.limparHistoricoFormulario();
        return;
      }

      this.aplicarEstadoEdicaoLocal(curso);
    },

    voltarLista() {
      this.aplicarEstadoListaLocal();
      this.limparHistoricoFormulario();
    },

    aplicarEstadoListaLocal() {
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
      const erroBasico = combinarValidacoes(
        textoObrigatorio(this.form.eixo, 'Selecione o segmento / área.'),
        textoObrigatorio(this.form.titulo, 'O título do curso é obrigatório.'),
        tamanhoMaximo(this.form.titulo, 255, 'O título deve ter no máximo 255 caracteres.'),
        textoObrigatorio(this.form.carga_horaria, 'Informe a carga horária.'),
        validarInteiro(this.form.carga_horaria, { obrigatorio: true, rotulo: 'Carga horária', min: 1, max: 99999 }),
        this.form.turmas ? validarInteiro(this.form.turmas, { rotulo: 'Turmas', min: 0, max: 9999 }) : '',
        this.form.alunos ? validarInteiro(this.form.alunos, { rotulo: 'Alunos', min: 0, max: 99999 }) : '',
        this.form.processo_sei ? validarProcessoSei(this.form.processo_sei) : '',
      );

      if (erroBasico) {
        this.abaForm = 'basico';
        return erroBasico;
      }

      const erroTecnico = combinarValidacoes(
        textoObrigatorio(this.form.status, 'Selecione o status.'),
        textoObrigatorio(this.form.modalidade, 'Selecione a modalidade.'),
        textoObrigatorio(this.form.codigo_sig, 'Informe o código SIG.'),
        tamanhoMaximo(this.form.codigo_sig, 100, 'O código SIG deve ter no máximo 100 caracteres.'),
        textoObrigatorio(this.form.tipo, 'Selecione o tipo de curso.'),
        validarData(this.form.data_inicio, { rotulo: 'Data de início' }),
        validarData(this.form.data_fim, { rotulo: 'Data de término' }),
        validarOrdemDatas(
          this.form.data_inicio,
          this.form.data_fim,
          'A data de término deve ser igual ou posterior à data de início.',
        ),
      );

      if (erroTecnico) {
        this.abaForm = 'tecnico';
        return erroTecnico;
      }

      return '';
    },

    formatarProcessoSei: formatarProcessoSeiInput('processo_sei'),
    formatarCargaHoraria: formatarInteiroInput('carga_horaria'),
    formatarTurmas: formatarInteiroInput('turmas'),
    formatarAlunos: formatarInteiroInput('alunos'),

    async salvarCurso() {
      if (!this.podeEditar) {
        this.erroFormulario = 'Seu perfil não tem permissão para salvar cursos.';
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

      const payload = {
        ciclo_id: this.form.ciclo_id || null,
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
        processo_sei: this.form.processo_sei
          ? somenteAlfanumericoProcesso(this.form.processo_sei).trim()
          : null,
        data_inicio: this.form.data_inicio || null,
        data_fim: this.form.data_fim || null,
        valores: this.form.valores || null,
        compativel_bolsa: this.form.compativel_bolsa || null,
        comercial: this.form.comercial || null,
        pcn: this.form.pcn || null,
        pcr: this.form.pcr || null,
        observacoes: this.form.observacoes || null,
        justificativa_duplicidade: this.form.justificativa_duplicidade || null,
      };

      try {
        if (this.editandoId) {
          const { data } = await window.axios.put(`/api/cursos/${this.editandoId}`, payload);
          this.mensagemSucesso = data.message;
        } else {
          const { data } = await window.axios.post('/api/cursos', payload);
          this.mensagemSucesso = data.message;
        }

        this.duplicidadeAberta = false;
        this.voltarLista();
        await this.carregarCursos();
      } catch (error) {
        if (error.response?.status === 409 && error.response?.data?.duplicidade) {
          this.duplicidadeSimilares = error.response.data.similares ?? [];
          this.duplicidadeAberta = true;
          this.erroDuplicidade = '';
          this.erroFormulario = error.response.data.message
            || 'Já existe curso semelhante. Informe uma justificativa para continuar.';
          return;
        }

        const mensagem = extrairErroApi(error, 'Não foi possível salvar o curso.');

        if (this.duplicidadeAberta) {
          this.erroDuplicidade = mensagem;
        } else {
          this.erroFormulario = mensagem;
        }
      } finally {
        this.salvando = false;
      }
    },

    aplicarCicloInicial() {
      const cicloQuery = this.$route.query.ciclo_id;
      const contexto = lerCicloContexto('cursos');
      const cicloId = cicloQuery && cicloQuery !== 'todos'
        ? String(cicloQuery)
        : (contexto?.id ? String(contexto.id) : '');

      if (!cicloId) {
        return;
      }

      this.cicloInicializado = true;
      this.filtros.ciclo_id = cicloId;

      if (contexto?.id && String(contexto.id) === cicloId) {
        this.ciclos = [contexto];
      }
    },

    lembrarCicloSelecionado() {
      if (!this.filtros.ciclo_id || this.filtros.ciclo_id === 'todos') {
        return;
      }

      const ciclo = this.ciclos.find((item) => String(item.id) === String(this.filtros.ciclo_id));

      if (ciclo) {
        salvarCicloContexto(ciclo, 'cursos');
      }
    },

    onCicloFiltroChange() {
      this.lembrarCicloSelecionado();
      this.carregarCursos();
    },

    cicloFormPadrao() {
      if (this.filtros.ciclo_id && this.filtros.ciclo_id !== 'todos') {
        return String(this.filtros.ciclo_id);
      }

      const contexto = lerCicloContexto('cursos');
      if (contexto?.id) {
        return String(contexto.id);
      }

      return this.meta.ciclo_atual_id ? String(this.meta.ciclo_atual_id) : '';
    },

    cancelarDuplicidade() {
      this.duplicidadeAberta = false;
      this.justificativaDuplicidade = '';
      this.erroDuplicidade = '';
    },

    async confirmarDuplicidade() {
      const texto = this.justificativaDuplicidade.trim();

      if (texto.length < 10) {
        this.erroDuplicidade = 'A justificativa deve ter pelo menos 10 caracteres.';
        return;
      }

      this.form.justificativa_duplicidade = texto;
      await this.salvarCurso();
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
        this.erroDetalhe = extrairErroApi(error, 'Não foi possível carregar os detalhes do curso.');
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
        this.mensagemErro = extrairErroApi(error, 'Não foi possível excluir o curso.');
      }
    },
  },
};
