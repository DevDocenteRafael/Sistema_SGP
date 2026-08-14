<template>
  <div v-if="visivel" class="feedback" :class="`feedback-${tipo}`" role="alert">
    <div class="feedback-conteudo">
      <span class="feedback-icone" aria-hidden="true">
        <svg
          v-if="tipo === 'sucesso'"
          xmlns="http://www.w3.org/2000/svg"
          width="18"
          height="18"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <polyline points="20 6 9 17 4 12" />
        </svg>
        <svg
          v-else-if="tipo === 'erro'"
          xmlns="http://www.w3.org/2000/svg"
          width="18"
          height="18"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <circle cx="12" cy="12" r="10" />
          <line x1="15" y1="9" x2="9" y2="15" />
          <line x1="9" y1="9" x2="15" y2="15" />
        </svg>
        <svg
          v-else-if="tipo === 'aviso'"
          xmlns="http://www.w3.org/2000/svg"
          width="18"
          height="18"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3.05h16.94a2 2 0 0 0 1.71-3.05L13.71 3.86a2 2 0 0 0-3.42 0z" />
          <line x1="12" y1="9" x2="12" y2="13" />
          <line x1="12" y1="17" x2="12.01" y2="17" />
        </svg>
        <svg
          v-else
          xmlns="http://www.w3.org/2000/svg"
          width="18"
          height="18"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <circle cx="12" cy="12" r="10" />
          <line x1="12" y1="16" x2="12" y2="12" />
          <line x1="12" y1="8" x2="12.01" y2="8" />
        </svg>
      </span>
      <span class="feedback-mensagem">{{ mensagem }}</span>
    </div>
    <button
      v-if="fechavel"
      type="button"
      class="feedback-fechar"
      @click="visivel = false"
      aria-label="Fechar mensagem"
    >
      ×
    </button>
  </div>
</template>

<script>
export default {
  name: 'Feedback',
  props: {
    tipo: {
      type: String,
      default: 'info',
      validator: (v) => ['sucesso', 'erro', 'aviso', 'info'].includes(v),
    },
    mensagem: {
      type: String,
      required: true,
    },
    fechavel: {
      type: Boolean,
      default: true,
    },
    autoFechar: {
      type: Number,
      default: 0, // 0 = não fecha automaticamente
    },
  },
  data() {
    return {
      visivel: true,
    };
  },
  watch: {
    mensagem() {
      this.visivel = true;
      this.iniciarAutoFechar();
    },
  },
  mounted() {
    this.iniciarAutoFechar();
  },
  methods: {
    iniciarAutoFechar() {
      if (this.autoFechar > 0) {
        setTimeout(() => {
          this.visivel = false;
        }, this.autoFechar);
      }
    },
  },
};
</script>

<style scoped>
.feedback {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.85rem 1.15rem;
  border-radius: 0.5rem;
  border: 1px solid transparent;
  font-size: 0.875rem;
  animation: slideIn 0.2s ease-out;
}

.feedback-conteudo {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  flex: 1;
}

.feedback-icone {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.feedback-mensagem {
  line-height: 1.4;
}

.feedback-fechar {
  flex-shrink: 0;
  width: 1.5rem;
  height: 1.5rem;
  border: none;
  border-radius: 0.25rem;
  background: transparent;
  color: inherit;
  font-size: 1.25rem;
  line-height: 1;
  cursor: pointer;
  opacity: 0.7;
  transition: opacity 0.15s ease;
}

.feedback-fechar:hover {
  opacity: 1;
}

/* Status: Sucesso */
.feedback-sucesso {
  border-color: #bbf7d0;
  background: #ecfdf5;
  color: #047857;
}

/* Status: Erro */
.feedback-erro {
  border-color: #fecaca;
  background: #fef2f2;
  color: #b91c1c;
}

/* Status: Aviso */
.feedback-aviso {
  border-color: #fde68a;
  background: #fffbeb;
  color: #b45309;
}

/* Status: Info */
.feedback-info {
  border-color: #dbeafe;
  background: #eff6ff;
  color: #1d4ed8;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-0.5rem);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
