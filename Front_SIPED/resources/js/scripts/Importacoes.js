import { podeImportarDados } from './auth';
import { mixinHistoricoCatalogo } from './formularioHistorico';
import { lerCicloContexto } from './cicloContexto';
import HistoricoImportacoes from '../components/importacoes/HistoricoImportacoes.vue';
import DocumentoPdfUpload from '../components/importacoes/DocumentoPdfUpload.vue';
import { ehPdf } from '../utils/documentos';

const FILTROS_POR_MODULO = {
  cursos: ['status', 'eixo', 'unidade', 'tipo'],
  'plano-de-metas': ['status', 'segmento', 'tipo', 'ano'],
  pcas: ['status', 'eixo', 'unidade', 'ano'],
  eixos: ['status', 'eixo', 'unidade', 'ano'],
  'visitas-tecnicas': ['status', 'eixo', 'unidade'],
  'horas-pedagogicas': ['status', 'eixo', 'ano'],
  'acoes-extensivas': ['status', 'eixo', 'tipo'],
  eventos: ['status', 'eixo', 'unidade', 'ano'],
};

const CAMPOS_BUSCA = [
  'titulo', 'curso', 'nome', 'assunto', 'pessoa', 'unidade', 'eixo',
  'segmento', 'status', 'tipo', 'processo_sei', 'numero_sei', 'codigo_sig',
];

function filtrosVazios() {
  return {
    busca: '',
    status: '',
    eixo: '',
    unidade: '',
    tipo: '',
    ano: '',
    segmento: '',
  };
}

function previaVazia(colunas = [], label = '') {
  return {
    aba: '',
    total: 0,
    ignoradas: 0,
    erros: [],
    linhas: [],
    colunas_preview: colunas,
    label,
    resumo_acoes: null,
    ciclo: null,
    incompletos: 0,
  };
}

const ROTULOS_ACAO = {
  novo: 'Novo',
  atualizar: 'Atualizar',
  sem_alteracao: 'Sem alteração',
  pendente: 'Pendente',
  erro: 'Ignorada',
};

