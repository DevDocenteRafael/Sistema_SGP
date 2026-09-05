import Sidebar from '../components/Sidebar.vue';
import AcessibilidadeFlutuante from '../components/ui/AcessibilidadeFlutuante.vue';
import CicloSeletor from '../components/CicloSeletor.vue';
import { BREAKPOINTS } from '../responsive/breakpoints';

export default {
  name: 'AppLayout',
  components: { Sidebar, AcessibilidadeFlutuante, CicloSeletor },
  data() {
    return {
      menuAberto: false,
      menuRecolhido: localStorage.getItem('sgp_menu_recolhido') === 'true',
    };
  },
  computed: {
    cicloBarraAzul() {
      const rota = this.$route?.name || this.$route?.meta?.menu || '';
      return rota === 'inicio' || rota === 'cped';
    },
    /** Páginas full-bleed (hero colado na barra de ciclo) — sem padding do main. */
    paginaFlush() {
      const rota = this.$route?.name || this.$route?.meta?.menu || '';
      return rota === 'inicio' || rota === 'cped';
    },
  },
  watch: {
    $route() {
      this.fecharMenu();
    },
    menuAberto(aberto) {
      document.body.style.overflow = aberto ? 'hidden' : '';
    },
  },
  mounted() {
    window.addEventListener('resize', this.onResize);
  },
  beforeUnmount() {
    window.removeEventListener('resize', this.onResize);
    document.body.style.overflow = '';
  },
  methods: {
    toggleMenu() {
      this.menuAberto = !this.menuAberto;
    },
    fecharMenu() {
      this.menuAberto = false;
    },
    alternarRecolhimento() {
      this.menuRecolhido = !this.menuRecolhido;
      localStorage.setItem('sgp_menu_recolhido', String(this.menuRecolhido));
    },
    onResize() {
      if (window.innerWidth > BREAKPOINTS.md && this.menuAberto) {
        this.menuAberto = false;
      }
    },
  },
};
