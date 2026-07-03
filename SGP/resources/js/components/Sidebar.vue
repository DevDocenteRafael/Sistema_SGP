<template>
  <nav class="sidebar bg-dark text-white">
    <div class="sidebar-header p-3">
      <h5 class="mb-0">SGP</h5>
    </div>

    <div class="sidebar-links p-3 pt-0">
      <router-link class="sidebar-link" to="/app/inicio">Início</router-link>
      <router-link class="sidebar-link" to="/app/dashboard">Dashboard</router-link>
      <router-link class="sidebar-link" to="/app/relatorios">Relatórios</router-link>
      <router-link class="sidebar-link" to="/app/importacoes">Importações</router-link>
      <router-link class="sidebar-link" to="/app/cursos">Cursos</router-link>
      <router-link class="sidebar-link" to="/app/plano-de-metas">Plano de Metas</router-link>
      <router-link class="sidebar-link" to="/app/pca">PCA</router-link>
      <router-link class="sidebar-link" to="/app/eixos">Eixos</router-link>
      <router-link class="sidebar-link" to="/app/visitas-tecnicas">Visitas Técnicas</router-link>
      <router-link class="sidebar-link" to="/app/horas-pedagogicas">Horas Pedagógicas</router-link>
      <router-link class="sidebar-link" to="/app/acoes-extensivas">Ações Extensivas</router-link>
      <router-link class="sidebar-link" to="/app/eventos">Eventos</router-link>
      <router-link class="sidebar-link" to="/app/cped">CPED</router-link>
      <router-link class="sidebar-link" to="/app/usuarios">Usuários</router-link>
    </div>

    <div class="sidebar-footer p-3">
      <button type="button" class="sidebar-logout" @click="logout">
        Sair
      </button>
    </div>
  </nav>
</template>

<script>
export default {
  name: 'Sidebar',
  methods: {
    async logout() {
      try {
        await window.axios.post('/api/logout');
      } catch {
        // Sessão já expirada ou token inválido — limpa localmente mesmo assim.
      }

      localStorage.removeItem('sgp_token');
      localStorage.removeItem('sgp_usuario');
      delete window.axios.defaults.headers.common.Authorization;
      this.$router.push('/login');
    },
  },
};
</script>

<style scoped>
.sidebar {
  display: flex;
  flex-direction: column;
  width: 220px;
  height: 100vh;
  position: sticky;
  top: 0;
  flex-shrink: 0;
}

.sidebar-links {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
}

.sidebar-link {
  display: block;
  color: #fff;
  text-decoration: none;
  margin-bottom: 0.5rem;
  font-size: 0.95rem;
}

.sidebar-link:hover,
.sidebar-link.router-link-active {
  color: #dee2e6;
  text-decoration: underline;
}

.sidebar-footer {
  border-top: 1px solid rgba(255, 255, 255, 0.2);
  flex-shrink: 0;
}

.sidebar-logout {
  width: 100%;
  padding: 0.5rem 1rem;
  border: 1px solid #fff;
  border-radius: 0.375rem;
  background: transparent;
  color: #fff;
  cursor: pointer;
  font-size: 0.95rem;
}

.sidebar-logout:hover {
  background: rgba(255, 255, 255, 0.1);
}
</style>
