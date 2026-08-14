<template>
  <span class="badge-status" :class="[`badge-${tipo}`, { 'badge-tamanho-grande': tamanho === 'grande' }]">
    <span v-if="icone" class="badge-icone" aria-hidden="true">{{ icone }}</span>
    {{ label }}
  </span>
</template>

<script>
export default {
  name: 'BadgeStatus',
  props: {
    tipo: {
      type: String,
      required: true,
      validator: (v) => [
        'planejamento',
        'andamento',
        'concluido',
        'arquivado',
        'sucesso',
        'aviso',
        'erro',
        'info',
      ].includes(v),
    },
    label: {
      type: String,
      required: true,
    },
    icone: {
      type: String,
      default: '',
    },
    tamanho: {
      type: String,
      default: 'padrao',
      validator: (v) => ['padrao', 'grande'].includes(v),
    },
  },
};
</script>

<style scoped>
.badge-status {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.2rem 0.55rem;
  border-radius: 999px;
  border: 1px solid transparent;
  font-size: 0.72rem;
  font-weight: 600;
  white-space: nowrap;
}

.badge-tamanho-grande {
  padding: 0.35rem 0.85rem;
  font-size: 0.8rem;
}

.badge-icone {
  font-size: 0.7rem;
  line-height: 1;
}

/* Status padrão do TR */
.badge-planejamento {
  border-color: #dbeafe;
  background: #eff6ff;
  color: #1d4ed8;
}

.badge-andamento {
  border-color: #fde68a;
  background: #fffbeb;
  color: #b45309;
}

.badge-concluido {
  border-color: #bbf7d0;
  background: #ecfdf5;
  color: #047857;
}

.badge-arquivado {
  border-color: #e5e7eb;
  background: #f3f4f6;
  color: #6b7280;
}

/* Status genéricos */
.badge-sucesso {
  border-color: #bbf7d0;
  background: #ecfdf5;
  color: #047857;
}

.badge-aviso {
  border-color: #fde68a;
  background: #fffbeb;
  color: #b45309;
}

.badge-erro {
  border-color: #fecaca;
  background: #fef2f2;
  color: #b91c1c;
}

.badge-info {
  border-color: #dbeafe;
  background: #eff6ff;
  color: #1d4ed8;
}
</style>
