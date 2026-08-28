<template>
  <div class="relatorios-page">
    <header class="rel-header">
      <div>
        <h1>Relatórios</h1>
        <p class="rel-subtitle">
          Selecione o relatório, filtre os dados e exporte em PDF.
        </p>
      </div>
      <button
        v-if="selecionado"
        type="button"
        class="btn-exportar"
        :disabled="exportando || carregandoPrevias || carregandoCatalogo"
        @click="exportarPdf"
      >
        {{ exportando ? 'Gerando PDF...' : 'Exportar PDF' }}
      </button>
    </header>

    <div v-if="erro" class="alert alert-error">{{ erro }}</div>
    <div v-if="mensagem" class="alert alert-success">{{ mensagem }}</div>

    <section class="rel-painel">
      <div class="rel-toolbar">
        <label class="rel-toolbar-label" for="relatorio-select">Relatório</label>
        <SearchableSelect
          id="relatorio-select"
          v-model="relatorioKey"
          class="rel-select-relatorio"
          aria-label="Selecionar relatório"
          placeholder="Selecione um relatório"
          :options="opcoesRelatorios"
          :disabled="carregandoCatalogo || !catalogo.length"
          @change="aoTrocarRelatorio"
        />
        <p v-if="selecionado" class="rel-toolbar-desc">{{ selecionado.description }}</p>
      </div>

      <div v-if="carregandoCatalogo" class="rel-loading">Carregando relatórios...</div>

      <template v-else-if="selecionado">
        <div class="rel-filtros">
          <div class="rel-filtros-row">
            <div class="rel-filtro-busca">
              <input
                v-model="filtros.busca"
                type="search"
                placeholder="Buscar nos registros..."
                aria-label="Buscar nos registros do relatório"
                @input="aoBuscar"
              />
            </div>

            <SearchableSelect
              v-if="temFiltro('ano')"
              v-model="filtros.ano"
              class="rel-filtro-select"
              :options="anosDisponiveis"
              empty-option="Todos os anos"
              @change="carregarPrevias"
            />

            <SearchableSelect
              v-if="temFiltro('unidade')"
              v-model="filtros.unidade"
              class="rel-filtro-select rel-filtro-select-wide"
              :options="unidadesDisponiveis"
              empty-option="Todas as unidades"
              @change="carregarPrevias"
            />

            <SearchableSelect
              v-if="temFiltro('eixo')"
              v-model="filtros.eixo"
              class="rel-filtro-select"
              :options="eixosDisponiveis"
              empty-option="Todos os eixos"
              @change="carregarPrevias"
            />

            <SearchableSelect
              v-if="temFiltro('status')"
              v-model="filtros.status"
              class="rel-filtro-select"
              :options="statusDisponiveis"
              empty-option="Todos os status"
              @change="carregarPrevias"
            />

            <SearchableSelect
              v-if="temFiltro('categoria')"
              v-model="filtros.categoria"
              class="rel-filtro-select"
              :options="categoriasDisponiveis"
              empty-option="Todas as categorias"
              @change="carregarPrevias"
            />

            <SearchableSelect
              v-if="temFiltro('setor')"
              v-model="filtros.setor"
              class="rel-filtro-select"
              :options="setoresDisponiveis"
              empty-option="Todos os setores"
              @change="carregarPrevias"
            />

            <SearchableSelect
              v-if="temFiltro('relator')"
              v-model="filtros.relator"
              class="rel-filtro-select"
              :options="relatoresDisponiveis"
              empty-option="Todos os relatores"
              @change="carregarPrevias"
            />
          </div>
        </div>

        <div class="rel-tabela-card">
          <div class="rel-tabela-header">
            <div>
              <p class="rel-kicker">Prévia</p>
              <h2>{{ selecionado.label }}</h2>
            </div>
            <TabelaContador :total="registros.length" />
          </div>

          <div v-if="carregandoPrevias" class="rel-loading">Carregando prévia...</div>

          <div v-else-if="!registros.length" class="rel-vazio">
            Nenhum registro encontrado para os filtros selecionados.
          </div>

          <div v-else class="rel-tabela-wrap">
            <table class="rel-table">
              <thead>
                <tr>
                  <th v-for="col in colunasPreview" :key="col.key">{{ col.label }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(linha, index) in registros" :key="linha.id || index">
                  <td v-for="col in colunasPreview" :key="col.key">
                    {{ valorCelula(linha, col.key) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>

      <div v-else class="rel-vazio rel-vazio-painel">
        Nenhum relatório disponível no momento.
      </div>
    </section>
  </div>
</template>

<script src="../scripts/Relatorios.js"></script>
<style scoped src="../../css/Relatorios.css"></style>
