<template>
  <div
    class="flux-raia-node"
    :style="{
      '--raia-fundo': data?.cor?.fundo || 'rgba(241, 245, 249, 0.7)',
      '--raia-borda': data?.cor?.borda || '#cbd5e1',
      '--raia-faixa': data?.cor?.faixa || '#64748b',
    }"
  >
    <div class="flux-raia-faixa">
      <span class="flux-raia-titulo">{{ data?.label || 'Raia' }}</span>
    </div>
  </div>
</template>

<script setup>
defineProps({
  id: { type: String, required: true },
  data: { type: Object, default: () => ({}) },
});
</script>
