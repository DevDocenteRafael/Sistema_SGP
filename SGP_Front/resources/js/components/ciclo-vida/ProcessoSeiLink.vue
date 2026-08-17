<template>
  <a
    v-if="href && texto"
    :href="href"
    class="processo-sei-link"
    target="_blank"
    rel="noopener noreferrer"
    :title="titulo"
  >{{ texto }}</a>
  <span v-else>{{ texto || '—' }}</span>
</template>

<script>
import { hrefProcessoSei, SEI_BASE_URL } from '../../utils/processoSei';

export default {
  name: 'ProcessoSeiLink',
  props: {
    valor: {
      type: [String, Number],
      default: '',
    },
  },
  computed: {
    texto() {
      return String(this.valor || '').trim();
    },
    href() {
      return hrefProcessoSei(this.texto);
    },
    titulo() {
      if (/^https?:\/\//i.test(this.texto)) {
        return 'Abrir processo SEI';
      }

      return `Abrir o portal SEI (${SEI_BASE_URL})`;
    },
  },
};
</script>

<style scoped>
.processo-sei-link {
  color: #003f7d;
  font-weight: 600;
  text-decoration: underline;
  text-underline-offset: 2px;
}

.processo-sei-link:hover {
  color: #f57c00;
}
</style>
