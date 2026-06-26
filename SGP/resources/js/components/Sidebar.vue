<template>
  <nav class="bg-dark text-white p-3" style="min-width: 220px; min-height: 100vh">
    <h5 class="mb-3">SGP</h5>
    <router-link class="d-block text-white mb-2 text-decoration-none" to="/app/inicio">Início</router-link>
    <router-link class="d-block text-white mb-2 text-decoration-none" to="/app/dashboard">Dashboard</router-link>
    <router-link class="d-block text-white mb-2 text-decoration-none" to="/app/relatorios">Relatórios</router-link>
    <router-link class="d-block text-white mb-2 text-decoration-none" to="/app/importacoes">Importações</router-link>
    <router-link class="d-block text-white mb-2 text-decoration-none" to="/app/cursos">Cursos</router-link>
    <router-link class="d-block text-white mb-2 text-decoration-none" to="/app/plano-de-metas">Plano de Metas</router-link>
    <router-link class="d-block text-white mb-2 text-decoration-none" to="/app/pca">PCA</router-link>
    <router-link class="d-block text-white mb-2 text-decoration-none" to="/app/eixos">Eixos</router-link>
    <router-link class="d-block text-white mb-2 text-decoration-none" to="/app/visitas-tecnicas">Visitas Técnicas</router-link>
    <router-link class="d-block text-white mb-2 text-decoration-none" to="/app/horas-pedagogicas">Horas Pedagógicas</router-link>
    <router-link class="d-block text-white mb-2 text-decoration-none" to="/app/acoes-extensivas">Ações Extensivas</router-link>
    <router-link class="d-block text-white mb-2 text-decoration-none" to="/app/eventos">Eventos</router-link>
    <router-link class="d-block text-white mb-2 text-decoration-none" to="/app/cped">CPED</router-link>
    <router-link class="d-block text-white mb-2 text-decoration-none" to="/app/usuarios">Usuários</router-link>
  </nav>
</template>

<script>
export default {
  name: 'Sidebar',
};
</script>
