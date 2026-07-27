<template>
  <div class="ferramentas-page">
    <header class="ferramentas-top">
      <div class="ferramentas-top-row">
        <div>
          <h1>Ferramentas</h1>
          <p class="ferramentas-subtitle">
            Recursos de apoio à organização e aos processos da CPED
          </p>
        </div>
      </div>

      <div class="ferramentas-info">
        Ferramentas internas em breve. Links externos abrem em nova aba.
        O catálogo é controlado pelo sistema — não há cadastro manual nesta versão.
      </div>
    </header>

    <div v-if="erro" class="alert alert-error">{{ erro }}</div>

    <div v-if="acessoBloqueado" class="alert alert-error">
      Você não possui autorização para consultar esta funcionalidade. Verifique seu perfil de acesso.
    </div>

    <section class="ferramentas-content" aria-label="Catálogo de ferramentas">
      <div v-if="carregando" class="ferramentas-loading">Carregando...</div>

      <div v-else-if="ferramentas.length === 0" class="ferramentas-vazia">
        Nenhuma ferramenta disponível para o seu perfil.
      </div>

      <div v-else class="ferramentas-grid">
        <article
          v-for="item in ferramentas"
          :key="item.key"
          class="ferramenta-card"
          :class="{
            'ferramenta-card-disabled': !podeAbrir(item),
            'ferramenta-card-external': item.type === 'external' && podeAbrir(item),
          }"
          :tabindex="podeAbrir(item) ? 0 : -1"
          :role="podeAbrir(item) ? 'link' : 'group'"
          :aria-disabled="!podeAbrir(item)"
          @click="abrirFerramenta(item)"
          @keydown.enter.prevent="abrirFerramenta(item)"
        >
          <div class="ferramenta-card-top">
            <span class="ferramenta-icon" v-html="icone(item.icon)" aria-hidden="true" />
            <span
              class="ferramenta-badge"
              :class="item.status === 'available' && item.enabled ? 'badge-disponivel' : 'badge-em-breve'"
            >
              {{ rotuloStatus(item) }}
            </span>
          </div>

          <h2 class="ferramenta-label">{{ item.label }}</h2>
          <p class="ferramenta-desc">{{ item.description }}</p>

          <span v-if="item.type === 'external' && podeAbrir(item)" class="ferramenta-hint">
            Abrir em nova aba
          </span>
          <span v-else-if="!podeAbrir(item)" class="ferramenta-hint">
            Em desenvolvimento
          </span>
        </article>
      </div>
    </section>
  </div>
</template>

<script src="../scripts/Ferramentas.js"></script>
<style scoped src="../../css/Ferramentas.css"></style>
