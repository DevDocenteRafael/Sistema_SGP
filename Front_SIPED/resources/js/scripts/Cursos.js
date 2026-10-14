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
import { formatSelectLabel } from '../utils/selectOptions';

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
      buscaTimeout: null,
      anosDisponiveis: ['2026', '2025', '2024', '2023'],
      unidades: [],
      unidadesOpcoes: [],
      seletorEstruturasAberto: false,
      buscaEstrutura: '',
      valorEmEdicao: '',
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
    gruposOferta() {
      const grupos = [];
      const adicionadas = new Set();
      const adicionarEstrutura = (grupo, unidade) => {
        if (!unidade?.nome || !this.unidades.includes(unidade.nome) || adicionadas.has(unidade.nome)) {
          return;
        }

        grupo.unidades.push(unidade);
        adicionadas.add(unidade.nome);
      };

      this.unidadesOpcoes.forEach((opcao) => {
        if (Array.isArray(opcao.grupos)) {
          opcao.grupos.forEach((grupo) => {
            const novoGrupo = {
              tipo: `${opcao.id ?? opcao.nome}-${grupo.tipo ?? grupo.label}`,
              label: `${opcao.nome} — ${grupo.label}`,
              unidades: [],
            };
            (grupo.unidades ?? []).forEach((unidade) => adicionarEstrutura(novoGrupo, unidade));
            if (novoGrupo.unidades.length) grupos.push(novoGrupo);
          });
          return;
        }

        if (!opcao?.nome) return;
        let grupo = grupos.find((item) => item.tipo === (opcao.tipo || 'outras'));
        if (!grupo) {
          grupo = {
            tipo: opcao.tipo || 'outras',
            label: opcao.tipo || 'Outras estruturas',
            unidades: [],
          };
          grupos.push(grupo);
        }
        adicionarEstrutura(grupo, opcao);
      });

      const faltantes = this.unidades.filter((nome) => !adicionadas.has(nome));
      if (faltantes.length) {
        const grupo = { tipo: 'outras-estruturas', label: 'Outras estruturas', unidades: [] };
        faltantes.forEach((nome) => adicionarEstrutura(grupo, { nome }));
        grupos.push(grupo);
      }

      return grupos;
    },
    gruposOfertaFiltrados() {
      const termo = this.buscaEstrutura.trim().toLocaleLowerCase('pt-BR');
      return this.gruposOferta
        .map((grupo) => ({
          ...grupo,
          unidades: (grupo.unidades ?? []).filter((unidade) => (
            !termo || unidade.nome.toLocaleLowerCase('pt-BR').includes(termo)
          )),
        }))
        .filter((grupo) => grupo.unidades.length);
    },
    unidadesSelecionadasResumo() {
      return [...new Set(this.form.unidades_oferta)];
    },
    todasEstruturasSelecionadas() {
      return this.unidades.length > 0
        && this.unidades.every((nome) => this.form.unidades_oferta.includes(nome));
    },
    algumasEstruturasSelecionadas() {
      return this.unidadesSelecionadasResumo.length > 0 && !this.todasEstruturasSelecionadas;
    },
    resumoEstruturasSelecionadas() {
      if (!this.unidadesSelecionadasResumo.length) return '';
      if (this.todasEstruturasSelecionadas) return 'Todas as estruturas selecionadas';
      if (this.unidadesSelecionadasResumo.length === 1) return this.unidadesSelecionadasResumo[0];
      const quantidade = this.unidadesSelecionadasResumo.length;
      return `${quantidade} estruturas selecionadas`;
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
    document.addEventListener('click', this.fecharSeletorEstruturasAoClicarFora);
    await Promise.all([
      this.carregarCursos(),
      this.carregarUnidadesOferta(),
    ]);
    this.aplicarQueryDaRota();
  },
  beforeUnmount() {
    window.removeEventListener(CICLO_CONTEXTO_EVENTO, this.aoMudarCicloGlobal);
    document.removeEventListener('click', this.fecharSeletorEstruturasAoClicarFora);
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
    formatarOpcaoSelect(valor) {
      return formatSelectLabel(valor);
    },

    limparFiltros() {
      const cicloId = this.filtros.ciclo_id;
      this.filtros = {
        busca: '',
        ciclo_id: cicloId,
        ano: '',
        eixo: '',
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
    },
    formVazio() {
      return {
        ciclo_id: '',
        titulo: '',
        eixo: '',
        segmento: '',
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

    validarIdentificacaoInput(evento) {
      const valor = evento.target.value;
      const permitido = valor.replace(/\D/g, '').slice(0, 6);
      const caracterInvalido = valor.replace(/\d/g, '').length > 0;
      if (valor !== permitido) {
        evento.target.value = permitido;
      }
      this.form.identificacao = permitido;
      this.errosCampo.identificacao = caracterInvalido || valor.length > 6
        ? 'A Identificação deve conter somente números e de 4 a 6 algarismos.'
        : permitido && permitido.length < 4
          ? 'A Identificação deve conter de 4 a 6 algarismos.'
          : '';
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

    iniciarEdicaoValor(evento) {
      if (this.valoresLegado) {
        evento.target.select();
        this.valorEmEdicao = String(this.form.valores ?? '');
        return;
      }

      const prefixoMoeda = this.valorEmEdicao.match(/^R\$\s*/i)?.[0] ?? '';
      this.valorEmEdicao = this.valorEmEdicao.slice(prefixoMoeda.length);
      evento.target.value = this.valorEmEdicao;
      const cursor = Math.max(0, evento.target.selectionStart - prefixoMoeda.length);
      evento.target.setSelectionRange(cursor, cursor);
    },

    aoEditarValor(evento) {
      const valor = evento.target.value;
      const prefixoMoeda = valor.match(/^R\$\s*/i)?.[0] ?? '';
      const texto = valor.slice(prefixoMoeda.length);
      const permitido = texto.replace(/[^0-9.,]/g, '');
      const posicaoCursor = Math.max(0, evento.target.selectionStart - prefixoMoeda.length);
      const cursorFiltrado = texto.slice(0, posicaoCursor).replace(/[^0-9.,]/g, '').length;
      this.valorEmEdicao = permitido;
      evento.target.value = permitido;
      this.valoresLegado = false;
      if (permitido !== texto) {
        this.errosCampo.valores = 'Use somente números, ponto para milhar e vírgula para centavos.';
        this.$nextTick(() => evento.target.setSelectionRange(cursorFiltrado, cursorFiltrado));
        return;
      }

      this.errosCampo.valores = '';
    },

    finalizarEdicaoValor() {
      if (this.valoresLegado && this.valorEmEdicao === this.form.valores) {
        return true;
      }

      const normalizado = this.normalizarValorMonetario(this.valorEmEdicao);
      if (!normalizado) {
        this.errosCampo.valores = this.valorEmEdicao
          ? 'Informe um valor monetário brasileiro válido, com até 12 algarismos inteiros e 2 decimais.'
          : 'Preencha o campo Valores.';
        return false;
      }

      this.form.valores = this.formatarValorMonetario(normalizado);
      this.valorEmEdicao = this.form.valores;
      this.valoresLegado = false;
      this.errosCampo.valores = '';
      return true;
    },

    normalizarValorMonetario(valor) {
      const texto = String(valor ?? '').trim().replace(/^R\$\s*/i, '').replace(/\s/g, '');
      const partes = texto.match(/^(\d+|\d{1,3}(?:\.\d{3})+)(?:,(\d{0,2}))?$/);
      if (!partes) return '';
      if (texto.includes(',') && partes[2] === '') return '';

      const inteiro = partes[1].replace(/\./g, '').replace(/^0+(?=\d)/, '');
      if (inteiro.length > 12) return '';
      const centavos = (partes[2] ?? '').padEnd(2, '0');
      return `${inteiro}.${centavos}`;
    },

    formatarValorMonetario(valor) {
      const [inteiro, centavos] = String(valor).split('.');
      const milhar = inteiro.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
      return `R$ ${milhar},${centavos}`;
    },

    formatarMoedaBrasileira(valor) {
      let texto = String(valor ?? '').trim().replace(/^R\$\s*/i, '').replace(/\s/g, '');
      if (!texto) return '';

      const normalizadoExistente = texto.match(/^(\d{1,12})(?:\.(\d{1,2}))?$/);
      if (normalizadoExistente) {
        return this.formatarValorMonetario(`${normalizadoExistente[1]}.${(normalizadoExistente[2] ?? '').padEnd(2, '0')}`);
      }

      const normalizado = this.normalizarValorMonetario(texto);
      return normalizado ? this.formatarValorMonetario(normalizado) : '';
    },

    valorMonetarioPersistido(valor) {
      return this.normalizarValorMonetario(valor) || valor;
    },

    alternarTodasEstruturas() {
      const todas = [...new Set(this.unidades)];
      if (!todas.length) return;

      if (this.todasEstruturasSelecionadas) {
        this.form.unidades_oferta = this.form.unidades_oferta.filter((nome) => !todas.includes(nome));
      } else {
        this.form.unidades_oferta = todas;
      }
      this.form.unidade = this.form.unidades_oferta[0] ?? '';
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
      this.valorEmEdicao = '';
      this.seletorEstruturasAberto = false;
      this.buscaEstrutura = '';
      this.errosCampo = {};
      this.pcnLegado = '';
      this.pcrLegado = '';
      this.valoresLegado = false;
      this.erroFormulario = '';
      this.fecharDetalhes();
    },

    abrirEdicao(curso) {
      this.aplicarEstadoEdicaoLocal(curso);
      this.empilharHistoricoFormulario('editar', curso.id);
    },

    aplicarEstadoEdicaoLocal(curso) {
      const unidadesOferta = Array.isArray(curso.unidades_oferta) && curso.unidades_oferta.length
        ? [...new Set(curso.unidades_oferta)]
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
      this.valorEmEdicao = valorMonetario
        ? valorMonetario
        : String(curso.valores ?? '');
      this.seletorEstruturasAberto = false;
      this.buscaEstrutura = '';
      this.errosCampo = {};
      this.form = {
        ciclo_id: curso.ciclo_id ? String(curso.ciclo_id) : this.cicloFormPadrao(),
        titulo: curso.titulo ?? '',
        eixo: curso.eixo ?? '',
        segmento: curso.segmento ?? '',
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
      this.valorEmEdicao = '';
      this.errosCampo = {};
      this.pcnLegado = '';
      this.pcrLegado = '';
      this.valoresLegado = false;
    },

    toggleUnidade(unidade) {
      const lista = [...new Set(this.form.unidades_oferta)];
      const indice = lista.indexOf(unidade);

      if (indice >= 0) {
        lista.splice(indice, 1);
      } else {
        lista.push(unidade);
      }

      this.form.unidades_oferta = lista;
      this.form.unidade = lista[0] ?? '';
    },

    definirUnidade(unidade, selecionada) {
      const lista = [...new Set(this.form.unidades_oferta)];
      const indice = lista.indexOf(unidade);
      if (selecionada && indice === -1) {
        lista.push(unidade);
      } else if (!selecionada && indice !== -1) {
        lista.splice(indice, 1);
      }

      this.form.unidades_oferta = lista;
      this.form.unidade = lista[0] ?? '';
    },

    unidadeSelecionada(unidade) {
      return this.form.unidades_oferta.includes(unidade);
    },

    alternarSeletorEstruturas() {
      this.seletorEstruturasAberto = !this.seletorEstruturasAberto;
      if (!this.seletorEstruturasAberto) {
        this.buscaEstrutura = '';
      }
    },

    fecharSeletorEstruturasAoClicarFora(evento) {
      const seletor = this.$refs.seletorEstruturas;
      if (this.seletorEstruturasAberto && seletor && !seletor.contains(evento.target)) {
        this.seletorEstruturasAberto = false;
        this.buscaEstrutura = '';
      }
    },

    validarAba(abaId) {
      if (abaId === 'basico') {
        return combinarValidacoes(
          textoObrigatorio(this.form.segmento, 'Preencha o campo Segmento.'),
          textoObrigatorio(this.form.ciclo_id, 'Preencha o campo Ciclo de gestão.'),
          textoObrigatorio(this.form.codigo_processo, 'Preencha o campo Código do processo.'),
          this.form.codigo_processo && !/^[0-9.-]+$/.test(this.form.codigo_processo)
            ? 'O código do processo deve conter somente números, ponto (.) e hífen (-).'
            : '',
          textoObrigatorio(this.form.instrutor, 'Preencha o campo Instrutor(es).'),
          textoObrigatorio(this.form.descricao, 'Preencha o campo Descrição.'),
          this.form.unidades_oferta?.length ? '' : 'Selecione ao menos uma localidade / região.',
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
            /^\d{4,6}$/.test(this.form.identificacao || '')
            ? ''
              : 'A Identificação deve conter de 4 a 6 algarismos.',
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
          textoObrigatorio(this.valorEmEdicao || this.form.valores, 'Preencha o campo Valores.'),
          !this.valoresLegado && !this.normalizarValorMonetario(this.valorEmEdicao || this.form.valores)
            ? 'Informe um valor monetário brasileiro válido, com até 12 algarismos inteiros e 2 decimais.'
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
        identificacao: /^\d{4,6}$/.test(this.form.identificacao || ''),
        valores: Boolean(this.valoresLegado || this.normalizarValorMonetario(this.valorEmEdicao || this.form.valores)),
      };
      const mensagensDeFormato = {
        codigo_processo: 'Use somente números, ponto (.) e hífen (-).',
        codigo_dn: 'Use somente números, ponto (.) e hífen (-).',
        codigo_sig: 'Use somente números, ponto (.) e hífen (-).',
        ultima_revisao: 'Use somente números, com no máximo 6 algarismos.',
        identificacao: 'A Identificação deve conter de 4 a 6 algarismos.',
        valores: 'Informe um valor monetário brasileiro válido, com até 12 algarismos inteiros e 2 decimais.',
      };
      const abaPorCampo = {
        codigo_processo: 'basico',
        codigo_dn: 'tecnico',
        codigo_sig: 'tecnico',
        ultima_revisao: 'tecnico',
        identificacao: 'tecnico',
        valores: 'comercial',
      };

      Object.entries(validacoesDeFormato).forEach(([campo, valido]) => {
        this.errosCampo[campo] = valido ? '' : mensagensDeFormato[campo];
      });
      const campoInvalido = ['identificacao', 'codigo_processo', 'codigo_dn', 'codigo_sig', 'ultima_revisao', 'valores']
        .find((campo) => !validacoesDeFormato[campo]);
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

      if (!this.valoresLegado || this.valorEmEdicao !== this.form.valores) {
        this.finalizarEdicaoValor();
      }
      const erroValidacao = this.validarFormulario();

      if (erroValidacao) {
        this.erroFormulario = erroValidacao;
        return;
      }

      this.salvando = true;
      this.erroFormulario = '';
      this.mensagemSucesso = '';

      const unidadesOferta = [...new Set(this.form.unidades_oferta)];
      const payload = {
        ciclo_id: this.form.ciclo_id || null,
        titulo: this.form.titulo,
        eixo: this.form.eixo,
        segmento: this.form.segmento || null,
        modalidade: this.form.modalidade || null,
        status: this.form.status,
        unidade: unidadesOferta[0] || this.form.unidade || null,
        unidades_oferta: unidadesOferta.length ? unidadesOferta : null,
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
          : this.valorMonetarioPersistido(this.valorEmEdicao || this.form.valores) || null,
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
