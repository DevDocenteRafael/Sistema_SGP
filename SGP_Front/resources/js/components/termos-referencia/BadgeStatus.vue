<template>
  <span class="badge-status-wrap">
    <span class="badge-status" :class="[`badge-${tipo}`, { 'badge-tamanho-grande': tamanho === 'grande' }]">
      <span v-if="icone" class="badge-icone" aria-hidden="true">{{ icone }}</span>
      {{ label }}
    </span>
    <SgpTooltip
      v-if="textoAjuda"
      :text="textoAjuda"
      mode="icon"
      :label="`Explicar status ${label}`"
    />
  </span>
</template>

<script>
import SgpTooltip from '../ui/SgpTooltip.vue';
import { explicar } from '../../utils/glossario';

export default {
  name: 'BadgeStatus',
  components: { SgpTooltip },
  props: {
    tipo: {
      type: String,
      required: true,
      validator: (v) => [
        'planejamento',
        'andamento',
        'tramitacao',
        'concluido',
        'arquivado',
        'sucesso',
        'aviso',
        'erro',
        'info',
        'padrao',
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
    help: {
      type: String,
      default: '',
    },
  },
  computed: {
    textoAjuda() {
      return this.help || explicar(this.label);
    },
  },
};
</script>

<style scoped>
.badge-status-wrap {
  display: inline-flex;
  align-items: center;
  gap: 0.2rem;
}

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

.badge-tramitacao {
  border-color: #c4b5fd;
  background: #f5f3ff;
  color: #6d28d9;
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

.badge-padrao {
  border-color: #e5e7eb;
  background: #f9fafb;
  color: #4b5563;
}

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

:global(html[data-theme='dark']) .badge-planejamento,
:global(html[data-theme='dark']) .badge-info {
  border-color: #1e3a5f;
  background: #10233f;
  color: #93c5fd;
}

:global(html[data-theme='dark']) .badge-andamento,
:global(html[data-theme='dark']) .badge-aviso {
  border-color: #9a3412;
  background: #3a240f;
  color: #fdba74;
}

:global(html[data-theme='dark']) .badge-tramitacao {
  border-color: #5b21b6;
  background: #2e1065;
  color: #c4b5fd;
}

:global(html[data-theme='dark']) .badge-concluido,
:global(html[data-theme='dark']) .badge-sucesso {
  border-color: #166534;
  background: #0b2a1c;
  color: #86efac;
}

:global(html[data-theme='dark']) .badge-arquivado,
:global(html[data-theme='dark']) .badge-padrao {
  border-color: #334155;
  background: #1e293b;
  color: #94a3b8;
}

:global(html[data-theme='dark']) .badge-erro {
  border-color: #7f1d1d;
  background: #3f1212;
  color: #fecaca;
}
</style>
