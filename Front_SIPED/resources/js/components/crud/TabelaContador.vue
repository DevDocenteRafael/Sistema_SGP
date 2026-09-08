<template>
  <span class="tabela-contador">{{ texto }}</span>
</template>

<script>
import { formatRegistrosCount } from '../../utils/formatRegistrosCount';

export default {
  name: 'TabelaContador',
  props: {
    total: {
      type: [Number, String],
      default: 0,
    },
  },
  computed: {
    texto() {
      return formatRegistrosCount(this.total);
    },
  },
};
</script>
