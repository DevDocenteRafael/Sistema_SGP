<template>
  <div class="importacoes-page">
    <header class="imp-header">
      <div>
        <h1>Importações</h1>
        <p class="imp-subtitle">
          Selecione um módulo, envie a planilha (.xlsx/.xls) e confira a prévia antes de confirmar.
          Linhas incompletas são importadas com aviso; PDFs entram como documento anexado a um registro.
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
        <div class="imp-upload-card">
          <div class="imp-modulo-selector">
            <label class="imp-toolbar-label" for="importacao-select">Módulos</label>
            <SearchableSelect
              id="importacao-select"
              v-model="moduloKey"
              class="imp-select-modulo"
              aria-label="Selecionar módulo de importação"
              placeholder="Selecione um módulo"
              :options="opcoesModulos"
              :disabled="processando || !catalogo.length"
              @change="aoTrocarModulo"
            />
          </div>

          <div v-if="!moduloAtivo" class="imp-vazio">
            {{ catalogo.length ? 'Selecione um módulo para iniciar a importação.' : 'Carregando módulos...' }}
          </div>

          <template v-else>
            <div class="imp-painel-head">
              <p class="imp-kicker">{{ moduloAtivo.label }}</p>
              <h2>{{ etapa === 'previa' ? 'Confirmar importação' : 'Enviar arquivo' }}</h2>
              <p class="imp-ajuda">
                <template v-if="etapa === 'previa'">
                  {{ linhasValidas }} registro(s) a importar
                  <template v-if="previa.incompletos"> · <strong>{{ previa.incompletos }} incompleto(s)</strong> (entram com aviso e podem ser corrigidos depois)</template>
                  <template v-if="linhasIgnoradas"> · {{ linhasIgnoradas }} linha(s) ignorada(s)</template>
                  . A confirmação faz upsert no ciclo
                  <strong>{{ previa.ciclo?.nome || cicloSelecionadoNome || 'selecionado' }}</strong>
                  e <strong>não apaga</strong> registros de outros ciclos.
                  <template v-if="resumoAcoesTexto"> {{ resumoAcoesTexto }}</template>
                </template>
                <template v-else>
                  Aceita <code>.xlsx</code> / <code>.xls</code> para dados estruturados e <code>.pdf</code> como documento anexado.
                  Usa as abas de portfólio por eixo. A coluna Segmento é preservada. A aba Saúde vira Ambiente e Saúde. 60+ e Ensino Médio são programas, não eixos: cada linha precisa de um segmento (ou outro dado) que resolva um dos 5 eixos oficiais. A confirmação faz upsert no ciclo selecionado no seletor.
                </template>
              </p>
            </div>

            <template v-if="etapa === 'upload'">
              <label class="imp-dropzone" :class="{ 'has-file': !!arquivo }">
                <input
                  ref="inputArquivo"
                  type="file"
                  accept=".xlsx,.xls,.pdf,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,application/pdf"
                  @change="onArquivoSelecionado"
                />
                <span v-if="!arquivo">Clique para selecionar a planilha Excel ou o documento PDF</span>
                <span v-else>{{ arquivo.name }}</span>
              </label>
            </template>

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
                  empty-option="Todas as estruturas"
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

            <template v-if="etapa === 'upload'">
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

            <DocumentoPdfUpload
              v-else-if="etapa === 'documento'"
              :arquivo="arquivo"
              @cancelar="voltarUpload(); limparArquivo()"
              @enviado="aoEnviarDocumento"
            />

            <template v-else>
              <div v-if="previa.erros?.length" class="imp-erros">
                <h3>{{ temErroBloqueante ? 'Erros que bloqueiam a importação' : 'Avisos (não impedem a importação das demais linhas)' }}</h3>
                <ul>
                  <li v-for="(item, idx) in previa.erros.slice(0, 20)" :key="idx">
                    {{ item.mensagem }}
                  </li>
                </ul>
                <p v-if="previa.erros.length > 20">… e mais {{ previa.erros.length - 20 }} aviso(s).</p>
              </div>

              <div class="imp-tabela-card">
                <div class="imp-tabela-meta">
                  <span>
                    Prévia · {{ previa.aba || moduloAtivo.label }}
                    · {{ intervaloLinhas }} linha(s)
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
                      <tr v-for="(linha, index) in linhasDaPagina" :key="(paginaAtual - 1) * registrosPorPagina + index">
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
                <Pagination
                  v-if="linhasFiltradas.length"
                  :current-page="paginaAtual"
                  :total-pages="totalPaginas"
                  :total-records="linhasFiltradas.length"
                  :page-size="registrosPorPagina"
                  :disabled="processando"
                  aria-label="Paginação da análise da planilha importada"
                  @change="irParaPaginaPrevia"
                  @per-page-change="alterarRegistrosPorPaginaPrevia"
                />
              </div>

              <div class="imp-acoes">
                <button type="button" class="btn-secundario" :disabled="processando" @click="voltarUpload">
                  Trocar arquivo
                </button>
                <button
                  type="button"
                  class="btn-primario"
                  :disabled="processando || !linhasValidas || temErroBloqueante"
                  @click="confirmarImportacao"
                >
                  {{ processando ? 'Importando...' : 'Confirmar importação' }}
                </button>
              </div>
            </template>
          </template>
        </div>
      </template>
    </section>

    <section v-if="podeImportar" class="imp-painel">
      <HistoricoImportacoes ref="historico" />
    </section>
  </div>
</template>

<script src="../scripts/Importacoes.js"></script>
<style scoped src="../../css/Importacoes.css"></style>
