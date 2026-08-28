<template>
  <div class="importacoes-page">
    <header class="imp-header">
      <div>
        <h1>Importações</h1>
        <p class="imp-subtitle">
          Selecione o módulo, envie a planilha e confira a prévia antes de substituir os dados.
        </p>
      </div>
    </header>

    <div v-if="erro" class="alert alert-error">{{ erro }}</div>
    <div v-if="mensagem" class="alert alert-success">{{ mensagem }}</div>

    <section class="imp-painel">
      <div v-if="!podeImportar" class="alert alert-error">
        Seu perfil não tem permissão para importar planilhas.
      </div>

      <template v-else>
        <div class="imp-toolbar">
          <label class="imp-toolbar-label" for="importacao-select">Módulo</label>
          <SearchableSelect
            id="importacao-select"
            v-model="moduloKey"
            class="imp-select-modulo"
            aria-label="Selecionar módulo de importação"
            placeholder="Selecione o módulo"
            :options="opcoesModulos"
            :disabled="processando || !catalogo.length"
            @change="aoTrocarModulo"
          />
          <p v-if="moduloAtivo" class="imp-toolbar-desc">
            {{ moduloAtivo.ajuda || moduloAtivo.description }}
          </p>
        </div>

        <div v-if="!moduloAtivo" class="imp-vazio">
          {{ catalogo.length ? 'Selecione um módulo para iniciar a importação.' : 'Carregando módulos...' }}
        </div>

        <template v-else>
          <div class="imp-filtros">
            <div class="imp-filtros-row">
              <div class="imp-filtro-busca">
                <input
                  v-model="filtros.busca"
                  type="search"
                  placeholder="Filtrar prévia..."
                  aria-label="Filtrar linhas da prévia"
                  :disabled="etapa !== 'previa'"
                />
              </div>

              <SearchableSelect
                v-if="temFiltro('status')"
                v-model="filtros.status"
                class="imp-filtro-select"
                :options="opcoesFiltro('status')"
                empty-option="Todos os status"
                :disabled="etapa !== 'previa'"
              />

              <SearchableSelect
                v-if="temFiltro('eixo')"
                v-model="filtros.eixo"
                class="imp-filtro-select"
                :options="opcoesFiltro('eixo')"
                empty-option="Todos os eixos"
                :disabled="etapa !== 'previa'"
              />

              <SearchableSelect
                v-if="temFiltro('unidade')"
                v-model="filtros.unidade"
                class="imp-filtro-select"
                :options="opcoesFiltro('unidade')"
                empty-option="Todas as unidades"
                :disabled="etapa !== 'previa'"
              />

              <SearchableSelect
                v-if="temFiltro('tipo')"
                v-model="filtros.tipo"
                class="imp-filtro-select"
                :options="opcoesFiltro('tipo')"
                empty-option="Todos os tipos"
                :disabled="etapa !== 'previa'"
              />

              <SearchableSelect
                v-if="temFiltro('ano')"
                v-model="filtros.ano"
                class="imp-filtro-select"
                :options="opcoesFiltro('ano')"
                empty-option="Todos os anos"
                :disabled="etapa !== 'previa'"
              />

              <SearchableSelect
                v-if="temFiltro('segmento')"
                v-model="filtros.segmento"
                class="imp-filtro-select"
                :options="opcoesFiltro('segmento')"
                empty-option="Todos os segmentos"
                :disabled="etapa !== 'previa'"
              />
            </div>
          </div>

          <div class="imp-upload-card">
            <div class="imp-painel-head">
              <p class="imp-kicker">{{ moduloAtivo.label }}</p>
              <h2>{{ etapa === 'previa' ? 'Confirmar importação' : 'Enviar planilha' }}</h2>
              <p class="imp-ajuda">
                <template v-if="etapa === 'previa'">
                  {{ previa.total }} registro(s) válidos
                  <template v-if="previa.ignoradas"> · {{ previa.ignoradas }} linha(s) ignorada(s)</template>
                  . A confirmação <strong>substitui todos</strong> os dados atuais de {{ previa.label || moduloAtivo.label }}.
                </template>
                <template v-else>
                  Aceita <code>.xlsx</code> / <code>.xls</code>.
                  {{ moduloAtivo.ajuda || moduloAtivo.description }}
                </template>
              </p>
            </div>

            <template v-if="etapa === 'upload'">
              <label class="imp-dropzone" :class="{ 'has-file': !!arquivo }">
                <input
                  ref="inputArquivo"
                  type="file"
                  accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel"
                  @change="onArquivoSelecionado"
                />
                <span v-if="!arquivo">Clique para selecionar o arquivo Excel</span>
                <span v-else>{{ arquivo.name }}</span>
              </label>

              <div class="imp-acoes">
                <button
                  type="button"
                  class="btn-secundario"
                  :disabled="!arquivo || processando"
                  @click="limparArquivo"
                >
                  Limpar arquivo
                </button>
                <button
                  type="button"
                  class="btn-primario"
                  :disabled="!arquivo || processando"
                  @click="gerarPrevia"
                >
                  {{ processando ? 'Lendo planilha...' : 'Gerar prévia' }}
                </button>
              </div>
            </template>

            <template v-else>
              <div v-if="previa.erros?.length" class="imp-erros">
                <h3>Avisos de linha</h3>
                <ul>
                  <li v-for="(item, idx) in previa.erros.slice(0, 20)" :key="idx">
                    Linha {{ item.linha }}: {{ item.mensagem }}
                  </li>
                </ul>
                <p v-if="previa.erros.length > 20">… e mais {{ previa.erros.length - 20 }} aviso(s).</p>
              </div>

              <div class="imp-tabela-card">
                <div class="imp-tabela-meta">
                  <span>
                    Prévia · {{ previa.aba || moduloAtivo.label }}
                    · {{ linhasFiltradas.length }} de {{ previa.linhas.length }} linha(s)
                  </span>
                </div>
                <div class="imp-tabela-wrap">
                  <table class="imp-table">
                    <thead>
                      <tr>
                        <th v-for="col in previa.colunas_preview" :key="col.key">{{ col.label }}</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-if="!linhasFiltradas.length">
                        <td :colspan="Math.max(previa.colunas_preview.length, 1)" class="imp-td-vazio">
                          Nenhuma linha correspondente aos filtros.
                        </td>
                      </tr>
                      <tr v-for="(linha, index) in linhasFiltradas.slice(0, 50)" :key="index">
                        <td
                          v-for="col in previa.colunas_preview"
                          :key="col.key"
                          :class="{ 'col-assunto': col.key === 'assunto' || col.key === 'titulo' || col.key === 'curso' || col.key === 'nome' }"
                        >
                          {{ celula(linha, col.key) }}
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <p v-if="linhasFiltradas.length > 50" class="imp-tabela-nota">
                  Mostrando as primeiras 50 linhas filtradas de {{ linhasFiltradas.length }}.
                </p>
              </div>

              <div class="imp-acoes">
                <button type="button" class="btn-secundario" :disabled="processando" @click="voltarUpload">
                  Trocar arquivo
                </button>
                <button
                  type="button"
                  class="btn-perigo"
                  :disabled="processando || !previa.total"
                  @click="confirmarImportacao"
                >
                  {{ processando ? 'Importando...' : 'Importar e substituir' }}
                </button>
              </div>
            </template>
          </div>
        </template>
      </template>
    </section>
  </div>
</template>

<script src="../scripts/Importacoes.js"></script>
<style scoped src="../../css/Importacoes.css"></style>
