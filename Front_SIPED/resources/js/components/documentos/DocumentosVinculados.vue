<template>
  <section class="docs-vinculados" :aria-labelledby="tituloId">
    <div class="docs-vinculados__head">
      <h3 :id="tituloId">Documentos PDF</h3>
      <label v-if="podeEnviar" class="docs-vinculados__enviar" :class="{ 'is-disabled': enviando }">
        <input
          ref="arquivo"
          type="file"
          accept=".pdf,application/pdf"
          :disabled="enviando"
          @change="enviar"
        />
        {{ enviando ? 'Enviando...' : 'Anexar PDF' }}
      </label>
    </div>

    <p v-if="erro" class="docs-vinculados__erro" role="alert">{{ erro }}</p>
    <p v-if="carregando" class="docs-vinculados__vazio">Carregando documentos...</p>
    <p v-else-if="!documentos.length" class="docs-vinculados__vazio">Nenhum documento PDF vinculado.</p>

    <ul v-else class="docs-vinculados__lista">
      <li v-for="doc in documentos" :key="doc.id">
        <div class="docs-vinculados__info">
          <strong>{{ doc.titulo }}</strong>
          <small>
            {{ doc.arquivo_nome }} · {{ formatarTamanho(doc.tamanho) }}
            <template v-if="doc.processo_sei"> · SEI {{ doc.processo_sei }}</template>
            · {{ formatarDataHora(doc.created_at) }}<template v-if="doc.usuario"> · {{ doc.usuario }}</template>
          </small>
        </div>
        <div class="docs-vinculados__acoes">
          <button type="button" class="btn-secundario btn-sm" @click="baixar(doc)">Baixar</button>
          <button v-if="podeRemover" type="button" class="btn-secundario btn-sm" @click="remover(doc)">Remover</button>
        </div>
      </li>
    </ul>
  </section>
</template>

<script>
import { podeEditarDados } from '../../scripts/auth';
import { baixarArquivo, ehPdf, formatarDataHora, formatarTamanho } from '../../utils/documentos';

let seq = 0;

export default {
  name: 'DocumentosVinculados',
  props: {
    modulo: { type: String, required: true },
    registroId: { type: [Number, String], required: true },
  },
  data() {
    seq += 1;
    return {
      tituloId: `docs-vinculados-${seq}`,
      documentos: [],
      carregando: false,
      enviando: false,
      erro: '',
    };
  },
  computed: {
    podeEnviar() {
      return podeEditarDados();
    },
    podeRemover() {
      return podeEditarDados();
    },
  },
  watch: {
    registroId() {
      this.carregar();
    },
  },
  mounted() {
    this.carregar();
  },
  methods: {
    formatarTamanho,
    formatarDataHora,
    async carregar() {
      if (!this.registroId) return;
      this.carregando = true;
      this.erro = '';
      try {
        const { data } = await window.axios.get('/api/documentos', {
          params: { modulo: this.modulo, registro_id: this.registroId },
        });
        this.documentos = Array.isArray(data.data) ? data.data : [];
      } catch (error) {
        this.erro = error.response?.data?.message || 'Não foi possível carregar os documentos.';
      } finally {
        this.carregando = false;
      }
    },
    async enviar(event) {
      const arquivo = event.target.files?.[0];
      event.target.value = '';
      if (!arquivo) return;
      if (!ehPdf(arquivo)) {
        this.erro = 'Selecione um arquivo PDF.';
        return;
      }

      const form = new FormData();
      form.append('arquivo', arquivo);
      form.append('modulo', this.modulo);
      form.append('registro_id', String(this.registroId));

      this.enviando = true;
      this.erro = '';
      try {
        await window.axios.post('/api/documentos', form, { headers: { 'Content-Type': 'multipart/form-data' } });
        await this.carregar();
      } catch (error) {
        this.erro = error.response?.data?.message || 'Não foi possível enviar o PDF.';
      } finally {
        this.enviando = false;
      }
    },
    async baixar(doc) {
      try {
        await baixarArquivo(`/api/documentos/${doc.id}/arquivo`, doc.arquivo_nome);
      } catch {
        this.erro = 'Não foi possível baixar o documento.';
      }
    },
    async remover(doc) {
      if (!window.confirm(`Remover o documento "${doc.titulo}"? O registro não é alterado.`)) return;
      try {
        await window.axios.delete(`/api/documentos/${doc.id}`);
        await this.carregar();
      } catch (error) {
        this.erro = error.response?.data?.message || 'Não foi possível remover o documento.';
      }
    },
  },
};
</script>

<style scoped>
.docs-vinculados {
  margin-top: 1rem;
}

.docs-vinculados__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
}

.docs-vinculados__head h3 {
  margin: 0;
  font-size: 0.95rem;
}

.docs-vinculados__enviar {
  display: inline-flex;
  align-items: center;
  padding: 0.35rem 0.75rem;
  border: 1px solid var(--sgp-border, #d1d5db);
  border-radius: 0.45rem;
  background: var(--sgp-surface, #fff);
  color: var(--sgp-brand, #003f7d);
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
}

.docs-vinculados__enviar:focus-within {
  outline: 2px solid var(--sgp-brand, #003f7d);
  outline-offset: 2px;
}

.docs-vinculados__enviar.is-disabled {
  opacity: 0.6;
  cursor: progress;
}

.docs-vinculados__enviar input {
  position: absolute;
  width: 1px;
  height: 1px;
  opacity: 0;
}

.docs-vinculados__lista {
  margin: 0.6rem 0 0;
  padding: 0;
  list-style: none;
}

.docs-vinculados__lista li {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  padding: 0.55rem 0;
  border-top: 1px solid var(--sgp-border, #e5e7eb);
}

.docs-vinculados__info {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.docs-vinculados__info small {
  color: var(--sgp-text-muted, #6b7280);
  overflow-wrap: anywhere;
}

.docs-vinculados__acoes {
  display: flex;
  gap: 0.4rem;
}

.btn-sm {
  height: auto;
  padding: 0.3rem 0.6rem;
  border: 1px solid var(--sgp-border, #d1d5db);
  border-radius: 0.4rem;
  background: var(--sgp-surface, #fff);
  color: var(--sgp-text, #374151);
  font-size: 0.78rem;
  cursor: pointer;
}

.docs-vinculados__vazio {
  margin: 0.5rem 0 0;
  color: var(--sgp-text-muted, #6b7280);
  font-size: 0.85rem;
}

.docs-vinculados__erro {
  margin: 0.5rem 0 0;
  color: #b91c1c;
  font-size: 0.85rem;
}
</style>