export default {
  name: 'Importacoes',
  mixins: [mixinHistoricoCatalogo],
  components: { HistoricoImportacoes, DocumentoPdfUpload },

  data() {
    return {
      etapa: 'upload',
      catalogo: [],
      moduloAtivo: null,
      moduloKey: '',
      arquivo: null,
      processando: false,
      erro: '',
      mensagem: '',
      filtros: filtrosVazios(),
      previa: previaVazia(),
      _trocandoModulo: false,
    };
  },

  computed: {
    podeImportar() {
      return podeImportarDados();
    },

    opcoesModulos() {
      return this.catalogo.map((item) => ({
        value: item.key,
        label: item.label,
      }));
    },

    temErroBloqueante() {
      return (this.previa.erros || []).some((item) => item.bloqueante);
    },

    linhasValidas() {
      return (this.previa.linhas || []).filter((linha) => linha?.status_importacao !== 'erro').length;
    },

    linhasIgnoradas() {
      const comErro = (this.previa.linhas || []).filter((linha) => linha?.status_importacao === 'erro').length;
      return comErro + (this.previa.ignoradas || 0);
    },

    cicloSelecionadoNome() {
      return lerCicloContexto()?.nome || '';
    },

    resumoAcoesTexto() {
      const resumo = this.previa.resumo_acoes;
      if (!resumo) {
        return '';
      }

      const partes = [];
      if (resumo.novo) partes.push(`${resumo.novo} novo(s)`);
      if (resumo.atualizar) partes.push(`${resumo.atualizar} atualizar`);
      if (resumo.sem_alteracao) partes.push(`${resumo.sem_alteracao} sem alteração`);
      if (resumo.pendente) partes.push(`${resumo.pendente} pendente(s)`);
      if (resumo.incompleto) partes.push(`${resumo.incompleto} incompleto(s)`);

      return partes.length ? `Resumo: ${partes.join(' · ')}.` : '';
    },

    linhasFiltradas() {
      const linhas = Array.isArray(this.previa.linhas) ? this.previa.linhas : [];
      const busca = this.filtros.busca.trim().toLowerCase();

      return linhas.filter((linha) => {
        if (this.filtros.status && String(linha.status || '') !== String(this.filtros.status)) {
          return false;
        }
        if (this.filtros.eixo && String(linha.eixo || '') !== String(this.filtros.eixo)) {
          return false;
        }
        if (this.filtros.unidade && String(linha.unidade || '') !== String(this.filtros.unidade)) {
          return false;
        }
        if (this.filtros.tipo && String(linha.tipo || '') !== String(this.filtros.tipo)) {
          return false;
        }
        if (this.filtros.ano && String(linha.ano || '') !== String(this.filtros.ano)) {
          return false;
        }
        if (this.filtros.segmento && String(linha.segmento || '') !== String(this.filtros.segmento)) {
          return false;
        }

        if (!busca) {
          return true;
        }

        return CAMPOS_BUSCA.some((campo) => String(linha?.[campo] || '').toLowerCase().includes(busca));
      });
    },
  },

  async created() {
    await this.carregarCatalogo();
  },

  methods: {
    chaveModulo(item) {
      return item?.key || item?.modulo || item?.id || '';
    },

    temFiltro(nome) {
      const key = this.chaveModulo(this.moduloAtivo);
      return (FILTROS_POR_MODULO[key] || []).includes(nome);
    },

    opcoesFiltro(campo) {
      const set = new Set(
        (this.previa.linhas || [])
          .map((linha) => linha?.[campo])
          .filter((valor) => valor !== null && valor !== undefined && valor !== ''),
      );
      return Array.from(set).map(String).sort((a, b) => a.localeCompare(b, 'pt-BR'));
    },

    async carregarCatalogo() {
      if (!this.podeImportar) {
        this.catalogo = [];
        return;
      }

      try {
        const { data } = await window.axios.get('/api/importacoes');
        this.catalogo = data.data || [];

        const viewKey = this.$route?.query?.view;
        if (viewKey && this.catalogo.some((item) => this.chaveModulo(item) === String(viewKey))) {
          this.aplicarEstadoCatalogoDaRota(String(viewKey));
        } else if (this.catalogo.length) {
          this.selecionarModulo(this.catalogo[0], { replaceHistorico: true });
        }
      } catch (error) {
        this.erro = error.response?.data?.message || 'Não foi possível carregar os módulos de importação.';
      }
    },

    aoTrocarModulo(chave) {
      if (this._trocandoModulo || this.processando) {
        return;
      }

      const key = chave || this.moduloKey;
      const item = this.catalogo.find((entrada) => this.chaveModulo(entrada) === key);
      if (!item) {
        return;
      }

      this.selecionarModulo(item);
    },

    selecionarModulo(item, { replaceHistorico = false } = {}) {
      this.aplicarModuloLocal(item);

      if (replaceHistorico) {
        this.definirHistoricoCatalogo(this.chaveModulo(item), true);
      } else {
        this.empilharHistoricoCatalogo(this.chaveModulo(item));
      }
    },

    aplicarModuloLocal(item) {
      this._trocandoModulo = true;
      this.moduloKey = this.chaveModulo(item);
      this.$nextTick(() => {
        this._trocandoModulo = false;
      });

      const mesma = this.moduloAtivo && this.chaveModulo(this.moduloAtivo) === this.chaveModulo(item);
      if (mesma && this.etapa === 'upload' && !this.arquivo) {
        return;
      }

      this.moduloAtivo = item;
      this.etapa = 'upload';
      this.arquivo = null;
      this.filtros = filtrosVazios();
      this.previa = previaVazia(item.preview_columns || [], item.label);
      this.erro = '';
      this.mensagem = '';

      if (this.$refs.inputArquivo) {
        this.$refs.inputArquivo.value = '';
      }
    },

    aplicarEstadoCatalogoLocal() {
      this.etapa = 'upload';
      this.moduloAtivo = null;
      this.moduloKey = '';
      this.arquivo = null;
      this.filtros = filtrosVazios();
      this.previa = previaVazia();
      this.erro = '';
      if (this.$refs.inputArquivo) {
        this.$refs.inputArquivo.value = '';
      }
    },

    aplicarEstadoCatalogoDaRota(viewKey) {
      if (!viewKey) {
        if (this.catalogo.length) {
          this.aplicarModuloLocal(this.catalogo[0]);
        } else {
          this.aplicarEstadoCatalogoLocal();
        }
        return;
      }

      if (this.chaveModulo(this.moduloAtivo) === String(viewKey) && this.moduloKey === String(viewKey)) {
        return;
      }

      const item = this.catalogo.find((entrada) => this.chaveModulo(entrada) === String(viewKey));
      if (!item) {
        if (this.catalogo.length) {
          this.aplicarModuloLocal(this.catalogo[0]);
        }
        return;
      }

      this.aplicarModuloLocal(item);
    },

    async definirHistoricoCatalogo(chave, replace = false) {
      if (!this.$router || !this.$route || !chave) {
        return;
      }

      if (String(this.$route.query.view || '') === String(chave)) {
        return;
      }

      this._navCatalogoSilenciosa = true;
      try {
        const nav = replace ? this.$router.replace : this.$router.push;
        await nav.call(this.$router, {
          query: { ...this.$route.query, view: String(chave) },
        });
      } catch {
        // ignore
      } finally {
        this._navCatalogoSilenciosa = false;
      }
    },

    limparArquivo() {
      this.arquivo = null;
      this.erro = '';
      this.mensagem = '';
      if (this.$refs.inputArquivo) {
        this.$refs.inputArquivo.value = '';
      }
    },

    voltarUpload() {
      this.etapa = 'upload';
      this.filtros = filtrosVazios();
      this.previa = previaVazia(this.moduloAtivo?.preview_columns || [], this.moduloAtivo?.label || '');
      this.erro = '';
    },

    onArquivoSelecionado(event) {
      const file = event.target.files?.[0] || null;
      this.arquivo = file;
      this.erro = '';
      this.mensagem = '';
      // PDF não é planilha: segue o fluxo de documento/anexo.
      if (ehPdf(file)) {
        this.etapa = 'documento';
      }
    },

    aoEnviarDocumento(data) {
      this.voltarUpload();
      this.limparArquivo();
      this.mensagem = data?.message || 'Documento PDF guardado.';
      this.$refs.historico?.carregar(1);
    },

    formComArquivo(congelarCiclo = false) {
      const form = new FormData();
      form.append('arquivo', this.arquivo);
      const cicloId = congelarCiclo
        ? (this.previa.ciclo?.id || lerCicloContexto()?.id)
        : lerCicloContexto()?.id;
      if (cicloId) {
        form.append('ciclo_id', String(cicloId));
      }
      return form;
    },

    celula(linha, key) {
      const valor = linha?.[key];
      if (key === 'status_importacao') {
        return ROTULOS_ACAO[valor] || valor || '—';
      }
      return valor === null || valor === undefined || valor === '' ? '—' : valor;
    },

    async gerarPrevia() {
      if (!this.arquivo || this.processando || !this.moduloAtivo) return;

      this.processando = true;
      this.erro = '';
      this.mensagem = '';

      try {
        const { data } = await window.axios.post(
          `/api/importacoes/${this.moduloAtivo.key}/preview`,
          this.formComArquivo(),
          { headers: { 'Content-Type': 'multipart/form-data' } },
        );

        this.filtros = filtrosVazios();
        this.previa = {
          aba: data.aba || '',
          total: data.total || 0,
          ignoradas: data.ignoradas || 0,
          erros: data.erros || [],
          linhas: data.linhas || [],
          colunas_preview: data.colunas_preview || this.moduloAtivo.preview_columns || [],
          label: data.label || this.moduloAtivo.label,
          resumo_acoes: data.resumo_acoes || null,
          ciclo: data.ciclo || null,
          incompletos: data.incompletos || 0,
        };
        this.etapa = 'previa';

        if (!this.linhasValidas) {
          this.erro = `Nenhuma linha válida encontrada para ${this.previa.label}.`;
        }
      } catch (error) {
        if (error.response?.data?.tipo === 'documento') {
          this.etapa = 'documento';
          return;
        }
        this.erro = error.response?.data?.message || 'Não foi possível gerar a prévia.';
      } finally {
        this.processando = false;
      }
    },

    async confirmarImportacao() {
      if (!this.arquivo || this.processando || !this.linhasValidas || !this.moduloAtivo || this.temErroBloqueante) return;

      const resumo = this.previa.resumo_acoes || {};
      const partes = [
        resumo.novo ? `${resumo.novo} novo(s)` : null,
        resumo.atualizar ? `${resumo.atualizar} atualizar` : null,
        resumo.sem_alteracao ? `${resumo.sem_alteracao} sem alteração` : null,
        resumo.pendente ? `${resumo.pendente} pendente(s)` : null,
        resumo.incompleto ? `${resumo.incompleto} incompleto(s) — entram com aviso` : null,
        this.linhasIgnoradas ? `${this.linhasIgnoradas} linha(s) ignorada(s)` : null,
      ].filter(Boolean);
      const detalhe = partes.length ? `\n\n${partes.join(' · ')}.` : '';

      const cicloNome = this.previa.ciclo?.nome || this.cicloSelecionadoNome;
      const ok = window.confirm(
        `A importação de ${this.previa.label || this.moduloAtivo.label} fará upsert no ciclo ${cicloNome || 'selecionado'} e não apagará registros de outros ciclos.${detalhe}\n\nDeseja continuar?`,
      );
      if (!ok) return;

      this.processando = true;
      this.erro = '';
      this.mensagem = '';

      try {
        const { data } = await window.axios.post(
          `/api/importacoes/${this.moduloAtivo.key}/commit`,
          this.formComArquivo(true),
          { headers: { 'Content-Type': 'multipart/form-data' } },
        );

        const backupTotal = data.backup?.total;
        const backupPath = data.backup?.path;
        let msg = data.message || `Importação concluída: ${data.importados} registro(s).`;

        if (backupPath) {
          msg += ` Backup prévio: ${backupTotal ?? 0} registro(s) em ${backupPath}.`;
        }

        this.voltarUpload();
        this.limparArquivo();
        this.mensagem = msg;
        // Sem redirecionar para a Revisão de Dados: o histórico mostra os incompletos.
        this.$refs.historico?.abrirPorId(data.historico_id);
      } catch (error) {
        this.erro = error.response?.data?.message || 'Não foi possível concluir a importação.';
        this.$refs.historico?.carregar(1);
      } finally {
        this.processando = false;
      }
    },
  },
};
