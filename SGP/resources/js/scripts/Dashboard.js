import { UNIDADES } from './unidades';

const EIXOS_PADRAO = [
  'Gastronomia',
  'Ambiente e Saúde',
  'Gestão e Moda',
  'Tecnologia e Economia Criativa',
  'Beleza e Cuidado Pessoal',
  'Turismo e Hospitalidade',
  'Comunicação e Audiovisual',
  'Artes e Design',
];

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
      contagens: {
        visitas: 0,
        horas: 0,
        acoes: 0,
        eventos: 0,
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
        status: ['ATIVO', 'INATIVO'],
      },
      unidadesBase: UNIDADES,
    };
  },

  computed: {
    temFiltro() {
      return Boolean(this.filtros.ano || this.filtros.unidade || this.filtros.eixo || this.filtros.status);
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

        if (this.filtros.eixo && curso.eixo !== this.filtros.eixo) {
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

    metricCards() {
      const filtrados = this.cursosFiltrados;
      const total = filtrados.length;
      const ativos = filtrados.filter((curso) => this.statusCurso(curso) === 'ATIVO').length;
      const inativos = filtrados.filter((curso) => this.statusCurso(curso) === 'INATIVO').length;
      const eixos = this.filtros.eixo
        ? 1
        : new Set(filtrados.map((curso) => curso.eixo).filter(Boolean)).size;
      const unidades = this.filtros.unidade
        ? 1
        : new Set(
          filtrados.flatMap((curso) => [
            curso.unidade,
            ...(Array.isArray(curso.unidades_oferta) ? curso.unidades_oferta : []),
          ]).filter(Boolean),
        ).size;

      return [
        {
          label: 'Total de Cursos',
          value: total,
          sub: 'portfólio',
          icon: this.iconPortfolio,
        },
        {
          label: 'Cursos Ativos',
          value: ativos,
          accent: true,
          icon: this.iconCheck,
        },
        {
          label: 'Cursos Inativos',
          value: inativos,
          warn: true,
          icon: this.iconInactive,
        },
        {
          label: 'Eixos Tecnológicos',
          value: eixos,
          sub: 'eixos',
          icon: this.iconEixos,
        },
        {
          label: 'Unidades',
          value: unidades,
          sub: 'unidades',
          icon: this.iconUnidades,
        },
        {
          label: 'Visitas Técnicas',
          value: this.contagens.visitas,
          sub: 'processos',
          icon: this.iconVisitas,
        },
        {
          label: 'Horas Pedagógicas',
          value: this.contagens.horas,
          sub: 'solicitações',
          icon: this.iconHoras,
        },
        {
          label: 'Ações Extensivas',
          value: this.contagens.acoes,
          sub: 'cadastradas',
          icon: this.iconAcoes,
        },
        {
          label: 'Eventos',
          value: this.contagens.eventos,
          sub: 'cadastrados',
          icon: this.iconEventos,
        },
      ];
    },

    chartEixos() {
      const contagem = this.contarPor(this.cursosParaGraficos, 'eixo');
      return this.enriquecerBarras(
        Object.entries(contagem)
          .map(([label, value], index) => ({
            label,
            value,
            color: CORES_EIXO[index % CORES_EIXO.length],
          }))
          .filter((item) => item.value > 0)
          .sort((a, b) => b.value - a.value),
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
        cards: [
          { title: 'Total no período', value: total, subtitle: '100% do total', percent: 100, color: '#003F7D' },
          { title: 'Realizadas', value: realizadas, subtitle: `${this.percentual(realizadas, total)}% do total`, percent: this.percentual(realizadas, total), color: '#15803d' },
          { title: 'Pendentes', value: pendentes, subtitle: `${this.percentual(pendentes, total)}% do total`, percent: this.percentual(pendentes, total), color: '#a16207' },
          { title: 'Fora do prazo', value: foraPrazo, subtitle: `${this.percentual(foraPrazo, total)}% do total`, percent: this.percentual(foraPrazo, total), color: '#b91c1c' },
          { title: 'Dentro do prazo', value: dentroPrazo, subtitle: `${this.percentual(dentroPrazo, total)}% do total`, percent: this.percentual(dentroPrazo, total), color: '#1d4ed8' },
          { title: 'Devolvidas/Recusadas', value: devolvidas, subtitle: `${this.percentual(devolvidas, total)}% do total`, percent: this.percentual(devolvidas, total), color: '#7e22ce' },
        ],
        porEixo: this.listaContagem(this.visitas, 'eixo'),
        porStatus: this.listaContagem(this.visitas, 'status'),
        porUnidade: this.listaContagem(this.visitas, 'unidade'),
        porResponsavel: this.listaContagem(this.visitas, 'responsavel'),
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
        cards: [
          { title: 'Total no período', value: total, subtitle: '100% do total', percent: 100, color: '#003F7D' },
          { title: 'Concluídas', value: concluidas, subtitle: `${this.percentual(concluidas, total)}% do total`, percent: this.percentual(concluidas, total), color: '#15803d' },
          { title: 'Aprovadas', value: aprovadas, subtitle: `${this.percentual(aprovadas, total)}% do total`, percent: this.percentual(aprovadas, total), color: '#047857' },
          { title: 'Em análise', value: emAnalise, subtitle: `${this.percentual(emAnalise, total)}% do total`, percent: this.percentual(emAnalise, total), color: '#a16207' },
          { title: 'Solicitadas', value: solicitadas, subtitle: `${this.percentual(solicitadas, total)}% do total`, percent: this.percentual(solicitadas, total), color: '#1d4ed8' },
          { title: 'Recusadas', value: recusadas, subtitle: `${this.percentual(recusadas, total)}% do total`, percent: this.percentual(recusadas, total), color: '#b91c1c' },
          { title: 'Inativas', value: inativas, subtitle: `${this.percentual(inativas, total)}% do total`, percent: this.percentual(inativas, total), color: '#6b7280' },
        ],
        porEixo: this.listaContagem(this.horas, 'eixo'),
        porStatus: this.listaContagem(this.horas, 'status'),
        porSegmento: this.listaContagem(this.horas, 'segmento'),
        porPessoa: this.listaContagem(this.horas, 'pessoa'),
      };
    },

    iconPortfolio() {
      return `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/></svg>`;
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

  created() {
    this.carregarDashboard();
  },

  methods: {
    async carregarDashboard() {
      this.carregando = true;
      this.erro = '';

      try {
        const [cursosRes, visitasRes, horasRes, acoesRes, eventosRes] = await Promise.all([
          window.axios.get('/api/cursos'),
          window.axios.get('/api/visitas-tecnicas'),
          window.axios.get('/api/horas-pedagogicas'),
          window.axios.get('/api/acoes-extensivas'),
          window.axios.get('/api/eventos'),
        ]);

        this.courses = cursosRes.data.data ?? [];

        const eixosApi = cursosRes.data.meta?.eixos ?? [];
        const eixosBanco = this.courses.map((curso) => curso.eixo).filter(Boolean);
        const eixos = Array.from(new Set([...eixosApi, ...eixosBanco, ...EIXOS_PADRAO])).sort();

        const statusApi = cursosRes.data.meta?.status ?? [];
        const statusBanco = this.courses.map((curso) => curso.status).filter(Boolean);
        const status = Array.from(new Set([...statusApi, ...statusBanco])).sort();

        this.meta = {
          eixos,
          status: status.length ? status : ['ATIVO', 'INATIVO'],
        };

        this.visitas = visitasRes.data.data ?? [];
        this.horas = horasRes.data.data ?? [];
        this.contagens = {
          visitas: visitasRes.data.meta?.total_geral ?? this.visitas.length,
          horas: horasRes.data.meta?.total_geral ?? this.horas.length,
          acoes: acoesRes.data.meta?.total_geral ?? (acoesRes.data.data?.length || 0),
          eventos: eventosRes.data.meta?.total_geral ?? (eventosRes.data.data?.length || 0),
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

    listaContagem(lista, campo) {
      const contagem = this.contarPor(lista, campo);

      return this.enriquecerBarras(
        Object.entries(contagem)
          .map(([label, value]) => ({ label, value }))
          .sort((a, b) => b.value - a.value),
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
      return String(tipo || '').trim();
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
