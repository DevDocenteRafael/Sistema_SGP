import { podeConsultarDados } from './auth';

const ICONS = {
  kanban: '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="6" height="14" x="4" y="5" rx="1"/><rect width="6" height="9" x="14" y="5" rx="1"/></svg>',
  organograma: '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="8" height="6" x="8" y="2" rx="1"/><path d="M12 8v4"/><path d="M6 20v-4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v4"/><path d="M6 16H4"/><path d="M20 16h-2"/><path d="M12 12h0"/></svg>',
  fluxograma: '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="8" height="5" x="8" y="2" rx="1"/><path d="M12 7v3"/><path d="m8 14 4 4 4-4"/><path d="M12 10v8"/><rect width="8" height="4" x="8" y="18" rx="1"/></svg>',
  loop: '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 2.1l4 4-4 4"/><path d="M3 12.2v-2a4 4 0 0 1 4-4h12.8M7 21.9l-4-4 4-4"/><path d="M21 11.8v2a4 4 0 0 1-4 4H4.2"/></svg>',
  canva: '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5"/><path d="M5 12h14"/><circle cx="12" cy="12" r="9"/></svg>',
};

export default {
  name: 'Ferramentas',

  data() {
    return {
      ferramentas: [],
      carregando: true,
      erro: '',
    };
  },

  computed: {
    acessoBloqueado() {
      return !podeConsultarDados();
    },
  },

  mounted() {
    this.carregarFerramentas();
  },

  methods: {
    async carregarFerramentas() {
      this.carregando = true;
      this.erro = '';

      if (!podeConsultarDados()) {
        this.ferramentas = [];
        this.carregando = false;
        this.erro = 'Seu perfil não possui acesso para consultar ferramentas.';
        return;
      }

      try {
        const { data } = await window.axios.get('/api/ferramentas');
        this.ferramentas = Array.isArray(data.data) ? data.data : [];
      } catch (error) {
        this.erro = error.response?.data?.message
          || 'Não foi possível carregar as ferramentas.';
        this.ferramentas = [];
      } finally {
        this.carregando = false;
      }
    },

    podeAbrir(item) {
      return Boolean(item?.enabled) && item?.status === 'available';
    },

    rotuloStatus(item) {
      if (item.status === 'available' && item.enabled) {
        return item.type === 'external' ? 'Externo' : 'Disponível';
      }

      return 'Em breve';
    },

    icone(chave) {
      return ICONS[chave] || ICONS.kanban;
    },

    abrirFerramenta(item) {
      if (!this.podeAbrir(item)) {
        return;
      }

      if (item.type === 'external' && item.url) {
        window.open(item.url, '_blank', 'noopener,noreferrer');
        return;
      }

      if (item.type === 'internal' && item.route) {
        this.$router.push(item.route);
      }
    },
  },
};
