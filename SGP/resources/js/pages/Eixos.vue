<template>
  <div class="eixos-page">
    <header class="eixos-top">
      <div class="eixos-top-row">
        <div>
          <h1>Eixos</h1>
          <p class="eixos-subtitle">Visão consolidada dos segmentos e áreas do portfólio pedagógico.</p>
        </div>
      </div>

      <div class="eixos-info">
        Acompanhe a distribuição dos cursos por eixo, a cobertura do portfólio e a presença por unidade.
      </div>
    </header>

    <section class="eixos-summary-grid" aria-label="Resumo de eixos">
      <article v-for="card in resumoCards" :key="card.label" class="eixos-summary-card">
        <p class="eixos-summary-label">{{ card.label }}</p>
        <p class="eixos-summary-value">{{ card.value }}</p>
        <p class="eixos-summary-help">{{ card.help }}</p>
      </article>
    </section>

    <section class="filtros-bar">
      <div class="filtro-busca">
        <input
          v-model="filtroBusca"
          type="search"
          placeholder="Buscar eixo..."
          aria-label="Buscar eixo"
        />
      </div>

      <button v-if="temFiltro" type="button" class="btn-limpar" @click="limparFiltros">
        Limpar
      </button>
    </section>

    <section class="tabela-card">
      <div class="tabela-header">
        <span>{{ eixosFiltrados.length }} eixo{{ eixosFiltrados.length !== 1 ? 's' : '' }}</span>
      </div>

      <div v-if="carregando" class="tabela-loading">Carregando eixos...</div>

      <div v-else-if="erro" class="alert alert-error">{{ erro }}</div>

      <div v-else-if="eixosFiltrados.length === 0" class="tabela-vazia estado-vazio">
        <p class="estado-vazio-titulo">Nenhum eixo encontrado.</p>
        <p class="estado-vazio-texto">Tente ajustar a busca para localizar o segmento desejado.</p>
      </div>

      <div v-else class="tabela-wrap">
        <table class="eixos-table">
          <thead>
            <tr>
              <th>Eixo</th>
              <th>Cursos</th>
              <th>Participação</th>
              <th>Unidade principal</th>
              <th class="text-center">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="eixo in eixosFiltrados" :key="eixo.nome">
              <td>
                <span class="eixo-pill">{{ eixo.nome }}</span>
              </td>
              <td>
                <span class="numero-chip">{{ eixo.cursos }}</span>
              </td>
              <td>
                <span class="badge-participacao">{{ eixo.participacao }}</span>
              </td>
              <td>{{ eixo.unidade }}</td>
              <td class="text-center acoes">
                <button
                  type="button"
                  class="btn-icon btn-view"
                  title="Visualizar eixo"
                  @click="abrirDetalhes(eixo)"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <div v-if="eixoDetalhe" class="modal-overlay" @click.self="fecharDetalhes">
      <div class="modal-detalhes" role="dialog" aria-modal="true" aria-labelledby="detalhe-eixo-titulo">
        <div class="modal-detalhes-header">
          <h2 id="detalhe-eixo-titulo">Detalhes do eixo</h2>
          <button type="button" class="btn-fechar-x" title="Fechar" @click="fecharDetalhes">
            ×
          </button>
        </div>

        <div class="detalhe-eixo-body">
          <div class="detalhe-eixo-hero">
            <span class="eixo-pill">{{ eixoDetalhe.nome }}</span>
            <span class="badge-participacao">{{ eixoDetalhe.participacao }}</span>
          </div>

          <div class="detalhe-grid">
            <div class="detalhe-campo">
              <span class="detalhe-label">Cursos vinculados</span>
              <span class="detalhe-valor">{{ eixoDetalhe.cursos }}</span>
            </div>
            <div class="detalhe-campo">
              <span class="detalhe-label">Unidadede principal</span>
              <span class="detalhe-valor">{{ eixoDetalhe.unidade }}</span>
            </div>
            <div class="detalhe-campo detalhe-campo-full">
              <span class="detalhe-label">Observações</span>
              <span class="detalhe-valor detalhe-valor-texto">
                Este eixo representa o segmento principal do portfólio e está disponível para consulta no catálogo de cursos.
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script src="../scripts/Eixos.js"></script>
<style scoped src="../../css/Eixos.css"></style>
