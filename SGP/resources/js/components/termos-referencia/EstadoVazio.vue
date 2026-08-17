<template>
  <div class="estado-vazio">
    <div class="estado-vazio-icone" aria-hidden="true">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        width="48"
        height="48"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
      >
        <path v-if="tipo === 'padrao'" d="M12 2v20M2 12h20M6 6l12 12M18 6l-12 12" />
        <circle v-else-if="tipo === 'busca'" cx="11" cy="11" r="8" />
        <path v-else-if="tipo === 'busca'" d="m21 21-4.3-4.3" />
        <path v-else-if="tipo === 'documento'" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
        <polyline v-else-if="tipo === 'documento'" points="14 2 14 8 20 8" />
        <path v-else d="M12 7v14M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z" />
      </svg>
    </div>

    <h3 class="estado-vazio-titulo">{{ titulo }}</h3>
    <p class="estado-vazio-texto">{{ descricao }}</p>

    <button
      v-if="botaoLabel"
      type="button"
      class="estado-vazio-botao"
      @click="$emit('acao')"
    >
      {{ botaoLabel }}
    </button>
  </div>
</template>

<script>
export default {
  name: 'EstadoVazio',
  props: {
    tipo: {
      type: String,
      default: 'padrao',
      validator: (v) => ['padrao', 'busca', 'documento', 'curso'].includes(v),
    },
    titulo: {
      type: String,
      required: true,
    },
    descricao: {
      type: String,
      required: true,
    },
    botaoLabel: {
      type: String,
      default: '',
    },
  },
  emits: ['acao'],
};
</script>

<style scoped>
.estado-vazio {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3.5rem 1.5rem;
  text-align: center;
}

.estado-vazio-icone {
  margin-bottom: 1.25rem;
  color: #d1d5db;
  display: flex;
  align-items: center;
  justify-content: center;
}

.estado-vazio-icone svg {
  width: 3rem;
  height: 3rem;
}

.estado-vazio-titulo {
  margin: 0 0 0.5rem;
  color: #374151;
  font-size: 1rem;
  font-weight: 600;
  line-height: 1.4;
}

.estado-vazio-texto {
  margin: 0 0 1.25rem;
  color: #9ca3af;
  font-size: 0.875rem;
  max-width: 26rem;
  line-height: 1.5;
}

.estado-vazio-botao {
  padding: 0.65rem 1.25rem;
  border: none;
  border-radius: 0.5rem;
  background: #f57c00;
  color: #fff;
  font-weight: 600;
  font-size: 0.875rem;
  cursor: pointer;
  transition: background 0.15s ease;
}

.estado-vazio-botao:hover {
  background: #e67300;
}

.estado-vazio-botao:active {
  background: #d46a00;
}
</style>
