<template>
  <div class="unidades-page crud-page" :class="{ 'crud-page-form': modo !== 'lista' }">
    <template v-if="modo === 'lista'">
      <CrudPageHeader
        title="Unidades"
        subtitle="Regiões administrativas e unidades de oferta (CEP, Polo, Faculdade)"
        info="Cadastre e inative unidades. Não há exclusão — o histórico permanece no SGP."
        :show-novo="podeEditar"
        :novo-label="abaLista === 'regioes' ? 'Nova Região' : 'Nova Unidade'"
        :show-clear-filters="temFiltro"
        filters-aria-label="Filtros de unidades"
        @novo="abrirNovo"
        @limpar-filtros="limparFiltros"
      >
        <template #filters>
          <section class="filtros-bar" aria-label="Filtros">
            <div class="filtro-busca">
              <input
                v-model="filtros.busca"
                type="search"
                placeholder="Buscar..."
                aria-label="Buscar"
                @input="recarregarLista"
              />
            </div>
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
            <SearchableSelect
              v-if="abaLista === 'unidades'"
              v-model="filtros.tipo"
              :options="opcoesTipo"
              empty-option="Todos os tipos"
              aria-label="Filtrar por tipo"
              @change="recarregarLista"
            />
          </section>
        </template>
      </CrudPageHeader>

      <div class="form-tabs lista-tabs" role="tablist">
        <button
          type="button"
          class="form-tab"
          :class="{ active: abaLista === 'regioes' }"
          @click="trocarAbaLista('regioes')"
        >
          Regiões Administrativas
        </button>
        <button
          type="button"
          class="form-tab"
          :class="{ active: abaLista === 'unidades' }"
          @click="trocarAbaLista('unidades')"
        >
          Unidades de Oferta
        </button>
      </div>

      <div v-if="mensagemSucesso" class="alert alert-success">{{ mensagemSucesso }}</div>
      <div v-if="mensagemErro" class="alert alert-error">{{ mensagemErro }}</div>

      <PageTableCard :total="registros.length" aria-label="Tabela">
        <div v-if="carregando" class="tabela-loading">Carregando...</div>
        <div v-else class="tabela-wrap">
          <table class="crud-table">
            <thead>
              <tr v-if="abaLista === 'regioes'">
                <th>Nome</th>
                <th>Unidades</th>
                <th class="text-center">Status</th>
                <th class="text-center">Ações</th>
              </tr>
              <tr v-else>
                <th>Nome</th>
                <th>Tipo</th>
                <th>Região</th>
                <th class="text-center">Status</th>
                <th class="text-center">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="registros.length === 0">
                <td :colspan="abaLista === 'regioes' ? 4 : 5" class="tabela-vazia">
                  Nenhum registro encontrado.
                </td>
              </tr>
              <tr v-for="item in registros" :key="item.id">
                <template v-if="abaLista === 'regioes'">
                  <td>{{ item.nome }}</td>
                  <td>{{ item.unidades_ativas ?? 0 }} ativas / {{ item.unidades_total ?? 0 }}</td>
                  <td class="text-center">
                    <span class="badge" :class="item.ativo ? 'badge-ativo' : 'badge-inativo'">
                      {{ item.ativo ? 'Ativo' : 'Inativo' }}
                    </span>
                  </td>
                  <td class="text-center acoes">
                    <button v-if="podeEditar" type="button" class="btn-icon btn-edit" title="Editar" @click="abrirEdicao(item)">✎</button>
                    <button
                      v-if="podeEditar"
                      type="button"
                      class="btn-icon"
                      :title="item.ativo ? 'Inativar' : 'Reativar'"
                      @click="alternarAtivo(item)"
                    >
                      {{ item.ativo ? '⏸' : '▶' }}
                    </button>
                  </td>
                </template>
                <template v-else>
                  <td>{{ item.nome }}</td>
                  <td>{{ labelTipo(item.tipo) }}</td>
                  <td>{{ item.regiao_administrativa?.nome || '—' }}</td>
                  <td class="text-center">
                    <span class="badge" :class="item.ativo ? 'badge-ativo' : 'badge-inativo'">
                      {{ item.ativo ? 'Ativo' : 'Inativo' }}
                    </span>
                  </td>
                  <td class="text-center acoes">
                    <button v-if="podeEditar" type="button" class="btn-icon btn-edit" title="Editar" @click="abrirEdicao(item)">✎</button>
                    <button
                      v-if="podeEditar"
                      type="button"
                      class="btn-icon"
                      :title="item.ativo ? 'Inativar' : 'Reativar'"
                      @click="alternarAtivo(item)"
                    >
                      {{ item.ativo ? '⏸' : '▶' }}
                    </button>
                  </td>
                </template>
              </tr>
            </tbody>
          </table>
        </div>
      </PageTableCard>
    </template>

    <template v-else>
      <div class="form-page">
        <header class="form-header">
          <button type="button" class="btn-voltar" @click="voltarLista">←</button>
          <div>
            <h1>{{ tituloForm }}</h1>
            <p>{{ abaLista === 'regioes' ? 'Região administrativa do DF' : 'CEP, Polo ou Faculdade vinculada a uma RA' }}</p>
          </div>
        </header>

        <form class="form-body" novalidate @submit.prevent="salvar">
          <div v-if="erroFormulario" class="alert alert-error">{{ erroFormulario }}</div>

          <section class="form-section">
            <div class="form-grid">
              <div class="form-group full">
                <label for="nome">Nome <span>*</span></label>
                <input id="nome" v-model="form.nome" type="text" maxlength="100" required />
              </div>

              <template v-if="abaLista === 'unidades'">
                <div class="form-group">
                  <label for="tipo">Tipo <span>*</span></label>
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
                  <label for="regiao">Região administrativa <span>*</span></label>
                  <SearchableSelect
                    id="regiao"
                    input-id="regiao"
                    v-model="form.regiao_administrativa_id"
                    :options="opcoesRegiao"
                    empty-option="Selecione..."
                    :required="true"
                  />
                </div>
              </template>

              <div class="form-group">
                <label class="check-field">
                  <input v-model="form.ativo" type="checkbox" />
                  Ativo
                </label>
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
