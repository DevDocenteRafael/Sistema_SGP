<template>
  <div class="linha-do-tempo">
    <div v-if="eventos.length === 0" class="linha-vazia">
      <p class="linha-vazia-texto">Nenhum evento registrado ainda.</p>
    </div>

    <div v-else class="timeline-container">
      <div
        v-for="(evento, index) in eventos"
        :key="evento.id || index"
        class="timeline-item"
      >
        <div class="timeline-marker" :class="evento.tipo || 'padrao'">
          <span class="timeline-icone" aria-hidden="true">●</span>
        </div>

        <div class="timeline-conteudo">
          <div class="timeline-header">
            <span class="timeline-acao">{{ evento.acao }}</span>
            <span class="timeline-data">{{ formatarData(evento.data) }}</span>
          </div>

          <p v-if="evento.usuario" class="timeline-usuario">Por: {{ evento.usuario }}</p>

          <div v-if="evento.detalhe" class="timeline-detalhe">
            <p v-if="evento.situacaoAnterior" class="detalhe-item">
              <span class="detalhe-label">De:</span>
              <span class="detalhe-valor">{{ evento.situacaoAnterior }}</span>
            </p>
            <p v-if="evento.novaSituacao" class="detalhe-item">
              <span class="detalhe-label">Para:</span>
              <span class="detalhe-valor">{{ evento.novaSituacao }}</span>
            </p>
          </div>

          <p v-if="evento.observacao" class="timeline-observacao">
            {{ evento.observacao }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'LinhaDoTempo',
  props: {
    eventos: {
      type: Array,
      default: () => [],
      validator: (arr) => Array.isArray(arr),
    },
  },
  methods: {
    formatarData(data) {
      if (!data) return '';
      try {
        const d = new Date(data);
        return d.toLocaleDateString('pt-BR', {
          day: '2-digit',
          month: '2-digit',
          year: 'numeric',
          hour: '2-digit',
          minute: '2-digit',
        });
      } catch {
        return data;
      }
    },
  },
};
</script>

<style scoped>
.linha-do-tempo {
  width: 100%;
}

.linha-vazia {
  padding: 1.5rem;
  text-align: center;
  background: #f9fafb;
  border: 1px dashed #e5e7eb;
  border-radius: 0.5rem;
}

.linha-vazia-texto {
  margin: 0;
  color: #9ca3af;
  font-size: 0.875rem;
}

.timeline-container {
  position: relative;
}

.timeline-container::before {
  content: '';
  position: absolute;
  left: 0.6rem;
  top: 0;
  bottom: 0;
  width: 2px;
  background: #e5e7eb;
}

.timeline-item {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
  position: relative;
}

.timeline-marker {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 1.5rem;
  height: 1.5rem;
  border-radius: 50%;
  background: #fff;
  border: 2px solid #e5e7eb;
  color: #6b7280;
  flex-shrink: 0;
  position: relative;
  z-index: 1;
}

.timeline-icone {
  font-size: 0.7rem;
  line-height: 1;
}

.timeline-marker.padrao {
  border-color: #003f7d;
  color: #003f7d;
}

.timeline-marker.sucesso {
  border-color: #047857;
  background: #ecfdf5;
  color: #047857;
}

.timeline-marker.aviso {
  border-color: #b45309;
  background: #fffbeb;
  color: #b45309;
}

.timeline-marker.erro {
  border-color: #b91c1c;
  background: #fef2f2;
  color: #b91c1c;
}

.timeline-marker.info {
  border-color: #1d4ed8;
  background: #eff6ff;
  color: #1d4ed8;
}

.timeline-conteudo {
  flex: 1;
  padding: 0.75rem 1rem;
  border-radius: 0.5rem;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
}

.timeline-header {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: flex-start;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.timeline-acao {
  color: #111827;
  font-size: 0.9rem;
  font-weight: 600;
}

.timeline-data {
  color: #9ca3af;
  font-size: 0.75rem;
  white-space: nowrap;
}

.timeline-usuario {
  margin: 0.35rem 0 0;
  color: #6b7280;
  font-size: 0.8rem;
}

.timeline-detalhe {
  margin: 0.5rem 0;
  padding: 0.5rem 0.75rem;
  background: #fff;
  border-left: 2px solid #dbeafe;
  border-radius: 0.25rem;
}

.detalhe-item {
  margin: 0.25rem 0;
  color: #374151;
  font-size: 0.8rem;
}

.detalhe-item:last-child {
  margin-bottom: 0;
}

.detalhe-label {
  color: #6b7280;
  font-weight: 600;
  margin-right: 0.4rem;
}

.detalhe-valor {
  color: #111827;
  font-weight: 500;
}

.timeline-observacao {
  margin: 0.5rem 0 0;
  padding: 0.5rem 0.75rem;
  background: #fffbeb;
  border-left: 2px solid #fcd34d;
  border-radius: 0.25rem;
  color: #78350f;
  font-size: 0.8rem;
  line-height: 1.4;
}
</style>
