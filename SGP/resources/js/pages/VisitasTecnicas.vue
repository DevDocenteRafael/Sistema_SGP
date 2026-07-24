<template>
  <div class="visitas-page" :class="{ 'visitas-page-form': modo !== 'lista' }">
    <template v-if="modo === 'lista'">
      <header class="visitas-top">
        <div class="visitas-top-row">
          <div>
            <h1>Visitas Técnicas</h1>
            <p class="visitas-subtitle">Processos de visitas técnicas registradas — SENAC DF</p>
          </div>

          <button v-if="podeEditarVisita" type="button" class="btn-novo" @click="abrirNovo">
            <span class="btn-novo-icon">+</span>
            Nova Visita
          </button>
        </div>

        <div class="visitas-info">
          Consulte e filtre os processos de visita técnica por unidade, eixo, SEI, responsável, ano, status e prazo.
        </div>
      </header>

      <div v-if="mensagemSucesso" class="alert alert-success">{{ mensagemSucesso }}</div>
      <div v-if="erro" class="alert alert-error">{{ erro }}</div>

      <div v-if="acessoBloqueado" class="alert alert-error">
        Você não possui autorização para consultar esta funcionalidade. Verifique seu perfil de acesso.
      </div>

      <section class="filtros-panel" aria-label="Filtros de visitas técnicas">
        <div class="filtros-row">
          <div class="filtro-busca">
            <span class="filtro-busca-icon" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </span>
            <input
              v-model="filtros.busca"
              type="search"
              placeholder="Buscar por unidade, eixo, SEI ou responsável..."
              aria-label="Buscar visita técnica"
              @input="aplicarFiltros"
            />
          </div>

          <div class="filtro-campo">
            <label for="filtro-ano-visita">Ano</label>
            <select id="filtro-ano-visita" v-model="filtros.ano" @change="aplicarFiltros">
              <option value="">Todos</option>
              <option v-for="ano in anosDisponiveis" :key="ano" :value="ano">{{ ano }}</option>
            </select>
          </div>

          <div class="filtro-campo">
            <label for="filtro-unidade-visita">Unidade</label>
            <select id="filtro-unidade-visita" v-model="filtros.unidade" @change="aplicarFiltros">
              <option value="">Todas</option>
              <option v-for="unidade in unidades" :key="unidade" :value="unidade">{{ unidade }}</option>
            </select>
          </div>

          <div class="filtro-campo filtro-campo-eixo">
            <label for="filtro-eixo-visita">Eixo Tecnológico</label>
            <select id="filtro-eixo-visita" v-model="filtros.eixo" @change="aplicarFiltros">
              <option value="">Todos</option>
              <option v-for="eixo in eixos" :key="eixo" :value="eixo">{{ eixo }}</option>
            </select>
          </div>

          <div class="filtro-campo">
            <label for="filtro-status-visita">Status</label>
            <select id="filtro-status-visita" v-model="filtros.status" @change="aplicarFiltros">
              <option value="">Todos</option>
              <option v-for="status in statusLista" :key="status" :value="status">{{ status }}</option>
            </select>
          </div>

          <div class="filtro-campo">
            <label for="filtro-prazo-visita">Prazo</label>
            <select id="filtro-prazo-visita" v-model="filtros.prazo" @change="aplicarFiltros">
              <option value="">Todos</option>
              <option v-for="opcao in prazoLista" :key="opcao.value" :value="opcao.value">
                {{ opcao.label }}
              </option>
            </select>
          </div>
        </div>
      </section>

      <section class="tabela-card" aria-label="Tabela de visitas técnicas">
        <div class="tabela-header">
          <span>{{ totalVisitas }} visita{{ totalVisitas !== 1 ? 's' : '' }}</span>
        </div>

        <div v-if="carregando" class="tabela-loading">Carregando...</div>

        <div v-else-if="visitas.length === 0" class="tabela-vazia estado-vazio">
          <p class="estado-vazio-titulo">Nenhuma visita técnica cadastrada ainda.</p>
          <p class="estado-vazio-texto">Os registros aparecerão aqui após o cadastro ou a importação.</p>
        </div>

        <div v-else class="tabela-wrap">
          <table class="visitas-table">
            <thead>
              <tr>
                <th>Processo SEI</th>
                <th>Unidade</th>
                <th>Eixo</th>
                <th>Responsável</th>
                <th>Solicitação</th>
                <th>Visita prevista</th>
                <th>Prazo</th>
                <th>Status</th>
                <th class="text-center">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="totalVisitas === 0">
                <td colspan="9" class="tabela-vazia">
                  Nenhuma visita encontrada para os filtros selecionados.
                </td>
              </tr>
              <tr v-for="visita in visitasFiltradas" :key="visita.id">
                <td>
                  <strong class="visita-sei">{{ visita.processo_sei || '—' }}</strong>
                </td>
                <td>{{ visita.unidade || '—' }}</td>
                <td>{{ visita.eixo || '—' }}</td>
                <td>{{ visita.responsavel || '—' }}</td>
                <td>{{ formatarData(visita.data_solicitacao) }}</td>
                <td>{{ formatarData(visita.data_visita_prevista) }}</td>
                <td>{{ formatarData(visita.prazo_limite) }}</td>
                <td>
                  <span class="badge-status" :class="statusClass(visita.status)">{{ visita.status }}</span>
                </td>
                <td class="text-center acoes">
                  <button type="button" class="btn-icon btn-view" title="Visualizar" @click="abrirDetalhes(visita)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                  <button v-if="podeEditarVisita" type="button" class="btn-icon btn-edit" title="Editar" @click="abrirEdicao(visita)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                  </button>
                  <button v-if="podeEditarVisita" type="button" class="btn-icon btn-delete" title="Excluir" @click="excluirVisita(visita)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <div v-if="visitaDetalhe" class="modal-overlay" @click.self="fecharDetalhes">
        <div class="modal-detalhes" role="dialog" aria-modal="true" aria-labelledby="detalhe-visita-titulo">
          <div class="modal-detalhes-header">
            <h2 id="detalhe-visita-titulo">Detalhes da Visita Técnica</h2>
            <button type="button" class="btn-fechar-x" title="Fechar" @click="fecharDetalhes">×</button>
          </div>

          <div class="detalhe-grid">
            <div class="detalhe-campo">
              <span class="detalhe-label">Processo SEI</span>
              <span class="detalhe-valor">{{ visitaDetalhe.processo_sei || '—' }}</span>
            </div>
            <div class="detalhe-campo">
              <span class="detalhe-label">Status</span>
              <span class="detalhe-valor">
                <span class="badge-status" :class="statusClass(visitaDetalhe.status)">{{ visitaDetalhe.status }}</span>
              </span>
            </div>
            <div class="detalhe-campo">
              <span class="detalhe-label">Unidade</span>
              <span class="detalhe-valor">{{ visitaDetalhe.unidade || '—' }}</span>
            </div>
            <div class="detalhe-campo">
              <span class="detalhe-label">Eixo</span>
              <span class="detalhe-valor">{{ visitaDetalhe.eixo || '—' }}</span>
            </div>
            <div class="detalhe-campo">
              <span class="detalhe-label">Responsável</span>
              <span class="detalhe-valor">{{ visitaDetalhe.responsavel || '—' }}</span>
            </div>
            <div class="detalhe-campo">
              <span class="detalhe-label">Data de solicitação</span>
              <span class="detalhe-valor">{{ formatarData(visitaDetalhe.data_solicitacao) }}</span>
            </div>
            <div class="detalhe-campo">
              <span class="detalhe-label">Data visita prevista</span>
              <span class="detalhe-valor">{{ formatarData(visitaDetalhe.data_visita_prevista) }}</span>
            </div>
            <div class="detalhe-campo">
              <span class="detalhe-label">Prazo limite</span>
              <span class="detalhe-valor">{{ formatarData(visitaDetalhe.prazo_limite) }}</span>
            </div>
            <div class="detalhe-campo detalhe-campo-full">
              <span class="detalhe-label">Relatório</span>
              <span class="detalhe-valor detalhe-valor-texto">{{ visitaDetalhe.relatorio || '—' }}</span>
            </div>
            <div class="detalhe-campo detalhe-campo-full">
              <span class="detalhe-label">Observação</span>
              <span class="detalhe-valor detalhe-valor-texto">{{ visitaDetalhe.observacao || '—' }}</span>
            </div>
          </div>

          <div class="modal-detalhes-actions">
            <button v-if="podeEditarVisita" type="button" class="btn-editar-modal" @click="abrirEdicao(visitaDetalhe)">
              Editar Visita
            </button>
            <button type="button" class="btn-secondary" @click="fecharDetalhes">Fechar</button>
          </div>
        </div>
      </div>
    </template>

    <template v-else>
      <div class="form-page">
        <div class="form-top-bar"></div>
        <header class="form-header">
          <button type="button" class="btn-voltar" @click="voltarLista">←</button>
          <div>
            <h1>{{ modo === 'novo' ? 'Cadastrar Nova Visita Técnica' : 'Editar Visita Técnica' }}</h1>
            <p>
              {{
                modo === 'novo'
                  ? 'Preencha os dados para registrar um novo processo de visita técnica.'
                  : 'Atualize as informações da visita técnica selecionada.'
              }}
            </p>
          </div>
        </header>

        <form class="form-body" @submit.prevent="salvarVisita">
          <div v-if="erroFormulario" class="alert alert-error">{{ erroFormulario }}</div>

          <section class="form-section">
            <h2>Dados do processo</h2>
            <div class="form-grid">
              <div class="form-group">
                <label for="processo_sei">Processo SEI <span>*</span></label>
                <input
                  id="processo_sei"
                  v-model="form.processo_sei"
                  type="text"
                  maxlength="50"
                  required
                  placeholder="Ex: 00001.000123/2026-01"
                />
              </div>
              <div class="form-group">
                <label for="status">Status <span>*</span></label>
                <select id="status" v-model="form.status" required>
                  <option value="" disabled>Selecione o status</option>
                  <option v-for="status in statusLista" :key="status" :value="status">{{ status }}</option>
                </select>
              </div>
              <div class="form-group">
                <label for="unidade">Unidade <span>*</span></label>
                <select id="unidade" v-model="form.unidade" required>
                  <option value="" disabled>Selecione a unidade</option>
                  <option v-for="unidade in unidades" :key="unidade" :value="unidade">{{ unidade }}</option>
                </select>
              </div>
              <div class="form-group">
                <label for="eixo">Eixo <span>*</span></label>
                <select id="eixo" v-model="form.eixo" required>
                  <option value="" disabled>Selecione o eixo</option>
                  <option v-for="eixo in eixos" :key="eixo" :value="eixo">{{ eixo }}</option>
                </select>
              </div>
              <div class="form-group">
                <label for="responsavel">Responsável <span>*</span></label>
                <input
                  id="responsavel"
                  v-model="form.responsavel"
                  type="text"
                  maxlength="100"
                  required
                  placeholder="Nome do responsável"
                />
              </div>
            </div>
          </section>

          <section class="form-section">
            <h2>Prazos</h2>
            <div class="form-grid">
              <div class="form-group">
                <label for="data_solicitacao">Data de solicitação <span>*</span></label>
                <input id="data_solicitacao" v-model="form.data_solicitacao" type="date" required />
              </div>
              <div class="form-group">
                <label for="data_visita_prevista">Data visita prevista <span>*</span></label>
                <input id="data_visita_prevista" v-model="form.data_visita_prevista" type="date" required />
              </div>
              <div class="form-group">
                <label for="prazo_limite">Prazo limite <span>*</span></label>
                <input id="prazo_limite" v-model="form.prazo_limite" type="date" required />
              </div>
            </div>
          </section>

          <section class="form-section">
            <h2>Relatório e observações</h2>
            <div class="form-grid">
              <div class="form-group full">
                <label for="relatorio">Relatório</label>
                <textarea
                  id="relatorio"
                  v-model="form.relatorio"
                  rows="4"
                  placeholder="Descreva o relatório da visita, quando houver"
                ></textarea>
              </div>
              <div class="form-group full">
                <label for="observacao">Observação</label>
                <textarea
                  id="observacao"
                  v-model="form.observacao"
                  rows="3"
                  placeholder="Informações adicionais ou pendências"
                ></textarea>
              </div>
            </div>
          </section>

          <div class="form-actions">
            <button type="button" class="btn-secondary" @click="voltarLista">Cancelar</button>
            <button type="submit" class="btn-salvar" :disabled="salvando">
              {{ salvando ? 'Salvando...' : modo === 'novo' ? 'Cadastrar Visita' : 'Salvar Alterações' }}
            </button>
          </div>
        </form>
      </div>
    </template>
  </div>
</template>

<script src="../scripts/VisitasTecnicas.js"></script>
<style scoped src="../../css/VisitasTecnicas.css"></style>
