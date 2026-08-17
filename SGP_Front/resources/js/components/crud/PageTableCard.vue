<template>
  <component :is="tag" :class="cardClass" :aria-label="ariaLabel || undefined">
    <div :class="headerClass">
      <TabelaContador :total="total" />
      <slot name="extra" />
    </div>
    <slot />
  </component>
</template>

<script>
import TabelaContador from './TabelaContador.vue';

export default {
  name: 'PageTableCard',
  components: { TabelaContador },
  props: {
    total: {
      type: [Number, String],
      default: 0,
    },
    ariaLabel: {
      type: String,
      default: '',
    },
    tag: {
      type: String,
      default: 'section',
    },
    cardClass: {
      type: String,
      default: 'tabela-card',
    },
    headerClass: {
      type: String,
      default: 'tabela-header',
    },
  },
};
</script>
