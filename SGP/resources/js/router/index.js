import { createRouter, createWebHistory } from 'vue-router';

const appChildren = [
  { path: 'inicio', name: 'inicio', component: () => import('../pages/Inicio.vue') },
  { path: 'dashboard', name: 'dashboard', component: () => import('../pages/Dashboard.vue') },
  { path: 'relatorios', name: 'relatorios', component: () => import('../pages/Relatorios.vue') },
  { path: 'importacoes', name: 'importacoes', component: () => import('../pages/Importacoes.vue') },
  { path: 'cursos', name: 'cursos', component: () => import('../pages/Cursos.vue') },
  { path: 'plano-de-metas', name: 'plano-de-metas', component: () => import('../pages/PlanoDeMetas.vue') },
  { path: 'pca', name: 'pca', component: () => import('../pages/Pca.vue') },
  { path: 'eixos', name: 'eixos', component: () => import('../pages/Eixos.vue') },
  { path: 'visitas-tecnicas', name: 'visitas-tecnicas', component: () => import('../pages/VisitasTecnicas.vue') },
  { path: 'horas-pedagogicas', name: 'horas-pedagogicas', component: () => import('../pages/HorasPedagogicas.vue') },
  { path: 'acoes-extensivas', name: 'acoes-extensivas', component: () => import('../pages/AcoesExtensivas.vue') },
  { path: 'eventos', name: 'eventos', component: () => import('../pages/Eventos.vue') },
  { path: 'cped', name: 'cped', component: () => import('../pages/Cped.vue') },
  { path: 'usuarios', name: 'usuarios', component: () => import('../pages/Usuarios.vue') },
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
});

export default router;
