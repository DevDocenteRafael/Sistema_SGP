export default {
  name: 'Dashboard',
  data() {
    return {
      carregando: true,
      erro: '',
      courses: [],
      filtros: {
        grupo: 'gerais',
        ano: '',
        unidade: '',
        eixo: '',
        status: '',
      },
      meta: {
        eixos: [],
        status: [],
      },
    };
  },
  computed: {
    anosDisponiveis() {
      const anos = new Set(this.courses.map((curso) => curso.ultima_revisao?.slice(0, 4)).filter(Boolean));
      return Array.from(anos).sort();
    },
    unidadesDisponiveis() {
      const unidades = new Set(this.courses
        .flatMap((curso) => [curso.unidade, ...(Array.isArray(curso.unidades_oferta) ? curso.unidades_oferta : [])])
        .filter(Boolean));
      return Array.from(unidades).sort();
    },
    metricCards() {
      return [
        {
          label: 'Total de Cursos',
          value: this.totalCursos,
          icon: this.iconPortfolio,
        },
        {
          label: 'Cursos Ativos',
          value: this.cursosAtivos,
          icon: this.iconCheck,
        },
        {
          label: 'Cursos Inativos',
          value: this.cursosInativos,
          icon: this.iconInactive,
        },
        {
          label: 'Eixos Tecnológicos',
          value: this.eixosCadastrados,
          icon: this.iconEixos,
        },
      ];
    },
    totalCursos() {
      return this.courses.length;
    },
    cursosAtivos() {
      return this.courses.filter((curso) => curso.status === 'ATIVO').length;
    },
    cursosInativos() {
      return this.courses.filter((curso) => curso.status === 'INATIVO').length;
    },
    eixosCadastrados() {
      return [...new Set(this.courses.map((curso) => curso.eixo).filter(Boolean))].length;
    },
    chartEixos() {
      const contagem = this.courses.reduce((acc, curso) => {
        if (!curso.eixo) {
          return acc;
        }
        acc[curso.eixo] = (acc[curso.eixo] ?? 0) + 1;
        return acc;
      }, {});
      return this.buildChartData(contagem, this.meta.eixos);
    },
    chartTipos() {
      const contagem = this.courses.reduce((acc, curso) => {
        if (!curso.tipo) {
          return acc;
        }
        acc[curso.tipo] = (acc[curso.tipo] ?? 0) + 1;
        return acc;
      }, {});
      return this.buildChartData(contagem, []);
    },
    chartStatus() {
      const contagem = this.courses.reduce((acc, curso) => {
        if (!curso.status) {
          return acc;
        }
        acc[curso.status] = (acc[curso.status] ?? 0) + 1;
        return acc;
      }, {});
      return this.buildChartData(contagem, this.meta.status);
    },
    chartCargaHoraria() {
      const faixas = {
        '≤100h': 0,
        '101–300h': 0,
        '301–800h': 0,
        '>800h': 0,
      };
      this.courses.forEach((curso) => {
        const carga = Number(curso.carga_horaria) || 0;
        if (carga <= 100) faixas['≤100h'] += 1;
        else if (carga <= 300) faixas['101–300h'] += 1;
        else if (carga <= 800) faixas['301–800h'] += 1;
        else faixas['>800h'] += 1;
      });
      return Object.entries(faixas).map(([label, value]) => ({ label, value }));
    },
    resumoPorEixo() {
      const contagem = this.courses.reduce((acc, curso) => {
        const eixo = curso.eixo || 'Sem eixo';
        acc[eixo] = (acc[eixo] ?? 0) + 1;
        return acc;
      }, {});
      return this.meta.eixos.map((eixo) => ({ eixo, count: contagem[eixo] ?? 0 }));
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
  },
  created() {
    this.carregarDashboard();
  },
  methods: {
    async carregarDashboard() {
      this.carregando = true;
      this.erro = '';
      try {
        const params = {};
        if (this.filtros.ano) params.ano = this.filtros.ano;
        if (this.filtros.eixo) params.eixo = this.filtros.eixo;
        if (this.filtros.status) params.status = this.filtros.status;
        const { data } = await window.axios.get('/api/cursos', { params });
        this.courses = data.data ?? [];
        this.meta = { ...this.meta, ...(data.meta ?? {}) };
      } catch (error) {
        this.erro = this.extrairErro(error, 'Não foi possível carregar os dados do dashboard.');
      } finally {
        this.carregando = false;
      }
    },
    buildChartData(counts, order = []) {
      const labels = order.length ? order : Object.keys(counts);
      return labels.map((label) => ({ label, value: counts[label] ?? 0 }));
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
