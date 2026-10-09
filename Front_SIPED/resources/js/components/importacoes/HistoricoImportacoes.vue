<template>
  <section class="imp-historico" aria-labelledby="imp-historico-titulo">
    <div class="imp-historico__head">
      <div>
        <h2 id="imp-historico-titulo">Histórico de importações</h2>
        <p class="imp-ajuda">Cada envio fica registrado com arquivo, usuário, data/hora, ciclo e contagens. Registros incompletos podem ser abertos para correção.</p>
      </div>
      <div class="imp-historico__head-acoes">
        <TabelaContador :total="meta.total" />
        <button type="button" class="btn-secundario" :disabled="carregando" @click="carregar(1)">Atualizar</button>
      </div>
    </div>

    <p v-if="erro" class="alert alert-error">{{ erro }}</p>

    <div class="imp-tabela-wrap">
      <table class="imp-table">
        <thead>
          <tr>
            <th>Data/hora</th>
            <th>Módulo</th>
            <th>Arquivo</th>
            <th>Usuário</th>
            <th>Ciclo</th>
            <th class="num">Novos</th>
            <th class="num">Atualizados</th>
            <th class="num">Incompletos</th>
            <th class="num">Ignorados</th>
            <th class="num">Erros</th>
            <th>Situação</th>
            <th class="text-center imp-historico__acoes">Ações</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="carregando">
            <td colspan="12" class="imp-td-vazio">Carregando histórico...</td>
          </tr>
          <tr v-else-if="!itens.length">
            <td colspan="12" class="imp-td-vazio">Nenhuma importação registrada ainda.</td>
          </tr>
          <tr v-for="item in itens" v-else :key="item.id">
            <td>{{ formatarDataHora(item.created_at) }}</td>
            <td>
              {{ item.modulo_label || item.modulo }}
              <small v-if="item.tipo === 'documento'" class="imp-tag">PDF</small>
            </td>
            <td class="col-assunto">{{ item.arquivo_nome }}</td>
            <td>{{ item.usuario || '—' }}</td>
            <td>{{ item.ciclo || '—' }}</td>
            <td class="num">{{ item.novos }}</td>
            <td class="num">{{ item.atualizados }}</td>
            <td class="num" :class="{ 'imp-destaque': item.incompletos }">{{ item.incompletos }}</td>
            <td class="num">{{ item.ignorados }}</td>
            <td class="num">{{ item.erros }}</td>
            <td>
              <span class="imp-situacao" :class="`imp-situacao--${item.situacao}`">
                {{ item.situacao === 'bloqueada' ? 'Bloqueada' : 'Concluída' }}
              </span>
            </td>
            <td class="text-center acoes imp-historico__acoes">
              <button
                type="button"
                class="btn-icon btn-view"
                title="Ver detalhes"
                :aria-label="`Ver detalhes da importação ${item.id}`"
                @click="abrir(item)"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <Pagination
      :current-page="meta.current_page"
      :total-pages="meta.last_page"
      :total-records="meta.total"
      :page-size="perPage"
      :page-size-options="[10, 20, 50]"
      :disabled="carregando"
      aria-label="Paginação do histórico de importações"
      @change="carregar"
      @per-page-change="alterarRegistrosPorPagina"
    />

    <div v-if="detalhe" class="modal-overlay" @click.self="fechar">
      <div class="imp-modal" role="dialog" aria-modal="true" aria-labelledby="imp-detalhe-titulo">
        <div class="imp-modal__head">
          <h2 id="imp-detalhe-titulo">Importação #{{ detalhe.id }} · {{ detalhe.modulo_label }}</h2>
          <button type="button" class="btn-fechar-x" aria-label="Fechar" @click="fechar">×</button>
        </div>

        <p class="imp-ajuda">
          {{ formatarDataHora(detalhe.created_at) }} · {{ detalhe.usuario || '—' }}
          <template v-if="detalhe.ciclo"> · Ciclo {{ detalhe.ciclo }}</template>
          · {{ detalhe.arquivo_nome }}
        </p>
        <p v-if="detalhe.mensagem" class="imp-modal__mensagem">{{ detalhe.mensagem }}</p>

        <template v-if="detalhe.tipo === 'planilha'">
          <h3>Registros incompletos ({{ detalhe.incompletos }})</h3>
          <p v-if="!incompletos.length" class="imp-ajuda">Nenhum registro entrou incompleto.</p>
          <ul v-else class="imp-lista-incompletos">
            <li v-for="registro in incompletos" :key="registro.id">
              <div>
                <strong>{{ registro.rotulo }}</strong>
                <small v-if="registro.linha"> · linha {{ registro.linha }} da planilha</small>
                <div class="imp-ajuda">Faltando: {{ (registro.campos || []).join(', ') }}</div>
              </div>
              <router-link
                v-if="rota(registro)"
                class="btn-secundario btn-sm"
                :to="rota(registro)"
              >
                Abrir registro
              </router-link>
            </li>
          </ul>
          <p v-if="detalhe.incompletos > incompletos.length" class="imp-ajuda">
            Mostrando {{ incompletos.length }} de {{ detalhe.incompletos }}.
          </p>

          <h3>Linhas ignoradas e avisos ({{ detalhe.detalhes?.erros_total || 0 }})</h3>
          <p v-if="!erros.length" class="imp-ajuda">Nenhum aviso.</p>
          <ul v-else class="imp-lista-erros">
            <li v-for="(item, idx) in erros" :key="idx">{{ item.mensagem }}</li>
          </ul>
        </template>

        <template v-else>
          <h3>Documento</h3>
          <p class="imp-ajuda">
            {{ detalhe.detalhes?.titulo }}
            <template v-if="detalhe.detalhes?.registro_id"> · vinculado ao registro #{{ detalhe.detalhes.registro_id }}</template>
            <template v-else> · sem vínculo</template>
          </p>
          <router-link
            v-if="detalhe.detalhes?.registro_id && rota({ id: detalhe.detalhes.registro_id })"
            class="btn-secundario btn-sm"
            :to="rota({ id: detalhe.detalhes.registro_id })"
          >
            Abrir registro
          </router-link>
        </template>
      </div>
    </div>
  </section>
