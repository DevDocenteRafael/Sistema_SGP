<template>
  <!-- Modo compacto (tabelas): só a bolinha do semáforo + "?" com tooltip acessível. -->
  <span
    v-if="compacto"
    class="indicador-prazo-compacto"
    :class="classeIndicador"
  >
    <span class="indicador-prazo-ponto indicador-prazo-ponto--grande" aria-hidden="true"></span>
    <span class="indicador-prazo-sr">{{ textoCompleto }}</span>
    <SgpTooltip
      :text="textoCompleto"
      mode="icon"
      :placement="placement"
      :label="`Explicar prazo: ${statusLabel}`"
    >
      <span class="indicador-prazo-ajuda" aria-hidden="true">?</span>
    </SgpTooltip>
  </span>

  <div v-else class="indicador-prazo">
    <div class="indicador-prazo-visual" :class="classeIndicador">
      <span class="indicador-prazo-ponto" aria-hidden="true"></span>
      <SgpHelpLabel :label="statusLabel" :help="textoAjuda" />
    </div>
    <span v-if="mostrarData && dataPrazo" class="indicador-prazo-data">{{ dataPrazo }}</span>
  </div>
</template>

<script>
import SgpHelpLabel from '../ui/SgpHelpLabel.vue';
import SgpTooltip from '../ui/SgpTooltip.vue';

/** Semáforo com exatamente três estados. */
const LABELS = {
  verde: 'Vigente',
  amarelo: 'Atenção',
  vermelho: 'Vencida',
};

const AJUDA = {
  verde: 'Vencimento ainda fora da janela de atenção.',
  amarelo: 'O prazo entrou na janela preventiva, mas ainda não venceu.',
  vermelho: 'A data de vencimento já passou.',
};

export default {
  name: 'IndicadorPrazo',
  components: { SgpHelpLabel, SgpTooltip },
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
    /** Explicação do estado; se omitida, usa o texto padrão do semáforo. */
    explicacao: {
      type: String,
      default: '',
    },
    dataPrazo: {
      type: String,
      default: '',
    },
    /** Rótulo da data no tooltip (ex.: "Vencimento da Ata"). */
    rotuloData: {
      type: String,
      default: 'Vencimento',
    },
    mostrarData: {
      type: Boolean,
      default: true,
    },
    compacto: {
      type: Boolean,
      default: false,
    },
    placement: {
      type: String,
      default: 'left',
    },
  },
  computed: {
    classeIndicador() {
      return `indicador-${this.status}`;
    },
    statusLabel() {
      return this.label || LABELS[this.status] || this.status;
    },
    textoAjuda() {
      return this.explicacao || AJUDA[this.status] || `Status do prazo: ${this.statusLabel}`;
    },
    textoCompleto() {
      const partes = [`${this.statusLabel}.`, this.textoAjuda];
      if (this.dataPrazo && this.dataPrazo !== '—') {
        partes.push(`${this.rotuloData}: ${this.dataPrazo}.`);
      }
      return partes.join(' ');
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

.indicador-prazo-compacto {
  display: inline-flex;
  align-items: center;
  gap: 0.1rem;
  white-space: nowrap;
}

.indicador-prazo-ponto--grande {
  width: 0.85rem;
  height: 0.85rem;
  box-shadow: 0 0 0 2px var(--sgp-surface, #fff), 0 0 0 3px currentColor;
}

.indicador-prazo-compacto :deep(.sgp-tooltip__bubble) {
  width: 15rem;
  white-space: normal;
}

.indicador-prazo-ajuda {
  font-size: 0.72rem;
  font-weight: 700;
  line-height: 1;
}

.indicador-prazo-sr {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
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
  background: #d97706;
}

.indicador-vermelho {
  border-color: #fecaca;
  background: #fef2f2;
  color: #b91c1c;
}

.indicador-vermelho .indicador-prazo-ponto {
  background: #b91c1c;
}

.indicador-prazo-compacto.indicador-verde,
.indicador-prazo-compacto.indicador-amarelo,
.indicador-prazo-compacto.indicador-vermelho {
  background: transparent;
  border: 0;
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

:global(html[data-theme='dark']) .indicador-prazo-compacto {
  background: transparent !important;
}

:global(html[data-theme='dark']) .indicador-prazo-data {
  color: var(--sgp-text-muted, #94a3b8);
}
</style>
