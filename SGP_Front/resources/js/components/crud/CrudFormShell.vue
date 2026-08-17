<template>
  <div class="form-page">
    <div class="form-top-bar"></div>
    <header class="form-header">
      <button type="button" class="btn-voltar" @click="$emit('voltar')">←</button>
      <div>
        <h1>{{ title }}</h1>
        <p v-if="subtitle">{{ subtitle }}</p>
      </div>
    </header>
    <slot />
  </div>
</template>

<script>
export default {
  name: 'CrudFormShell',
  props: {
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
  },
  emits: ['voltar'],
};
</script>
