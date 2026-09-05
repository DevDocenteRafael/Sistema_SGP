<template>
  <div class="app-layout" :class="{ 'menu-open': menuAberto, 'menu-collapsed': menuRecolhido }">
    <a class="sgp-skip-link" href="#conteudo-principal">Ir para o conteúdo principal</a>

    <header class="app-topbar">
      <button
        type="button"
        class="app-menu-toggle"
        :aria-expanded="menuAberto ? 'true' : 'false'"
        aria-controls="sgp-sidebar"
        aria-label="Abrir ou fechar menu"
        @click="toggleMenu"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <line x1="4" x2="20" y1="6" y2="6" />
          <line x1="4" x2="20" y1="12" y2="12" />
          <line x1="4" x2="20" y1="18" y2="18" />
        </svg>
      </button>
      <span class="app-topbar-title">SGP</span>
    </header>

    <div class="app-overlay" aria-hidden="true" @click="fecharMenu"></div>

    <Sidebar
      id="sgp-sidebar"
      :aberto="menuAberto"
      :recolhido="menuRecolhido"
      @fechar="fecharMenu"
      @alternar-recolhimento="alternarRecolhimento"
    />

    <div class="app-content-column">
      <div
        class="app-ciclo-bar"
        :class="{ 'app-ciclo-bar--brand': cicloBarraAzul }"
        aria-label="Contexto do ciclo"
      >
        <CicloSeletor />
      </div>

      <main
        id="conteudo-principal"
        class="app-main"
        :class="{ 'app-main--flush': paginaFlush }"
        tabindex="-1"
      >
        <router-view />
      </main>
    </div>

    <AcessibilidadeFlutuante />
  </div>
</template>

<script src="../scripts/AppLayout.js"></script>
<style scoped src="../../css/AppLayout.css"></style>
