import { createRouter, createWebHistory } from 'vue-router';
import { garantirSessao, podeAcessarMenu } from '../scripts/auth';

const appChildren = [
  { path: 'inicio', name: 'inicio', component: () => import('../pages/Inicio.vue'), meta: { menu: 'inicio' } },
  { path: 'dashboard', name: 'dashboard', component: () => import('../pages/Dashboard.vue'), meta: { menu: 'dashboard' } },
  { path: 'relatorios', name: 'relatorios', component: () => import('../pages/Relatorios.vue'), meta: { menu: 'relatorios' } },
  { path: 'importacoes', name: 'importacoes', component: () => import('../pages/Importacoes.vue'), meta: { menu: 'importacoes' } },
  { path: 'cursos', name: 'cursos', component: () => import('../pages/Cursos.vue'), meta: { menu: 'cursos' } },
  { path: 'ciclos-portfolio', name: 'ciclos-portfolio', component: () => import('../pages/CiclosPortfolio.vue'), meta: { menu: 'ciclos-portfolio' } },
  { path: 'plano-de-metas', name: 'plano-de-metas', component: () => import('../pages/PlanoDeMetas.vue'), meta: { menu: 'plano-de-metas' } },
  { path: 'pca', name: 'pca', component: () => import('../pages/Pca.vue'), meta: { menu: 'pca' } },
  { path: 'controle-de-resolucoes', name: 'controle-de-resolucoes', component: () => import('../pages/ControleDeResolucao.vue'), meta: { menu: 'controle-de-resolucoes' } },
  { path: 'termos-de-referencia', name: 'termos-de-referencia', component: () => import('../pages/TermosReferencia.vue'), meta: { menu: 'termos-de-referencia' } },
  { path: 'eixos', name: 'eixos', component: () => import('../pages/Eixos.vue'), meta: { menu: 'eixos' } },
  { path: 'visitas-tecnicas', name: 'visitas-tecnicas', component: () => import('../pages/VisitasTecnicas.vue'), meta: { menu: 'visitas-tecnicas' } },
  { path: 'horas-pedagogicas', name: 'horas-pedagogicas', component: () => import('../pages/HorasPedagogicas.vue'), meta: { menu: 'horas-pedagogicas' } },
  { path: 'acoes-extensivas', name: 'acoes-extensivas', component: () => import('../pages/AcoesExtensivas.vue'), meta: { menu: 'acoes-extensivas' } },
  { path: 'eventos', name: 'eventos', component: () => import('../pages/Eventos.vue'), meta: { menu: 'eventos' } },
  { path: 'jornada-pedagogica', name: 'jornada-pedagogica', component: () => import('../pages/JornadaPedagogica.vue'), meta: { menu: 'jornada-pedagogica' } },
  { path: 'sistemas-apoio', name: 'sistemas-apoio', component: () => import('../pages/SistemasApoio.vue'), meta: { menu: 'sistemas-apoio' } },
  { path: 'ferramentas', name: 'ferramentas', component: () => import('../pages/Ferramentas.vue'), meta: { menu: 'ferramentas' } },
  {
    path: 'ferramentas/kanban',
    name: 'ferramentas-kanban',
    component: () => import('../pages/KanbanQuadros.vue'),
    meta: { menu: 'ferramentas' },
  },
  {
    path: 'ferramentas/kanban/:slug',
    name: 'ferramentas-kanban-quadro',
    component: () => import('../pages/Kanban.vue'),
    meta: { menu: 'ferramentas' },
  },
  {
    path: 'ferramentas/organograma',
    name: 'ferramentas-organograma',
    component: () => import('../pages/Organograma.vue'),
    meta: { menu: 'ferramentas' },
  },
  {
    path: 'ferramentas/carometro',
    name: 'ferramentas-carometro',
    component: () => import('../pages/Carometro.vue'),
    meta: { menu: 'ferramentas' },
  },
  {
    path: 'ferramentas/fluxograma',
    name: 'ferramentas-fluxograma',
    component: () => import('../pages/Fluxogramas.vue'),
    meta: { menu: 'ferramentas' },
  },
  {
    path: 'ferramentas/fluxograma/:slug',
    name: 'ferramentas-fluxograma-editor',
    component: () => import('../pages/FluxogramaEditor.vue'),
    meta: { menu: 'ferramentas' },
  },
  { path: 'cped', name: 'cped', component: () => import('../pages/Cped.vue'), meta: { menu: 'cped' } },
  { path: 'auditoria', name: 'auditoria', component: () => import('../pages/Auditoria.vue'), meta: { menu: 'auditoria' } },
  { path: 'usuarios', name: 'usuarios', component: () => import('../pages/Usuarios.vue'), meta: { menu: 'usuarios' } },
];

const redirects = appChildren.map((route) => ({
  path: `/${route.path}`,
  redirect: `/app/${route.path}`,
}));

const routes = [
  {
    path: '/',
    redirect: '/login',
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('../pages/Login.vue'),
  },
  {
    path: '/app',
    component: () => import('../layouts/AppLayout.vue'),
    redirect: '/app/inicio',
    children: appChildren,
  },
  ...redirects,
  {
    path: '/:pathMatch(.*)*',
    redirect: '/login',
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach(async (to) => {
  const normalizedPath = to.path.toLowerCase();

  if (to.path !== normalizedPath) {
    return {
      path: normalizedPath,
      query: to.query,
      hash: to.hash,
    };
  }

  const requiresAuth = to.path.startsWith('/app');
  const sessaoOk = await garantirSessao();

  if (requiresAuth && !sessaoOk) {
    return {
      name: 'login',
      query: to.fullPath !== '/app/inicio' && to.fullPath !== '/app'
        ? { redirect: to.fullPath }
        : {},
    };
  }

  if (to.name === 'login' && sessaoOk) {
    return { path: '/app/inicio' };
  }

  if (requiresAuth && sessaoOk) {
    const menuKey = to.meta?.menu;

    if (menuKey && !podeAcessarMenu(menuKey)) {
      return { path: '/app/inicio' };
    }
  }
});

const TITULOS_PAGINA = {
  login: 'Login',
  inicio: 'Início',
  dashboard: 'Dashboard',
  relatorios: 'Relatórios',
  importacoes: 'Importações',
  cursos: 'Cursos',
  'ciclos-portfolio': 'Ciclos de Portfólio',
  'plano-de-metas': 'Plano de Metas',
  pca: 'PCA',
  'controle-de-resolucoes': 'Controle de Resoluções',
  'termos-de-referencia': 'Termos de Referência',
  eixos: 'Eixos',
  'visitas-tecnicas': 'Visitas Técnicas',
  'horas-pedagogicas': 'Horas Pedagógicas',
  'acoes-extensivas': 'Ações Extensivas',
  eventos: 'Eventos',
  'jornada-pedagogica': 'Jornada Pedagógica',
  'sistemas-apoio': 'Sistemas de Apoio',
  ferramentas: 'Ferramentas',
  'ferramentas-kanban': 'Kanban',
  'ferramentas-kanban-quadro': 'Kanban',
  'ferramentas-organograma': 'Organograma',
  'ferramentas-carometro': 'Carômetro',
  'ferramentas-fluxograma': 'Fluxogramas',
  'ferramentas-fluxograma-editor': 'Editor de Fluxograma',
  cped: 'CPED',
  auditoria: 'Auditoria',
  usuarios: 'Usuários',
};

router.afterEach((to) => {
  const titulo = TITULOS_PAGINA[to.name] || 'SGP';
  document.title = `${titulo} · SGP`;
});

export default router;
