<template>
  <div class="indicador-prazo">
    <div class="indicador-prazo-visual" :class="classeIndicador" :title="textoPrazo">
      <span class="indicador-prazo-ponto" aria-hidden="true"></span>
      <span class="indicador-prazo-label">{{ statusLabel }}</span>
    </div>
    <span v-if="mostrarData" class="indicador-prazo-data">{{ dataPrazo }}</span>
  </div>
</template>

<script>
export default {
  name: 'IndicadorPrazo',
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
        amarelo: 'Próximo ao prazo',
        vermelho: 'Atrasado',
      };
      return labels[this.status] || this.status;
    },
    textoPrazo() {
      return `Status do prazo: ${this.statusLabel}`;
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
  color: #6b7280;
  font-size: 0.8rem;
  font-weight: 500;
}
</style>
