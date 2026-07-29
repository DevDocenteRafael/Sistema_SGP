<template>
  <nav class="sidebar" :class="{ 'sidebar--open': aberto }">
    <div class="sidebar-header">
      <router-link to="/app/inicio" class="sidebar-logo-link" title="Ir para o início" @click="onNavigate">
        <img :src="logoSenac" alt="Senac" class="sidebar-logo" />
      </router-link>
    </div>

    <div class="sidebar-nav">
      <div
        v-for="(secao, index) in menuSecoes"
        :key="secao.titulo ?? `secao-${index}`"
        class="sidebar-section"
      >
        <p v-if="secao.titulo" class="sidebar-section-title">{{ secao.titulo }}</p>

        <router-link
          v-for="item in secao.itens"
          :key="item.rota"
          class="sidebar-link"
          :to="item.path"
          @click="onNavigate"
        >
          <span class="sidebar-link-icon" v-html="icons[item.icon]" />
          <span class="sidebar-link-label">{{ item.label }}</span>
        </router-link>
      </div>
    </div>

    <div class="sidebar-footer">
      <div v-if="usuario" class="sidebar-user-card">
        <span class="sidebar-avatar">{{ iniciais }}</span>
        <div class="sidebar-user-info">
          <p class="sidebar-user-nome">{{ usuario.nome }}</p>
          <p v-if="usuario.unidade" class="sidebar-user-unidade">{{ usuario.unidade }}</p>
        </div>
      </div>

      <button type="button" class="sidebar-logout" @click="logout">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
          <polyline points="16 17 21 12 16 7" />
          <line x1="21" x2="9" y1="12" y2="12" />
        </svg>
        Sair
      </button>
    </div>
  </nav>
</template>

<script src="../scripts/Sidebar.js"></script>
<style scoped src="../../css/Sidebar.css"></style>
