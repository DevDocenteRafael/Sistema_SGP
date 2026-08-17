<template>
  <div class="flux-node flux-node-shell" :style="cssVars">
    <FluxHandles :connectable="connectable" />
    <div class="flux-node-predefinido">
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
  connectable: { type: Boolean, default: true },
});

const label = computed(() => props.data?.label || 'Subprocesso');
const cssVars = computed(() => {
  const cor = CORES_SIMBOLOS.predefinido;
  return {
    '--flux-borda': cor.borda,
    '--flux-fundo': cor.fundo,
    '--flux-texto': cor.texto,
  };
});
</script>
