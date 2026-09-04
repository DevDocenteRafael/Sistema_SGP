import Sidebar from '../components/Sidebar.vue';
import { BREAKPOINTS } from '../responsive/breakpoints';
import {
  obterAcessibilidade,
  alternarTema,
  aumentarFonte,
  diminuirFonte,
  podeAumentarFonte,
  podeDiminuirFonte,
  onAcessibilidadeChange,
} from '../utils/acessibilidade';

export default {
  name: 'AppLayout',
  components: { Sidebar },
  data() {
    const atual = obterAcessibilidade();
    return {
      menuAberto: false,
      menuRecolhido: localStorage.getItem('sgp_menu_recolhido') === 'true',
      theme: atual.theme,
      fontScale: atual.fontScale,
      unsubscribe: null,
    };
  },
  computed: {
    podeAumentar() {
      return podeAumentarFonte();
    },
    podeDiminuir() {
      return podeDiminuirFonte();
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
    this.unsubscribe = onAcessibilidadeChange((estado) => {
      this.theme = estado.theme;
      this.fontScale = estado.fontScale;
    });
  },
  beforeUnmount() {
    window.removeEventListener('resize', this.onResize);
    document.body.style.overflow = '';
    if (this.unsubscribe) this.unsubscribe();
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
    alternarTema,
    aumentarFonte,
    diminuirFonte,
  },
};
