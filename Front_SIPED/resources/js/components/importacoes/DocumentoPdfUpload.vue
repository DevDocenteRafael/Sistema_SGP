<template>
  <div class="imp-documento">
    <p class="imp-ajuda">
      <strong>{{ arquivo.name }}</strong> é um PDF. PDFs (por exemplo, vindos do SEI) são guardados como
      <strong>documento/anexo</strong> e vinculados a um registro — o sistema não lê o PDF nem cria registros a partir dele.
    </p>

    <div class="imp-documento__grid">
      <div class="imp-campo">
        <label for="doc-modulo">Módulo do documento</label>
        <SearchableSelect
          id="doc-modulo"
          input-id="doc-modulo"
          v-model="form.modulo"
          :options="modulos"
          placeholder="Selecione o módulo"
          @change="aoTrocarModulo"
        />
      </div>

      <div class="imp-campo">
        <label for="doc-registro">Registro existente</label>
        <SearchableSelect
          id="doc-registro"
          input-id="doc-registro"
          v-model="form.registro_id"
          :options="registros"
          :disabled="!form.modulo || carregandoRegistros"
          empty-option="Ainda não há registro (informar metadados)"
        />
        <small class="imp-ajuda">
          {{ carregandoRegistros ? 'Carregando registros...' : 'Se o registro ainda não existe, informe o título agora e vincule depois, ou cadastre-o no módulo e anexe o PDF por lá.' }}
        </small>
      </div>

      <div class="imp-campo">
        <label for="doc-titulo">Título do documento <span v-if="!form.registro_id">(obrigatório sem registro)</span></label>
        <input id="doc-titulo" v-model="form.titulo" type="text" maxlength="255" :placeholder="tituloPadrao" />
      </div>

      <div class="imp-campo">
        <label for="doc-sei">Processo SEI</label>
        <input id="doc-sei" v-model="form.processo_sei" type="text" maxlength="100" placeholder="Ex: 0001.000001/2026-01" />
      </div>

      <div class="imp-campo imp-campo--full">
        <label for="doc-descricao">Descrição</label>
        <textarea id="doc-descricao" v-model="form.descricao" rows="2" maxlength="2000"></textarea>
      </div>
    </div>

    <p v-if="erro" class="alert alert-error">{{ erro }}</p>

    <div class="imp-acoes">
      <button type="button" class="btn-secundario" :disabled="enviando" @click="$emit('cancelar')">Trocar arquivo</button>
      <router-link
        v-if="form.modulo && !form.registro_id && rotaModulo"
        class="btn-secundario"
        :to="rotaModulo"
      >
        Cadastrar registro em {{ labelModulo }}
      </router-link>
      <button type="button" class="btn-primario" :disabled="!podeEnviar || enviando" @click="enviar">
        {{ enviando ? 'Guardando...' : 'Guardar documento' }}
      </button>
    </div>
  </div>
</template>

<script>
import { MODULOS_DOCUMENTO } from '../../utils/documentos';

export default {
  name: 'DocumentoPdfUpload',
  props: {
    arquivo: { type: File, required: true },
  },
  emits: ['cancelar', 'enviado'],
  data() {
    return {
      modulosMeta: [],
      registros: [],
      carregandoRegistros: false,
      enviando: false,
      erro: '',
      form: {
        modulo: '',
        registro_id: '',
        titulo: '',
        processo_sei: '',
        descricao: '',
      },
    };
  },
  computed: {
    modulos() {
      return this.modulosMeta.map((item) => ({ value: item.value, label: item.label }));
    },
    tituloPadrao() {
      return (this.arquivo?.name || '').replace(/\.pdf$/i, '');
    },
    labelModulo() {
      return this.modulosMeta.find((item) => item.value === this.form.modulo)?.label || '';
    },
    rotaModulo() {
      const rota = this.modulosMeta.find((item) => item.value === this.form.modulo)?.rota;
      return rota ? { path: rota, query: { novo: '1' } } : null;
    },
    podeEnviar() {
      if (!this.form.modulo) return false;
      return Boolean(this.form.registro_id) || Boolean(this.form.titulo.trim());
    },
  },
  async mounted() {
    try {
      const { data } = await window.axios.get('/api/documentos', { params: { sem_vinculo: 1 } });
      this.modulosMeta = data.meta?.modulos || [];
    } catch (error) {
      this.erro = error.response?.data?.message || 'Não foi possível carregar os módulos de documento.';
    }
  },
  methods: {
    async aoTrocarModulo() {
      this.form.registro_id = '';
      this.registros = [];
      const config = MODULOS_DOCUMENTO[this.form.modulo];
      if (!config) return;

      this.carregandoRegistros = true;
      try {
        const { data } = await window.axios.get(config.endpoint, { params: { per_page: 100, ciclo_id: 'todos' } });
        const lista = Array.isArray(data.data) ? data.data : [];
        this.registros = lista.map((item) => {
          const detalhe = config.detalhe(item);
          return { value: String(item.id), label: `${config.titulo(item) || `#${item.id}`}${detalhe ? ` — ${detalhe}` : ''}` };
        });
      } catch (error) {
        this.erro = error.response?.data?.message || 'Não foi possível carregar os registros do módulo.';
      } finally {
        this.carregandoRegistros = false;
      }
    },
    async enviar() {
      if (!this.podeEnviar || this.enviando) return;

      const form = new FormData();
      form.append('arquivo', this.arquivo);
      form.append('modulo', this.form.modulo);
      if (this.form.registro_id) form.append('registro_id', String(this.form.registro_id));
      if (this.form.titulo.trim()) form.append('titulo', this.form.titulo.trim());
      if (this.form.processo_sei.trim()) form.append('processo_sei', this.form.processo_sei.trim());
      if (this.form.descricao.trim()) form.append('descricao', this.form.descricao.trim());

      this.enviando = true;
      this.erro = '';
      try {
        const { data } = await window.axios.post('/api/documentos', form, { headers: { 'Content-Type': 'multipart/form-data' } });
        this.$emit('enviado', data);
      } catch (error) {
        this.erro = error.response?.data?.message || 'Não foi possível guardar o documento.';
      } finally {
        this.enviando = false;
      }
    },
  },
};
</script>

<style scoped src="../../../css/Importacoes.css"></style>
<style scoped src="../../../css/ImportacoesComponentes.css"></style>
