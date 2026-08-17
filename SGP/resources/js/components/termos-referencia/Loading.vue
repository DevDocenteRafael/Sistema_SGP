<template>
  <div class="loading">
    <div class="loading-spinner" :class="`loading-${tamanho}`" aria-hidden="true"></div>
    <p v-if="mostrarTexto" class="loading-texto">{{ texto }}</p>
  </div>
</template>

<script>
export default {
  name: 'Loading',
  props: {
    tamanho: {
      type: String,
      default: 'padrao',
      validator: (v) => ['pequeno', 'padrao', 'grande'].includes(v),
    },
    texto: {
      type: String,
      default: 'Carregando...',
    },
    mostrarTexto: {
      type: Boolean,
      default: true,
    },
  },
};
</script>

<style scoped>
.loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 2rem;
}

.loading-spinner {
  border: 3px solid #e5e7eb;
  border-top-color: #003f7d;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.loading-pequeno {
  width: 1.5rem;
  height: 1.5rem;
}

.loading-padrao {
  width: 2.5rem;
  height: 2.5rem;
}

.loading-grande {
  width: 4rem;
  height: 4rem;
}

.loading-texto {
  margin: 0;
  color: #6b7280;
  font-size: 0.875rem;
  font-weight: 500;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
