<template>
  <div class="dashboard-page">
    <header class="dashboard-header">
      <div>
        <h1>Dashboard</h1>
        <p class="dashboard-description">
          Indicadores do ciclo
          <strong>{{ cicloNome || 'selecionado' }}</strong>
          — SENAC DF · CPED
        </p>
      </div>

      <div class="dashboard-toolbar">
        <SearchableSelect
          v-model="filtros.grupo"
          aria-label="Grupo de indicadores"
          :options="[
            { value: 'gerais', label: 'Indicadores Gerais' },
            { value: 'visitas', label: 'Indicadores de Visitas Técnicas' },
            { value: 'horas', label: 'Indicadores de Horas Pedagógicas' },
          ]"
        />

        <template v-if="filtros.grupo === 'gerais'">
          <SearchableSelect
            v-model="filtros.ano"
            aria-label="Ano"
            empty-option="Todos os anos"
            :options="anosDisponiveis"
          />

          <SearchableSelect
            v-model="filtros.unidade"
            aria-label="Estrutura Institucional"
            empty-option="Todas as unidades"
            :options="unidadesDisponiveis"
          />

          <SearchableSelect
            v-model="filtros.eixo"
            aria-label="Eixo"
            empty-option="Todos os eixos"
            :options="meta.eixos"
          />

          <SearchableSelect
            v-model="filtros.status"
            aria-label="Status"
            empty-option="Todos os status"
            :options="meta.status"
          />

          <button v-if="temFiltro" type="button" class="btn-limpar" @click="limparFiltros">Limpar</button>
        </template>
      </div>
    </header>

    <div v-if="erro" class="alert alert-error">{{ erro }}</div>

    <template v-if="filtros.grupo === 'gerais'">
      <section class="dashboard-kpi-strip" aria-label="Indicadores principais">
        <article v-for="card in metricCards" :key="card.label" class="dashboard-kpi-tile dashboard-kpi-tile--standard">
          <div class="dashboard-kpi-tile-head">
            <div class="dashboard-metric-icon" v-html="card.icon"></div>
            <span class="dashboard-metric-category"><SgpHelpLabel :label="card.label" /></span>
          </div>
          <div class="dashboard-metric-content">
            <div class="dashboard-metric-primary">
              <p class="dashboard-metric-value">{{ card.value }}</p>
              <p v-if="card.sub" class="dashboard-metric-detail">{{ card.sub }}</p>
            </div>
            <div v-if="card.statuses?.length" class="dashboard-metric-status-list" :aria-label="`Status de ${card.label}`">
              <span v-for="status in card.statuses" :key="status.title" class="dashboard-metric-status">
                <span class="dashboard-metric-status-dot" :style="{ backgroundColor: status.color }" aria-hidden="true"></span>
                <strong>{{ status.value }}</strong>
                <SgpHelpLabel :label="status.title" :help="status.subtitle" :term="status.term" />
              </span>
            </div>
          </div>
        </article>
      </section>

      <section class="dashboard-analytics" aria-label="Análises do portfólio">
        <article class="dashboard-chart-card dashboard-chart-card--eixos">
          <div class="dashboard-chart-head">
            <div>
              <h3><SgpHelpLabel label="Eixos Tecnológicos" /></h3>
              <p class="dashboard-chart-subtitle">Cursos por eixo · {{ totalEixos }} eixos no filtro</p>
              <p class="dashboard-chart-summary">{{ cursosParaGraficos.length }} cursos · {{ chartEixos.length }} eixos</p>
            </div>
          </div>
          <div v-if="carregando" class="dashboard-chart-empty">Carregando...</div>
          <div v-else-if="chartEixos.length === 0" class="dashboard-chart-empty">Sem dados para os filtros selecionados</div>
          <div v-else class="dashboard-rank-list">
            <div v-for="item in chartEixos" :key="item.label" class="dashboard-rank-item">
              <div class="dashboard-rank-body">
                <div class="dashboard-rank-head">
                  <span class="dashboard-rank-label" :title="item.label">{{ item.label }}</span>
                  <span class="dashboard-rank-meta">
                    <strong>{{ item.value }}</strong>
                    <small>{{ item.share }}%</small>
                  </span>
                </div>
                <div class="dashboard-rank-track">
                  <div
                    class="dashboard-rank-fill"
                    :style="{ width: `${Math.max(item.bar, 4)}%` }"
                    :title="`${item.label}: ${item.value} (${item.share}%)`"
                  ></div>
                </div>
              </div>
            </div>
          </div>
        </article>

        <article class="dashboard-chart-card">
          <div class="dashboard-chart-head">
            <div>
              <h3><SgpHelpLabel label="Tipos de Curso" /></h3>
              <p class="dashboard-chart-subtitle">Distribuição por tipo de oferta</p>
              <p class="dashboard-chart-summary">{{ cursosParaGraficos.length }} cursos · {{ chartTipos.length }} tipos</p>
            </div>
          </div>
          <div v-if="carregando" class="dashboard-chart-empty">Carregando...</div>
          <div v-else-if="chartTipos.length === 0" class="dashboard-chart-empty">Sem dados para os filtros selecionados</div>
          <div v-else class="dashboard-vchart" role="img" aria-label="Gráfico de tipos de curso">
            <div class="dashboard-vchart-plot">
              <div class="dashboard-vchart-grid" aria-hidden="true">
                <span></span><span></span><span></span><span></span>
              </div>
              <div class="dashboard-vbars">
                <div v-for="item in chartTipos" :key="item.label" class="dashboard-vbar">
                  <div class="dashboard-vbar-shaft">
                    <div
                      class="dashboard-vbar-fill"
                      :style="{ height: `${Math.max(item.bar, 18)}%` }"
                      :title="`${item.label}: ${item.value} (${item.share}%)`"
                    >
                      <span class="dashboard-vbar-value">{{ item.value }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="dashboard-vchart-axis">
              <div v-for="item in chartTipos" :key="`axis-${item.label}`" class="dashboard-vbar-caption">
                <span class="dashboard-vbar-label" :title="item.label">{{ item.label }}</span>
                <small class="dashboard-vbar-share">{{ item.share }}%</small>
              </div>
            </div>
          </div>
        </article>

        <article class="dashboard-chart-card">
          <div class="dashboard-chart-head">
            <div>
              <h3><SgpHelpLabel label="Faixas de Carga Horária" /></h3>
              <p class="dashboard-chart-subtitle">Cursos agrupados por carga horária</p>
              <p class="dashboard-chart-summary">{{ cursosParaGraficos.length }} cursos · {{ chartCargaHoraria.length }} faixas</p>
            </div>
          </div>
          <div v-if="carregando" class="dashboard-chart-empty">Carregando...</div>
          <div v-else class="dashboard-rank-list dashboard-range-list" role="img" aria-label="Distribuição de cursos por faixa de carga horária">
            <div v-for="item in chartCargaHoraria" :key="item.label" class="dashboard-rank-item">
              <div class="dashboard-rank-body">
                <div class="dashboard-rank-head">
                  <span class="dashboard-rank-label">{{ item.label }}</span>
                  <span class="dashboard-rank-meta">
                    <strong>{{ item.value }}</strong>
                    <small>{{ item.share }}%</small>
                  </span>
                </div>
                <div class="dashboard-rank-track">
                  <div
                    class="dashboard-rank-fill"
                    :style="{ width: `${item.value ? Math.max(item.bar, 4) : 0}%` }"
                    :title="`${item.label}: ${item.value} (${item.share}%)`"
                  ></div>
                </div>
              </div>
            </div>
          </div>
        </article>
      </section>
    </template>

    <template v-else-if="filtros.grupo === 'visitas'">
      <section class="dashboard-kpi-strip dashboard-kpi-strip--single" aria-label="Indicadores de visitas técnicas">
        <article class="dashboard-kpi-tile is-accent dashboard-kpi-tile--summary">
          <div class="dashboard-kpi-tile-head">
            <div class="dashboard-metric-icon" v-html="iconVisitas"></div>
            <span class="dashboard-metric-sub">visitas</span>
          </div>
          <p class="dashboard-metric-value">{{ indicadoresVisitas.total }}</p>
          <p class="dashboard-metric-title">
            <SgpHelpLabel label="Total no período" />
          </p>
          <div class="dashboard-metric-chips" aria-label="Resumo das visitas">
            <span
              v-for="chip in indicadoresVisitas.chipsFluxo"
              :key="chip.title"
              class="dashboard-metric-chip"
            >
              <strong :style="{ color: chip.color }">{{ chip.value }}</strong>
              <SgpHelpLabel :label="chip.title" :help="chip.subtitle" />
            </span>
          </div>
        </article>
      </section>

      <section class="dashboard-analytics" aria-label="Análises de visitas técnicas">
        <article class="dashboard-chart-card">
          <div class="dashboard-chart-head">
            <div>
              <h3>Por Eixo Tecnológico</h3>
              <p class="dashboard-chart-subtitle">Visitas realizadas por eixo</p>
            </div>
          </div>
          <div v-if="indicadoresVisitas.porEixo.length === 0" class="dashboard-chart-empty">Nenhum dado para exibir.</div>
          <div v-else class="dashboard-rank-list">
            <div v-for="(item, index) in indicadoresVisitas.porEixo" :key="item.label" class="dashboard-rank-item">
              <span class="dashboard-rank-pos">{{ index + 1 }}</span>
              <div class="dashboard-rank-body">
                <div class="dashboard-rank-head">
                  <span class="dashboard-rank-label" :title="item.label">{{ item.label }}</span>
                  <span class="dashboard-rank-meta">
                    <strong>{{ item.value }}</strong>
                    <small>{{ item.share }}%</small>
                  </span>
                </div>
                <div class="dashboard-rank-track">
                  <div class="dashboard-rank-fill" :style="{ width: `${Math.max(item.bar, 4)}%`, background: item.color }"></div>
                </div>
              </div>
            </div>
          </div>
        </article>

        <article class="dashboard-chart-card">
          <div class="dashboard-chart-head">
            <div>
              <h3>Por Status</h3>
              <p class="dashboard-chart-subtitle">Distribuição das solicitações</p>
            </div>
          </div>
          <div v-if="indicadoresVisitas.porStatus.length === 0" class="dashboard-chart-empty">Nenhum dado para exibir.</div>
          <div v-else class="dashboard-vchart" role="img" aria-label="Gráfico de status das visitas">
            <div class="dashboard-vchart-plot">
              <div class="dashboard-vchart-grid" aria-hidden="true">
                <span></span><span></span><span></span><span></span>
              </div>
              <div class="dashboard-vbars">
                <div v-for="item in indicadoresVisitas.porStatus" :key="item.label" class="dashboard-vbar">
                  <div class="dashboard-vbar-shaft">
                    <div
                      class="dashboard-vbar-fill"
                      :style="{ height: `${Math.max(item.bar, 18)}%`, background: item.color }"
                      :title="`${item.label}: ${item.value} (${item.share}%)`"
                    >
                      <span class="dashboard-vbar-value">{{ item.value }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="dashboard-vchart-axis">
              <div v-for="item in indicadoresVisitas.porStatus" :key="`axis-${item.label}`" class="dashboard-vbar-caption">
                <span class="dashboard-vbar-label" :title="item.label">{{ item.label }}</span>
                <small class="dashboard-vbar-share">{{ item.share }}%</small>
              </div>
            </div>
          </div>
        </article>

        <article class="dashboard-chart-card">
          <div class="dashboard-chart-head">
            <div>
              <h3>Por Estrutura</h3>
              <p class="dashboard-chart-subtitle">Estruturas que mais solicitaram</p>
            </div>
          </div>
          <div v-if="indicadoresVisitas.porUnidade.length === 0" class="dashboard-chart-empty">Nenhum dado para exibir.</div>
          <div v-else class="dashboard-rank-list">
            <div v-for="(item, index) in indicadoresVisitas.porUnidade" :key="item.label" class="dashboard-rank-item">
              <span class="dashboard-rank-pos">{{ index + 1 }}</span>
              <div class="dashboard-rank-body">
                <div class="dashboard-rank-head">
                  <span class="dashboard-rank-label" :title="item.label">{{ item.label }}</span>
                  <span class="dashboard-rank-meta">
                    <strong>{{ item.value }}</strong>
                    <small>{{ item.share }}%</small>
                  </span>
                </div>
                <div class="dashboard-rank-track">
                  <div class="dashboard-rank-fill" :style="{ width: `${Math.max(item.bar, 4)}%`, background: item.color }"></div>
                </div>
              </div>
            </div>
          </div>
        </article>

        <article class="dashboard-chart-card">
          <div class="dashboard-chart-head">
            <div>
              <h3>Pessoas Mais Acionadas</h3>
              <p class="dashboard-chart-subtitle">Quantas vezes cada pessoa foi chamada</p>
            </div>
          </div>
          <div v-if="indicadoresVisitas.porResponsavel.length === 0" class="dashboard-chart-empty">Nenhum dado para exibir.</div>
          <div v-else class="dashboard-rank-list">
            <div v-for="(item, index) in indicadoresVisitas.porResponsavel" :key="item.label" class="dashboard-rank-item">
              <span class="dashboard-rank-pos">{{ index + 1 }}</span>
              <div class="dashboard-rank-body">
                <div class="dashboard-rank-head">
                  <span class="dashboard-rank-label" :title="item.label">{{ item.label }}</span>
                  <span class="dashboard-rank-meta">
                    <strong>{{ item.value }}</strong>
                    <small>{{ item.share }}%</small>
                  </span>
                </div>
                <div class="dashboard-rank-track">
                  <div class="dashboard-rank-fill" :style="{ width: `${Math.max(item.bar, 4)}%`, background: item.color }"></div>
                </div>
              </div>
            </div>
          </div>
        </article>
      </section>
    </template>

    <template v-else>
      <section class="dashboard-kpi-strip dashboard-kpi-strip--single" aria-label="Indicadores de horas pedagógicas">
        <article class="dashboard-kpi-tile is-accent dashboard-kpi-tile--summary">
          <div class="dashboard-kpi-tile-head">
            <div class="dashboard-metric-icon" v-html="iconHoras"></div>
            <span class="dashboard-metric-sub">solicitações</span>
          </div>
          <p class="dashboard-metric-value">{{ indicadoresHoras.total }}</p>
          <p class="dashboard-metric-title">
            <SgpHelpLabel
              label="Total no período"
              help="Total de solicitações de horas pedagógicas no sistema."
            />
          </p>
          <div class="dashboard-metric-chips" aria-label="Resumo das horas pedagógicas">
            <span
              v-for="chip in indicadoresHoras.chipsFluxo"
              :key="chip.title"
              class="dashboard-metric-chip"
            >
              <strong :style="{ color: chip.color }">{{ chip.value }}</strong>
              <SgpHelpLabel :label="chip.title" :help="chip.subtitle" />
            </span>
          </div>
        </article>
      </section>

      <section class="dashboard-analytics" aria-label="Análises de horas pedagógicas">
        <article class="dashboard-chart-card">
          <div class="dashboard-chart-head">
            <div>
              <h3>Por Eixo Tecnológico</h3>
              <p class="dashboard-chart-subtitle">Solicitações por eixo</p>
            </div>
          </div>
          <div v-if="indicadoresHoras.porEixo.length === 0" class="dashboard-chart-empty">Nenhum dado para exibir.</div>
          <div v-else class="dashboard-rank-list">
            <div v-for="(item, index) in indicadoresHoras.porEixo" :key="item.label" class="dashboard-rank-item">
              <span class="dashboard-rank-pos">{{ index + 1 }}</span>
              <div class="dashboard-rank-body">
                <div class="dashboard-rank-head">
                  <span class="dashboard-rank-label" :title="item.label">{{ item.label }}</span>
                  <span class="dashboard-rank-meta">
                    <strong>{{ item.value }}</strong>
                    <small>{{ item.share }}%</small>
                  </span>
                </div>
                <div class="dashboard-rank-track">
                  <div class="dashboard-rank-fill" :style="{ width: `${Math.max(item.bar, 4)}%`, background: item.color }"></div>
                </div>
              </div>
            </div>
          </div>
        </article>

        <article class="dashboard-chart-card">
          <div class="dashboard-chart-head">
            <div>
              <h3>Por Status</h3>
              <p class="dashboard-chart-subtitle">Distribuição das solicitações</p>
            </div>
          </div>
          <div v-if="indicadoresHoras.porStatus.length === 0" class="dashboard-chart-empty">Nenhum dado para exibir.</div>
          <div v-else class="dashboard-vchart" role="img" aria-label="Gráfico de status das horas">
            <div class="dashboard-vchart-plot">
              <div class="dashboard-vchart-grid" aria-hidden="true">
                <span></span><span></span><span></span><span></span>
              </div>
              <div class="dashboard-vbars">
                <div v-for="item in indicadoresHoras.porStatus" :key="item.label" class="dashboard-vbar">
                  <div class="dashboard-vbar-shaft">
                    <div
                      class="dashboard-vbar-fill"
                      :style="{ height: `${Math.max(item.bar, 18)}%`, background: item.color }"
                      :title="`${item.label}: ${item.value} (${item.share}%)`"
                    >
                      <span class="dashboard-vbar-value">{{ item.value }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="dashboard-vchart-axis">
              <div v-for="item in indicadoresHoras.porStatus" :key="`axis-${item.label}`" class="dashboard-vbar-caption">
                <span class="dashboard-vbar-label" :title="item.label">{{ item.label }}</span>
                <small class="dashboard-vbar-share">{{ item.share }}%</small>
              </div>
            </div>
          </div>
        </article>

        <article class="dashboard-chart-card">
          <div class="dashboard-chart-head">
            <div>
              <h3>Por Segmento</h3>
              <p class="dashboard-chart-subtitle">Segmentos com maior volume</p>
            </div>
          </div>
          <div v-if="indicadoresHoras.porSegmento.length === 0" class="dashboard-chart-empty">Nenhum dado para exibir.</div>
          <div v-else class="dashboard-rank-list">
            <div v-for="(item, index) in indicadoresHoras.porSegmento" :key="item.label" class="dashboard-rank-item">
              <span class="dashboard-rank-pos">{{ index + 1 }}</span>
              <div class="dashboard-rank-body">
                <div class="dashboard-rank-head">
                  <span class="dashboard-rank-label" :title="item.label">{{ item.label }}</span>
                  <span class="dashboard-rank-meta">
                    <strong>{{ item.value }}</strong>
                    <small>{{ item.share }}%</small>
                  </span>
                </div>
                <div class="dashboard-rank-track">
                  <div class="dashboard-rank-fill" :style="{ width: `${Math.max(item.bar, 4)}%`, background: item.color }"></div>
                </div>
              </div>
            </div>
          </div>
        </article>

        <article class="dashboard-chart-card">
          <div class="dashboard-chart-head">
            <div>
              <h3>Pessoas Mais Acionadas</h3>
              <p class="dashboard-chart-subtitle">Quantidade de solicitações por pessoa</p>
            </div>
          </div>
          <div v-if="indicadoresHoras.porPessoa.length === 0" class="dashboard-chart-empty">Nenhum dado para exibir.</div>
          <div v-else class="dashboard-rank-list">
            <div v-for="(item, index) in indicadoresHoras.porPessoa" :key="item.label" class="dashboard-rank-item">
              <span class="dashboard-rank-pos">{{ index + 1 }}</span>
              <div class="dashboard-rank-body">
                <div class="dashboard-rank-head">
                  <span class="dashboard-rank-label" :title="item.label">{{ item.label }}</span>
                  <span class="dashboard-rank-meta">
                    <strong>{{ item.value }}</strong>
                    <small>{{ item.share }}%</small>
                  </span>
                </div>
                <div class="dashboard-rank-track">
                  <div class="dashboard-rank-fill" :style="{ width: `${Math.max(item.bar, 4)}%`, background: item.color }"></div>
                </div>
              </div>
            </div>
          </div>
        </article>
      </section>
    </template>
  </div>
</template>

<script src="../scripts/Dashboard.js"></script>
<style scoped src="../../css/Dashboard.css"></style>
