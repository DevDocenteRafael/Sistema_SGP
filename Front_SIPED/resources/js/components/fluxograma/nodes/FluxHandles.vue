<template>
  <Handle
    id="top"
    type="source"
    :position="Position.Top"
    :connectable="connectable"
  />
  <Handle
    id="right"
    type="source"
    :position="Position.Right"
    :connectable="connectable"
  />
  <Handle
    id="bottom"
    type="source"
    :position="Position.Bottom"
    :connectable="connectable"
  />
  <Handle
    id="left"
    type="source"
    :position="Position.Left"
    :connectable="connectable"
  />
</template>

<script setup>
import { Handle, Position } from '@vue-flow/core';

defineProps({
  connectable: { type: Boolean, default: true },
});
</script>
