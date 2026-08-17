<template>
  <div class="flux-node flux-node-shell" :style="cssVars">
    <FluxHandles :connectable="connectable" />
    <div class="flux-node-oval" :class="variantClass">
      <div class="flux-node-label">{{ label }}</div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { CORES_SIMBOLOS } from '../tipos';
import FluxHandles from './FluxHandles.vue';

const props = defineProps({
  id: { type: String, required: true },
  data: { type: Object, default: () => ({}) },
  type: { type: String, default: 'inicio' },
  connectable: { type: Boolean, default: true },
});

const isFim = computed(() => props.type === 'fim');
const label = computed(() => props.data?.label || (isFim.value ? 'Fim' : 'Início'));
const variantClass = computed(() => (isFim.value ? 'is-fim' : 'is-inicio'));

const cssVars = computed(() => {
  const cor = CORES_SIMBOLOS[props.type] || CORES_SIMBOLOS.inicio;
  return {
    '--flux-borda': cor.borda,
    '--flux-fundo': cor.fundo,
    '--flux-texto': cor.texto,
  };
});
</script>
