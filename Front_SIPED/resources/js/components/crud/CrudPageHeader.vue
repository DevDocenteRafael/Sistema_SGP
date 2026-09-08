<template>
  <header class="crud-top">
    <div class="crud-top-row">
      <div>
        <h1>{{ title }}</h1>
        <p v-if="subtitle" class="crud-subtitle">{{ subtitle }}</p>
      </div>

      <div class="crud-top-actions">
        <slot name="actions" />
        <button v-if="showNovo" type="button" class="btn-novo" @click="$emit('novo')">
          <span class="btn-novo-icon">+</span>
          {{ novoLabel }}
        </button>
      </div>
    </div>

    <div v-if="info" class="crud-info">{{ info }}</div>

    <div v-if="$slots.filters" class="crud-toolbar" role="search" :aria-label="filtersAriaLabel">
      <slot name="filters" />
      <button
        v-if="showClearFilters"
        type="button"
        class="btn-limpar-filtros"
        @click="$emit('limpar-filtros')"
      >
        Limpar filtros
      </button>
    </div>
  </header>
</template>

<script>
export default {
  name: 'CrudPageHeader',
  props: {
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    info: { type: String, default: '' },
    showNovo: { type: Boolean, default: false },
    novoLabel: { type: String, default: 'Novo' },
    showClearFilters: { type: Boolean, default: false },
    filtersAriaLabel: { type: String, default: 'Filtros da listagem' },
  },
  emits: ['novo', 'limpar-filtros'],
};
</script>
