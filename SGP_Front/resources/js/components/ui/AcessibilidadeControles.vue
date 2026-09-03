<template>
  <div class="sgp-a11y" role="group" aria-label="Acessibilidade">
    <p class="sgp-a11y-title">Acessibilidade</p>

    <div class="sgp-a11y-row" role="group" aria-label="Tema">
      <button
        type="button"
        class="sgp-a11y-btn"
        :class="{ 'is-active': theme === 'light' }"
        :aria-pressed="theme === 'light' ? 'true' : 'false'"
        @click="definirTema('light')"
      >
        Claro
      </button>
      <button
        type="button"
        class="sgp-a11y-btn"
        :class="{ 'is-active': theme === 'dark' }"
        :aria-pressed="theme === 'dark' ? 'true' : 'false'"
        @click="definirTema('dark')"
      >
        Escuro
      </button>
    </div>

    <div class="sgp-a11y-row" role="group" aria-label="Tamanho do texto">
      <button
        type="button"
        class="sgp-a11y-btn sgp-a11y-btn--icon"
        aria-label="Diminuir tamanho do texto"
        :disabled="!podeDiminuir"
        @click="diminuirFonte"
      >
        A−
      </button>
      <button
        type="button"
        class="sgp-a11y-btn"
        aria-label="Tamanho padrão do texto"
        :title="`Tamanho do texto: ${percentual}%`"
        @click="resetarFonte"
      >
        A
      </button>
      <button
        type="button"
        class="sgp-a11y-btn sgp-a11y-btn--icon"
        aria-label="Aumentar tamanho do texto"
        :disabled="!podeAumentar"
        @click="aumentarFonte"
      >
        A+
      </button>
    </div>
  </div>
</template>

<script>
import {
  obterAcessibilidade,
  definirTema,
  aumentarFonte,
  diminuirFonte,
  resetarFonte,
  podeAumentarFonte,
  podeDiminuirFonte,
  onAcessibilidadeChange,
} from '../../utils/acessibilidade';

export default {
  name: 'AcessibilidadeControles',
  data() {
    const atual = obterAcessibilidade();
    return {
      theme: atual.theme,
      fontScale: atual.fontScale,
      unsubscribe: null,
    };
  },
  computed: {
    percentual() {
      return Math.round(this.fontScale * 100);
    },
    podeAumentar() {
      return this.fontScale < 2 - 0.001 && podeAumentarFonte();
    },
    podeDiminuir() {
      return this.fontScale > 0.75 + 0.001 && podeDiminuirFonte();
    },
  },
  mounted() {
    this.unsubscribe = onAcessibilidadeChange((estado) => {
      this.theme = estado.theme;
      this.fontScale = estado.fontScale;
    });
  },
  beforeUnmount() {
    if (this.unsubscribe) this.unsubscribe();
  },
  methods: {
    definirTema,
    aumentarFonte,
    diminuirFonte,
    resetarFonte,
  },
};
</script>
