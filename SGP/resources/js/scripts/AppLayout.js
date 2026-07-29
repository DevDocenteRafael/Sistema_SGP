import Sidebar from '../components/Sidebar.vue';
import { BREAKPOINTS } from '../responsive/breakpoints';

export default {
  name: 'AppLayout',
  components: { Sidebar },
  data() {
    return {
      menuAberto: false,
    };
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
    onResize() {
      if (window.innerWidth > BREAKPOINTS.md && this.menuAberto) {
        this.menuAberto = false;
      }
    },
  },
};
