<template>
  <a
    v-if="href && texto"
    :href="href"
    class="processo-sei-link"
    target="_blank"
    rel="noopener noreferrer"
    :title="titulo"
    :aria-label="`${texto} — ${titulo} (abre em nova aba)`"
  >{{ texto }}</a>
  <span v-else>{{ texto || '—' }}</span>
</template>

<script>
import { hrefProcessoSei, seiLinkDireto } from '../../utils/processoSei';
import { carregarSistemasExternos, sistemasExternos } from '../../utils/sistemasExternos';

/** Processo SEI clicável (abre o SEI em nova aba quando a URL está configurada). */
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
      return hrefProcessoSei(this.texto, sistemasExternos);
    },
    titulo() {
      return seiLinkDireto(this.texto, sistemasExternos)
        ? 'Abrir processo no SEI'
        : 'Abrir o SEI (localize o processo pelo número)';
    },
  },
  mounted() {
    carregarSistemasExternos();
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
