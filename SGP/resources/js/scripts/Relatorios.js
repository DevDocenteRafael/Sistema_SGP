import { UNIDADES } from './unidades';
import { podeConsultarDados } from './auth';
import TabelaContador from '../components/crud/TabelaContador.vue';

const ICONS = {
  cursos: '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/></svg>',
  metas: '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>',
  pca: '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 12h4"/><path d="M10 8h4"/><path d="M14 21v-3a2 2 0 0 0-4 0v3"/><path d="M6 10H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2h-2"/><path d="M6 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16"/></svg>',
  eixos: '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" x2="12" y1="20" y2="10"/><line x1="18" x2="18" y1="20" y2="4"/><line x1="6" x2="6" y1="20" y2="16"/></svg>',
  visitas: '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg>',
  horas: '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
  acoes: '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg>',
  eventos: '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/></svg>',
};

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

export default {
  name: 'Relatorios',
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
      registros: [],
      metaApi: {},
      filtros: {
        busca: '',
        ano: '',
        unidade: '',
        eixo: '',
        status: '',
      },
      unidadesBase: UNIDADES,
      eixosBase: EIXOS_PADRAO,
      buscaTimer: null,
    };
  },

  computed: {
    podeConsultar() {
      return podeConsultarDados();
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
  },

  created() {
    if (!this.podeConsultar) {
      this.erro = 'Você não tem permissão para consultar relatórios.';
      this.carregandoCatalogo = false;
      return;
    }
    this.carregarCatalogo();
  },

  methods: {
    icone(nome) {
      return ICONS[nome] || ICONS.cursos;
    },

    temFiltro(nome) {
      return (this.selecionado?.filtros || []).includes(nome);
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
        if (Array.isArray(data.meta?.unidades) && data.meta.unidades.length) {
          this.unidadesBase = data.meta.unidades;
        }
      } catch (error) {
        this.erro = error.response?.data?.message || 'Não foi possível carregar o catálogo de relatórios.';
      } finally {
        this.carregandoCatalogo = false;
      }
    },

    selecionar(item) {
      this.selecionado = item;
      this.mensagem = '';
      this.erro = '';
      this.filtros = { busca: '', ano: '', unidade: '', eixo: '', status: '' };
      this.carregarPrevias();
    },

    voltarCatalogo() {
      this.selecionado = null;
      this.registros = [];
      this.metaApi = {};
      this.mensagem = '';
      this.erro = '';
      this.carregarCatalogo();
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
        if (! valor) {
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
