<template>
  <span v-if="!texto">—</span>
  <a
    v-else-if="hrefDireto"
    :href="hrefDireto"
    class="codigo-sig-link"
    target="_blank"
    rel="noopener noreferrer"
    :aria-label="`${texto} — abrir curso no SIG (nova aba)`"
  >{{ texto }}</a>
  <span v-else class="codigo-sig">
    <span>{{ texto }}</span>
    <a
      v-if="atalho"
      :href="atalho"
      class="codigo-sig-atalho"
      target="_blank"
      rel="noopener noreferrer"
      title="Abrir o SIG (localize o curso pelo código)"
      :aria-label="`Abrir o SIG para localizar o código ${texto} (nova aba)`"
    >SIG ↗</a>
  </span>
</template>

<script>
import { hrefCursoSig } from '../../utils/processoSei';
import { carregarSistemasExternos, sistemasExternos } from '../../utils/sistemasExternos';

/**
 * Código SIG oficial. Só vira link direto com o padrão oficial (SIG_CURSO_URL);
 * até lá mostra o código e um atalho para o SIG — sem fingir integração.
 */
export default {
  name: 'CodigoSig',
  props: {
    valor: { type: [String, Number], default: '' },
    mostrarAtalho: { type: Boolean, default: true },
  },
  computed: {
    texto() {
      return String(this.valor ?? '').trim();
    },
    hrefDireto() {
      return hrefCursoSig(this.texto, sistemasExternos);
    },
    atalho() {
      return this.mostrarAtalho ? sistemasExternos.sig?.base_url || null : null;
    },
  },
  mounted() {
    carregarSistemasExternos();
  },
};
</script>

<style scoped>
.codigo-sig {
  display: inline-flex;
  align-items: baseline;
  gap: 0.35rem;
  flex-wrap: wrap;
}

.codigo-sig-link {
  color: #003f7d;
  font-weight: 600;
  text-decoration: underline;
  text-underline-offset: 2px;
}

.codigo-sig-atalho {
  color: var(--sgp-text-muted, #6b7280);
  font-size: 0.72rem;
  font-weight: 600;
  text-decoration: none;
  white-space: nowrap;
}

.codigo-sig-atalho:hover,
.codigo-sig-atalho:focus-visible,
.codigo-sig-link:hover {
  color: #f57c00;
  text-decoration: underline;
}
</style>
