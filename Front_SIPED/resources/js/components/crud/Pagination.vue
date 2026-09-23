<template>
  <nav
    v-if="totalPages > 1 || totalRecords > 0"
    class="sgp-pagination"
    :aria-label="ariaLabel"
  >
    <div class="sgp-pagination-navigation">
      <button
        type="button"
        class="sgp-pagination-button"
        :disabled="disabled || currentPage <= 1"
        aria-label="Primeira página"
        @click="$emit('change', 1)"
      >
        ««
      </button>
    <button
      type="button"
      class="sgp-pagination-button"
      :disabled="disabled || currentPage <= 1"
      aria-label="Página anterior"
      @click="$emit('change', currentPage - 1)"
    >
      «
    </button>
    <button
      v-for="pagina in paginas"
      :key="pagina"
      type="button"
      class="sgp-pagination-button"
      :class="{ ativo: pagina === currentPage }"
      :disabled="pagina === '...' || disabled"
      :aria-current="pagina === currentPage ? 'page' : undefined"
      @click="pagina !== '...' && $emit('change', pagina)"
    >
      {{ pagina }}
    </button>
    <button
      type="button"
      class="sgp-pagination-button"
      :disabled="disabled || currentPage >= totalPages"
      aria-label="Próxima página"
      @click="$emit('change', currentPage + 1)"
    >
      »
    </button>
      <button
        type="button"
        class="sgp-pagination-button"
        :disabled="disabled || currentPage >= totalPages"
        aria-label="Última página"
        @click="$emit('change', totalPages)"
      >
        »»
      </button>
    </div>

    <div class="sgp-pagination-options">
      <label class="sgp-pagination-field">
        <span>Registros por página</span>
        <select
          :value="pageSize"
          :disabled="disabled"
          aria-label="Registros por página"
          @change="$emit('per-page-change', Number($event.target.value))"
        >
          <option v-for="opcao in opcoesPorPagina" :key="opcao" :value="opcao">{{ opcao }}</option>
        </select>
      </label>

      <span class="sgp-pagination-separador" aria-hidden="true">|</span>

      <form class="sgp-pagination-field sgp-pagination-go" @submit.prevent="irParaPagina">
        <label for="sgp-pagina-destino">Ir para página:</label>
        <input
          id="sgp-pagina-destino"
          v-model="paginaDestino"
          type="number"
          min="1"
          :max="totalPages"
          step="1"
          inputmode="numeric"
          :disabled="disabled"
          aria-label="Número da página"
          @keydown="validarTecla"
          @input="normalizarEntrada"
        />
        <button type="submit" class="sgp-pagination-go-button" :disabled="disabled">Ir</button>
      </form>
      <span v-if="mensagemPagina" class="sgp-pagination-error" role="alert">{{ mensagemPagina }}</span>
    </div>
  </nav>
</template>

<script>
export default {
  name: 'Pagination',
  emits: ['change', 'per-page-change'],
  props: {
    currentPage: { type: Number, default: 1 },
    totalPages: { type: Number, default: 1 },
    disabled: { type: Boolean, default: false },
    ariaLabel: { type: String, default: 'Paginação' },
    totalRecords: { type: Number, default: 0 },
    pageSize: { type: Number, default: 10 },
    pageSizeOptions: { type: Array, default: () => [10, 20, 50, 100] },
  },
  data() {
    return {
      paginaDestino: String(this.currentPage),
      mensagemPagina: '',
    };
  },
  computed: {
    opcoesPorPagina() {
      return [...new Set([...this.pageSizeOptions, this.pageSize])].sort((a, b) => a - b);
    },
    paginas() {
      const total = Math.max(1, this.totalPages);
      const atual = Math.min(Math.max(1, this.currentPage), total);
      if (total <= 7) {
        return Array.from({ length: total }, (_, indice) => indice + 1);
      }

      const paginas = [1];
      const inicio = Math.max(2, atual - 1);
      const fim = Math.min(total - 1, atual + 1);
      if (inicio > 2) paginas.push('...');
      for (let pagina = inicio; pagina <= fim; pagina += 1) paginas.push(pagina);
      if (fim < total - 1) paginas.push('...');
      paginas.push(total);
      return paginas;
    },
  },
  watch: {
    currentPage(novaPagina) {
      this.paginaDestino = String(novaPagina);
      this.mensagemPagina = '';
    },
  },
  methods: {
    validarTecla(evento) {
      if (['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'Home', 'End', 'Enter'].includes(evento.key)) {
        return;
      }

      if (!/^\d$/.test(evento.key)) {
        evento.preventDefault();
      }
    },

    normalizarEntrada(evento) {
      const valor = String(evento.target.value || '').replace(/\D/g, '');
      this.paginaDestino = valor;
      this.mensagemPagina = '';
    },

    irParaPagina() {
      const pagina = Number(this.paginaDestino);
      if (!Number.isInteger(pagina) || pagina < 1) {
        this.mensagemPagina = 'Informe uma página inteira positiva.';
        return;
      }

      if (pagina > this.totalPages) {
        this.mensagemPagina = `A última página disponível é a ${this.totalPages}.`;
        return;
      }

      this.mensagemPagina = '';
      this.$emit('change', pagina);
    },
  },
};
</script>