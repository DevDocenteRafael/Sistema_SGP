<template>
  <div class="sistemas-apoio-page">
    <CrudPageHeader
      title="Sistemas de Apoio"
      subtitle="Acessos rápidos aos sistemas institucionais"
      info="Somente links externos. Não há senhas nem autenticação integrada nesta tela."
    />

    <div v-if="erro" class="alert alert-error">{{ erro }}</div>

    <section class="sistemas-content" aria-label="Atalhos institucionais">
      <div v-if="carregando" class="sistemas-loading">Carregando atalhos...</div>

      <div v-else-if="links.length === 0" class="tabela-vazia estado-vazio">
        <p class="estado-vazio-titulo">Nenhum atalho cadastrado ainda.</p>
        <p class="estado-vazio-texto">Os links institucionais aparecerão aqui após a configuração.</p>
      </div>

      <div v-else class="sistemas-grid">
        <a
          v-for="item in links"
          :key="item.key"
          class="sistema-card"
          :href="item.url"
          target="_blank"
          rel="noopener noreferrer"
        >
          <div class="sistema-card-top">
            <h2>{{ item.label }}</h2>
            <span v-if="item.placeholder" class="sistema-badge">URL a confirmar</span>
            <span v-else class="sistema-badge sistema-badge-ok">Externo</span>
          </div>
          <p>{{ item.descricao }}</p>
          <span class="sistema-url">{{ item.url }}</span>
        </a>
      </div>
    </section>
  </div>
</template>

<script src="../scripts/SistemasApoio.js"></script>
<style scoped src="../../css/SistemasApoio.css"></style>
