<template>
  <div class="estruturas-page crud-page" :class="{ 'crud-page-form': modo !== 'lista' }">
    <template v-if="modo === 'lista'">
      <CrudPageHeader
        title="Estruturas Institucionais"
        subtitle="Gerencie faculdades, polos e unidades vinculadas ao sistema."
        info="Cadastre e inative estruturas. Não há exclusão — o histórico permanece no SGP."
        :show-novo="podeEditar"
        novo-label="Nova Estrutura"
        :show-clear-filters="temFiltro"
        filters-aria-label="Filtros de estruturas institucionais"
        @novo="abrirNovo"
        @limpar-filtros="limparFiltros"
      >
        <template #filters>
          <section class="filtros-bar" aria-label="Filtros">
            <div class="filtro-busca">
              <input
                v-model="filtros.busca"
                type="search"
                placeholder="Buscar por nome..."
                aria-label="Buscar estrutura institucional"
                @input="recarregarLista"
              />
            </div>
            <SearchableSelect
              v-model="filtros.tipo"
              :options="opcoesTipo"
              empty-option="Todos os tipos"
              aria-label="Filtrar por tipo de estrutura"
              @change="recarregarLista"
            />
            <SearchableSelect
              v-model="filtros.ativo"
              :options="[
                { value: 'true', label: 'Ativos' },
                { value: 'false', label: 'Inativos' },
              ]"
              empty-option="Todos os status"
              aria-label="Filtrar por status"
              @change="recarregarLista"
            />
          </section>
        </template>
      </CrudPageHeader>

      <div v-if="mensagemSucesso" class="alert alert-success">{{ mensagemSucesso }}</div>
      <div v-if="mensagemErro" class="alert alert-error">{{ mensagemErro }}</div>

      <PageTableCard :total="registros.length" aria-label="Tabela de estruturas institucionais">
        <div v-if="carregando" class="tabela-loading">Carregando...</div>
        <div v-else class="tabela-wrap">
          <table class="crud-table">
            <thead>
              <tr>
                <th>Estrutura</th>
                <th>Tipo</th>
                <th>Localidade</th>
                <th class="text-center">Status</th>
                <th>Motivo da inativação</th>
                <th class="text-center">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="registros.length === 0">
                <td colspan="6" class="tabela-vazia">Nenhuma estrutura institucional encontrada.</td>
              </tr>
              <tr v-for="item in registros" :key="item.id">
                <td>{{ item.nome }}</td>
                <td>
                  <span class="badge" :class="classeBadgeTipo(item.tipo)">{{ labelTipo(item.tipo) }}</span>
                </td>
                <td>{{ item.regiao_administrativa?.nome || '—' }}</td>
                <td class="text-center">
                  <span class="badge" :class="item.ativo ? 'badge-ativo' : 'badge-inativo'">
                    {{ item.ativo ? 'Ativo' : 'Inativo' }}
                  </span>
                </td>
                <td class="col-motivo" :title="item.motivo_inativacao || ''">
                  {{ item.ativo ? '—' : (item.motivo_inativacao || '—') }}
                </td>
                <td class="text-center acoes">
                  <button
                    v-if="podeEditar"
                    type="button"
                    class="btn-icon btn-edit"
                    title="Editar"
                    aria-label="Editar"
                    @click="abrirEdicao(item)"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                  </button>
                  <button
                    v-if="podeEditar && item.ativo"
                    type="button"
                    class="btn-icon btn-delete"
                    title="Inativar"
                    aria-label="Inativar"
                    @click="pedirInativacao(item)"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m4.9 4.9 14.2 14.2"/></svg>
                  </button>
                  <button
                    v-if="podeEditar && !item.ativo"
                    type="button"
                    class="btn-icon btn-reactivar"
                    title="Reativar"
                    aria-label="Reativar"
                    @click="reativar(item)"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7"/><polyline points="21 3 21 9 15 9"/></svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </PageTableCard>

      <div
        v-if="modalInativacao.aberto"
        class="modal-overlay"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modal-inativacao-titulo"
        @click.self="fecharModalInativacao"
      >
        <div class="modal-detalhes modal-inativacao">
          <header class="modal-detalhes-header">
            <div>
              <h2 id="modal-inativacao-titulo">Inativar estrutura</h2>
              <p class="modal-detalhes-subtitle">
                Informe o motivo. A estrutura deixará de aparecer nas listas de seleção.
              </p>
            </div>
            <button type="button" class="btn-fechar-x" aria-label="Fechar" @click="fecharModalInativacao">×</button>
          </header>

          <div class="modal-detalhes-body">
            <p class="modal-inativacao-nome">{{ modalInativacao.item?.nome }}</p>
            <div class="form-group">
              <label for="motivo-inativacao">Motivo da inativação <span>*</span></label>
              <textarea
                id="motivo-inativacao"
                v-model="modalInativacao.motivo"
                rows="4"
                maxlength="2000"
                placeholder="Ex.: Unidade encerrada, migração de oferta, duplicidade..."
              />
            </div>
            <p v-if="modalInativacao.erro" class="alert alert-error">{{ modalInativacao.erro }}</p>
          </div>

          <div class="modal-detalhes-actions">
            <button type="button" class="btn-secondary" :disabled="modalInativacao.salvando" @click="fecharModalInativacao">
              Cancelar
            </button>
            <button type="button" class="btn-salvar btn-inativar" :disabled="modalInativacao.salvando" @click="confirmarInativacao">
              {{ modalInativacao.salvando ? 'Inativando...' : 'Confirmar inativação' }}
            </button>
          </div>
        </div>
      </div>
    </template>

    <template v-else>
      <div class="form-page">
        <header class="form-header">
          <button type="button" class="btn-voltar" @click="voltarLista">←</button>
          <div>
            <h1>{{ tituloForm }}</h1>
            <p>Faculdade, polo ou unidade vinculada a uma localidade/região.</p>
          </div>
        </header>

        <form class="form-body" novalidate @submit.prevent="salvar">
          <div v-if="erroFormulario" class="alert alert-error">{{ erroFormulario }}</div>

          <section class="form-section">
            <div class="form-grid">
              <div class="form-group full">
                <label for="nome">Nome <span>*</span></label>
                <input id="nome" v-model="form.nome" type="text" maxlength="180" required />
              </div>

              <div class="form-group">
                <label for="tipo">Tipo de Estrutura <span>*</span></label>
                <SearchableSelect
                  id="tipo"
                  input-id="tipo"
                  v-model="form.tipo"
                  :options="opcoesTipo"
                  empty-option="Selecione..."
                  :required="true"
                />
              </div>

              <div class="form-group">
                <label for="localidade">Localidade / Região <span>*</span></label>
                <input
                  id="localidade"
                  v-model="form.localidade"
                  type="text"
                  maxlength="100"
                  required
                  placeholder="Ex.: Asa Norte, Taguatinga, Setor Comercial Sul"
                  autocomplete="off"
                />
              </div>

              <div class="form-group full">
                <label class="form-check">
                  <input id="ativo" v-model="form.ativo" type="checkbox" />
                  <span>Ativo</span>
                </label>
              </div>

              <div v-if="!form.ativo" class="form-group full">
                <label for="motivo_inativacao">Motivo da inativação <span>*</span></label>
                <textarea
                  id="motivo_inativacao"
                  v-model="form.motivo_inativacao"
                  rows="3"
                  maxlength="2000"
                  placeholder="Informe o motivo da inativação..."
                />
              </div>
            </div>
          </section>

          <div class="form-actions">
            <button type="button" class="btn-secondary" @click="voltarLista">Cancelar</button>
            <button type="submit" class="btn-salvar" :disabled="salvando">
              {{ salvando ? 'Salvando...' : 'Salvar' }}
            </button>
          </div>
        </form>
      </div>
    </template>
  </div>
</template>

<script src="../scripts/UnidadesPage.js"></script>
<style scoped src="../../css/Unidades.css"></style>
