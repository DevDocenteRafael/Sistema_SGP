<template>
  <div class="ciclo-contexto-chip" role="status" :aria-label="ariaLabel">
    <span class="ciclo-contexto-chip__dot" aria-hidden="true" />
    <div class="ciclo-contexto-chip__text">
      <span class="ciclo-contexto-chip__kicker">Ciclo</span>
      <strong class="ciclo-contexto-chip__nome">{{ cicloAtivo?.nome || 'Não selecionado' }}</strong>
      <span v-if="cicloAtivo?.atual" class="ciclo-contexto-chip__badge">atual</span>
    </div>
    <button
      type="button"
      class="ciclo-contexto-chip__btn"
      :aria-label="cicloAtivo ? 'Trocar ciclo de portfólio' : 'Escolher ciclo de portfólio'"
      @click="trocarCiclo"
    >
      {{ cicloAtivo ? 'Trocar' : 'Escolher' }}
    </button>
  </div>
</template>

<script>
import { CICLO_CONTEXTO_EVENTO, lerCicloContexto } from '../../scripts/cicloContexto';

export default {
  name: 'CicloContextoBanner',
  props: {
    ciclo: {
      type: Object,
      default: null,
    },
    modulo: {
      type: String,
      default: '',
    },
  },
  data() {
    return {
      cicloLocal: lerCicloContexto(this.modulo || null),
    };
  },
  computed: {
    cicloAtivo() {
      const origem = this.ciclo && this.ciclo.id ? this.ciclo : this.cicloLocal;
      return origem?.id && origem?.nome ? origem : null;
    },
    ariaLabel() {
      if (!this.cicloAtivo) return 'Nenhum ciclo de portfólio selecionado';
      return `Ciclo ativo: ${this.cicloAtivo.nome}${this.cicloAtivo.atual ? ' (atual)' : ''}`;
    },
  },
  mounted() {
    this.aoMudarContexto = (evento) => {
      const detalhe = evento.detail || {};
      if (detalhe.modulo && this.modulo && detalhe.modulo !== this.modulo) {
        return;
      }

      this.cicloLocal = Object.prototype.hasOwnProperty.call(detalhe, 'ciclo')
        ? detalhe.ciclo
        : detalhe;
    };
    window.addEventListener(CICLO_CONTEXTO_EVENTO, this.aoMudarContexto);
  },
  beforeUnmount() {
    window.removeEventListener(CICLO_CONTEXTO_EVENTO, this.aoMudarContexto);
  },
  methods: {
    trocarCiclo() {
      this.$router.push({
        path: '/app/ciclos-portfolio',
        query: {
          voltar: this.$route.path,
          modulo: this.modulo || undefined,
        },
      });
    },
  },
};
</script>

<style scoped>
.ciclo-contexto-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  max-width: min(100%, 22rem);
  min-height: 2.5rem;
  padding: 0.25rem 0.35rem 0.25rem 0.65rem;
  border: 1px solid var(--sgp-border, #dbe3ef);
  border-radius: 999px;
  background: var(--sgp-surface-muted, #f3f6fa);
  color: var(--sgp-text, #111827);
  box-shadow: none;
}

.ciclo-contexto-chip__dot {
  width: 0.5rem;
  height: 0.5rem;
  flex: 0 0 auto;
  border-radius: 999px;
  background: var(--sgp-accent, #f57c00);
}

.ciclo-contexto-chip__text {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  min-width: 0;
  flex: 1 1 auto;
}

.ciclo-contexto-chip__kicker {
  flex: 0 0 auto;
  color: var(--sgp-text-muted, #6b7280);
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.ciclo-contexto-chip__nome {
  overflow: hidden;
  color: var(--sgp-brand, #003f7d);
  font-size: 0.8125rem;
  font-weight: 700;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.ciclo-contexto-chip__badge {
  flex: 0 0 auto;
  padding: 0.1rem 0.4rem;
  border-radius: 999px;
  background: color-mix(in srgb, #10b981 16%, transparent);
  color: #047857;
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 0.03em;
  text-transform: uppercase;
}

.ciclo-contexto-chip__btn {
  flex: 0 0 auto;
  height: 2rem;
  padding: 0 0.75rem;
  border: 0;
  border-radius: 999px;
  background: var(--sgp-brand, #003f7d);
  color: #fff;
  font-size: 0.75rem;
  font-weight: 700;
  cursor: pointer;
}

.ciclo-contexto-chip__btn:hover {
  filter: brightness(1.08);
}

.ciclo-contexto-chip__btn:focus-visible {
  outline: 2px solid var(--sgp-accent, #f57c00);
  outline-offset: 2px;
}

html[data-theme='dark'] .ciclo-contexto-chip__badge {
  background: color-mix(in srgb, #34d399 22%, transparent);
  color: #6ee7b7;
}

@media (max-width: 640px) {
  .ciclo-contexto-chip {
    max-width: 100%;
    width: 100%;
  }
}
</style>
