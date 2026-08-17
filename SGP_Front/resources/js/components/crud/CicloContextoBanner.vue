<template>
  <div class="ciclo-contexto-banner" role="status">
    <div>
      <strong v-if="cicloAtivo">Portfólio {{ cicloAtivo.nome }}</strong>
      <strong v-else>Nenhum ciclo selecionado</strong>
      <span v-if="cicloAtivo?.atual" class="ciclo-contexto-atual">atual</span>
      <p v-if="cicloAtivo">
        Somente os registros deste ciclo.
        <template v-if="textoAnos">Anos no nome: {{ textoAnos }}.</template>
      </p>
      <p v-else>Escolha o ciclo para ver Cursos, Metas, PCA ou Eixos deste período.</p>
    </div>
    <button type="button" class="ciclo-contexto-trocar" @click="trocarCiclo">
      {{ cicloAtivo ? 'Trocar ciclo' : 'Escolher ciclo' }}
    </button>
  </div>
</template>

<script>
import { CICLO_CONTEXTO_EVENTO, lerCicloContexto } from '../../scripts/cicloContexto';

export default {
  name: 'CicloContextoBanner',
  props: {
    ciclo: {
      type: Object,
      default: null,
    },
    modulo: {
      type: String,
      default: '',
    },
  },
  data() {
    return {
      cicloLocal: lerCicloContexto(this.modulo || null),
    };
  },
  computed: {
    cicloAtivo() {
      const origem = this.ciclo && this.ciclo.id ? this.ciclo : this.cicloLocal;

      return origem?.id && origem?.nome ? origem : null;
    },
    textoAnos() {
      const anos = this.cicloAtivo?.anos;

      return Array.isArray(anos) && anos.length ? anos.join(', ') : '';
    },
  },
  mounted() {
    this.aoMudarContexto = (evento) => {
      const detalhe = evento.detail || {};
      if (detalhe.modulo && this.modulo && detalhe.modulo !== this.modulo) {
        return;
      }

      this.cicloLocal = Object.prototype.hasOwnProperty.call(detalhe, 'ciclo')
        ? detalhe.ciclo
        : detalhe;
    };
    window.addEventListener(CICLO_CONTEXTO_EVENTO, this.aoMudarContexto);
  },
  beforeUnmount() {
    window.removeEventListener(CICLO_CONTEXTO_EVENTO, this.aoMudarContexto);
  },
  methods: {
    trocarCiclo() {
      this.$router.push({
        path: '/app/ciclos-portfolio',
        query: {
          voltar: this.$route.path,
          modulo: this.modulo || undefined,
        },
      });
    },
  },
};
</script>

<style scoped>
.ciclo-contexto-banner {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem 1.25rem;
  margin: 1rem 2rem 0;
  padding: 0.85rem 1.1rem;
  border: 1px solid #c7d7ea;
  border-radius: 0.65rem;
  background: #f4f8fd;
}

.ciclo-contexto-banner strong {
  color: #003f7d;
  font-size: 0.95rem;
}

.ciclo-contexto-banner p {
  margin: 0.2rem 0 0;
  color: #4b5563;
  font-size: 0.8rem;
}

.ciclo-contexto-atual {
  display: inline-block;
  margin-left: 0.45rem;
  padding: 0.1rem 0.45rem;
  border-radius: 999px;
  background: #ecfdf5;
  color: #047857;
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.ciclo-contexto-trocar {
  height: 2.25rem;
  padding: 0 0.95rem;
  border: 1px solid #003f7d;
  border-radius: 0.45rem;
  background: #fff;
  color: #003f7d;
  font-weight: 600;
  cursor: pointer;
}

.ciclo-contexto-trocar:hover {
  background: #eff6ff;
}
</style>
