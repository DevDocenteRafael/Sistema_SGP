<template>
  <div class="dashboard-page">
    <div class="dashboard-header">
      <div>
        <h1>Dashboard</h1>
        <p class="dashboard-description">Indicadores do portfólio de cursos — SENAC DF · CPED</p>
      </div>

      <div class="dashboard-toolbar">
        <select v-model="filtros.grupo" aria-label="Grupo de indicadores">
          <option value="gerais">Indicadores Gerais</option>
          <option value="visitas">Indicadores de Visitas Técnicas</option>
          <option value="horas">Indicadores de Horas Pedagógicas</option>
        </select>

        <select v-model="filtros.ano" @change="carregarDashboard" aria-label="Ano">
          <option value="">Todos</option>
          <option v-for="ano in anosDisponiveis" :key="ano" :value="ano">{{ ano }}</option>
        </select>

        <select v-model="filtros.unidade" @change="carregarDashboard" aria-label="Unidade">
          <option value="">Todos</option>
          <option v-for="unidade in unidadesDisponiveis" :key="unidade" :value="unidade">{{ unidade }}</option>
        </select>

        <select v-model="filtros.eixo" @change="carregarDashboard" aria-label="Eixo">
          <option value="">Todos</option>
          <option v-for="eixo in meta.eixos" :key="eixo" :value="eixo">{{ eixo }}</option>
        </select>

        <select v-model="filtros.status" @change="carregarDashboard" aria-label="Status">
          <option value="">Todos</option>
          <option v-for="status in meta.status" :key="status" :value="status">{{ status }}</option>
        </select>
      </div>
    </div>

    <section class="dashboard-alert-card">
      <div class="dashboard-alert-header">
        <div>
          <h2>Alertas de prazo</h2>
          <p>Visitas e metas com entrega nos próximos 15 dias ou vencidas</p>
        </div>
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      </div>

      <div class="dashboard-alert-body">
        Nenhum prazo vencendo nos próximos 15 dias.
      </div>
    </section>

    <section class="dashboard-metrics-grid">
      <article class="dashboard-metric-card" v-for="card in metricCards" :key="card.label">
        <div class="dashboard-metric-icon" v-html="card.icon"></div>
        <div>
          <p class="dashboard-metric-title">{{ card.label }}</p>
          <p class="dashboard-metric-value">{{ card.value }}</p>
        </div>
      </article>
    </section>

    <p class="dashboard-note">* Gráficos exibem apenas cursos ativos. Use o filtro de Status para incluir inativos.</p>

    <div class="dashboard-content-grid">
      <div class="dashboard-charts-grid">
        <section class="dashboard-chart-card">
          <h3>Eixos Tecnológicos</h3>
          <div v-if="carregando" class="dashboard-chart-empty">Carregando...</div>
          <div v-else-if="chartEixos.length === 0" class="dashboard-chart-empty">Sem dados para os filtros selecionados</div>
          <ul v-else class="dashboard-chart-list">
            <li v-for="item in chartEixos" :key="item.label">
              <span>{{ item.label }}</span>
              <span>{{ item.value }}</span>
            </li>
          </ul>
        </section>

        <section class="dashboard-chart-card">
          <h3>Tipos de Curso</h3>
          <div v-if="carregando" class="dashboard-chart-empty">Carregando...</div>
          <div v-else-if="chartTipos.length === 0" class="dashboard-chart-empty">Sem dados para os filtros selecionados</div>
          <ul v-else class="dashboard-chart-list">
            <li v-for="item in chartTipos" :key="item.label">
              <span>{{ item.label }}</span>
              <span>{{ item.value }}</span>
            </li>
          </ul>
        </section>

        <section class="dashboard-chart-card">
          <h3>Status dos Cursos</h3>
          <div v-if="carregando" class="dashboard-chart-empty">Carregando...</div>
          <div v-else-if="chartStatus.length === 0" class="dashboard-chart-empty">Sem dados para os filtros selecionados</div>
          <ul v-else class="dashboard-chart-list">
            <li v-for="item in chartStatus" :key="item.label">
              <span>{{ item.label }}</span>
              <span>{{ item.value }}</span>
            </li>
          </ul>
        </section>

        <section class="dashboard-chart-card">
          <h3>Faixas de Carga Horária</h3>
          <div v-if="carregando" class="dashboard-chart-empty">Carregando...</div>
          <div v-else class="dashboard-chart-list">
            <li v-for="item in chartCargaHoraria" :key="item.label">
              <span>{{ item.label }}</span>
              <span>{{ item.value }}</span>
            </li>
          </div>
        </section>
      </div>

      <section class="dashboard-summary-panel">
        <h3>Resumo por Eixo</h3>
        <div class="dashboard-summary-list">
          <div class="dashboard-summary-row" v-for="item in resumoPorEixo" :key="item.eixo">
            <span>{{ item.eixo }}</span>
            <span>{{ item.count }}</span>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script src="../scripts/Dashboard.js"></script>
<style scoped src="../../css/Dashboard.css"></style>
