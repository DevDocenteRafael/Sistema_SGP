<template>
  <div class="ciclo-seletor" ref="raiz">
    <button
      type="button"
      class="ciclo-seletor__trigger"
      :aria-expanded="painelAberto ? 'true' : 'false'"
      aria-haspopup="dialog"
      aria-controls="ciclo-seletor-painel"
      @click="alternarPainel"
    >
      <span class="ciclo-seletor__kicker">Ciclo</span>
      <strong class="ciclo-seletor__nome">{{ rotuloCiclo }}</strong>
      <span v-if="cicloAtivo?.atual" class="ciclo-seletor__badge">atual</span>
      <svg
        class="ciclo-seletor__chevron"
        :class="{ 'is-open': painelAberto }"
        xmlns="http://www.w3.org/2000/svg"
        width="14"
        height="14"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2.5"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
      >
        <path d="m6 9 6 6 6-6" />
      </svg>
    </button>

    <div
      v-if="painelAberto"
      id="ciclo-seletor-painel"
      class="ciclo-seletor__painel"
      role="dialog"
      aria-label="Selecionar ciclo"
    >
      <p v-if="carregando" class="ciclo-seletor__hint">Carregando ciclos…</p>
      <p v-else-if="erroLista" class="ciclo-seletor__erro">{{ erroLista }}</p>
      <ul v-else class="ciclo-seletor__lista" role="listbox" aria-label="Ciclos disponíveis">
        <li
          v-for="ciclo in ciclos"
          :key="ciclo.id"
          role="option"
          :aria-selected="cicloAtivo && String(cicloAtivo.id) === String(ciclo.id) ? 'true' : 'false'"
        >
          <button
            type="button"
            class="ciclo-seletor__item"
            :class="{ 'is-active': cicloAtivo && String(cicloAtivo.id) === String(ciclo.id) }"
            @click="selecionarCiclo(ciclo)"
          >
            <span class="ciclo-seletor__item-nome">{{ ciclo.nome }}</span>
            <span v-if="ciclo.atual" class="ciclo-seletor__badge">atual</span>
          </button>
        </li>
        <li v-if="!ciclos.length" class="ciclo-seletor__vazio">Nenhum ciclo cadastrado.</li>
      </ul>

      <div class="ciclo-seletor__acoes">
        <button
          v-if="podeEditar"
          type="button"
          class="ciclo-seletor__acao"
          @click="abrirModal('criar')"
        >
          Criar ciclo
        </button>
        <button
          v-if="podeEditar"
          type="button"
          class="ciclo-seletor__acao"
          :disabled="!ciclos.length"
          @click="abrirModal('gerar')"
        >
          Gerar próximo
        </button>
        <button
          type="button"
          class="ciclo-seletor__acao ciclo-seletor__acao--gerenciar"
          @click="irGerenciar"
        >
          Gerenciar ciclos
        </button>
      </div>
    </div>

    <div
      v-if="modal"
      class="modal-overlay ciclo-seletor-modal"
      @click.self="fecharModal"
    >
      <div
        class="modal-detalhes"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="modal === 'gerar' ? 'ciclo-modal-gerar-titulo' : 'ciclo-modal-criar-titulo'"
      >
        <div class="modal-detalhes-header">
          <h2 :id="modal === 'gerar' ? 'ciclo-modal-gerar-titulo' : 'ciclo-modal-criar-titulo'">
            {{ modal === 'gerar' ? 'Gerar próximo ciclo' : 'Criar ciclo' }}
          </h2>
          <button type="button" class="btn-fechar-x" aria-label="Fechar" @click="fecharModal">×</button>
        </div>

        <form class="ciclo-seletor-form" novalidate @submit.prevent="salvarModal">
          <p v-if="erroModal" class="alert alert-error">{{ erroModal }}</p>

          <div v-if="modal === 'gerar'" class="form-group">
            <label for="ciclo-modal-origem">Ciclo de origem <span>*</span></label>
            <select id="ciclo-modal-origem" v-model="form.origem_id" required>
              <option value="" disabled>Selecione…</option>
              <option
                v-for="ciclo in ciclos"
                :key="ciclo.id"
                :value="String(ciclo.id)"
              >
                {{ ciclo.nome }}{{ ciclo.atual ? ' (atual)' : '' }}
              </option>
            </select>
          </div>

          <div class="form-group">
            <label for="ciclo-modal-nome">Nome do ciclo <span>*</span></label>
            <input
              id="ciclo-modal-nome"
              v-model="form.nome"
              type="text"
              maxlength="80"
              required
              placeholder="Ex.: 2028 ou 2028-2029"
              autocomplete="off"
            />
            <small class="campo-ajuda">Os anos no nome ligam Metas, PCA e Eixos deste ciclo.</small>
          </div>

          <div class="form-group">
            <label for="ciclo-modal-obs">Observação</label>
            <textarea id="ciclo-modal-obs" v-model="form.observacao" rows="3" maxlength="2000" />
          </div>

          <div v-if="modal === 'gerar'" class="form-group">
            <label class="campo-check">
              <input v-model="form.copiar_cursos" type="checkbox" />
              Copiar cursos do ciclo de origem
            </label>
            <p class="campo-ajuda ciclo-seletor-aviso" :class="{ 'is-alerta': form.copiar_cursos && cursosOrigemCount > 0 }">
              {{ avisoCopiaCursos }}
            </p>
          </div>

          <div class="form-group">
            <label class="campo-check">
              <input
                v-if="modal === 'gerar'"
                v-model="form.marcar_atual"
                type="checkbox"
              />
              <input
                v-else
                v-model="form.atual"
                type="checkbox"
              />
              {{ modal === 'gerar' ? 'Definir o ciclo gerado como atual' : 'Definir como ciclo atual' }}
            </label>
          </div>

          <div class="modal-detalhes-actions">
            <button type="button" class="btn-secondary" :disabled="salvando" @click="fecharModal">
              Cancelar
            </button>
            <button type="submit" class="btn-salvar" :disabled="salvando">
              {{ textoSalvarModal }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { podeAcessarMenu, podeEditarDados } from '../scripts/auth';
import {
  CICLO_CONTEXTO_EVENTO,
  buscarCiclosPortfolio,
  invalidarCacheCiclos,
  lerCicloContexto,
  salvarCicloContexto,
} from '../scripts/cicloContexto';
import {
  combinarValidacoes,
  extrairErroApi,
  tamanhoMaximo,
  textoObrigatorio,
} from '../utils/validacao';

const ENDPOINT = '/api/portfolio-ciclos';

function formVazio() {
  return {
    nome: '',
    observacao: '',
    atual: false,
    origem_id: '',
    marcar_atual: true,
    copiar_cursos: true,
  };
}

export default {
  name: 'CicloSeletor',
  data() {
    return {
      painelAberto: false,
      modal: null,
      carregando: false,
      salvando: false,
      ciclos: [],
      cicloAtivo: lerCicloContexto(),
      erroLista: '',
      erroModal: '',
      form: formVazio(),
    };
  },
  computed: {
    podeEditar() {
      return podeEditarDados();
    },
    rotuloCiclo() {
      return this.cicloAtivo?.nome || 'Selecionar';
    },
    textoSalvarModal() {
      if (this.salvando) {
        return this.modal === 'gerar' ? 'Gerando…' : 'Salvando…';
      }
      return this.modal === 'gerar' ? 'Gerar ciclo' : 'Criar ciclo';
    },
    origemSelecionada() {
      if (!this.form.origem_id) {
        return null;
      }
      return this.ciclos.find((item) => String(item.id) === String(this.form.origem_id)) || null;
    },
    cursosOrigemCount() {
      const origem = this.origemSelecionada;
      if (!origem) {
        return 0;
      }
      return Number(origem.composicao?.cursos ?? origem.cursos_count ?? 0);
    },
    avisoCopiaCursos() {
      if (this.modal !== 'gerar' || !this.form.copiar_cursos) {
        return this.modal === 'gerar'
          ? 'O ciclo será criado vazio (sem copiar cursos). Metas, PCA e Eixos entram pelos anos do nome.'
          : '';
      }
      const total = this.cursosOrigemCount;
      const nome = this.origemSelecionada?.nome || 'origem';
      if (total === 1) {
        return `Será copiado 1 curso do ciclo ${nome}. Metas, PCA e Eixos não são clonados.`;
      }
      return `Serão copiados ${total} cursos do ciclo ${nome}. Metas, PCA e Eixos não são clonados.`;
    },
  },
  mounted() {
    this.aoMudarContexto = (evento) => {
      const detalhe = evento.detail || {};
      this.cicloAtivo = Object.prototype.hasOwnProperty.call(detalhe, 'ciclo')
        ? detalhe.ciclo
        : detalhe;
    };
    this.aoCliqueFora = (evento) => {
      if (!this.painelAberto || this.modal) {
        return;
      }
      const raiz = this.$refs.raiz;
      if (raiz && !raiz.contains(evento.target)) {
        this.painelAberto = false;
      }
    };
    this.aoTecla = (evento) => {
      if (evento.key === 'Escape') {
        if (this.modal) {
          this.fecharModal();
        } else if (this.painelAberto) {
          this.painelAberto = false;
        }
      }
    };

    window.addEventListener(CICLO_CONTEXTO_EVENTO, this.aoMudarContexto);
    document.addEventListener('click', this.aoCliqueFora, true);
    document.addEventListener('keydown', this.aoTecla);
    this.inicializar();
  },
  beforeUnmount() {
    window.removeEventListener(CICLO_CONTEXTO_EVENTO, this.aoMudarContexto);
    document.removeEventListener('click', this.aoCliqueFora, true);
    document.removeEventListener('keydown', this.aoTecla);
  },
  methods: {
    async inicializar() {
      try {
        await this.carregarCiclos();
        if (!this.cicloAtivo?.id) {
          const atual = this.ciclos.find((item) => item.atual) || this.ciclos[0];
          if (atual) {
            salvarCicloContexto(atual);
            this.cicloAtivo = lerCicloContexto();
          }
        }
      } catch {
        /* lista vazia / sem rede — seletor continua utilizável */
      }
    },

    async carregarCiclos() {
      this.carregando = true;
      this.erroLista = '';
      try {
        invalidarCacheCiclos();
        this.ciclos = await buscarCiclosPortfolio();
      } catch (error) {
        this.erroLista = extrairErroApi(error, 'Não foi possível carregar os ciclos.');
        this.ciclos = [];
      } finally {
        this.carregando = false;
      }
    },

    alternarPainel() {
      this.painelAberto = !this.painelAberto;
      if (this.painelAberto) {
        this.carregarCiclos();
      }
    },

    selecionarCiclo(ciclo) {
      salvarCicloContexto(ciclo);
      this.cicloAtivo = lerCicloContexto();
      this.painelAberto = false;
    },

    abrirModal(tipo) {
      if (!this.podeEditar) {
        return;
      }
      this.painelAberto = false;
      this.modal = tipo;
      this.erroModal = '';
      const atual = this.ciclos.find((item) => item.atual) || this.ciclos[0];
      this.form = {
        ...formVazio(),
        origem_id: tipo === 'gerar' && atual ? String(atual.id) : '',
        marcar_atual: true,
        atual: false,
      };
    },

    fecharModal() {
      if (this.salvando) {
        return;
      }
      this.modal = null;
      this.erroModal = '';
      this.form = formVazio();
    },

    validarModal() {
      return combinarValidacoes(
        textoObrigatorio(this.form.nome, 'Informe o nome do ciclo.'),
        tamanhoMaximo(this.form.nome, 80, 'O nome deve ter no máximo 80 caracteres.'),
        this.form.observacao
          ? tamanhoMaximo(this.form.observacao, 2000, 'A observação deve ter no máximo 2000 caracteres.')
          : '',
        this.modal === 'gerar' && !this.form.origem_id
          ? 'Selecione o ciclo de origem.'
          : '',
      );
    },

    async salvarModal() {
      if (!this.podeEditar) {
        this.erroModal = 'Seu perfil não tem permissão para alterar ciclos.';
        return;
      }

      const erro = this.validarModal();
      if (erro) {
        this.erroModal = erro;
        return;
      }

      this.salvando = true;
      this.erroModal = '';

      try {
        let response;

        if (this.modal === 'gerar') {
          response = await window.axios.post(`${ENDPOINT}/gerar-proximo`, {
            origem_id: this.form.origem_id || null,
            nome: this.form.nome.trim(),
            observacao: this.form.observacao.trim() || null,
            marcar_atual: this.form.marcar_atual,
            copiar_cursos: this.form.copiar_cursos,
          });
        } else {
          response = await window.axios.post(ENDPOINT, {
            nome: this.form.nome.trim(),
            observacao: this.form.observacao.trim() || null,
            atual: this.form.atual,
          });
        }

        const ciclo = response.data?.ciclo;
        invalidarCacheCiclos();
        await this.carregarCiclos();

        if (ciclo?.id) {
          salvarCicloContexto(ciclo);
          this.cicloAtivo = lerCicloContexto();
        }

        this.modal = null;
        this.form = formVazio();
      } catch (error) {
        this.erroModal = extrairErroApi(error, 'Não foi possível salvar o ciclo.');
      } finally {
        this.salvando = false;
      }
    },

    irGerenciar() {
      this.painelAberto = false;
      if (!podeAcessarMenu('ciclos-portfolio')) {
        return;
      }

      const rotaAtual = this.$route?.fullPath || '/app/cursos';
      const query = rotaAtual.startsWith('/app/ciclos-portfolio')
        ? {}
        : { voltar: rotaAtual };

      this.$router.push({ path: '/app/ciclos-portfolio', query }).catch(() => {
        const qs = query.voltar ? `?voltar=${encodeURIComponent(query.voltar)}` : '';
        window.location.assign(`/app/ciclos-portfolio${qs}`);
      });
    },
  },
};
</script>

<style scoped>
.ciclo-seletor {
  position: relative;
  display: inline-flex;
  max-width: 100%;
}

.ciclo-seletor__trigger {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  max-width: min(100%, 22rem);
  min-height: 2.35rem;
  padding: 0.3rem 0.65rem 0.3rem 0.75rem;
  border: 1px solid var(--sgp-border, #dbe3ef);
  border-radius: 0.55rem;
  background: var(--sgp-surface, #fff);
  color: var(--sgp-text, #111827);
  cursor: pointer;
  box-shadow: none;
}

.ciclo-seletor__trigger:hover {
  border-color: color-mix(in srgb, var(--sgp-brand, #003f7d) 35%, var(--sgp-border, #dbe3ef));
}

.ciclo-seletor__trigger:focus-visible {
  outline: 2px solid var(--sgp-accent, #f57c00);
  outline-offset: 2px;
}

.ciclo-seletor__kicker {
  flex: 0 0 auto;
  color: var(--sgp-text-muted, #6b7280);
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.ciclo-seletor__nome {
  overflow: hidden;
  color: var(--sgp-brand, #003f7d);
  font-size: 0.875rem;
  font-weight: 700;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.ciclo-seletor__badge {
  flex: 0 0 auto;
  padding: 0.08rem 0.38rem;
  border-radius: 999px;
  background: color-mix(in srgb, #10b981 16%, transparent);
  color: #047857;
  font-size: 0.62rem;
  font-weight: 700;
  letter-spacing: 0.03em;
  text-transform: uppercase;
}

.ciclo-seletor__chevron {
  flex: 0 0 auto;
  color: var(--sgp-text-muted, #6b7280);
  transition: transform 0.15s ease;
}

.ciclo-seletor__chevron.is-open {
  transform: rotate(180deg);
}

.ciclo-seletor__painel {
  position: absolute;
  top: calc(100% + 0.35rem);
  right: 0;
  z-index: 60;
  width: min(20rem, calc(100vw - 1.5rem));
  padding: 0.45rem;
  border: 1px solid var(--sgp-border, #dbe3ef);
  border-radius: 0.65rem;
  background: var(--sgp-surface, #fff);
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12);
}

.ciclo-seletor__lista {
  margin: 0;
  padding: 0;
  list-style: none;
  max-height: 14rem;
  overflow-y: auto;
}

.ciclo-seletor__item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  width: 100%;
  padding: 0.55rem 0.65rem;
  border: 0;
  border-radius: 0.45rem;
  background: transparent;
  color: var(--sgp-text, #111827);
  font-size: 0.875rem;
  text-align: left;
  cursor: pointer;
}

.ciclo-seletor__item:hover,
.ciclo-seletor__item.is-active {
  background: var(--sgp-surface-muted, #f3f6fa);
}

.ciclo-seletor__item-nome {
  overflow: hidden;
  font-weight: 600;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.ciclo-seletor__vazio,
.ciclo-seletor__hint,
.ciclo-seletor__erro {
  margin: 0;
  padding: 0.65rem;
  color: var(--sgp-text-muted, #6b7280);
  font-size: 0.8125rem;
}

.ciclo-seletor__erro {
  color: #b91c1c;
}

.ciclo-seletor__acoes {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  margin-top: 0.35rem;
  padding-top: 0.35rem;
  border-top: 1px solid var(--sgp-border, #e5e7eb);
}

.ciclo-seletor__acao {
  width: 100%;
  padding: 0.55rem 0.65rem;
  border: 0;
  border-radius: 0.45rem;
  background: transparent;
  color: var(--sgp-brand, #003f7d);
  font-size: 0.8125rem;
  font-weight: 650;
  text-align: left;
  cursor: pointer;
}

.ciclo-seletor__acao:hover:not(:disabled) {
  background: var(--sgp-surface-muted, #f3f6fa);
}

.ciclo-seletor__acao:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.ciclo-seletor__acao--gerenciar {
  color: var(--sgp-text-muted, #4b5563);
  font-weight: 600;
}

.ciclo-seletor-form {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
  padding: 0 1rem 1rem;
}

.ciclo-seletor-form .form-group {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.ciclo-seletor-form label {
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--sgp-text, #111827);
}

.ciclo-seletor-form input[type='text'],
.ciclo-seletor-form select,
.ciclo-seletor-form textarea {
  width: 100%;
  padding: 0.55rem 0.65rem;
  border: 1px solid var(--sgp-border, #d1d5db);
  border-radius: 0.45rem;
  background: var(--sgp-surface, #fff);
  color: var(--sgp-text, #111827);
  font: inherit;
}

.ciclo-seletor-form .campo-check {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  width: fit-content;
  max-width: 100%;
  font-weight: 500;
  cursor: pointer;
}

.ciclo-seletor-form .campo-check input[type='checkbox'] {
  width: 1rem !important;
  height: 1rem !important;
  min-width: 1rem;
  max-width: 1rem;
  margin: 0;
  padding: 0;
  flex-shrink: 0;
  appearance: auto;
  -webkit-appearance: checkbox;
  accent-color: var(--sgp-brand, #003f7d);
  background: transparent !important;
  border: none !important;
  box-shadow: none !important;
  cursor: pointer;
}

.ciclo-seletor-form .campo-ajuda {
  color: var(--sgp-text-muted, #6b7280);
  font-size: 0.75rem;
}

.ciclo-seletor-aviso {
  margin: 0.35rem 0 0;
  padding: 0.55rem 0.65rem;
  border-radius: 0.45rem;
  background: var(--sgp-surface-muted, #f3f6fa);
}

.ciclo-seletor-aviso.is-alerta {
  background: color-mix(in srgb, #f59e0b 14%, transparent);
  color: #92400e;
}

.ciclo-seletor-modal .modal-detalhes {
  width: min(28rem, calc(100vw - 1.5rem));
}

html[data-theme='dark'] .ciclo-seletor__badge {
  background: color-mix(in srgb, #34d399 22%, transparent);
  color: #6ee7b7;
}
</style>
