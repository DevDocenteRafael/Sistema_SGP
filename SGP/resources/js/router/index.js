import { createRouter, createWebHistory } from 'vue-router';
import { getUsuario, podeAcessarMenu } from '../scripts/auth';

const appChildren = [
  { path: 'inicio', name: 'inicio', component: () => import('../pages/Inicio.vue'), meta: { menu: 'inicio' } },
  { path: 'dashboard', name: 'dashboard', component: () => import('../pages/Dashboard.vue'), meta: { menu: 'dashboard' } },
  { path: 'relatorios', name: 'relatorios', component: () => import('../pages/Relatorios.vue'), meta: { menu: 'relatorios' } },
  { path: 'importacoes', name: 'importacoes', component: () => import('../pages/Importacoes.vue'), meta: { menu: 'importacoes' } },
  { path: 'cursos', name: 'cursos', component: () => import('../pages/Cursos.vue'), meta: { menu: 'cursos' } },
  { path: 'plano-de-metas', name: 'plano-de-metas', component: () => import('../pages/PlanoDeMetas.vue'), meta: { menu: 'plano-de-metas' } },
  { path: 'pca', name: 'pca', component: () => import('../pages/Pca.vue'), meta: { menu: 'pca' } },
  { path: 'eixos', name: 'eixos', component: () => import('../pages/Eixos.vue'), meta: { menu: 'eixos' } },
  { path: 'visitas-tecnicas', name: 'visitas-tecnicas', component: () => import('../pages/VisitasTecnicas.vue'), meta: { menu: 'visitas-tecnicas' } },
  { path: 'horas-pedagogicas', name: 'horas-pedagogicas', component: () => import('../pages/HorasPedagogicas.vue'), meta: { menu: 'horas-pedagogicas' } },
  { path: 'acoes-extensivas', name: 'acoes-extensivas', component: () => import('../pages/AcoesExtensivas.vue'), meta: { menu: 'acoes-extensivas' } },
  { path: 'eventos', name: 'eventos', component: () => import('../pages/Eventos.vue'), meta: { menu: 'eventos' } },
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
  { path: 'cped', name: 'cped', component: () => import('../pages/Cped.vue'), meta: { menu: 'cped' } },
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

router.beforeEach((to) => {
  const normalizedPath = to.path.toLowerCase();

  if (to.path !== normalizedPath) {
    return {
      path: normalizedPath,
      query: to.query,
      hash: to.hash,
    };
  }

  const token = localStorage.getItem('sgp_token');
  const requiresAuth = to.path.startsWith('/app');

  if (requiresAuth && !token) {
    return {
      name: 'login',
      query: { redirect: to.fullPath },
    };
  }

  if (to.name === 'login' && token) {
    return { path: '/app/inicio' };
  }

  if (requiresAuth && token) {
    const usuario = getUsuario();

    if (!usuario?.perfil) {
      localStorage.removeItem('sgp_token');
      localStorage.removeItem('sgp_usuario');

      return { name: 'login' };
    }

    const menuKey = to.meta?.menu;

    if (menuKey && !podeAcessarMenu(menuKey)) {
      return { path: '/app/inicio' };
    }
  }
});

export default router;
