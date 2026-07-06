<template>
  <div class="inicio-page">
    <header class="inicio-hero">
      <div class="inicio-hero-inner">
        <img :src="logoSenac" alt="Senac" class="inicio-logo" />

        <div class="inicio-hero-text">
          <p class="inicio-hero-tag">SENAC DF · CPED</p>
          <h1 class="inicio-hero-title">SGP — Sistema de Gerenciamento de Portfólio</h1>
          <p class="inicio-hero-desc">
            Plataforma institucional para gestão e acompanhamento do portfólio de cursos,
            processos educacionais e indicadores do SENAC DF.
          </p>
        </div>

        <div class="inicio-stats">
          <div class="inicio-stat">
            <p class="inicio-stat-value">8</p>
            <p class="inicio-stat-label">Eixos</p>
          </div>
          <span class="inicio-stat-divider" aria-hidden="true" />
          <div class="inicio-stat">
            <p class="inicio-stat-value">2025-26</p>
            <p class="inicio-stat-label">Portfólio</p>
          </div>
          <span class="inicio-stat-divider" aria-hidden="true" />
          <div class="inicio-stat">
            <p class="inicio-stat-value">CPED</p>
            <p class="inicio-stat-label">Unidade</p>
          </div>
        </div>
      </div>
    </header>

    <div class="inicio-accent-bar" aria-hidden="true" />

    <section class="inicio-content">
      <div class="inicio-section-head">
        <h2>Acesso Rápido</h2>
        <p>
          {{ modulosDisponiveis.length }} módulos disponíveis — navegue pelos principais recursos do sistema
        </p>
      </div>

      <div class="inicio-modulos-grid">
        <router-link
          v-for="modulo in modulosDisponiveis"
          :key="modulo.rota"
          :to="modulo.path"
          class="inicio-modulo-card"
        >
          <span class="inicio-modulo-icon" v-html="icons[modulo.icon]" />
          <span class="inicio-modulo-label">{{ modulo.label }}</span>
          <span class="inicio-modulo-desc">{{ modulo.descricao }}</span>
        </router-link>
      </div>
    </section>

    <footer class="inicio-footer">
      © 2026 SENAC DF · SGP v1.0-beta · Uso interno
    </footer>
  </div>
</template>

<script src="../scripts/Inicio.js"></script>
<style scoped src="../../css/Inicio.css"></style>
