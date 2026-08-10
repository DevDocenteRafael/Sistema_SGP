<template>
  <div class="relatorios-page">
    <header class="rel-header">
      <div>
        <h1>Relatórios</h1>
        <p class="rel-subtitle">
          Central executiva para visualizar, gerar e exportar relatórios institucionais da CPED.
        </p>
      </div>
      <button
        v-if="selecionado"
        type="button"
        class="btn-voltar"
        @click="voltarCatalogo"
      >
        ← Todos os relatórios
      </button>
    </header>

    <div v-if="erro" class="alert alert-error">{{ erro }}</div>
    <div v-if="mensagem" class="alert alert-success">{{ mensagem }}</div>

    <!-- CATÁLOGO -->
    <section v-if="!selecionado" class="rel-catalogo">
      <div v-if="carregandoCatalogo" class="rel-loading">Carregando catálogo...</div>
      <div v-else class="rel-cards">
        <button
          v-for="item in catalogo"
          :key="item.key"
          type="button"
          class="rel-card"
          @click="selecionar(item)"
        >
          <div class="rel-card-top">
            <span class="rel-card-icon" v-html="icone(item.icon)"></span>
            <span class="rel-card-count">{{ item.total }}</span>
          </div>
          <h2>{{ item.label }}</h2>
          <p>{{ item.description }}</p>
          <span class="rel-card-cta">Abrir relatório →</span>
        </button>
      </div>
    </section>

    <!-- DETALHE / PRÉVIA -->
    <section v-else class="rel-detalhe">
      <div class="rel-detalhe-head">
        <div>
          <p class="rel-kicker">Prévia e exportação</p>
          <h2>{{ selecionado.label }}</h2>
          <p class="rel-detalhe-desc">{{ selecionado.description }}</p>
        </div>
        <button
          type="button"
          class="btn-exportar"
          :disabled="exportando || carregandoPrevias"
          @click="exportarPdf"
        >
          {{ exportando ? 'Gerando PDF...' : 'Exportar PDF' }}
        </button>
      </div>

      <div class="rel-filtros">
        <div class="rel-filtro-busca">
          <input
            v-model="filtros.busca"
            type="search"
            placeholder="Buscar nos registros..."
            aria-label="Buscar nos registros do relatório"
            @input="aoBuscar"
          />
        </div>

        <select
          v-if="temFiltro('ano')"
          v-model="filtros.ano"
          @change="carregarPrevias"
        >
          <option value="">Todos os anos</option>
          <option v-for="ano in anosDisponiveis" :key="ano" :value="ano">{{ ano }}</option>
        </select>

        <select
          v-if="temFiltro('unidade')"
          v-model="filtros.unidade"
          @change="carregarPrevias"
        >
          <option value="">Todas as unidades</option>
          <option v-for="unidade in unidadesDisponiveis" :key="unidade" :value="unidade">{{ unidade }}</option>
        </select>

        <select
          v-if="temFiltro('eixo')"
          v-model="filtros.eixo"
          @change="carregarPrevias"
        >
          <option value="">Todos os eixos</option>
          <option v-for="eixo in eixosDisponiveis" :key="eixo" :value="eixo">{{ eixo }}</option>
        </select>

        <select
          v-if="temFiltro('status')"
          v-model="filtros.status"
          @change="carregarPrevias"
        >
          <option value="">Todos os status</option>
          <option v-for="status in statusDisponiveis" :key="status" :value="status">{{ status }}</option>
        </select>

        <button
          v-if="temFiltroAtivo"
          type="button"
          class="btn-limpar"
          @click="limparFiltros"
        >
          Limpar
        </button>
      </div>

      <div class="rel-tabela-card">
        <div class="rel-tabela-header">
          <span>{{ registros.length }} registro{{ registros.length !== 1 ? 's' : '' }} na prévia</span>
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
    </section>
  </div>
</template>

<script src="../scripts/Relatorios.js"></script>
<style scoped src="../../css/Relatorios.css"></style>
