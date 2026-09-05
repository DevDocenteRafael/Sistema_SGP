<template>
  <div
    ref="root"
    class="sgp-a11y-fab"
    :class="{ 'is-open': aberto }"
  >
    <div
      v-if="aberto"
      id="sgp-a11y-panel"
      class="sgp-a11y-fab__panel"
      role="dialog"
      aria-label="Opções de acessibilidade"
    >
      <div class="sgp-a11y-fab__section">
        <p class="sgp-a11y-fab__label">
          <span class="sgp-a11y-fab__label-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
          </span>
          Tema
        </p>
        <div class="sgp-a11y-fab__row" role="group" aria-label="Tema">
          <button
            type="button"
            class="sgp-a11y-fab__chip"
            :class="{ 'is-active': theme === 'light' }"
            :aria-pressed="theme === 'light' ? 'true' : 'false'"
            title="Modo claro"
            @click="definirTema('light')"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
            <span>Claro</span>
          </button>
          <button
            type="button"
            class="sgp-a11y-fab__chip"
            :class="{ 'is-active': theme === 'dark' }"
            :aria-pressed="theme === 'dark' ? 'true' : 'false'"
            title="Modo escuro"
            @click="definirTema('dark')"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
            <span>Escuro</span>
          </button>
        </div>
      </div>

      <div class="sgp-a11y-fab__section">
        <p class="sgp-a11y-fab__label">
          <span class="sgp-a11y-fab__label-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2v20" fill="currentColor" stroke="none"/><path d="M12 2a10 10 0 0 1 0 20"/></svg>
          </span>
          Contraste
        </p>
        <button
          type="button"
          class="sgp-a11y-fab__chip sgp-a11y-fab__chip--wide"
          :class="{ 'is-active': highContrast }"
          :aria-pressed="highContrast ? 'true' : 'false'"
          @click="alternarAltoContraste"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 2a10 10 0 0 1 0 20z" fill="currentColor"/></svg>
          <span>{{ highContrast ? 'Alto contraste ativo' : 'Ativar alto contraste' }}</span>
        </button>
      </div>

      <div class="sgp-a11y-fab__section">
        <p class="sgp-a11y-fab__label">
          <span class="sgp-a11y-fab__label-icon" aria-hidden="true">T</span>
          Texto
          <span class="sgp-a11y-fab__meta">{{ percentual }}%</span>
        </p>
        <div class="sgp-a11y-fab__row" role="group" aria-label="Tamanho do texto">
          <button
            type="button"
            class="sgp-a11y-fab__chip sgp-a11y-fab__chip--icon"
            aria-label="Diminuir tamanho do texto"
            :disabled="!podeDiminuir"
            @click="diminuirFonte"
          >
            A−
          </button>
          <button
            type="button"
            class="sgp-a11y-fab__chip"
            aria-label="Tamanho padrão do texto"
            title="Restaurar tamanho padrão"
            @click="resetarFonte"
          >
            A
          </button>
          <button
            type="button"
            class="sgp-a11y-fab__chip sgp-a11y-fab__chip--icon"
            aria-label="Aumentar tamanho do texto"
            :disabled="!podeAumentar"
            @click="aumentarFonte"
          >
            A+
          </button>
        </div>
      </div>
    </div>

    <button
      type="button"
      class="sgp-a11y-fab__toggle"
      :aria-expanded="aberto ? 'true' : 'false'"
      aria-controls="sgp-a11y-panel"
      aria-label="Abrir ou fechar acessibilidade"
      title="Acessibilidade"
      @click="toggle"
    >
      <svg
        v-if="!aberto"
        xmlns="http://www.w3.org/2000/svg"
        width="22"
        height="22"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
      >
        <circle cx="12" cy="12" r="10" />
        <circle cx="12" cy="7.5" r="1.5" fill="currentColor" stroke="none" />
        <path d="M9.5 20v-1.2a2.5 2.5 0 0 1 2.5-2.5h0a2.5 2.5 0 0 1 2.5 2.5V20" />
        <path d="M8 12.5h8" />
        <path d="M12 11v4" />
      </svg>
      <svg
        v-else
        xmlns="http://www.w3.org/2000/svg"
        width="20"
        height="20"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2.25"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
      >
        <path d="M18 6 6 18" />
        <path d="m6 6 12 12" />
      </svg>
    </button>
  </div>
</template>

<script>
import {
  obterAcessibilidade,
  definirTema,
  alternarAltoContraste,
  aumentarFonte,
  diminuirFonte,
  resetarFonte,
  podeAumentarFonte,
  podeDiminuirFonte,
  onAcessibilidadeChange,
} from '../../utils/acessibilidade';

export default {
  name: 'AcessibilidadeFlutuante',
  data() {
    const atual = obterAcessibilidade();
    return {
      aberto: false,
      theme: atual.theme,
      highContrast: atual.highContrast,
      fontScale: atual.fontScale,
      unsubscribe: null,
    };
  },
  computed: {
    percentual() {
      return Math.round(this.fontScale * 100);
    },
    podeAumentar() {
      return podeAumentarFonte();
    },
    podeDiminuir() {
      return podeDiminuirFonte();
    },
  },
  mounted() {
    this.unsubscribe = onAcessibilidadeChange((estado) => {
      this.theme = estado.theme;
      this.highContrast = estado.highContrast;
      this.fontScale = estado.fontScale;
    });
    document.addEventListener('pointerdown', this.onPointerDown, true);
    document.addEventListener('keydown', this.onKeydown);
  },
  beforeUnmount() {
    if (this.unsubscribe) this.unsubscribe();
    document.removeEventListener('pointerdown', this.onPointerDown, true);
    document.removeEventListener('keydown', this.onKeydown);
  },
  methods: {
    definirTema,
    alternarAltoContraste,
    aumentarFonte,
    diminuirFonte,
    resetarFonte,
    toggle() {
      this.aberto = !this.aberto;
    },
    fechar() {
      this.aberto = false;
    },
    onPointerDown(event) {
      if (!this.aberto) return;
      const root = this.$refs.root;
      if (root && !root.contains(event.target)) {
        this.fechar();
      }
    },
    onKeydown(event) {
      if (event.key === 'Escape' && this.aberto) {
        this.fechar();
      }
    },
  },
};
</script>
