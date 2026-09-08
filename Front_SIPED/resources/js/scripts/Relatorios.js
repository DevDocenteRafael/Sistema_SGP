import { hidratarUnidadesSelect } from './unidadesApi';
import { podeConsultarDados } from './auth';
import TabelaContador from '../components/crud/TabelaContador.vue';
import { mixinHistoricoCatalogo } from './formularioHistorico';

const EIXOS_PADRAO = [
  'Gastronomia',
  'Ambiente e Saúde',
  'Gestão e Moda',
  'Tecnologia e Economia Criativa',
  'Beleza e Cuidado Pessoal',
  'Turismo e Hospitalidade',
  'Comunicação e Audiovisual',
  'Artes e Design',
  'Gestão e Negócios',
];

const ORDEM_RELATORIOS = [
  'resolucoes',
  'termos-referencia',
  'cursos',
  'plano-de-metas',
  'pcas',
  'eixos',
  'jornadas-pedagogicas',
  'visitas-tecnicas',
  'horas-pedagogicas',
  'acoes-extensivas',
  'eventos',
];

function filtrosVazios() {
  return {
    busca: '',
    ano: '',
    unidade: '',
    eixo: '',
    status: '',
    categoria: '',
    setor: '',
    relator: '',
  };
}

export default {
  name: 'Relatorios',
  mixins: [mixinHistoricoCatalogo],
  components: { TabelaContador },

  data() {
    return {
      carregandoCatalogo: true,
      carregandoPrevias: false,
      exportando: false,
      erro: '',
      mensagem: '',
      catalogo: [],
      selecionado: null,
      relatorioKey: '',
      registros: [],
      metaApi: {},
      filtros: filtrosVazios(),
      unidadesBase: [],
      eixosBase: EIXOS_PADRAO,
      buscaTimer: null,
      _trocandoRelatorio: false,
    };
  },

  computed: {
    podeConsultar() {
      return podeConsultarDados();
    },

    catalogoOrdenado() {
      const peso = (key) => {
        const indice = ORDEM_RELATORIOS.indexOf(key);
        return indice === -1 ? 999 : indice;
      };

      return [...this.catalogo].sort((a, b) => peso(a.key) - peso(b.key));
    },

    opcoesRelatorios() {
      return this.catalogoOrdenado.map((item) => ({
        value: item.key,
        label: item.label,
      }));
    },

    colunasPreview() {
      if (!this.selecionado) return [];
      const keys = this.selecionado.preview_keys || [];
      const mapa = Object.fromEntries((this.selecionado.colunas || []).map((c) => [c.key, c]));
      return keys.map((key) => mapa[key] || { key, label: key });
    },

    anosDisponiveis() {
      const anos = new Set();
      this.registros.forEach((item) => {
        const candidatos = [
          item.ano,
          item.ultima_revisao,
          item.semestre,
          item.data,
          item.data_solicitacao,
          item.data_inicio,
          item.data_fim,
          item.data_pre_jornada,
          item.data_inicio_vigencia,
          item.data_fim_vigencia,
          item.prazo_deadline,
        ];
        candidatos.forEach((valor) => {
          const ano = String(valor || '').slice(0, 4);
          if (/^\d{4}$/.test(ano)) anos.add(ano);
        });
      });
      const atual = new Date().getFullYear();
      anos.add(String(atual));
      anos.add(String(atual - 1));
      return Array.from(anos).sort((a, b) => b.localeCompare(a));
    },

    unidadesDisponiveis() {
      const set = new Set([
        ...this.unidadesBase,
        ...(this.metaApi.unidades || []),
        ...this.registros.map((r) => r.unidade).filter(Boolean),
      ]);
      return Array.from(set).sort();
    },

    eixosDisponiveis() {
      const set = new Set([
        ...this.eixosBase,
        ...(Array.isArray(this.metaApi.eixos) ? this.metaApi.eixos : []),
        ...this.registros.map((r) => r.eixo).filter(Boolean),
      ]);
      return Array.from(set).sort();
    },

    statusDisponiveis() {
      const daMeta = this.metaApi.status;
      if (Array.isArray(daMeta) && daMeta.length) {
        return daMeta;
      }
      const set = new Set(this.registros.map((r) => r.status).filter(Boolean));
      return Array.from(set).sort();
    },

    categoriasDisponiveis() {
      const set = new Set([
        ...(this.metaApi.categorias || []),
        ...this.registros.map((r) => r.categoria).filter(Boolean),
      ]);
      return Array.from(set).sort();
    },

    setoresDisponiveis() {
      const set = new Set([
        ...(this.metaApi.setores || []),
        ...this.registros.map((r) => r.setor).filter(Boolean),
      ]);
      return Array.from(set).sort();
    },

    relatoresDisponiveis() {
      const set = new Set(this.registros.map((r) => r.relator).filter(Boolean));
      return Array.from(set).sort();
    },

    temFiltrosAtivos() {
      return Object.values(this.filtros).some((valor) => valor !== '' && valor != null);
    },

    /**
     * Resumo derivado apenas dos registros já retornados pela API (sem inventar dados).
     */
    resumoRelatorio() {
      const total = this.metaApi.total ?? this.registros.length;
      const cards = [
        {
          label: 'Total filtrado',
          value: total,
          help: 'Quantidade de registros que atendem aos filtros atuais (conforme a API).',
        },
      ];

      const porStatus = {};
      this.registros.forEach((item) => {
        const status = String(item.status || '').trim();
        if (!status) return;
        porStatus[status] = (porStatus[status] || 0) + 1;
      });

      const topStatus = Object.entries(porStatus)
        .sort((a, b) => b[1] - a[1])
        .slice(0, 4);

      topStatus.forEach(([status, qtd]) => {
        cards.push({
          label: status,
          value: qtd,
          help: `Registros com status “${status}” na prévia carregada.`,
        });
      });

      return cards;
    },
  },

  created() {
    if (!this.podeConsultar) {
      this.erro = 'Você não tem permissão para consultar relatórios.';
      this.carregandoCatalogo = false;
      return;
    }
    this.carregarCatalogo();
    hidratarUnidadesSelect(this, ['unidadesBase'], { forcar: true });
  },

  methods: {
    temFiltro(nome) {
      return (this.selecionado?.filtros || []).includes(nome);
    },

    limparFiltros() {
      this.filtros = filtrosVazios();
      this.carregarPrevias();
    },

    valorCelula(linha, key) {
      const valor = linha?.[key];
      if (valor === null || valor === undefined || valor === '') return '—';
      if (typeof valor === 'boolean') return valor ? 'Sim' : 'Não';
      return valor;
    },

    async carregarCatalogo() {
      this.carregandoCatalogo = true;
      this.erro = '';

      try {
        const { data } = await window.axios.get('/api/relatorios');
        this.catalogo = data.data || [];
        if (Array.isArray(data.meta?.eixos) && data.meta.eixos.length) {
          this.eixosBase = data.meta.eixos;
        }

        const viewKey = this.$route?.query?.view;
        if (viewKey && this.catalogo.some((item) => item.key === String(viewKey))) {
          this.aplicarEstadoCatalogoDaRota(String(viewKey));
        } else if (this.catalogoOrdenado.length) {
          this.selecionar(this.catalogoOrdenado[0], { replaceHistorico: true });
        }
      } catch (error) {
        this.erro = error.response?.data?.message || 'Não foi possível carregar o catálogo de relatórios.';
      } finally {
        this.carregandoCatalogo = false;
      }
    },

    aoTrocarRelatorio(chave) {
      if (this._trocandoRelatorio) {
        return;
      }

      const key = chave || this.relatorioKey;
      const item = this.catalogo.find((entrada) => entrada.key === key);
      if (!item) {
        return;
      }

      this.selecionar(item);
    },

    selecionar(item, { replaceHistorico = false } = {}) {
      this.aplicarSelecaoLocal(item);

      if (replaceHistorico) {
        this.definirHistoricoCatalogo(item.key, true);
      } else {
        this.empilharHistoricoCatalogo(item.key);
      }
    },

    aplicarSelecaoLocal(item) {
      this._trocandoRelatorio = true;
      this.relatorioKey = item.key;
      this.$nextTick(() => {
        this._trocandoRelatorio = false;
      });

      if (this.selecionado?.key === item.key && this.registros.length) {
        return;
      }

      this.selecionado = item;
      this.mensagem = '';
      this.erro = '';
      this.filtros = filtrosVazios();
      this.carregarPrevias();
    },

    aplicarEstadoCatalogoLocal() {
      this.selecionado = null;
      this.relatorioKey = '';
      this.registros = [];
      this.metaApi = {};
      this.filtros = filtrosVazios();
      this.mensagem = '';
      this.erro = '';
    },

    aplicarEstadoCatalogoDaRota(viewKey) {
      if (!viewKey) {
        if (this.catalogoOrdenado.length) {
          this.aplicarSelecaoLocal(this.catalogoOrdenado[0]);
        } else {
          this.aplicarEstadoCatalogoLocal();
        }
        return;
      }

      if (this.selecionado?.key === String(viewKey) && this.relatorioKey === String(viewKey)) {
        return;
      }

      const item = this.catalogo.find((entrada) => String(entrada.key) === String(viewKey));
      if (!item) {
        if (this.catalogoOrdenado.length) {
          this.aplicarSelecaoLocal(this.catalogoOrdenado[0]);
        }
        return;
      }

      this.aplicarSelecaoLocal(item);
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

    aoBuscar() {
      if (this.buscaTimer) {
        clearTimeout(this.buscaTimer);
      }
      this.buscaTimer = setTimeout(() => {
        this.carregarPrevias();
      }, 300);
    },

    paramsFiltros() {
      const params = {};
      Object.entries(this.filtros).forEach(([chave, valor]) => {
        if (!valor) {
          return;
        }
        if (chave === 'busca' || this.temFiltro(chave)) {
          params[chave] = valor;
        }
      });
      return params;
    },

    async carregarPrevias() {
      if (!this.selecionado) return;

      this.carregandoPrevias = true;
      this.erro = '';

      try {
        const { data } = await window.axios.get(this.selecionado.api, {
          params: this.paramsFiltros(),
        });
        this.registros = Array.isArray(data.data) ? data.data : [];
        this.metaApi = data.meta || {};

        if (data.meta?.truncado) {
          this.mensagem = `Prévia limitada: ${data.meta.total_exibido ?? this.registros.length} de ${data.meta.total} registros. Use filtros ou exporte o PDF.`;
        } else {
          this.mensagem = '';
        }
      } catch (error) {
        this.registros = [];
        this.erro = error.response?.data?.message || 'Não foi possível carregar a prévia do relatório.';
      } finally {
        this.carregandoPrevias = false;
      }
    },

    async exportarPdf() {
      if (!this.selecionado || this.exportando) return;

      this.exportando = true;
      this.erro = '';
      this.mensagem = '';

      try {
        const response = await window.axios.get(`/api/relatorios/${this.selecionado.key}/pdf`, {
          params: this.paramsFiltros(),
          responseType: 'blob',
        });

        const contentType = response.headers['content-type'] || '';
        if (contentType.includes('application/json')) {
          const texto = await response.data.text();
          const json = JSON.parse(texto);
          throw new Error(json.message || 'Falha ao gerar o PDF.');
        }

        const blob = new Blob([response.data], { type: 'application/pdf' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        const hoje = new Date().toISOString().slice(0, 10).replace(/-/g, '');
        link.href = url;
        link.download = `relatorio-${this.selecionado.key}-${hoje}.pdf`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);

        this.mensagem = 'PDF gerado e baixado com sucesso.';
      } catch (error) {
        if (error.response?.data instanceof Blob) {
          try {
            const texto = await error.response.data.text();
            const json = JSON.parse(texto);
            this.erro = json.message || 'Não foi possível exportar o PDF.';
          } catch {
            this.erro = 'Não foi possível exportar o PDF.';
          }
        } else {
          this.erro = error.message || error.response?.data?.message || 'Não foi possível exportar o PDF.';
        }
      } finally {
        this.exportando = false;
      }
    },
  },
};
