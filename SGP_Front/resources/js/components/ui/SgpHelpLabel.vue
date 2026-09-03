<template>
  <span v-if="textoAjuda" class="sgp-help-label" :class="{ 'sgp-help-label--block': block }">
    <span class="sgp-help-label__text">{{ label }}</span>
    <SgpTooltip :text="textoAjuda" mode="icon" :label="`Explicar: ${label}`" />
  </span>
  <span v-else class="sgp-help-label" :class="{ 'sgp-help-label--block': block }">
    <span class="sgp-help-label__text">{{ label }}</span>
  </span>
</template>

<script>
import SgpTooltip from './SgpTooltip.vue';
import { explicar } from '../../utils/glossario';

export default {
  name: 'SgpHelpLabel',
  components: { SgpTooltip },
  props: {
    label: { type: String, required: true },
    /** Chave no glossário; se omitida, usa o próprio label */
    term: { type: String, default: '' },
    /** Texto explícito (tem prioridade sobre o glossário) */
    help: { type: String, default: '' },
    block: { type: Boolean, default: false },
  },
  computed: {
    textoAjuda() {
      if (this.help) return this.help;
      return explicar(this.term || this.label);
    },
  },
};
</script>
