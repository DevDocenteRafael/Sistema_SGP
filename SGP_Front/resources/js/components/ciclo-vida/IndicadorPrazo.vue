<template>
  <div class="indicador-prazo">
    <div class="indicador-prazo-visual" :class="classeIndicador">
      <span class="indicador-prazo-ponto" aria-hidden="true"></span>
      <SgpHelpLabel :label="statusLabel" :help="textoPrazo" />
    </div>
    <span v-if="mostrarData && dataPrazo" class="indicador-prazo-data">{{ dataPrazo }}</span>
  </div>
</template>

<script>
import SgpHelpLabel from '../ui/SgpHelpLabel.vue';

export default {
  name: 'IndicadorPrazo',
  components: { SgpHelpLabel },
  props: {
    status: {
      type: String,
      required: true,
      validator: (v) => ['verde', 'amarelo', 'vermelho'].includes(v),
    },
    label: {
      type: String,
      default: null,
    },
    dataPrazo: {
      type: String,
      default: '',
    },
    mostrarData: {
      type: Boolean,
      default: true,
    },
  },
  computed: {
    classeIndicador() {
      return `indicador-${this.status}`;
    },
    statusLabel() {
      if (this.label) return this.label;
      const labels = {
        verde: 'No prazo',
        amarelo: 'Atenção',
        vermelho: 'Crítico',
      };
      return labels[this.status] || this.status;
    },
    textoPrazo() {
      const ajuda = {
        verde: 'Dentro do prazo esperado (semáforo verde).',
        amarelo: 'Prazo em atenção — próximo do limite.',
        vermelho: 'Prazo crítico ou vencido — ação urgente.',
      };
      return ajuda[this.status] || `Status do prazo: ${this.statusLabel}`;
    },
  },
};
</script>

<style scoped>
.indicador-prazo {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.indicador-prazo-visual {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.2rem 0.55rem;
  border-radius: 999px;
  border: 1px solid transparent;
  font-size: 0.72rem;
  font-weight: 600;
  white-space: nowrap;
}

.indicador-prazo-ponto {
  display: inline-block;
  width: 0.5rem;
  height: 0.5rem;
  border-radius: 50%;
}

.indicador-verde {
  border-color: #bbf7d0;
  background: #ecfdf5;
  color: #047857;
}

.indicador-verde .indicador-prazo-ponto {
  background: #047857;
}

.indicador-amarelo {
  border-color: #fde68a;
  background: #fffbeb;
  color: #b45309;
}

.indicador-amarelo .indicador-prazo-ponto {
  background: #b45309;
}

.indicador-vermelho {
  border-color: #fecaca;
  background: #fef2f2;
  color: #b91c1c;
}

.indicador-vermelho .indicador-prazo-ponto {
  background: #b91c1c;
}

.indicador-prazo-data {
  color: var(--sgp-text-muted, #6b7280);
  font-size: 0.8rem;
  font-weight: 500;
}

:global(html[data-theme='dark']) .indicador-verde {
  border-color: #166534;
  background: #0b2a1c;
  color: #86efac;
}

:global(html[data-theme='dark']) .indicador-verde .indicador-prazo-ponto {
  background: #86efac;
}

:global(html[data-theme='dark']) .indicador-amarelo {
  border-color: #9a3412;
  background: #3a240f;
  color: #fdba74;
}

:global(html[data-theme='dark']) .indicador-amarelo .indicador-prazo-ponto {
  background: #fdba74;
}

:global(html[data-theme='dark']) .indicador-vermelho {
  border-color: #7f1d1d;
  background: #3f1212;
  color: #fecaca;
}

:global(html[data-theme='dark']) .indicador-vermelho .indicador-prazo-ponto {
  background: #fecaca;
}

:global(html[data-theme='dark']) .indicador-prazo-data {
  color: var(--sgp-text-muted, #94a3b8);
}
</style>
