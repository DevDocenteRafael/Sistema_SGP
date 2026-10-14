<template>
  <span
    class="sgp-tooltip"
    :class="{
      'sgp-tooltip--open': aberto,
      'sgp-tooltip--block': block,
      'sgp-tooltip--text': mode === 'text',
    }"
    @mouseenter="abrirHover"
    @mouseleave="fecharHover"
    @focusin="abrirFocus"
    @focusout="fecharFocus"
  >
    <button
      v-if="mode === 'icon'"
      type="button"
      class="sgp-tooltip__trigger sgp-tooltip__trigger--icon"
      :aria-label="ariaLabel"
      :aria-describedby="aberto ? tooltipId : undefined"
      :aria-expanded="aberto ? 'true' : 'false'"
      @click.stop="toggleClick"
    >
      <slot>
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <circle cx="12" cy="12" r="10" />
          <path d="M12 16v-4" />
          <path d="M12 8h.01" />
        </svg>
      </slot>
    </button>

    <span
      v-else-if="mode === 'text'"
      class="sgp-tooltip__trigger sgp-tooltip__trigger--text"
      tabindex="0"
      :aria-describedby="aberto ? tooltipId : undefined"
    >
      <slot />
    </span>

    <span
      v-else
      class="sgp-tooltip__trigger sgp-tooltip__trigger--inline"
      tabindex="0"
      role="button"
      :aria-label="ariaLabel"
      :aria-describedby="aberto ? tooltipId : undefined"
      :aria-expanded="aberto ? 'true' : 'false'"
      @click.stop="toggleClick"
      @keydown.enter.prevent="toggleClick"
      @keydown.space.prevent="toggleClick"
    >
      <slot />
    </span>

    <Transition name="sgp-tooltip-fade">
      <span
        v-show="aberto"
        :id="tooltipId"
        role="tooltip"
        class="sgp-tooltip__bubble"
        :class="`sgp-tooltip__bubble--${placement}`"
      >
        {{ text }}
      </span>
    </Transition>
  </span>
</template>

<script>
let tooltipSeq = 0;

export default {
  name: 'SgpTooltip',
  props: {
    text: { type: String, required: true },
    /** icon = botão (i); text = hover/foco sem ação; inline = interação por clique */
    mode: { type: String, default: 'icon' },
    placement: { type: String, default: 'top' },
    label: { type: String, default: 'Mais informações' },
    block: { type: Boolean, default: false },
  },
  data() {
    tooltipSeq += 1;
    return {
      tooltipId: `sgp-tip-${tooltipSeq}`,
      porHover: false,
      porFocus: false,
      porClick: false,
    };
  },
  computed: {
    aberto() {
      return Boolean(this.text) && (this.porHover || this.porFocus || this.porClick);
    },
    ariaLabel() {
      return this.label || 'Mais informações';
    },
  },
  mounted() {
    this._onDocClick = (evento) => {
      if (!this.porClick) return;
      if (this.$el.contains(evento.target)) return;
      this.porClick = false;
    };
    document.addEventListener('click', this._onDocClick);
    document.addEventListener('keydown', this._onEsc = (e) => {
      if (e.key === 'Escape') this.fecharTudo();
    });
  },
  beforeUnmount() {
    document.removeEventListener('click', this._onDocClick);
    document.removeEventListener('keydown', this._onEsc);
  },
  methods: {
    abrirHover() {
      this.porHover = true;
    },
    fecharHover() {
      this.porHover = false;
    },
    abrirFocus() {
      this.porFocus = true;
    },
    fecharFocus() {
      this.porFocus = false;
    },
    toggleClick() {
      this.porClick = !this.porClick;
    },
    fecharTudo() {
      this.porHover = false;
      this.porFocus = false;
      this.porClick = false;
    },
  },
};
</script>
