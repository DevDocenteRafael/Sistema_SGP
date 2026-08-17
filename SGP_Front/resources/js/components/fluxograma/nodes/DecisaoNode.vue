<template>
  <div class="flux-node flux-node-shell flux-node-decisao" :style="cssVars">
    <Handle id="top" type="source" :position="Position.Top" :connectable="connectable" />
    <Handle id="left" type="source" :position="Position.Left" :connectable="connectable" />
    <Handle id="sim" type="source" :position="Position.Right" :connectable="connectable" />
    <Handle id="nao" type="source" :position="Position.Bottom" :connectable="connectable" />

    <div class="flux-node-diamond">
      <span class="flux-node-label">{{ label }}</span>
    </div>
    <span class="flux-handle-hint hint-sim">Sim</span>
    <span class="flux-handle-hint hint-nao">Não</span>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Handle, Position } from '@vue-flow/core';
import { CORES_SIMBOLOS } from '../tipos';

const props = defineProps({
  id: { type: String, required: true },
  data: { type: Object, default: () => ({}) },
  connectable: { type: Boolean, default: true },
});

const label = computed(() => props.data?.label || 'Decisão?');
const cssVars = computed(() => {
  const cor = CORES_SIMBOLOS.decisao;
  return {
    '--flux-borda': cor.borda,
    '--flux-fundo': cor.fundo,
    '--flux-texto': cor.texto,
  };
});
</script>
