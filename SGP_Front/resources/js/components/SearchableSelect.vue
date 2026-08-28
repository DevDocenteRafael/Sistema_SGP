<template>
  <div class="sgp-select" :class="{ 'is-open': aberto, 'is-disabled': disabled }">
    <button
      :id="inputId || id || undefined"
      type="button"
      class="sgp-select__trigger"
      :aria-label="ariaLabel"
      :aria-expanded="aberto ? 'true' : 'false'"
      :disabled="disabled"
      @click="alternar"
    >
      <span class="sgp-select__value" :class="{ 'is-placeholder': !temValor }">
        {{ rotuloAtual }}
      </span>
      <span class="sgp-select__chevron" aria-hidden="true">▾</span>
    </button>

    <div v-if="aberto" class="sgp-select__dropdown" @mousedown.prevent>
      <input
        ref="busca"
        v-model="termo"
        type="search"
        class="sgp-select__search"
        placeholder="Pesquisar..."
        autocomplete="off"
        @keydown.esc.prevent="fechar"
        @keydown.enter.prevent="escolherPrimeiro"
      />
      <ul class="sgp-select__list" role="listbox">
        <li
          v-if="emptyOption != null"
          class="sgp-select__option"
          :class="{ 'is-active': modelValue === '' || modelValue == null }"
          role="option"
          @click="escolher('')"
        >
          {{ emptyOption }}
        </li>
        <li
          v-for="opcao in opcoesFiltradas"
          :key="String(opcao.value)"
          class="sgp-select__option"
          :class="{ 'is-active': String(modelValue) === String(opcao.value) }"
          role="option"
          @click="escolher(opcao.value)"
        >
          {{ opcao.label }}
        </li>
        <li v-if="opcoesFiltradas.length === 0" class="sgp-select__empty">Nenhuma opção</li>
      </ul>
    </div>
  </div>
</template>

<script>
export default {
  name: 'SearchableSelect',
  props: {
    modelValue: { type: [String, Number, Boolean, null], default: '' },
    options: { type: Array, default: () => [] },
    emptyOption: { type: String, default: null },
    placeholder: { type: String, default: 'Selecione' },
    ariaLabel: { type: String, default: 'Selecionar' },
    inputId: { type: String, default: '' },
    id: { type: String, default: '' },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
  },
  emits: ['update:modelValue', 'change'],
  data() {
    return {
      aberto: false,
      termo: '',
    };
  },
  computed: {
    opcoesNormalizadas() {
      return (this.options || []).map((item) => {
        if (item != null && typeof item === 'object') {
          return {
            value: item.value ?? '',
            label: item.label ?? String(item.value ?? ''),
          };
        }

        return { value: item, label: String(item) };
      });
    },
    opcoesFiltradas() {
      const termo = this.termo.trim().toLowerCase();
      if (!termo) {
        return this.opcoesNormalizadas;
      }

      return this.opcoesNormalizadas.filter((item) => item.label.toLowerCase().includes(termo));
    },
    temValor() {
      return this.modelValue !== '' && this.modelValue != null;
    },
    rotuloAtual() {
      if (!this.temValor) {
        return this.emptyOption ?? this.placeholder;
      }

      const encontrada = this.opcoesNormalizadas.find(
        (item) => String(item.value) === String(this.modelValue),
      );

      return encontrada?.label ?? String(this.modelValue);
    },
  },
  mounted() {
    document.addEventListener('click', this.aoClicarFora);
  },
  beforeUnmount() {
    document.removeEventListener('click', this.aoClicarFora);
  },
  methods: {
    alternar() {
      if (this.disabled) return;
      this.aberto = !this.aberto;
      if (this.aberto) {
        this.termo = '';
        this.$nextTick(() => this.$refs.busca?.focus());
      }
    },
    fechar() {
      this.aberto = false;
      this.termo = '';
    },
    escolher(valor) {
      this.$emit('update:modelValue', valor);
      this.$emit('change', valor);
      this.fechar();
    },
    escolherPrimeiro() {
      if (this.emptyOption != null && !this.termo.trim()) {
        this.escolher('');
        return;
      }

      const primeira = this.opcoesFiltradas[0];
      if (primeira) {
        this.escolher(primeira.value);
      }
    },
    aoClicarFora(event) {
      if (!this.$el.contains(event.target)) {
        this.fechar();
      }
    },
  },
};
</script>
