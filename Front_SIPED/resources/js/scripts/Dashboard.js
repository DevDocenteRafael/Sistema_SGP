import { lerCicloContexto, CICLO_CONTEXTO_EVENTO } from './cicloContexto';
import { carregarUnidadesNomes } from './unidadesApi';
import { EIXOS_OFICIAIS, eixosIguais } from '../utils/catalogoOficial';

const EIXOS_PADRAO = [...EIXOS_OFICIAIS];

const CORES_EIXO = ['#003F7D', '#F57C00', '#0d9488', '#7c3aed', '#db2777', '#2563eb', '#ca8a04', '#64748b'];

export default {
  name: 'Dashboard',

  data() {
    return {
      carregando: true,
      erro: '',
      courses: [],
      visitas: [],
      horas: [],
      estruturas: [],
      contagens: {
        visitas: 0,
        horas: 0,
        acoes: 0,
        eventos: 0,
        resolucoes: 0,
        termos: 0,
        estruturas: 0,
        estruturas_faculdade: 0,
        estruturas_polo: 0,
        estruturas_unidade: 0,
        usuarios: 0,
        usuarios_ativos: 0,
        usuarios_inativos: 0,
      },
      distribuicoes: {
        horas: {},
        acoes: {},
        eventos: {},
        visitas: {},
      },
      resolucoesContagens: {
        no_prazo: 0,
        atencao: 0,
        vencidos: 0,
      },
      termosContagens: {
        no_prazo: 0,
        atencao: 0,
        vencidos: 0,
      },
      filtros: {
        grupo: 'gerais',
        ano: '',
        unidade: '',
        eixo: '',
        status: '',
      },
      meta: {
        eixos: EIXOS_PADRAO,
        status: ['ATIVO', 'INATIVO', 'EM REVISÃO'],
      },
      cicloNome: lerCicloContexto()?.nome || '',
      unidadesBase: [],
    };
  },

  computed: {
    temFiltro() {
      return Boolean(this.filtros.ano || this.filtros.unidade || this.filtros.eixo || this.filtros.status);
    },

    totalResolucoes() {
      return this.contagens.resolucoes;
    },

    totalTermos() {
      return this.contagens.termos;
    },

    cardsResolucoesPrazo() {
      return this.montarCardsPrazo(this.resolucoesContagens, this.contagens.resolucoes);
    },

    cardsTermosPrazo() {
      return this.montarCardsPrazo(this.termosContagens, this.contagens.termos);
    },

    anosDisponiveis() {
      const anos = new Set(
        this.courses
          .map((curso) => String(curso.ultima_revisao || curso.ano || '').slice(0, 4))
          .filter((ano) => /^\d{4}$/.test(ano)),
      );
      return Array.from(anos).sort((a, b) => b.localeCompare(a));
    },

    unidadesDisponiveis() {
      const unidades = new Set([
        ...this.unidadesBase,
        ...this.courses.flatMap((curso) => [
          curso.unidade,
          ...(Array.isArray(curso.unidades_oferta) ? curso.unidades_oferta : []),
        ]),
      ].filter(Boolean));

      return Array.from(unidades).sort();
    },

    cursosFiltrados() {
      return this.courses.filter((curso) => {
        if (this.filtros.ano) {
          const ano = String(curso.ultima_revisao || curso.ano || '').slice(0, 4);
          if (ano !== String(this.filtros.ano)) {
            return false;
          }
        }

        if (this.filtros.eixo && !eixosIguais(curso.eixo, this.filtros.eixo)) {
          return false;
        }

        if (this.filtros.status && this.statusCurso(curso) !== this.statusCurso({ status: this.filtros.status })) {
          return false;
        }

        if (this.filtros.unidade) {
          const unidades = [
            curso.unidade,
            ...(Array.isArray(curso.unidades_oferta) ? curso.unidades_oferta : []),
          ].filter(Boolean);

          if (!unidades.includes(this.filtros.unidade)) {
            return false;
          }
        }

        return true;
      });
    },

    cursosParaGraficos() {
      return this.cursosFiltrados;
    },

    totalCursos() {
      return this.cursosFiltrados.length;
    },

    cursosAtivos() {
      return this.cursosFiltrados.filter((curso) => this.statusCurso(curso) === 'ATIVO').length;
    },

    cursosInativos() {
      return this.cursosFiltrados.filter((curso) => this.statusCurso(curso) === 'INATIVO').length;
    },

    cursosEmRevisao() {
      return this.cursosFiltrados.filter((curso) => this.statusCurso(curso) === 'EM REVISAO').length;
    },

    totalEixos() {
      return this.meta.eixos.length;
    },

    metricCards() {
      const estruturas = this.filtros.unidade
        ? this.estruturas.filter((estrutura) => estrutura.nome === this.filtros.unidade)
        : this.estruturas;
      const estruturasAtivas = estruturas.filter((estrutura) => estrutura.ativo === true);
      const estruturasInativas = estruturas.length - estruturasAtivas.length;
      const estruturasPorTipo = {
        faculdade: estruturasAtivas.filter((estrutura) => estrutura.tipo === 'faculdade').length,
        polo: estruturasAtivas.filter((estrutura) => estrutura.tipo === 'polo').length,
        unidade: estruturasAtivas.filter((estrutura) => ['unidade', 'cep'].includes(estrutura.tipo)).length,
      };

      const estruturasSub = this.filtros.unidade
        ? (estruturas.length ? 'filtrada' : '')
        : [
          estruturasPorTipo.faculdade ? `${estruturasPorTipo.faculdade} fac.` : null,
          estruturasPorTipo.polo ? `${estruturasPorTipo.polo} polo` : null,
          estruturasPorTipo.unidade ? `${estruturasPorTipo.unidade} unid.` : null,
        ].filter(Boolean).join(' · ') || 'cadastradas';

      return [
        {
          label: 'Resoluções',
          category: 'Portfólio',
          value: this.totalResolucoes,
          icon: this.iconResolucoes,
          statuses: this.cardsResolucoesPrazo.filter((status) => status.value > 0),
        },
        {
          label: 'Termos de Referência',
          category: 'Portfólio',
          value: this.totalTermos,
          icon: this.iconPortfolio,
          statuses: this.cardsTermosPrazo.filter((status) => status.value > 0),
        },
        {
          label: 'Total de Cursos',
          category: 'Portfólio',
          value: this.totalCursos,
          icon: this.iconPortfolio,
          statuses: [
            { title: 'Ativos', value: this.cursosAtivos, color: '#16A34A', term: 'ativo' },
            { title: 'Inativos', value: this.cursosInativos, color: '#DC2626', term: 'inativo' },
            { title: 'Em revisão', value: this.cursosEmRevisao, color: '#2563EB', term: 'em revisao' },
          ].filter((status) => status.value > 0),
        },
        {
          label: 'Estruturas',
          category: 'Estruturas',
          value: estruturas.length,
          sub: estruturasSub,
          icon: this.iconUnidades,
          statuses: [
            { title: 'Ativos', value: estruturasAtivas.length, color: '#16A34A' },
            { title: 'Inativos', value: estruturasInativas, color: '#DC2626' },
          ].filter((status) => status.value > 0),
        },
        {
          label: 'Horas Pedagógicas',
          category: 'Solicitações',
          value: this.contagens.horas,
          icon: this.iconHoras,
          statuses: this.montarCardsDistribuicao(this.distribuicoes.horas, [
            { key: 'em_andamento', title: 'Em andamento', color: '#2563EB' },
            { key: 'pendente', title: 'Pendentes', color: '#F59E0B' },
            { key: 'concluida', title: 'Concluídas', color: '#16A34A' },
            { key: 'cancelada', title: 'Canceladas', color: '#DC2626' },
          ]),
        },
        {
          label: 'Ações Extensivas',
          category: 'Cadastradas',
          value: this.contagens.acoes,
          icon: this.iconAcoes,
          statuses: this.montarCardsDistribuicao(this.distribuicoes.acoes, [
            { key: 'alta', title: 'Alta', color: '#DC2626' },
            { key: 'media', title: 'Média', color: '#F59E0B' },
            { key: 'baixa', title: 'Baixa', color: '#16A34A' },
            { key: 'resolvido', title: 'Resolvido', color: '#2563EB' },
          ]),
        },
        {
          label: 'Eventos',
          category: 'Cadastrados',
          value: this.contagens.eventos,
          icon: this.iconEventos,
          statuses: this.montarCardsDistribuicao(this.distribuicoes.eventos, [
            { key: 'planejado', title: 'Planejados', color: '#2563EB' },
            { key: 'realizado', title: 'Realizados', color: '#16A34A' },
            { key: 'cancelado', title: 'Cancelados', color: '#DC2626' },
          ]),
        },
        {
          label: 'Visitas Técnicas',
          category: 'Processos',
          value: this.contagens.visitas,
          icon: this.iconVisitas,
          statuses: this.montarCardsDistribuicao(this.distribuicoes.visitas, [
            { key: 'pendente', title: 'Pendentes', color: '#F59E0B' },
            { key: 'em_andamento', title: 'Em andamento', color: '#2563EB' },
            { key: 'realizada', title: 'Realizadas', color: '#16A34A' },
            { key: 'cancelada', title: 'Canceladas', color: '#DC2626' },
            { key: 'atrasada', title: 'Atrasadas', color: '#DC2626' },
          ]),
        },
        {
          label: 'Usuários',
          category: 'Acesso',
          value: this.contagens.usuarios,
          icon: this.iconUsuarios,
          statuses: [
            { title: 'Ativos', value: this.contagens.usuarios_ativos, color: '#16A34A' },
            { title: 'Inativos', value: this.contagens.usuarios_inativos, color: '#DC2626' },
          ].filter((status) => status.value > 0),
        },
      ];
    },

    chartEixos() {
      const cores = {
        'Gastronomia e Turismo': '#F57C00',
        'Ambiente e Saúde': '#0d9488',
        'Gestão e Moda': '#003F7D',
        'Tecnologia e Economia Criativa': '#7c3aed',
        'Beleza e Cuidado Pessoal': '#db2777',
      };

      return this.enriquecerBarras(
        EIXOS_PADRAO.map((label) => ({
          label,
          value: this.cursosParaGraficos.filter((curso) => eixosIguais(curso.eixo, label)).length,
          color: cores[label] || '#64748b',
        })),
      );
    },

    chartTipos() {
      const contagem = {};
      this.cursosParaGraficos.forEach((curso) => {
        const tipo = this.normalizarTipo(curso.tipo);
        if (!tipo) return;
        contagem[tipo] = (contagem[tipo] || 0) + 1;
      });

      return this.enriquecerBarras(
        Object.entries(contagem)
          .map(([label, value]) => ({ label, value }))
          .sort((a, b) => b.value - a.value)
          .slice(0, 6),
        { orange: true },
      );
    },

    chartStatus() {
      const contagem = {};

      this.cursosFiltrados.forEach((curso) => {
        const status = curso.status || 'Sem status';
        contagem[status] = (contagem[status] || 0) + 1;
      });

      return this.enriquecerBarras(
        Object.entries(contagem)
          .map(([label, value], index) => ({
            label,
            value,
            color: label === 'ATIVO'
              ? '#003F7D'
              : (label === 'INATIVO' ? '#ef4444' : CORES_EIXO[index % CORES_EIXO.length]),
          }))
          .sort((a, b) => b.value - a.value),
      );
    },

    chartCargaHoraria() {
      const faixas = [
        { label: 'Até 100h', min: 0, max: 100, value: 0 },
        { label: '101 a 300h', min: 101, max: 300, value: 0 },
        { label: '301 a 800h', min: 301, max: 800, value: 0 },
        { label: 'Acima de 800h', min: 801, max: 99999, value: 0 },
      ];

      this.cursosParaGraficos.forEach((curso) => {
        const carga = Number(String(curso.carga_horaria || '').replace(/\D/g, '')) || 0;
        faixas.forEach((faixa) => {
          if (carga >= faixa.min && carga <= faixa.max) {
            faixa.value += 1;
          }
        });
      });

      return this.enriquecerBarras(faixas, { orange: true });
    },

    resumoPorEixo() {
      const contagem = this.contarPor(this.cursosParaGraficos, 'eixo');
      const itens = Object.entries(contagem)
        .map(([eixo, count]) => ({ label: eixo, value: count }))
        .filter((item) => item.value > 0)
        .sort((a, b) => b.value - a.value);

      return this.enriquecerBarras(itens, { orange: true });
    },
    indicadoresVisitas() {
      const total = this.visitas.length;
      const realizadas = this.visitas.filter((item) => this.statusInclui(item.status, ['realizad', 'conclu'])).length;
      const pendentes = this.visitas.filter((item) => this.statusInclui(item.status, ['pendente', 'andamento', 'aguard'])).length;
      const foraPrazo = this.visitas.filter((item) => this.estaForaDoPrazo(item)).length;
      const devolvidas = this.visitas.filter((item) => this.statusInclui(item.status, ['cancel', 'devolv', 'recus'])).length;
      const dentroPrazo = Math.max(total - foraPrazo - devolvidas, 0);

      return {
        total,
        chipsFluxo: [
          { title: 'Realizadas', value: realizadas, color: '#15803d', subtitle: `${this.percentual(realizadas, total)}% do total` },
          { title: 'Pendentes', value: pendentes, color: '#a16207', subtitle: `${this.percentual(pendentes, total)}% do total` },
          { title: 'No prazo', value: dentroPrazo, color: '#003F7D', subtitle: `${this.percentual(dentroPrazo, total)}% do total` },
          { title: 'Fora do prazo', value: foraPrazo, color: '#c2410c', subtitle: `${this.percentual(foraPrazo, total)}% do total` },
          { title: 'Devolvidas', value: devolvidas, color: '#b91c1c', subtitle: `${this.percentual(devolvidas, total)}% do total` },
        ],
        porEixo: this.listaContagem(this.visitas, 'eixo'),
        porStatus: this.listaContagem(this.visitas, 'status', { orange: true }).slice(0, 6),
        porUnidade: this.listaContagem(this.visitas, 'unidade').slice(0, 8),
        porResponsavel: this.listaContagem(this.visitas, 'responsavel').slice(0, 8),
      };
    },

    indicadoresHoras() {
      const total = this.horas.length;
      const concluidas = this.horas.filter((item) => this.statusInclui(item.status, ['conclu'])).length;
      const aprovadas = this.horas.filter((item) => this.statusInclui(item.status, ['aprovad'])).length;
      const emAnalise = this.horas.filter((item) => this.statusInclui(item.status, ['analise', 'análise', 'andamento'])).length;
      const solicitadas = this.horas.filter((item) => this.statusInclui(item.status, ['solicit', 'pendente'])).length;
      const recusadas = this.horas.filter((item) => this.statusInclui(item.status, ['recus', 'cancel', 'devolv'])).length;
      const inativas = this.horas.filter((item) => item.ativo === false || this.statusInclui(item.status, ['inativ'])).length;

      return {
        total,
        chipsFluxo: [
          { title: 'Concluídas', value: concluidas, color: '#15803d', subtitle: `${this.percentual(concluidas, total)}% do total` },
          { title: 'Aprovadas', value: aprovadas, color: '#003F7D', subtitle: `${this.percentual(aprovadas, total)}% do total` },
          { title: 'Em análise', value: emAnalise, color: '#a16207', subtitle: `${this.percentual(emAnalise, total)}% do total` },
          { title: 'Solicitadas', value: solicitadas, color: '#F57C00', subtitle: `${this.percentual(solicitadas, total)}% do total` },
          { title: 'Recusadas', value: recusadas, color: '#b91c1c', subtitle: `${this.percentual(recusadas, total)}% do total` },
          { title: 'Inativas', value: inativas, color: '#6b7280', subtitle: `${this.percentual(inativas, total)}% do total` },
        ],
        porEixo: this.listaContagem(this.horas, 'eixo'),
        porStatus: this.listaContagem(this.horas, 'status', { orange: true }).slice(0, 6),
        porSegmento: this.listaContagem(this.horas, 'segmento').slice(0, 8),
        porPessoa: this.listaContagem(this.horas, 'pessoa').slice(0, 8),
      };
    },

    iconPortfolio() {
      return `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/></svg>`;
    },
    iconResolucoes() {
      return `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7z"/><path d="M14 2v5h5"/><path d="M8 13h8"/><path d="M8 17h8"/></svg>`;
    },
    iconCheck() {
      return `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>`;
    },
    iconInactive() {
      return `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>`;
    },
    iconEixos() {
      return `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" x2="12" y1="20" y2="10"/><line x1="18" x2="18" y1="20" y2="4"/><line x1="6" x2="6" y1="20" y2="16"/></svg>`;
    },
    iconUnidades() {
      return `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>`;
    },
    iconVisitas() {
      return `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg>`;
    },
    iconUsuarios() {
      return `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M20 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>`;
    },
    iconHoras() {
      return `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>`;
    },
    iconAcoes() {
      return `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/><rect width="20" height="14" x="2" y="6" rx="2"/></svg>`;
    },
    iconEventos() {
      return `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/></svg>`;
    },
  },

  mounted() {
    window.addEventListener(CICLO_CONTEXTO_EVENTO, this.carregarDashboard);
  },
  beforeUnmount() {
    window.removeEventListener(CICLO_CONTEXTO_EVENTO, this.carregarDashboard);
  },
  created() {
    this.carregarDashboard();
  },

  activated() {
    this.carregarDashboard();
  },

  watch: {
    '$route'(para, de) {
      if (para?.name === 'dashboard' && de?.name !== 'dashboard') {
        this.carregarDashboard();
      }
    },
  },

  methods: {
    async carregarDashboard() {
      this.carregando = true;
      this.erro = '';

      try {
        const { data } = await window.axios.get('/api/dashboard', { params: { ciclo_id: lerCicloContexto()?.id } });
        const payload = data.data || {};

        this.cicloNome = payload.meta?.ciclo?.nome || lerCicloContexto()?.nome || '';
        this.courses = payload.cursos ?? [];
        this.visitas = payload.visitas ?? [];
        this.horas = payload.horas ?? [];
        this.estruturas = payload.estruturas ?? [];
        this.distribuicoes = {
          horas: {},
          acoes: {},
          eventos: {},
          visitas: {},
          ...(payload.distribuicoes || {}),
        };

        const eixosApi = Array.isArray(payload.meta?.eixos) && payload.meta.eixos.length
          ? payload.meta.eixos.filter((eixo) => EIXOS_PADRAO.includes(eixo))
          : [];
        const eixos = eixosApi.length === EIXOS_PADRAO.length ? eixosApi : [...EIXOS_PADRAO];

        const statusApi = payload.meta?.status ?? [];
        const statusBanco = this.courses.map((curso) => curso.status).filter(Boolean);
        const status = Array.from(new Set([...statusApi, ...statusBanco])).sort();

        this.meta = {
          eixos,
          status: status.length ? status : ['ATIVO', 'INATIVO', 'EM REVISÃO'],
        };

        this.unidadesBase = await carregarUnidadesNomes({ forcar: true });

        this.contagens = {
          visitas: 0,
          horas: 0,
          acoes: 0,
          eventos: 0,
          resolucoes: 0,
          termos: 0,
          estruturas: 0,
          estruturas_faculdade: 0,
          estruturas_polo: 0,
          estruturas_unidade: 0,
          ...(payload.contagens || {}),
        };
        this.resolucoesContagens = {
          no_prazo: 0,
          atencao: 0,
          vencidos: 0,
          ...(payload.resolucoes_contagens || {}),
        };
        this.termosContagens = {
          no_prazo: 0,
          atencao: 0,
          vencidos: 0,
          ...(payload.termos_contagens || {}),
        };
      } catch (error) {
        this.erro = this.extrairErro(error, 'Não foi possível carregar os dados do dashboard.');
      } finally {
        this.carregando = false;
      }
    },

    limparFiltros() {
      this.filtros.ano = '';
      this.filtros.unidade = '';
      this.filtros.eixo = '';
      this.filtros.status = '';
    },

    statusCurso(curso) {
      return String(curso?.status || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toUpperCase();
    },

    contarPor(lista, campo) {
      return lista.reduce((acc, item) => {
        const chave = item[campo];
        if (!chave) return acc;
        acc[chave] = (acc[chave] || 0) + 1;
        return acc;
      }, {});
    },

    listaContagem(lista, campo, opcoes = {}) {
      const contagem = this.contarPor(lista, campo);

      return this.enriquecerBarras(
        Object.entries(contagem)
          .map(([label, value]) => ({ label, value }))
          .sort((a, b) => b.value - a.value),
        opcoes,
      );
    },

    enriquecerBarras(items, { orange = false } = {}) {
      const total = items.reduce((sum, item) => sum + (Number(item.value) || 0), 0);
      const max = Math.max(...items.map((item) => Number(item.value) || 0), 1);

      return items.map((item, index) => {
        const value = Number(item.value) || 0;

        return {
          ...item,
          value,
          color: item.color || (orange ? '#F57C00' : CORES_EIXO[index % CORES_EIXO.length]),
          share: total ? Math.round((value / total) * 100) : 0,
          bar: Math.round((value / max) * 100),
          texto: value === 1 ? '1 registro' : `${value} registros`,
        };
      });
    },

    normalizarTipo(tipo) {
      const bruto = String(tipo || '').trim().replace(/\s+/g, ' ');
      if (!bruto) return '';

      const chave = bruto
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase();

      const mapa = {
        aperfeicoamento: 'Aperfeiçoamento',
        'aperfeicoamento/atualizacao': 'Aperfeiçoamento/Atualização',
        'qualificacao profissional': 'Qualificação Profissional',
        'habilitacao tecnica': 'Habilitação Técnica',
      };

      return mapa[chave] || bruto.replace(/\w\S*/g, (palavra) => {
        const lower = palavra.toLowerCase();
        if (['de', 'da', 'do', 'das', 'dos', 'e', 'ou'].includes(lower)) {
          return lower;
        }
        return lower.charAt(0).toUpperCase() + lower.slice(1);
      });
    },

    montarCardsPrazo(contagens, total) {
      // Semáforo de três estados: Vigente / Atenção / Vencida.
      const itens = [
        { title: 'Vigente', key: 'no_prazo', color: '#16A34A' },
        { title: 'Atenção', key: 'atencao', color: '#F59E0B' },
        { title: 'Vencida', key: 'vencidos', color: '#DC2626' },
      ];

      return itens.map((item) => {
        const value = Number(contagens?.[item.key] || 0);
        return {
          ...item,
          value,
          subtitle: `${this.percentual(value, total)}% do total`,
        };
      });
    },

    montarCardsDistribuicao(contagens, categorias) {
      const statuses = categorias.map((categoria) => ({
        ...categoria,
        value: Number(contagens?.[categoria.key] || 0),
      }));

      if (Number(contagens?.sem_classificacao || 0) > 0) {
        statuses.push({
          title: 'Sem classificação',
          value: Number(contagens.sem_classificacao),
          color: '#6B7280',
        });
      }

      if (Number(contagens?.outros || 0) > 0) {
        statuses.push({
          title: 'Outros',
          value: Number(contagens.outros),
          color: '#6B7280',
        });
      }

      return statuses.filter((status) => status.value > 0);
    },

    percentual(parte, total) {
      if (!total) return 0;
      return Math.round((parte / total) * 100);
    },

    statusInclui(status, termos) {
      const valor = String(status || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase();

      return termos.some((termo) => valor.includes(termo));
    },

    estaForaDoPrazo(item) {
      if (this.statusInclui(item.status, ['atrasad', 'fora'])) {
        return true;
      }

      if (!item.prazo_limite) {
        return false;
      }

      const prazo = new Date(String(item.prazo_limite).slice(0, 10));
      const hoje = new Date();
      hoje.setHours(0, 0, 0, 0);

      return prazo < hoje && !this.statusInclui(item.status, ['realizad', 'conclu', 'cancel']);
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
