<template>
  <section class="imp-historico" aria-labelledby="imp-historico-titulo">
    <div class="imp-historico__head">
      <div>
        <h2 id="imp-historico-titulo">Histórico de importações</h2>
        <p class="imp-ajuda">Cada envio fica registrado com arquivo, usuário, data/hora, ciclo e contagens. Registros incompletos podem ser abertos para correção.</p>
      </div>
      <button type="button" class="btn-secundario" :disabled="carregando" @click="carregar(1)">Atualizar</button>
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
            <th><span class="sr-only">Ações</span></th>
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
            <td>
              <button type="button" class="btn-secundario btn-sm" @click="abrir(item)">Detalhes</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="meta.last_page > 1" class="imp-historico__paginacao">
      <button type="button" class="btn-secundario btn-sm" :disabled="meta.current_page <= 1 || carregando" @click="carregar(meta.current_page - 1)">Anterior</button>
      <span>Página {{ meta.current_page }} de {{ meta.last_page }}</span>
      <button type="button" class="btn-secundario btn-sm" :disabled="meta.current_page >= meta.last_page || carregando" @click="carregar(meta.current_page + 1)">Próxima</button>
    </div>

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

        <button
          v-if="detalhe.arquivo_disponivel"
          type="button"
          class="btn-secundario btn-sm"
          @click="baixarArquivoImportado"
        >
          Baixar arquivo enviado
        </button>

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
import { baixarArquivo, formatarDataHora, rotaDoRegistro } from '../../utils/documentos';

export default {
  name: 'HistoricoImportacoes',
  data() {
    return {
      itens: [],
      meta: { current_page: 1, last_page: 1, total: 0 },
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
        const { data } = await window.axios.get('/api/importacoes/historico', { params: { page: pagina, per_page: 10 } });
        this.itens = Array.isArray(data.data) ? data.data : [];
        this.meta = { ...this.meta, ...(data.meta || {}) };
      } catch (error) {
        this.erro = error.response?.data?.message || 'Não foi possível carregar o histórico de importações.';
      } finally {
        this.carregando = false;
      }
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
    async baixarArquivoImportado() {
      try {
        await baixarArquivo(`/api/importacoes/historico/${this.detalhe.id}/arquivo`, this.detalhe.arquivo_nome);
      } catch {
        this.erro = 'Não foi possível baixar o arquivo desta importação.';
      }
    },
  },
};
</script>

<style scoped src="../../../css/Importacoes.css"></style>
<style scoped src="../../../css/ImportacoesComponentes.css"></style>
