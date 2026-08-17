<template>
  <div class="importacoes-page">
    <header class="imp-header">
      <div>
        <h1>Importações</h1>
        <p class="imp-subtitle">
          Centralize aqui as cargas de planilhas. Cada importação substitui os dados atuais do módulo selecionado.
        </p>
      </div>
      <button
        v-if="etapa !== 'catalogo'"
        type="button"
        class="btn-voltar"
        :disabled="processando"
        @click="voltarCatalogo"
      >
        ← Voltar
      </button>
    </header>

    <div v-if="erro" class="alert alert-error">{{ erro }}</div>
    <div v-if="mensagem" class="alert alert-success">{{ mensagem }}</div>

    <!-- CATÁLOGO -->
    <section v-if="etapa === 'catalogo'" class="imp-catalogo">
      <div v-if="!podeImportar" class="alert alert-error">
        Seu perfil não tem permissão para importar planilhas.
      </div>

      <div class="imp-cards">
        <article
          v-for="item in catalogo"
          :key="item.key"
          class="imp-card is-active"
        >
          <div class="imp-card-top">
            <span class="imp-card-badge">Disponível</span>
          </div>
          <h2>{{ item.label }}</h2>
          <p>{{ item.description }}</p>
          <button
            type="button"
            class="btn-primario"
            :disabled="!podeImportar"
            @click="iniciarModulo(item)"
          >
            Importar planilha
          </button>
        </article>
      </div>
    </section>

    <!-- UPLOAD -->
    <section v-else-if="etapa === 'upload'" class="imp-painel">
      <div class="imp-painel-head">
        <p class="imp-kicker">{{ moduloAtivo?.label }}</p>
        <h2>Enviar planilha</h2>
        <p class="imp-ajuda">
          Aceita <code>.xlsx</code> / <code>.xls</code>.
          {{ moduloAtivo?.ajuda || moduloAtivo?.description }}
        </p>
      </div>

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
        <button type="button" class="btn-secundario" :disabled="processando" @click="voltarCatalogo">
          Cancelar
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
    </section>

    <!-- PRÉVIA -->
    <section v-else class="imp-painel">
      <div class="imp-painel-head">
        <p class="imp-kicker">Prévia · {{ previa.aba }}</p>
        <h2>Confirmar importação</h2>
        <p class="imp-ajuda">
          {{ previa.total }} registro(s) válidos
          <template v-if="previa.ignoradas"> · {{ previa.ignoradas }} linha(s) ignorada(s)</template>
          . A confirmação <strong>substitui todos</strong> os dados atuais de {{ previa.label || moduloAtivo?.label }}.
        </p>
      </div>

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
        <div class="imp-tabela-wrap">
          <table class="imp-table">
            <thead>
              <tr>
                <th v-for="col in previa.colunas_preview" :key="col.key">{{ col.label }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(linha, index) in previa.linhas.slice(0, 50)" :key="index">
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
        <p v-if="previa.linhas.length > 50" class="imp-tabela-nota">
          Mostrando as primeiras 50 linhas de {{ previa.linhas.length }}.
        </p>
      </div>

      <div class="imp-acoes">
        <button type="button" class="btn-secundario" :disabled="processando" @click="etapa = 'upload'">
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
    </section>
  </div>
</template>

<script src="../scripts/Importacoes.js"></script>
<style scoped src="../../css/Importacoes.css"></style>
