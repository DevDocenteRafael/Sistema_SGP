<template>
  <div class="sgp-table-scroll" ref="root">
    <div
      ref="viewport"
      class="sgp-table-scroll__viewport"
      :class="{ 'sgp-table-scroll__viewport--native-hidden': needsScroll }"
      @scroll="onViewportScroll"
    >
      <slot />
    </div>

    <div
      v-show="needsScroll"
      ref="bar"
      class="sgp-table-scroll__sticky-bar"
      role="scrollbar"
      aria-orientation="horizontal"
      :aria-valuenow="ariaValueNow"
      aria-valuemin="0"
      aria-valuemax="100"
      aria-label="Rolagem horizontal da tabela"
      tabindex="0"
      @scroll="onBarScroll"
      @keydown="onBarKeydown"
    >
      <div class="sgp-table-scroll__spacer" :style="{ width: `${contentWidth}px` }" />
    </div>
  </div>
</template>

<script>
export default {
  name: 'SgpTableScroll',
  data() {
    return {
      needsScroll: false,
      contentWidth: 0,
      maxScroll: 0,
      syncing: false,
      ariaValueNow: 0,
    };
  },
  mounted() {
    this.ro = typeof ResizeObserver !== 'undefined'
      ? new ResizeObserver(() => this.medir())
      : null;

    this.$nextTick(() => {
      this.medir();
      const viewport = this.$refs.viewport;
      if (viewport && this.ro) {
        this.ro.observe(viewport);
        const first = viewport.firstElementChild;
        if (first) this.ro.observe(first);
      }
    });

    window.addEventListener('resize', this.medir);
  },
  beforeUnmount() {
    window.removeEventListener('resize', this.medir);
    if (this.ro) this.ro.disconnect();
  },
  updated() {
    this.$nextTick(() => this.medir());
  },
  methods: {
    medir() {
      const viewport = this.$refs.viewport;
      if (!viewport) return;

      const contentWidth = Math.max(viewport.scrollWidth, 0);
      const clientWidth = viewport.clientWidth;
      const maxScroll = Math.max(contentWidth - clientWidth, 0);

      this.contentWidth = contentWidth;
      this.maxScroll = maxScroll;
      this.needsScroll = maxScroll > 2;

      const bar = this.$refs.bar;
      if (bar && this.needsScroll && !this.syncing) {
        this.syncing = true;
        bar.scrollLeft = viewport.scrollLeft;
        this.syncing = false;
      }

      this.atualizarAria(viewport.scrollLeft);
    },
    atualizarAria(scrollLeft) {
      if (this.maxScroll <= 0) {
        this.ariaValueNow = 0;
        return;
      }
      this.ariaValueNow = Math.round((scrollLeft / this.maxScroll) * 100);
    },
    onViewportScroll() {
      if (this.syncing) return;
      const viewport = this.$refs.viewport;
      const bar = this.$refs.bar;
      if (!viewport || !bar) return;

      this.syncing = true;
      bar.scrollLeft = viewport.scrollLeft;
      this.atualizarAria(viewport.scrollLeft);
      this.syncing = false;
    },
    onBarScroll() {
      if (this.syncing) return;
      const viewport = this.$refs.viewport;
      const bar = this.$refs.bar;
      if (!viewport || !bar) return;

      this.syncing = true;
      viewport.scrollLeft = bar.scrollLeft;
      this.atualizarAria(bar.scrollLeft);
      this.syncing = false;
    },
    onBarKeydown(event) {
      const viewport = this.$refs.viewport;
      if (!viewport) return;

      const step = 48;
      let next = viewport.scrollLeft;

      if (event.key === 'ArrowRight') next += step;
      else if (event.key === 'ArrowLeft') next -= step;
      else if (event.key === 'Home') next = 0;
      else if (event.key === 'End') next = this.maxScroll;
      else return;

      event.preventDefault();
      viewport.scrollLeft = Math.max(0, Math.min(this.maxScroll, next));
      this.onViewportScroll();
    },
  },
};
</script>

<style scoped>
.sgp-table-scroll {
  position: relative;
  width: 100%;
}

.sgp-table-scroll__viewport {
  overflow-x: auto;
  overflow-y: visible;
  width: 100%;
}

/* Mantém a rolagem nativa, mas esconde a barra inferior (a sticky assume o controle visual) */
.sgp-table-scroll__viewport--native-hidden {
  scrollbar-width: none;
}

.sgp-table-scroll__viewport--native-hidden::-webkit-scrollbar {
  display: none;
}

.sgp-table-scroll__sticky-bar {
  position: sticky;
  bottom: 0;
  z-index: var(--sgp-z-sticky, 50);
  height: 0.875rem;
  overflow-x: auto;
  overflow-y: hidden;
  background: color-mix(in srgb, var(--sgp-surface, #fff) 88%, var(--sgp-border, #e5e7eb));
  border-top: 1px solid var(--sgp-border, #e5e7eb);
  box-shadow: 0 -4px 12px rgba(15, 23, 42, 0.08);
}

.sgp-table-scroll__sticky-bar:focus-visible {
  outline: 2px solid var(--sgp-accent, #f57c00);
  outline-offset: -2px;
}

.sgp-table-scroll__spacer {
  height: 1px;
  pointer-events: none;
}

html[data-theme='dark'] .sgp-table-scroll__sticky-bar {
  background: color-mix(in srgb, var(--sgp-surface, #1e293b) 85%, #000);
  box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.35);
}
</style>