</template>

<script>
import Pagination from '../crud/Pagination.vue';
import TabelaContador from '../crud/TabelaContador.vue';
import { formatarDataHora, rotaDoRegistro } from '../../utils/documentos';

export default {
  name: 'HistoricoImportacoes',
  components: { Pagination, TabelaContador },
  data() {
    return {
      itens: [],
      meta: { current_page: 1, last_page: 1, total: 0 },
      perPage: 10,
      carregando: false,
      erro: '',
      detalhe: null,
    };
  },
  computed: {
    incompletos() {
      return this.detalhe?.detalhes?.incompletos || [];
    },
    erros() {
      return (this.detalhe?.detalhes?.erros || []).slice(0, 100);
    },
  },
  mounted() {
    this.carregar(1);
  },
  methods: {
    formatarDataHora,
    async carregar(pagina = 1) {
      this.carregando = true;
      this.erro = '';
      try {
        const { data } = await window.axios.get('/api/importacoes/historico', {
          params: { page: pagina, per_page: this.perPage },
        });
        this.itens = Array.isArray(data.data) ? data.data : [];
        this.meta = { ...this.meta, ...(data.meta || {}) };
      } catch (error) {
        this.erro = error.response?.data?.message || 'Não foi possível carregar o histórico de importações.';
      } finally {
        this.carregando = false;
      }
    },
    alterarRegistrosPorPagina(quantidade) {
      const tamanho = Number(quantidade);
      if (!Number.isInteger(tamanho) || tamanho < 1 || tamanho > 50 || this.carregando) return;
      this.perPage = tamanho;
      this.carregar(1);
    },
    async abrir(item) {
      try {
        const { data } = await window.axios.get(`/api/importacoes/historico/${item.id}`);
        this.detalhe = data.data || item;
      } catch (error) {
        this.erro = error.response?.data?.message || 'Não foi possível abrir o detalhe da importação.';
      }
    },
    async abrirPorId(id) {
      await this.carregar(1);
      if (id) await this.abrir({ id });
    },
    fechar() {
      this.detalhe = null;
    },
    rota(registro) {
      return rotaDoRegistro(this.detalhe?.modulo, registro.id, this.detalhe?.ciclo_id);
    },
  },
};
</script>

<style scoped src="../../../css/Importacoes.css"></style>
<style scoped src="../../../css/ImportacoesComponentes.css"></style>
