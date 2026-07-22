export default {
  name: 'Eixos',
  data() {
    return {
      carregando: false,
      erro: '',
      filtroBusca: '',
      eixoDetalhe: null,
      eixos: [
        { nome: 'Gastronomia', cursos: 12, participacao: '16%', unidade: 'CPED' },
        { nome: 'Ambiente e Saúde', cursos: 9, participacao: '12%', unidade: 'CPED' },
        { nome: 'Gestão e Moda', cursos: 11, participacao: '15%', unidade: 'CPED' },
        { nome: 'Tecnologia e Economia Criativa', cursos: 18, participacao: '24%', unidade: 'CPED' },
        { nome: 'Beleza e Cuidado Pessoal', cursos: 7, participacao: '9%', unidade: 'CPED' },
        { nome: 'Turismo e Hospitalidade', cursos: 8, participacao: '11%', unidade: 'CPED' },
        { nome: 'Comunicação e Audiovisual', cursos: 6, participacao: '8%', unidade: 'CPED' },
        { nome: 'Artes e Design', cursos: 5, participacao: '5%', unidade: 'CPED' },
      ],
    };
  },
  computed: {
    eixosFiltrados() {
      const busca = this.filtroBusca.trim().toLowerCase();

      if (!busca) {
        return this.eixos;
      }

      return this.eixos.filter((eixo) => eixo.nome.toLowerCase().includes(busca));
    },
    resumoCards() {
      const totalCursos = this.eixos.reduce((total, eixo) => total + eixo.cursos, 0);
      const eixoMaisRepresentativo = [...this.eixos].sort((a, b) => b.cursos - a.cursos)[0];

      return [
        { label: 'Eixos cadastrados', value: this.eixos.length, help: 'Segmentos disponíveis no portfólio' },
        { label: 'Cursos vinculados', value: totalCursos, help: 'Total de ocorrências no catálogo' },
        { label: 'Eixo principal', value: eixoMaisRepresentativo?.nome ?? '—', help: 'Maiores registros no momento' },
        { label: 'Cobertura', value: '98%', help: 'Alinhamento com o portfólio vigente' },
      ];
    },
    temFiltro() {
      return Boolean(this.filtroBusca.trim());
    },
  },
  methods: {
    limparFiltros() {
      this.filtroBusca = '';
    },
    abrirDetalhes(eixo) {
      this.eixoDetalhe = eixo;
    },
    fecharDetalhes() {
      this.eixoDetalhe = null;
    },
  },
};
