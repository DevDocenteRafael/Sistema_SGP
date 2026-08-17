<template>
  <div class="dashboard-page">
    <header class="dashboard-header">
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

        <template v-if="filtros.grupo === 'gerais'">
          <select v-model="filtros.ano" aria-label="Ano">
            <option value="">Todos</option>
            <option v-for="ano in anosDisponiveis" :key="ano" :value="ano">{{ ano }}</option>
          </select>

          <select v-model="filtros.unidade" aria-label="Unidade">
            <option value="">Todos</option>
            <option v-for="unidade in unidadesDisponiveis" :key="unidade" :value="unidade">{{ unidade }}</option>
          </select>

          <select v-model="filtros.eixo" aria-label="Eixo">
            <option value="">Todos</option>
            <option v-for="eixo in meta.eixos" :key="eixo" :value="eixo">{{ eixo }}</option>
          </select>

          <select v-model="filtros.status" aria-label="Status">
            <option value="">Todos</option>
            <option v-for="status in meta.status" :key="status" :value="status">{{ status }}</option>
          </select>

          <button v-if="temFiltro" type="button" class="btn-limpar" @click="limparFiltros">Limpar</button>
        </template>
      </div>
    </header>

    <div v-if="erro" class="alert alert-error">{{ erro }}</div>

    <template v-if="filtros.grupo === 'gerais'">
      <section class="dashboard-metrics-grid">
        <article class="dashboard-metric-card is-accent">
          <div class="dashboard-metric-top">
            <div class="dashboard-metric-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7z"/><path d="M14 2v5h5"/><path d="M8 13h8"/><path d="M8 17h8"/></svg>
            </div>
          </div>
          <p class="dashboard-metric-value">{{ totalResolucoes }}</p>
          <p class="dashboard-metric-title">Resoluções</p>
        </article>

        <article class="dashboard-metric-card">
          <div class="dashboard-metric-top">
            <div class="dashboard-metric-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/></svg>
            </div>
          </div>
          <p class="dashboard-metric-value">{{ totalTermos }}</p>
          <p class="dashboard-metric-title">Termos de Referência</p>
        </article>

        <article
          v-for="card in metricCards"
          :key="card.label"
          class="dashboard-metric-card"
          :class="{ 'is-accent': card.accent, 'is-warn': card.warn }"
        >
          <div class="dashboard-metric-top">
            <div class="dashboard-metric-icon" v-html="card.icon"></div>
            <span v-if="card.sub" class="dashboard-metric-sub">{{ card.sub }}</span>
          </div>
          <p class="dashboard-metric-value">{{ card.value }}</p>
          <p class="dashboard-metric-title">{{ card.label }}</p>
        </article>
      </section>

      <section class="dashboard-ciclo-vida" aria-label="Prazos de Resoluções e Termos de Referência">
        <div class="dashboard-ciclo-bloco">
          <h3>Resoluções por prazo</h3>
          <p class="dashboard-chart-subtitle">Semáforo de vigência (5 anos; atenção 6 meses, crítico 1 mês)</p>
          <section class="dashboard-kpi-grid ciclo">
            <article v-for="card in cardsResolucoesPrazo" :key="card.title" class="dashboard-kpi-card">
              <p class="dashboard-kpi-value" :style="{ color: card.color }">{{ card.value }}</p>
              <p class="dashboard-kpi-title">{{ card.title }}</p>
              <p class="dashboard-kpi-subtitle">{{ card.subtitle }}</p>
            </article>
          </section>
        </div>

        <div class="dashboard-ciclo-bloco">
          <h3>Termos de Referência por prazo</h3>
          <p class="dashboard-chart-subtitle">Acompanhamento de deadline — no prazo, atenção, crítico e vencidos</p>
          <section class="dashboard-kpi-grid ciclo">
            <article v-for="card in cardsTermosPrazo" :key="card.title" class="dashboard-kpi-card">
              <p class="dashboard-kpi-value" :style="{ color: card.color }">{{ card.value }}</p>
              <p class="dashboard-kpi-title">{{ card.title }}</p>
              <p class="dashboard-kpi-subtitle">{{ card.subtitle }}</p>
            </article>
          </section>
        </div>
      </section>

      <div class="dashboard-content-grid">
        <div class="dashboard-charts-grid">
          <section class="dashboard-chart-card">
            <h3>Eixos Tecnológicos</h3>
            <p class="dashboard-chart-subtitle">Quantidade de cursos por eixo</p>
            <div v-if="carregando" class="dashboard-chart-empty">Carregando...</div>
            <div v-else-if="chartEixos.length === 0" class="dashboard-chart-empty">Sem dados para os filtros selecionados</div>
            <div v-else class="dashboard-bars">
              <div v-for="item in chartEixos" :key="item.label" class="dashboard-bar-item">
                <div class="dashboard-bar-head">
                  <span class="dashboard-bar-dot" :style="{ background: item.color }"></span>
                  <span class="dashboard-bar-label" :title="item.label">{{ item.label }}</span>
                  <span class="dashboard-bar-meta">
                    <strong>{{ item.value }}</strong>
                    <small>{{ item.share }}%</small>
                  </span>
                </div>
                <div class="dashboard-bar-track">
                  <div class="dashboard-bar-fill" :style="{ width: `${Math.max(item.bar, 4)}%`, background: item.color }"></div>
                </div>
              </div>
            </div>
          </section>

          <section class="dashboard-chart-card">
            <h3>Tipos de Curso</h3>
            <p class="dashboard-chart-subtitle">Distribuição por tipo de oferta</p>
            <div v-if="carregando" class="dashboard-chart-empty">Carregando...</div>
            <div v-else-if="chartTipos.length === 0" class="dashboard-chart-empty">Sem dados para os filtros selecionados</div>
            <div v-else class="dashboard-bars">
              <div v-for="item in chartTipos" :key="item.label" class="dashboard-bar-item">
                <div class="dashboard-bar-head">
                  <span class="dashboard-bar-dot" :style="{ background: item.color }"></span>
                  <span class="dashboard-bar-label" :title="item.label">{{ item.label }}</span>
                  <span class="dashboard-bar-meta">
                    <strong>{{ item.value }}</strong>
                    <small>{{ item.share }}%</small>
                  </span>
                </div>
                <div class="dashboard-bar-track">
                  <div class="dashboard-bar-fill" :style="{ width: `${Math.max(item.bar, 4)}%`, background: item.color }"></div>
                </div>
              </div>
            </div>
          </section>

          <section class="dashboard-chart-card">
            <h3>Status dos Cursos</h3>
            <p class="dashboard-chart-subtitle">Situação atual do portfólio</p>
            <div v-if="carregando" class="dashboard-chart-empty">Carregando...</div>
            <div v-else-if="chartStatus.length === 0" class="dashboard-chart-empty">Sem dados para os filtros selecionados</div>
            <div v-else class="dashboard-status-grid">
              <div v-for="item in chartStatus" :key="item.label" class="dashboard-status-card">
                <span class="dashboard-status-dot" :style="{ background: item.color }"></span>
                <div>
                  <p class="dashboard-status-value">{{ item.value }}</p>
                  <p class="dashboard-status-label">{{ item.label }}</p>
                  <p class="dashboard-status-share">{{ item.share }}% do total</p>
                </div>
              </div>
            </div>
          </section>

          <section class="dashboard-chart-card">
            <h3>Faixas de Carga Horária</h3>
            <p class="dashboard-chart-subtitle">Cursos agrupados por carga horária</p>
            <div v-if="carregando" class="dashboard-chart-empty">Carregando...</div>
            <div v-else class="dashboard-bars">
              <div v-for="item in chartCargaHoraria" :key="item.label" class="dashboard-bar-item">
                <div class="dashboard-bar-head">
                  <span class="dashboard-bar-dot" :style="{ background: item.color }"></span>
                  <span class="dashboard-bar-label">{{ item.label }}</span>
                  <span class="dashboard-bar-meta">
                    <strong>{{ item.value }}</strong>
                    <small>{{ item.share }}%</small>
                  </span>
                </div>
                <div class="dashboard-bar-track">
                  <div class="dashboard-bar-fill" :style="{ width: `${Math.max(item.bar, item.value ? 4 : 0)}%`, background: item.color }"></div>
                </div>
              </div>
            </div>
          </section>
        </div>

        <section class="dashboard-summary-panel">
          <h3>Resumo por Eixo</h3>
          <p class="dashboard-chart-subtitle">Participação de cada eixo no resultado filtrado</p>
          <div v-if="resumoPorEixo.length === 0" class="dashboard-chart-empty">Sem dados para exibir.</div>
          <div v-else class="dashboard-summary-list">
            <div v-for="item in resumoPorEixo" :key="item.label" class="dashboard-summary-item">
              <div class="dashboard-summary-row">
                <span class="dashboard-summary-name" :title="item.label">{{ item.label }}</span>
                <span class="dashboard-summary-meta">
                  <strong>{{ item.value }}</strong>
                  <small>{{ item.share }}%</small>
                </span>
              </div>
              <div class="dashboard-summary-track">
                <div class="dashboard-summary-fill" :style="{ width: `${Math.max(item.bar, 4)}%` }"></div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </template>

    <template v-else-if="filtros.grupo === 'visitas'">
      <section class="dashboard-group-intro">
        <h2>Indicadores de Visitas Técnicas</h2>
        <p>Dados consolidados a partir dos registros de visitas técnicas.</p>
      </section>

      <section class="dashboard-kpi-grid visitas">
        <article v-for="card in indicadoresVisitas.cards" :key="card.title" class="dashboard-kpi-card">
          <p class="dashboard-kpi-value">{{ card.value }}</p>
          <p class="dashboard-kpi-title">{{ card.title }}</p>
          <p class="dashboard-kpi-subtitle">{{ card.subtitle }}</p>
          <div class="dashboard-kpi-track">
            <div class="dashboard-kpi-fill" :style="{ width: `${card.percent}%`, background: card.color }"></div>
          </div>
        </article>
      </section>

      <div class="dashboard-split-grid">
        <section class="dashboard-chart-card">
          <h3>Visitas por Eixo Tecnológico</h3>
          <p class="dashboard-chart-subtitle">Quantas visitas cada eixo realizou no período</p>
          <div v-if="indicadoresVisitas.porEixo.length === 0" class="dashboard-chart-empty">Nenhum dado para exibir.</div>
          <div v-else class="dashboard-bars">
            <div v-for="item in indicadoresVisitas.porEixo" :key="item.label" class="dashboard-bar-item">
              <div class="dashboard-bar-head">
                <span class="dashboard-bar-dot" :style="{ background: item.color }"></span>
                <span class="dashboard-bar-label" :title="item.label">{{ item.label }}</span>
                <span class="dashboard-bar-meta">
                  <strong>{{ item.value }}</strong>
                  <small>{{ item.share }}%</small>
                </span>
              </div>
              <div class="dashboard-bar-track">
                <div class="dashboard-bar-fill" :style="{ width: `${Math.max(item.bar, 4)}%`, background: item.color }"></div>
              </div>
            </div>
          </div>
        </section>

        <section class="dashboard-chart-card">
          <h3>Distribuição por Status</h3>
          <p class="dashboard-chart-subtitle">Situação atual de cada solicitação</p>
          <div v-if="indicadoresVisitas.porStatus.length === 0" class="dashboard-chart-empty">Nenhum dado para exibir.</div>
          <div v-else class="dashboard-status-grid">
            <div v-for="item in indicadoresVisitas.porStatus" :key="item.label" class="dashboard-status-card">
              <span class="dashboard-status-dot" :style="{ background: item.color }"></span>
              <div>
                <p class="dashboard-status-value">{{ item.value }}</p>
                <p class="dashboard-status-label">{{ item.label }}</p>
                <p class="dashboard-status-share">{{ item.share }}% do total</p>
              </div>
            </div>
          </div>
        </section>

        <section class="dashboard-chart-card">
          <h3>Visitas por Unidade Solicitante</h3>
          <p class="dashboard-chart-subtitle">Qual unidade mais solicitou visitas técnicas</p>
          <div v-if="indicadoresVisitas.porUnidade.length === 0" class="dashboard-chart-empty">Nenhum dado para exibir.</div>
          <div v-else class="dashboard-bars">
            <div v-for="item in indicadoresVisitas.porUnidade" :key="item.label" class="dashboard-bar-item">
              <div class="dashboard-bar-head">
                <span class="dashboard-bar-dot" :style="{ background: item.color }"></span>
                <span class="dashboard-bar-label" :title="item.label">{{ item.label }}</span>
                <span class="dashboard-bar-meta">
                  <strong>{{ item.value }}</strong>
                  <small>{{ item.share }}%</small>
                </span>
              </div>
              <div class="dashboard-bar-track">
                <div class="dashboard-bar-fill" :style="{ width: `${Math.max(item.bar, 4)}%`, background: item.color }"></div>
              </div>
            </div>
          </div>
        </section>

        <section class="dashboard-chart-card">
          <h3>Pessoas Mais Acionadas</h3>
          <p class="dashboard-chart-subtitle">Quantas vezes cada pessoa foi chamada</p>
          <div v-if="indicadoresVisitas.porResponsavel.length === 0" class="dashboard-chart-empty">Nenhum dado para exibir.</div>
          <div v-else class="dashboard-bars">
            <div v-for="item in indicadoresVisitas.porResponsavel" :key="item.label" class="dashboard-bar-item">
              <div class="dashboard-bar-head">
                <span class="dashboard-bar-dot" :style="{ background: item.color }"></span>
                <span class="dashboard-bar-label" :title="item.label">{{ item.label }}</span>
                <span class="dashboard-bar-meta">
                  <strong>{{ item.value }}</strong>
                  <small>{{ item.share }}%</small>
                </span>
              </div>
              <div class="dashboard-bar-track">
                <div class="dashboard-bar-fill" :style="{ width: `${Math.max(item.bar, 4)}%`, background: item.color }"></div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </template>

    <template v-else>
      <section class="dashboard-group-intro">
        <h2>Indicadores de Horas Pedagógicas</h2>
        <p>Dados consolidados a partir das solicitações de horas pedagógicas.</p>
      </section>

      <section class="dashboard-kpi-grid horas">
        <article v-for="card in indicadoresHoras.cards" :key="card.title" class="dashboard-kpi-card">
          <p class="dashboard-kpi-value">{{ card.value }}</p>
          <p class="dashboard-kpi-title">{{ card.title }}</p>
          <p class="dashboard-kpi-subtitle">{{ card.subtitle }}</p>
          <div class="dashboard-kpi-track">
            <div class="dashboard-kpi-fill" :style="{ width: `${card.percent}%`, background: card.color }"></div>
          </div>
        </article>
      </section>

      <div class="dashboard-split-grid">
        <section class="dashboard-chart-card">
          <h3>Solicitações por Eixo Tecnológico</h3>
          <p class="dashboard-chart-subtitle">Distribuição das solicitações por eixo</p>
          <div v-if="indicadoresHoras.porEixo.length === 0" class="dashboard-chart-empty">Nenhum dado para exibir.</div>
          <div v-else class="dashboard-bars">
            <div v-for="item in indicadoresHoras.porEixo" :key="item.label" class="dashboard-bar-item">
              <div class="dashboard-bar-head">
                <span class="dashboard-bar-dot" :style="{ background: item.color }"></span>
                <span class="dashboard-bar-label" :title="item.label">{{ item.label }}</span>
                <span class="dashboard-bar-meta">
                  <strong>{{ item.value }}</strong>
                  <small>{{ item.share }}%</small>
                </span>
              </div>
              <div class="dashboard-bar-track">
                <div class="dashboard-bar-fill" :style="{ width: `${Math.max(item.bar, 4)}%`, background: item.color }"></div>
              </div>
            </div>
          </div>
        </section>

        <section class="dashboard-chart-card">
          <h3>Distribuição por Status</h3>
          <p class="dashboard-chart-subtitle">Situação atual das solicitações</p>
          <div v-if="indicadoresHoras.porStatus.length === 0" class="dashboard-chart-empty">Nenhum dado para exibir.</div>
          <div v-else class="dashboard-status-grid">
            <div v-for="item in indicadoresHoras.porStatus" :key="item.label" class="dashboard-status-card">
              <span class="dashboard-status-dot" :style="{ background: item.color }"></span>
              <div>
                <p class="dashboard-status-value">{{ item.value }}</p>
                <p class="dashboard-status-label">{{ item.label }}</p>
                <p class="dashboard-status-share">{{ item.share }}% do total</p>
              </div>
            </div>
          </div>
        </section>

        <section class="dashboard-chart-card">
          <h3>Solicitações por Segmento</h3>
          <p class="dashboard-chart-subtitle">Segmentos com maior volume de solicitações</p>
          <div v-if="indicadoresHoras.porSegmento.length === 0" class="dashboard-chart-empty">Nenhum dado para exibir.</div>
          <div v-else class="dashboard-bars">
            <div v-for="item in indicadoresHoras.porSegmento" :key="item.label" class="dashboard-bar-item">
              <div class="dashboard-bar-head">
                <span class="dashboard-bar-dot" :style="{ background: item.color }"></span>
                <span class="dashboard-bar-label" :title="item.label">{{ item.label }}</span>
                <span class="dashboard-bar-meta">
                  <strong>{{ item.value }}</strong>
                  <small>{{ item.share }}%</small>
                </span>
              </div>
              <div class="dashboard-bar-track">
                <div class="dashboard-bar-fill" :style="{ width: `${Math.max(item.bar, 4)}%`, background: item.color }"></div>
              </div>
            </div>
          </div>
        </section>

        <section class="dashboard-chart-card">
          <h3>Pessoas Mais Acionadas</h3>
          <p class="dashboard-chart-subtitle">Quantidade de solicitações por pessoa</p>
          <div v-if="indicadoresHoras.porPessoa.length === 0" class="dashboard-chart-empty">Nenhum dado para exibir.</div>
          <div v-else class="dashboard-bars">
            <div v-for="item in indicadoresHoras.porPessoa" :key="item.label" class="dashboard-bar-item">
              <div class="dashboard-bar-head">
                <span class="dashboard-bar-dot" :style="{ background: item.color }"></span>
                <span class="dashboard-bar-label" :title="item.label">{{ item.label }}</span>
                <span class="dashboard-bar-meta">
                  <strong>{{ item.value }}</strong>
                  <small>{{ item.share }}%</small>
                </span>
              </div>
              <div class="dashboard-bar-track">
                <div class="dashboard-bar-fill" :style="{ width: `${Math.max(item.bar, 4)}%`, background: item.color }"></div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </template>
  </div>
</template>

<script src="../scripts/Dashboard.js"></script>
<style scoped src="../../css/Dashboard.css"></style>
