<template>
  <div class="flux-node flux-node-shell flux-node-documento-wrap" :style="cssVars">
    <FluxHandles :connectable="connectable" />
    <div class="flux-node-documento-shape">
      <svg class="flux-node-documento-svg" viewBox="0 0 160 90" preserveAspectRatio="none" aria-hidden="true">
        <path
          d="M4 4 H156 V62 Q130 78 112 66 Q88 52 80 70 Q72 88 48 72 Q24 56 4 68 Z"
          fill="var(--flux-fundo)"
          stroke="var(--flux-borda)"
          stroke-width="5"
          stroke-linejoin="round"
        />
      </svg>
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

const label = computed(() => props.data?.label || 'Documento');
const cssVars = computed(() => ({
  '--flux-borda': CORES_SIMBOLOS.documento.borda,
  '--flux-fundo': CORES_SIMBOLOS.documento.fundo,
  '--flux-texto': CORES_SIMBOLOS.documento.texto,
}));
</script>
