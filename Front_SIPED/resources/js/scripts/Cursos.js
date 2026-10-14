import { podeEditarDados } from './auth';
import { rotuloOrigem, textoSincronizacao } from './origemDados';
import { CICLO_CONTEXTO_EVENTO, lerCicloContexto, salvarCicloContexto } from './cicloContexto';
import { carregarUnidadesNomes, carregarUnidadesOpcoes } from './unidadesApi';
import PageTableCard from '../components/crud/PageTableCard.vue';
import Pagination from '../components/crud/Pagination.vue';
import CrudPageHeader from '../components/crud/CrudPageHeader.vue';
import { mixinHistoricoFormulario } from './formularioHistorico';
import {
  combinarValidacoes,
  extrairErroApi,
  formatarInteiroInput,
  formatarProcessoSeiInput,
  somenteAlfanumericoProcesso,
  somenteNumeros,
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
  components: { PageTableCard, CrudPageHeader, Pagination },
  data() {
    return {
      modo: 'lista',
      cursos: [],
      ciclos: [],
      cicloInicializado: false,
      meta: {
        total: 0,
        per_page: 10,
        current_page: 1,
        last_page: 1,
        from: 0,
        to: 0,
        eixos: [],
        segmentos: [],
        segmentos_por_eixo: {},
        programas: [],
        status: [],
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
        programa: '',
        status: '',
        unidade: '',
      },
      form: this.formVazio(),
      errosCampo: {},
      pcnLegado: '',
      pcrLegado: '',
      valoresLegado: false,
      ultimoValorMonetarioValido: '',
      digitosCentavosDigitados: 0,
      buscaTimeout: null,
      anosDisponiveis: ['2026', '2025', '2024', '2023'],
      unidades: [],
      unidadesOpcoes: [],
      regiaoOfertaSelecionada: '',
      detalheAberto: false,
      cursoDetalhe: null,
      carregandoDetalhe: false,
      erroDetalhe: '',
      dadosDoCiclo: [],
      formDadosCiclo: null,
      salvandoDadosCiclo: false,
      erroDadosCiclo: '',
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
      return Object.entries(this.filtros).some(([chave, valor]) => chave !== 'ciclo_id' && Boolean(valor));
    },
    totalCursos() {
      return this.cursos.length;
    },
    cicloAberto() {
      if (this.filtros.ciclo_id) {
        return this.ciclos.find((ciclo) => String(ciclo.id) === String(this.filtros.ciclo_id))
          || lerCicloContexto();
      }

      return lerCicloContexto();
    },
    abasForm() {
      return [
        { id: 'basico', label: 'Dados Básicos' },
        { id: 'tecnico', label: 'Informações Técnicas' },
        { id: 'comercial', label: 'Dados Comerciais' },
      ];
    },
    indiceAbaForm() {
      return this.abasForm.findIndex((aba) => aba.id === this.abaForm);
    },
    ehPrimeiraAbaForm() {
      return this.indiceAbaForm <= 0;
    },
    ehUltimaAbaForm() {
      return this.indiceAbaForm >= this.abasForm.length - 1;
    },
    opcoesRegiaoOferta() {
      return this.unidadesOpcoes.map((opcao) => ({
        value: this.valorOpcaoOferta(opcao),
        label: opcao.nome,
      }));
    },
    regiaoOfertaAtual() {
      if (!this.regiaoOfertaSelecionada) {
        return null;
      }
      return this.unidadesOpcoes.find((opcao) => (
        Array.isArray(opcao.grupos)
        && this.valorOpcaoOferta(opcao) === String(this.regiaoOfertaSelecionada)
      )) || null;
    },
    unidadesSelecionadasResumo() {
      return [...this.form.unidades_oferta];
    },
    segmentosDoEixo() {
      const mapa = this.meta.segmentos_por_eixo || {};
      if (this.form.eixo && Array.isArray(mapa[this.form.eixo])) {
        return mapa[this.form.eixo];
      }
      return this.meta.segmentos || [];
    },
    opcoesStatus() {
      return (this.meta.status || []).map((valor) => ({
        value: valor,
        label: this.textoPadronizado(valor),
      }));
    },
    opcoesSimNao() {
      return (this.meta.sim_nao || ['SIM', 'NÃO']).map((valor) => ({
        value: String(valor).trim().toLocaleUpperCase('pt-BR'),
        label: this.textoPadronizado(valor),
      }));
    },
  },
  async mounted() {
    this.aplicarCicloInicial();
    window.addEventListener(CICLO_CONTEXTO_EVENTO, this.aoMudarCicloGlobal);
    await Promise.all([
      this.carregarCursos(),
      this.carregarUnidadesOferta(),
    ]);
    this.aplicarQueryDaRota();
  },
  beforeUnmount() {
    window.removeEventListener(CICLO_CONTEXTO_EVENTO, this.aoMudarCicloGlobal);
  },
  watch: {
    'form.eixo'(novo, antigo) {
      if (!antigo || novo === antigo) {
        return;
      }
      const lista = this.segmentosDoEixo;
      if (this.form.segmento && lista.length && !lista.includes(this.form.segmento)) {
        this.form.segmento = '';
      }
    },
    '$route.query.ciclo_id'(id) {
      if (!id || String(id) === String(this.filtros.ciclo_id)) {
        return;
      }

      this.cicloInicializado = true;
      this.filtros.ciclo_id = String(id);
      this.lembrarCicloSelecionado();
      this.carregarCursos();
    },
    '$route.query.novo'() {
      this.aplicarQueryDaRota();
    },
    '$route.query.curso_id'() {
      this.aplicarQueryDaRota();
    },
    '$route.query.eixo'(valor) {
      if ((this.$route.query.novo === '1' || this.$route.query.novo === 'true') && valor) {
        this.form.eixo = String(valor);
      }
    },
  },
  methods: {
    limparFiltros() {
      const cicloId = this.filtros.ciclo_id;
      this.filtros = {
        busca: '',
        ciclo_id: cicloId,
        ano: '',
        eixo: '',
        programa: '',
        status: '',
        unidade: '',
      };
      this.meta.current_page = 1;
      this.carregarCursos();
    },

    aplicarFiltros() {
      clearTimeout(this.buscaTimeout);
      this.meta.current_page = 1;
      this.buscaTimeout = setTimeout(() => this.carregarCursos(), 200);
    },

    async carregarUnidadesOferta() {
      const [nomes, opcoes] = await Promise.all([
        carregarUnidadesNomes({ forcar: true }),
        carregarUnidadesOpcoes(),
      ]);
      this.unidades = nomes;
      this.unidadesOpcoes = opcoes;
      if (!this.regiaoOfertaSelecionada && opcoes.length) {
        this.regiaoOfertaSelecionada = this.valorOpcaoOferta(opcoes[0]);
      }
    },
    formVazio() {
      return {
        ciclo_id: '',
        titulo: '',
        eixo: '',
        segmento: '',
        programa: '',
        modalidade: '',
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

    textoPadronizado(valor) {
      const texto = String(valor ?? '').trim().toLocaleLowerCase('pt-BR');
      return texto ? texto.charAt(0).toLocaleUpperCase('pt-BR') + texto.slice(1) : '';
    },

    valorSimNao(valor) {
      const texto = String(valor ?? '').trim().toLocaleLowerCase('pt-BR');
      if (texto === 'sim') return 'SIM';
      if (texto === 'não' || texto === 'nao') return 'NÃO';
      return '';
    },

    validarCodigoInput(evento, campo) {
      const valor = evento.target.value;
      const permitido = valor.replace(/[^0-9.-]/g, '');
      if (valor !== permitido) {
        this.errosCampo[campo] = 'Use somente números, ponto (.) e hífen (-).';
        evento.target.value = permitido;
      } else {
        this.errosCampo[campo] = '';
      }
      this.form[campo] = permitido;
    },

    validarUltimaAtualizacaoInput(evento) {
      const valor = evento.target.value;
      const permitido = valor.replace(/\D/g, '').slice(0, 6);
      if (valor !== permitido) {
        this.errosCampo.ultima_revisao = 'Use somente números, com no máximo 6 algarismos.';
        evento.target.value = permitido;
      } else {
        this.errosCampo.ultima_revisao = '';
      }
      this.form.ultima_revisao = permitido;
    },

    aoFocarValor(evento) {
      this.digitosCentavosDigitados = 0;
      if (this.valoresLegado) {
        evento.target.select();
      }
    },

    prepararEntradaValor(evento) {
      const posicaoVirgula = evento.target.value.indexOf(',');
      if (evento.key === ',' || evento.key === '.') {
        if (posicaoVirgula < 0) return;
        evento.preventDefault();
        const parteInteira = evento.target.value.slice(0, posicaoVirgula);
        const posicaoCentavos = posicaoVirgula + 1;
        evento.target.value = `${parteInteira},`;
        this.form.valores = evento.target.value;
        this.ultimoValorMonetarioValido = evento.target.value;
        this.valoresLegado = false;
        this.digitosCentavosDigitados = 0;
        evento.target.setSelectionRange(posicaoCentavos, posicaoCentavos);
        return;
      }

      if (/^\d$/.test(evento.key) && posicaoVirgula >= 0
        && evento.target.selectionStart > posicaoVirgula
        && this.digitosCentavosDigitados >= 2) {
        evento.preventDefault();
        this.errosCampo.valores = 'Informe no máximo 2 algarismos para os centavos.';
      } else if (evento.key === 'Backspace' || evento.key === 'Delete') {
        this.digitosCentavosDigitados = 0;
      }
    },

    formatarValorInput(evento) {
      const entradaOriginal = evento.target.value;
      if (!String(entradaOriginal).trim()) {
        this.form.valores = '';
        this.ultimoValorMonetarioValido = '';
        this.valoresLegado = false;
        this.digitosCentavosDigitados = 0;
        this.errosCampo.valores = '';
        return;
      }

      if (String(entradaOriginal).replace(/R\$\s*/i, '').replace(/[0-9.,\s]/g, '') !== '') {
        this.errosCampo.valores = 'Use somente números e separadores monetários válidos.';
        evento.target.value = this.ultimoValorMonetarioValido;
        this.form.valores = this.ultimoValorMonetarioValido;
        return;
      }

      const virgulaOriginal = entradaOriginal.indexOf(',');
      const digitandoCentavos = evento.inputType === 'insertText'
        && /^\d$/.test(evento.data || '')
        && virgulaOriginal >= 0
        && evento.target.selectionStart > virgulaOriginal;
      if (digitandoCentavos) {
        if (this.digitosCentavosDigitados >= 2) {
          this.errosCampo.valores = 'Informe no máximo 2 algarismos para os centavos.';
          evento.target.value = this.ultimoValorMonetarioValido;
          this.form.valores = this.ultimoValorMonetarioValido;
          return;
        }
        this.digitosCentavosDigitados += 1;
      }

      let entrada = entradaOriginal;
      const centavosComPlaceholder = virgulaOriginal >= 0
        ? entradaOriginal.slice(virgulaOriginal + 1).replace(/\D/g, '')
        : '';
      if (digitandoCentavos && centavosComPlaceholder.length > 2) {
        entrada = `${entradaOriginal.slice(0, virgulaOriginal + 1)}${centavosComPlaceholder.slice(0, 2)}`;
      }

      const formatado = this.formatarMoedaBrasileira(entrada);
      if (!formatado) {
        this.errosCampo.valores = 'Informe um valor monetário válido, usando vírgula para os centavos.';
        evento.target.value = this.ultimoValorMonetarioValido;
        this.form.valores = this.ultimoValorMonetarioValido;
        return;
      }

      const virgulaDigitada = entrada.indexOf(',');
      const cursorNaParteDecimal = virgulaDigitada >= 0 && evento.target.selectionStart > virgulaDigitada;
      const digitosDecimais = cursorNaParteDecimal
        ? (entrada.slice(virgulaDigitada + 1).match(/\d/g) || []).length
        : 0;
      this.form.valores = formatado;
      this.ultimoValorMonetarioValido = formatado;
      this.valoresLegado = false;
      this.errosCampo.valores = '';
      if (digitandoCentavos || evento.inputType === 'insertFromPaste') {
        this.digitosCentavosDigitados = cursorNaParteDecimal ? Math.min(digitosDecimais, 2) : 0;
      } else if (evento.inputType?.startsWith('delete')) {
        this.digitosCentavosDigitados = 0;
      } else if (!cursorNaParteDecimal) {
        this.digitosCentavosDigitados = 0;
      }
      evento.target.value = formatado;

      const posicaoVirgula = formatado.indexOf(',');
      const posicaoCursor = cursorNaParteDecimal
        ? posicaoVirgula + 1 + Math.min(digitosDecimais, 2)
        : posicaoVirgula;
      this.$nextTick(() => evento.target.setSelectionRange(posicaoCursor, posicaoCursor));
    },

    formatarMoedaBrasileira(valor) {
      let texto = String(valor ?? '').trim().replace(/^R\$\s*/i, '').replace(/\s/g, '');
      if (!texto) return '';

      let inteiro = '';
      let centavos = '00';
      const formatoBrasileiro = texto.match(/^(\d+|\d{1,3}(?:\.\d{3})+)(?:,(\d{0,2}))?$/);
      const formatoNumerico = texto.match(/^(\d+)(?:\.(\d{1,2}))?$/);
      const partes = formatoBrasileiro || formatoNumerico;
      if (!partes) return '';

      inteiro = partes[1].replace(/\./g, '').replace(/^0+(?=\d)/, '');
      centavos = (partes[2] ?? '').padEnd(2, '0').slice(0, 2);
      inteiro = inteiro.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
      return `R$ ${inteiro},${centavos}`;
    },

    valorMonetarioPersistido(valor) {
      const formatado = this.formatarMoedaBrasileira(valor);
      if (!formatado) return valor;
      return formatado.replace(/^R\$\s*/, '').replace(/\./g, '').replace(',', '.');
    },

    aoSelecionarPcn(valor) {
      this.form.pcn = valor;
      if (valor) this.pcnLegado = '';
    },

    aoSelecionarPcr(valor) {
      this.form.pcr = valor;
      if (valor) this.pcrLegado = '';
    },

    async carregarCursos() {
      clearTimeout(this.buscaTimeout);

      this.buscaTimeout = setTimeout(async () => {
        this.carregando = true;
        this.mensagemErro = '';

        try {
          const params = { page: this.meta.current_page || 1, per_page: this.meta.per_page || 10 };

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

    irParaPagina(page) {
      const pagina = Number(page);
      if (!Number.isInteger(pagina) || pagina < 1 || pagina > (this.meta.last_page || 1) || this.carregando) {
        return;
      }
      this.meta.current_page = pagina;
      this.carregarCursos();
    },

    alterarRegistrosPorPagina(perPage) {
      const quantidade = Number(perPage);
      if (!Number.isInteger(quantidade) || quantidade < 1 || this.carregando) return;
      this.meta.per_page = quantidade;
      this.meta.current_page = 1;
      this.carregarCursos();
    },

    abrirNovo() {
      if (!this.podeEditar) {
        this.mensagemErro = 'Seu perfil não tem permissão para criar cursos.';
        return;
      }

      this.aplicarEstadoNovoLocal();
      this.empilharHistoricoFormulario('novo');
    },

    aplicarQueryDaRota() {
      const query = this.$route.query || {};
      if (query.curso_id) {
        const curso = this.cursos.find((item) => String(item.id) === String(query.curso_id))
          || { id: query.curso_id };
        this.abrirDetalhes(curso);
        return;
      }

      if (query.novo === '1' || query.novo === 'true') {
        this.abrirNovo();
        if (query.eixo) {
          this.form.eixo = String(query.eixo);
        }
      }
    },

    aplicarEstadoNovoLocal() {
      this.modo = 'novo';
      this.editandoId = null;
      this.abaForm = 'basico';
      this.form = this.formVazio();
      this.form.ciclo_id = this.cicloFormPadrao();
      this.errosCampo = {};
      this.pcnLegado = '';
      this.pcrLegado = '';
      this.valoresLegado = false;
      this.ultimoValorMonetarioValido = '';
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
      const valorPcn = this.valorSimNao(curso.pcn);
      const valorPcr = this.valorSimNao(curso.pcr);
      const valorMonetario = this.formatarMoedaBrasileira(curso.valores);
      this.pcnLegado = curso.pcn && !valorPcn ? String(curso.pcn) : '';
      this.pcrLegado = curso.pcr && !valorPcr ? String(curso.pcr) : '';
      this.valoresLegado = Boolean(curso.valores) && !valorMonetario;
      this.ultimoValorMonetarioValido = valorMonetario || String(curso.valores ?? '');
      this.errosCampo = {};
      this.form = {
        ciclo_id: curso.ciclo_id ? String(curso.ciclo_id) : this.cicloFormPadrao(),
        titulo: curso.titulo ?? '',
        eixo: curso.eixo ?? '',
        segmento: curso.segmento ?? '',
        programa: curso.programa ?? '',
        modalidade: curso.modalidade ?? '',
        status: curso.status ?? 'ATIVO',
        unidade: curso.unidade ?? '',
        unidades_oferta: unidadesOferta,
        carga_horaria: somenteNumeros(curso.carga_horaria).slice(0, 5),
        turmas: somenteNumeros(curso.turmas).slice(0, 4),
        codigo_processo: curso.codigo_processo ?? '',
        alunos: somenteNumeros(curso.alunos).slice(0, 5),
        instrutor: curso.instrutor ?? '',
        descricao: curso.descricao ?? '',
        codigo_dn: curso.codigo_dn ?? '',
        codigo_sig: curso.codigo_sig ?? '',
        identificacao: curso.identificacao ?? '',
        ultima_revisao: curso.ultima_revisao ?? '',
        processo_sei: curso.processo_sei ?? '',
        data_inicio: this.formatarDataInput(curso.data_inicio),
        data_fim: this.formatarDataInput(curso.data_fim),
        valores: valorMonetario || curso.valores || '',
        compativel_bolsa: this.valorSimNao(curso.compativel_bolsa) || curso.compativel_bolsa || '',
        comercial: this.valorSimNao(curso.comercial) || curso.comercial || '',
        pcn: valorPcn,
        pcr: valorPcr,
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
      this.errosCampo = {};
      this.pcnLegado = '';
      this.pcrLegado = '';
      this.valoresLegado = false;
      this.ultimoValorMonetarioValido = '';
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

    valorOpcaoOferta(opcao) {
      return Array.isArray(opcao.grupos)
        ? `regiao:${opcao.id}`
        : `estrutura:${opcao.nome}`;
    },

    selecionarEstruturaDaRegiao() {
      const estruturaDireta = this.unidadesOpcoes.find((opcao) => (
        !Array.isArray(opcao.grupos)
        && this.valorOpcaoOferta(opcao) === String(this.regiaoOfertaSelecionada)
      ));

      if (estruturaDireta?.nome) {
        const lista = this.form.unidades_oferta.includes(estruturaDireta.nome)
          ? this.form.unidades_oferta
          : [...this.form.unidades_oferta, estruturaDireta.nome];
        this.form.unidades_oferta = lista;
        this.form.unidade = lista[0] ?? '';
        return;
      }

      const grupos = this.regiaoOfertaAtual?.grupos ?? [];
      const estruturas = grupos.flatMap((grupo) => grupo.unidades ?? []);

      if (estruturas.length !== 1) {
        return;
      }

      const nome = estruturas[0].nome;
      if (!this.form.unidades_oferta.includes(nome)) {
        this.form.unidades_oferta = [...this.form.unidades_oferta, nome];
      }
      this.form.unidade = this.form.unidades_oferta[0] ?? '';
    },

    validarAba(abaId) {
      if (abaId === 'basico') {
        return combinarValidacoes(
          textoObrigatorio(this.regiaoOfertaSelecionada, 'Selecione a localidade / região.'),
          textoObrigatorio(this.form.segmento, 'Preencha o campo Segmento.'),
          textoObrigatorio(this.form.ciclo_id, 'Preencha o campo Ciclo de gestão.'),
          textoObrigatorio(this.form.codigo_processo, 'Preencha o campo Código do processo.'),
          this.form.codigo_processo && !/^[0-9.-]+$/.test(this.form.codigo_processo)
            ? 'O código do processo deve conter somente números, ponto (.) e hífen (-).'
            : '',
          textoObrigatorio(this.form.instrutor, 'Preencha o campo Instrutor(es).'),
          textoObrigatorio(this.form.descricao, 'Preencha o campo Descrição.'),
          this.form.unidades_oferta?.length ? '' : 'Selecione ao menos uma estrutura de oferta.',
          textoObrigatorio(this.form.eixo, 'Selecione o eixo.'),
          textoObrigatorio(this.form.titulo, 'O título do curso é obrigatório.'),
          tamanhoMaximo(this.form.titulo, 255, 'O título deve ter no máximo 255 caracteres.'),
          textoObrigatorio(this.form.carga_horaria, 'Informe a carga horária.'),
          validarInteiro(this.form.carga_horaria, { obrigatorio: true, rotulo: 'Carga horária', min: 1, max: 99999 }),
          validarInteiro(this.form.turmas, { obrigatorio: true, rotulo: 'Turmas', min: 0, max: 9999 }),
          validarInteiro(this.form.alunos, { obrigatorio: true, rotulo: 'Alunos', min: 0, max: 99999 }),
          this.form.codigo_processo
            ? tamanhoMaximo(this.form.codigo_processo, 100, 'O código do processo deve ter no máximo 100 caracteres.')
            : '',
          this.form.instrutor
            ? tamanhoMaximo(this.form.instrutor, 255, 'O instrutor deve ter no máximo 255 caracteres.')
            : '',
          this.form.descricao
            ? tamanhoMaximo(this.form.descricao, 5000, 'A descrição deve ter no máximo 5000 caracteres.')
            : '',
        );
      }

      if (abaId === 'tecnico') {
        return combinarValidacoes(
          textoObrigatorio(this.form.codigo_dn, 'Preencha o campo Cód. DN.'),
          this.form.codigo_dn && !/^[0-9.-]+$/.test(this.form.codigo_dn)
            ? 'O código DN deve conter somente números, ponto (.) e hífen (-).'
            : '',
          textoObrigatorio(this.form.identificacao, 'Preencha o campo Identificação.'),
          textoObrigatorio(this.form.ultima_revisao, 'Preencha o campo Última revisão.'),
          this.form.ultima_revisao && !/^\d{1,6}$/.test(this.form.ultima_revisao)
            ? 'Última atualização deve conter somente números e no máximo 6 algarismos.'
            : '',
          textoObrigatorio(this.form.status, 'Selecione o status.'),
          textoObrigatorio(this.form.modalidade, 'Selecione a modalidade.'),
          textoObrigatorio(this.form.codigo_sig, 'Informe o código SIG.'),
          this.form.codigo_sig && !/^[0-9.-]+$/.test(this.form.codigo_sig)
            ? 'O código SIG deve conter somente números, ponto (.) e hífen (-).'
            : '',
          tamanhoMaximo(this.form.codigo_sig, 100, 'O código SIG deve ter no máximo 100 caracteres.'),
          this.form.codigo_dn
            ? tamanhoMaximo(this.form.codigo_dn, 50, 'O código DN deve ter no máximo 50 caracteres.')
            : '',
          this.form.identificacao
            ? tamanhoMaximo(this.form.identificacao, 50, 'A identificação deve ter no máximo 50 caracteres.')
            : '',
          validarProcessoSei(this.form.processo_sei, { obrigatorio: true }),
          validarData(this.form.data_inicio, { obrigatorio: true, rotulo: 'Data de início' }),
          validarData(this.form.data_fim, { obrigatorio: true, rotulo: 'Data de término' }),
          validarOrdemDatas(
            this.form.data_inicio,
            this.form.data_fim,
            'A data de término deve ser igual ou posterior à data de início.',
          ),
        );
      }

      if (abaId === 'comercial') {
        return combinarValidacoes(
          textoObrigatorio(this.form.valores, 'Preencha o campo Valores.'),
          !this.valoresLegado && this.form.valores && !this.formatarMoedaBrasileira(this.form.valores)
            ? 'Informe um valor monetário válido.'
            : '',
          textoObrigatorio(this.form.compativel_bolsa, 'Preencha o campo Compatível com bolsa.'),
          textoObrigatorio(this.form.comercial, 'Preencha o campo Comercial.'),
          textoObrigatorio(this.form.pcn || this.pcnLegado, 'Selecione Sim ou Não para PCN.'),
          textoObrigatorio(this.form.pcr || this.pcrLegado, 'Selecione Sim ou Não para PCR.'),
          this.form.pcn && !['SIM', 'NÃO'].includes(this.form.pcn)
            ? 'Selecione Sim ou Não para PCN.'
            : '',
          this.form.pcr && !['SIM', 'NÃO'].includes(this.form.pcr)
            ? 'Selecione Sim ou Não para PCR.'
            : '',
          textoObrigatorio(this.form.observacoes, 'Preencha o campo Observações.'),
          this.form.observacoes
            ? tamanhoMaximo(this.form.observacoes, 2000, 'As observações devem ter no máximo 2000 caracteres.')
            : '',
          this.form.valores
            ? tamanhoMaximo(this.form.valores, 255, 'Valores deve ter no máximo 255 caracteres.')
            : '',
        );
      }

      return '';
    },

    validarFormulario() {
      const validacoesDeFormato = {
        codigo_processo: /^[0-9.-]+$/.test(this.form.codigo_processo || ''),
        codigo_dn: /^[0-9.-]+$/.test(this.form.codigo_dn || ''),
        codigo_sig: /^[0-9.-]+$/.test(this.form.codigo_sig || ''),
        ultima_revisao: /^\d{1,6}$/.test(this.form.ultima_revisao || ''),
        valores: Boolean(this.valoresLegado || !this.form.valores || this.formatarMoedaBrasileira(this.form.valores)),
      };
      const mensagensDeFormato = {
        codigo_processo: 'Use somente números, ponto (.) e hífen (-).',
        codigo_dn: 'Use somente números, ponto (.) e hífen (-).',
        codigo_sig: 'Use somente números, ponto (.) e hífen (-).',
        ultima_revisao: 'Use somente números, com no máximo 6 algarismos.',
        valores: 'Informe um valor monetário válido, usando vírgula para os centavos.',
      };
      const abaPorCampo = {
        codigo_processo: 'basico',
        codigo_dn: 'tecnico',
        codigo_sig: 'tecnico',
        ultima_revisao: 'tecnico',
        valores: 'comercial',
      };

      Object.entries(validacoesDeFormato).forEach(([campo, valido]) => {
        this.errosCampo[campo] = valido ? '' : mensagensDeFormato[campo];
      });
      const campoInvalido = Object.keys(validacoesDeFormato).find((campo) => !validacoesDeFormato[campo]);
      if (campoInvalido) {
        this.abaForm = abaPorCampo[campoInvalido];
        return mensagensDeFormato[campoInvalido];
      }

      for (const aba of this.abasForm) {
        const erro = this.validarAba(aba.id);
        if (erro) {
          this.abaForm = aba.id;
          return erro;
        }
      }

      return '';
    },

    selecionarAbaForm(abaId) {
      const destino = this.abasForm.findIndex((aba) => aba.id === abaId);
      if (destino < 0) {
        return;
      }

      const atual = Math.max(this.indiceAbaForm, 0);
      if (destino > atual) {
        for (let i = atual; i < destino; i += 1) {
          const erro = this.validarAba(this.abasForm[i].id);
          if (erro) {
            this.abaForm = this.abasForm[i].id;
            this.erroFormulario = erro;
            return;
          }
        }
      }

      this.erroFormulario = '';
      this.abaForm = abaId;
    },

    irAbaAnterior() {
      if (this.ehPrimeiraAbaForm) {
        return;
      }

      this.erroFormulario = '';
      this.abaForm = this.abasForm[this.indiceAbaForm - 1].id;
    },

    irAbaProxima() {
      if (this.ehUltimaAbaForm) {
        return;
      }

      const erro = this.validarAba(this.abaForm);
      if (erro) {
        this.erroFormulario = erro;
        return;
      }

      this.erroFormulario = '';
      this.abaForm = this.abasForm[this.indiceAbaForm + 1].id;
    },

    formatarProcessoSei: formatarProcessoSeiInput('processo_sei'),
    formatarCargaHoraria: formatarInteiroInput('carga_horaria', { maxDigitos: 5 }),
    formatarTurmas: formatarInteiroInput('turmas', { maxDigitos: 4 }),
    formatarAlunos: formatarInteiroInput('alunos', { maxDigitos: 5 }),

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
        segmento: this.form.segmento || null,
        programa: this.form.programa || null,
        modalidade: this.form.modalidade || null,
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
        valores: this.valoresLegado
          ? this.form.valores || null
          : this.form.valores
            ? this.valorMonetarioPersistido(this.form.valores)
            : null,
        compativel_bolsa: this.form.compativel_bolsa || null,
        comercial: this.form.comercial || null,
        pcn: this.form.pcn || this.pcnLegado || null,
        pcr: this.form.pcr || this.pcrLegado || null,
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

        const mensagem = this.mensagemErroValidacao(error, 'Não foi possível salvar o curso.');

        if (this.duplicidadeAberta) {
          this.erroDuplicidade = mensagem;
        } else {
          this.erroFormulario = mensagem;
        }
      } finally {
        this.salvando = false;
      }
    },

    mensagemErroValidacao(error, fallback) {
      const errors = error?.response?.data?.errors;
      if (errors && typeof errors === 'object') {
        const abaPorCampo = {
          eixo: 'basico',
          titulo: 'basico',
          carga_horaria: 'basico',
          turmas: 'basico',
          alunos: 'basico',
          codigo_processo: 'basico',
          instrutor: 'basico',
          descricao: 'basico',
          unidade: 'basico',
          unidades_oferta: 'basico',
          status: 'tecnico',
          modalidade: 'tecnico',
          codigo_sig: 'tecnico',
          codigo_dn: 'tecnico',
          identificacao: 'tecnico',
          processo_sei: 'tecnico',
          data_inicio: 'tecnico',
          data_fim: 'tecnico',
          observacoes: 'comercial',
          valores: 'comercial',
          pcn: 'comercial',
          pcr: 'comercial',
          compativel_bolsa: 'comercial',
          comercial: 'comercial',
        };

        const campo = Object.keys(errors)[0];
        if (campo && abaPorCampo[campo]) {
          this.abaForm = abaPorCampo[campo];
        }

        const primeiro = campo ? errors[campo] : null;
        if (Array.isArray(primeiro) && primeiro[0]) {
          return primeiro[0];
        }
        if (typeof primeiro === 'string' && primeiro) {
          return primeiro;
        }
      }

      return extrairErroApi(error, fallback);
    },

    aplicarCicloInicial() {
      const cicloQuery = this.$route.query.ciclo_id;
      const contexto = lerCicloContexto();
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

    aoMudarCicloGlobal(evento) {
      const ciclo = evento?.detail?.ciclo || lerCicloContexto();
      if (!ciclo?.id) {
        return;
      }

      if (String(this.filtros.ciclo_id) === String(ciclo.id)) {
        return;
      }

      this.cicloInicializado = true;
      this.filtros.ciclo_id = String(ciclo.id);
      this.lembrarCicloSelecionado();
      this.carregarCursos();
    },

    lembrarCicloSelecionado() {
      if (!this.filtros.ciclo_id || this.filtros.ciclo_id === 'todos') {
        return;
      }

      const ciclo = this.ciclos.find((item) => String(item.id) === String(this.filtros.ciclo_id));

      if (ciclo) {
        salvarCicloContexto(ciclo);
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

      const contexto = lerCicloContexto();
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

      if (texto.length > 2000) {
        this.erroDuplicidade = 'A justificativa deve ter no máximo 2000 caracteres.';
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
      return this.textoPadronizado(status) || '—';
    },

    formatarValorExibicao(valor) {
      return this.formatarMoedaBrasileira(valor) || this.valorCampo(valor);
    },

    valorCampo(valor) {
      if (valor === null || valor === undefined || valor === '') {
        return 'Não informado';
      }

      return valor;
    },

    rotuloSimNao(valor) {
      const normalizado = this.valorSimNao(valor);
      return normalizado ? this.textoPadronizado(normalizado) : this.valorCampo(valor);
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

    rotuloOrigem,

    textoSincronizacao,

    async abrirDetalhes(curso) {
      this.detalheAberto = true;
      this.cursoDetalhe = null;
      this.carregandoDetalhe = true;
      this.erroDetalhe = '';

      try {
        const { data } = await window.axios.get(`/api/cursos/${curso.id}`);
        this.cursoDetalhe = data.curso ?? curso;
        this.dadosDoCiclo = data.dados_do_ciclo || [];
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
      this.dadosDoCiclo = [];
      this.formDadosCiclo = null;
    },

    abrirEdicaoDadosCiclo(linha) {
      this.formDadosCiclo = {
        id: linha.id,
        codigo: linha.codigo || '',
        unidade: linha.unidade || '',
        turmas: linha.turmas || '',
        alunos: linha.alunos || '',
        instrutores: linha.instrutores || '',
      };
      this.erroDadosCiclo = '';
    },

    fecharEdicaoDadosCiclo() {
      this.formDadosCiclo = null;
      this.erroDadosCiclo = '';
    },

    async salvarDadosCiclo() {
      if (!this.formDadosCiclo?.id) return;
      this.salvandoDadosCiclo = true;
      this.erroDadosCiclo = '';
      try {
        await window.axios.put(`/api/curso-execucoes/${this.formDadosCiclo.id}`, {
          curso_id: this.cursoDetalhe?.id,
          codigo: this.formDadosCiclo.codigo || null,
          unidade: this.formDadosCiclo.unidade || null,
          turmas: this.formDadosCiclo.turmas || null,
          alunos: this.formDadosCiclo.alunos || null,
          instrutores: this.formDadosCiclo.instrutores || null,
        });
        this.fecharEdicaoDadosCiclo();
        if (this.cursoDetalhe) {
          await this.abrirDetalhes(this.cursoDetalhe);
        }
      } catch (error) {
        this.erroDadosCiclo = extrairErroApi(error, 'Não foi possível salvar os dados do ciclo.');
      } finally {
        this.salvandoDadosCiclo = false;
      }
    },

    editarDoDetalhe() {
      if (!this.cursoDetalhe) {
        return;
      }

      this.abrirEdicao(this.cursoDetalhe);
    },

    async excluirCurso(curso) {
      const confirmar = window.confirm(
        `Excluir o curso "${curso.titulo}"? Ele pode ser restaurado por um administrador na Auditoria.`
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
