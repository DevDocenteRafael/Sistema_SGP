import logoSenac from '../../images/Logo-Senac-branco.png';
import { clearSessao, getUsuario, podeAcessarMenu } from './auth';

const ICONS = {
  home: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
  dashboard: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>',
  relatorios: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 13H8"/><path d="M16 13h-2"/><path d="M10 17H8"/><path d="M16 17h-2"/></svg>',
  importacoes: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>',
  cursos: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/></svg>',
  metas: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>',
  pca: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 12h4"/><path d="M10 8h4"/><path d="M14 21v-3a2 2 0 0 0-4 0v3"/><path d="M6 10H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2h-2"/><path d="M6 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16"/></svg>',
  eixos: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" x2="12" y1="20" y2="10"/><line x1="18" x2="18" y1="20" y2="4"/><line x1="6" x2="6" y1="20" y2="16"/></svg>',
  visitas: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg>',
  horas: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
  acoes: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg>',
  eventos: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/></svg>',
  ferramentas: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
  cped: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>',
  auditoria: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>',
  usuarios: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
  resolucoes: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7z"/><path d="M14 2v5h5"/><path d="M8 13h8"/><path d="M8 17h8"/></svg>',
  termosReferencia: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 13h6"/><path d="M9 17h6"/><path d="M9 9h2"/></svg>',
  jornada: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/></svg>',
};

const MENU_SECOES = [
  {
    titulo: null,
    itens: [
      { rota: 'inicio', label: 'Início', path: '/app/inicio', icon: 'home' },
      { rota: 'dashboard', label: 'Dashboard', path: '/app/dashboard', icon: 'dashboard' },
      { rota: 'relatorios', label: 'Relatórios', path: '/app/relatorios', icon: 'relatorios' },
      { rota: 'importacoes', label: 'Importações', path: '/app/importacoes', icon: 'importacoes' },
    ],
  },
  {
    titulo: 'PORTFÓLIO',
    itens: [
      { rota: 'cursos', label: 'Cursos', path: '/app/cursos', icon: 'cursos' },
      { rota: 'plano-de-metas', label: 'Plano de Metas', path: '/app/plano-de-metas', icon: 'metas' },
      { rota: 'pca', label: 'PCA', path: '/app/pca', icon: 'pca' },
      { rota: 'eixos', label: 'Eixos', path: '/app/eixos', icon: 'eixos' },
    ],
  },
  {
    titulo: 'PRAZOS',
    itens: [
      { rota: 'controle-de-resolucoes', label: 'Controle de Resoluções', path: '/app/controle-de-resolucoes', icon: 'resolucoes' },
      { rota: 'termos-de-referencia', label: 'Termos de Referência', path: '/app/termos-de-referencia', icon: 'termosReferencia' },
    ],
  },
  {
    titulo: 'PROCESSOS',
    itens: [
      { rota: 'visitas-tecnicas', label: 'Visitas Técnicas', path: '/app/visitas-tecnicas', icon: 'visitas' },
      { rota: 'horas-pedagogicas', label: 'Horas Pedagógicas', path: '/app/horas-pedagogicas', icon: 'horas' },
      { rota: 'acoes-extensivas', label: 'Ações Extensivas', path: '/app/acoes-extensivas', icon: 'acoes' },
      { rota: 'eventos', label: 'Eventos', path: '/app/eventos', icon: 'eventos' },
      { rota: 'jornada-pedagogica', label: 'Jornada Pedagógica', path: '/app/jornada-pedagogica', icon: 'jornada' },
    ],
  },
  {
    titulo: 'INSTITUCIONAL',
    itens: [
      { rota: 'ferramentas', label: 'Ferramentas', path: '/app/ferramentas', icon: 'ferramentas' },
      { rota: 'cped', label: 'CPED', path: '/app/cped', icon: 'cped' },
      { rota: 'auditoria', label: 'Auditoria', path: '/app/auditoria', icon: 'auditoria' },
      { rota: 'usuarios', label: 'Usuários', path: '/app/usuarios', icon: 'usuarios' },
    ],
  },
];

export default {
  name: 'Sidebar',
  props: {
    aberto: {
      type: Boolean,
      default: false,
    },
  },
  emits: ['fechar'],
  data() {
    return {
      logoSenac,
      icons: ICONS,
    };
  },
  computed: {
    usuario() {
      return getUsuario();
    },
    iniciais() {
      const nome = this.usuario?.nome;

      if (!nome) {
        return '?';
      }

      const partes = nome.trim().split(/\s+/);

      if (partes.length === 1) {
        return partes[0].slice(0, 2).toUpperCase();
      }

      return `${partes[0][0]}${partes[partes.length - 1][0]}`.toUpperCase();
    },
    menuSecoes() {
      return MENU_SECOES
        .map((secao) => ({
          titulo: secao.titulo,
          itens: secao.itens.filter((item) => podeAcessarMenu(item.rota)),
        }))
        .filter((secao) => secao.itens.length > 0);
    },
  },
  methods: {
    onNavigate() {
      this.$emit('fechar');
    },
    async logout() {
      try {
        await window.axios.post('/api/logout');
      } catch {
        // Sessão já expirada ou token inválido — limpa localmente mesmo assim.
      }

      clearSessao();
      this.$emit('fechar');
      this.$router.replace('/login');
    },
  },
};
