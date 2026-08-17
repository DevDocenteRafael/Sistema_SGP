import CrudPageHeader from '../components/crud/CrudPageHeader.vue';

export default {
  name: 'SistemasApoio',
  components: { CrudPageHeader },
  data() {
    return {
      links: [],
      carregando: true,
      erro: '',
    };
  },
  mounted() {
    this.carregarLinks();
  },
  methods: {
    async carregarLinks() {
      this.carregando = true;
      this.erro = '';

      try {
        const { data } = await window.axios.get('/api/sistemas-apoio');
        this.links = Array.isArray(data.data) ? data.data : [];
      } catch (error) {
        this.erro = error.response?.data?.message
          || 'Não foi possível carregar os sistemas de apoio.';
        this.links = [];
      } finally {
        this.carregando = false;
      }
    },
  },
};
