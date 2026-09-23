<template>
  <component :is="tag" :class="cardClass" :aria-label="ariaLabel || undefined">
    <div :class="headerClass">
      <TabelaContador :total="total" />
      <slot name="extra" />
    </div>
    <slot />
    <Pagination
      v-if="pagination"
      :current-page="pagination.current_page"
      :total-pages="pagination.last_page"
      :total-records="pagination.total"
      :page-size="pagination.per_page"
      :disabled="paginationDisabled"
      @change="$emit('page-change', $event)"
      @per-page-change="$emit('per-page-change', $event)"
    />
  </component>
</template>

<script>
import TabelaContador from './TabelaContador.vue';
import Pagination from './Pagination.vue';

export default {
  name: 'PageTableCard',
  emits: ['page-change', 'per-page-change'],
  components: { TabelaContador, Pagination },
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
    pagination: {
      type: Object,
      default: null,
    },
    paginationDisabled: {
      type: Boolean,
      default: false,
    },
  },
};
</script>
