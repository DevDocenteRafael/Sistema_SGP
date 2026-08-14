<template>
  <div class="termos-referencia-page" :class="{ 'termos-referencia-page-form': modo !== 'lista' }">
    <!-- LISTA -->
    <template v-if="modo === 'lista'">
      <header class="termos-top">
        <div class="termos-top-row">
          <div>
            <h1>Termos de Referência</h1>
            <p class="termos-subtitle">Acompanhamento do ciclo de vida e tramitação</p>
          </div>
          <button
            type="button"
            class="btn-novo"
            @click="abrirNovo"
          >
            <span class="btn-novo-icon">+</span>
            Novo TR
          </button>
        </div>
        <div class="termos-info">
          Consulte e acompanhe os Termos de Referência por eixo, status, prazo e processo SEI.
        </div>
      </header>

      <CrudAlerts :sucesso="mensagemSucesso" :erro="mensagemErro" />

      <section class="filtros-bar">
        <div class="filtro-busca">
          <input
            v-model="filtros.busca"
            type="search"
            placeholder="Buscar por nome, eixo, SEI..."
            @input="aplicarFiltros"
          />
        </div>
        <select v-model="filtros.eixo" @change="aplicarFiltros">
          <option value="">Todos os eixos</option>
          <option v-for="eixo in eixosDisponiveis" :key="eixo" :value="eixo">{{ eixo }}</option>
        </select>
        <select v-model="filtros.status" @change="aplicarFiltros">
          <option value="">Todos os status</option>
          <option v-for="status in statusDisponiveis" :key="status" :value="status">{{ status }}</option>
        </select>
        <select v-model="filtros.prazo" @change="aplicarFiltros">
          <option value="">Todos os prazos</option>
          <option value="proximo">Próximos 30 dias</option>
          <option value="vencido">Vencidos</option>
        </select>
      </section>

      <section class="tabela-card">
        <div class="tabela-header">
          <span>{{ totalTermos }} termo de referência{{ totalTermos !== 1 ? 's' : '' }}</span>
        </div>

        <div v-if="carregando" class="tabela-loading">
          <Loading tamanho="padrao" texto="Carregando Termos de Referência..." />
        </div>

        <EstadoVazio
          v-else-if="totalTermos === 0"
          tipo="documento"
          titulo="Nenhum Termo de Referência cadastrado ainda."
          descricao="Os TRs aparecerão aqui após o cadastro e acompanhamento de prazos."
          botaoLabel="Novo TR"
          @acao="abrirNovo"
        />

        <div v-else class="tabela-wrap">
          <table class="crud-table">
            <thead>
              <tr>
                <th>Nome</th>
                <th>Eixo</th>
                <th>Processo SEI</th>
                <th>Prazo</th>
                <th>Status</th>
                <th class="text-center">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="termo in termos" :key="termo.id">
                <td>{{ termo.nome }}</td>
                <td>{{ termo.eixo || '—' }}</td>
                <td class="mono">{{ termo.processo_sei }}</td>
                <td>{{ formatarData(termo.prazo_deadline) }}</td>
                <td>
                  <BadgeStatus :tipo="statusToBadgeType(termo.status)" :label="termo.status" />
                </td>
                <td class="text-center acoes">
                  <button type="button" class="btn-icon btn-view" title="Visualizar" @click="abrirDetalhes(termo)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                  <button v-if="podeEditar" type="button" class="btn-icon btn-edit" title="Editar" @click="abrirEdicao(termo)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                  </button>
                  <button v-if="podeEditar" type="button" class="btn-icon btn-delete" title="Excluir" @click="iniciarExclusao(termo)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Modal detalhes -->
      <div
        v-if="detalheAberto"
        class="modal-overlay"
        @click.self="detalheAberto = false"
      >
        <div class="modal-detalhes" role="dialog" aria-labelledby="detalhes-tr-titulo">
          <div class="modal-detalhes-header">
            <h2 id="detalhes-tr-titulo">Detalhes do Termo de Referência</h2>
            <button type="button" class="btn-fechar-x" title="Fechar" @click="detalheAberto = false">
              ×
            </button>
          </div>

          <div v-if="termoSelecionado" class="detalhe-tr-topo">
            <span class="detalhe-tr-icone" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/></svg>
            </span>
            <div>
              <p class="detalhe-tr-nome">{{ termoSelecionado.nome }}</p>
              <p class="detalhe-tr-eixo">{{ termoSelecionado.eixo }}</p>
              <div class="detalhe-badges">
                <BadgeStatus :tipo="statusToBadgeType(termoSelecionado.status)" :label="termoSelecionado.status" />
                <IndicadorPrazo :status="statusPrazoBadge(termoSelecionado.prazo_deadline)" :dataPrazo="formatarData(termoSelecionado.prazo_deadline)" :mostrarData="true" />
              </div>
            </div>
          </div>

          <div v-if="termoSelecionado" class="detalhe-secao">
            <h3>Informações principais</h3>
            <div class="detalhe-grid">
              <div class="detalhe-campo">
                <span class="detalhe-label">Processo SEI</span>
                <span class="detalhe-valor detalhe-valor-mono">{{ termoSelecionado.processo_sei }}</span>
              </div>
              <div class="detalhe-campo">
                <span class="detalhe-label">Prazo / Deadline</span>
                <span class="detalhe-valor">{{ formatarData(termoSelecionado.prazo_deadline) }}</span>
              </div>
              <div class="detalhe-campo">
                <span class="detalhe-label">Data de início</span>
                <span class="detalhe-valor">{{ formatarData(termoSelecionado.data_inicio) || '—' }}</span>
              </div>
              <div class="detalhe-campo">
                <span class="detalhe-label">Data de término</span>
                <span class="detalhe-valor">{{ formatarData(termoSelecionado.data_fim) || '—' }}</span>
              </div>
              <div class="detalhe-campo detalhe-campo-full">
                <span class="detalhe-label">Observação</span>
                <span class="detalhe-valor detalhe-valor-texto">{{ termoSelecionado.observacao || '—' }}</span>
              </div>
            </div>
          </div>

          <div class="detalhe-secao">
            <h3>Linha do tempo</h3>
            <LinhaDoTempo :eventos="historico" />
          </div>

          <div class="modal-detalhes-actions">
            <button
              v-if="podeEditar && termoSelecionado"
              type="button"
              class="btn-editar-modal"
              @click="abrirEdicao(termoSelecionado)"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
              Editar TR
            </button>
            <button
              v-if="podeEditar && termoSelecionado"
              type="button"
              class="btn-delete"
              @click="iniciarExclusao(termoSelecionado)"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
              Excluir TR
            </button>
            <button type="button" class="btn-secondary" @click="fecharDetalhes">
              Fechar
            </button>
          </div>
        </div>
      </div>
    </template>

    <!-- FORMULÁRIO NOVO / EDITAR -->
    <template v-else>
      <div class="form-page">
        <div class="form-top-bar"></div>
        <header class="form-header">
          <button type="button" class="btn-voltar" @click="fecharFormulario">←</button>
          <div>
            <h1>{{ modo === 'novo' ? 'Cadastrar Novo Termo de Referência' : 'Editar Termo de Referência' }}</h1>
            <p>
              {{
                modo === 'novo'
                  ? 'Preencha as informações para adicionar um novo TR'
                  : 'Atualize os dados do TR selecionado'
              }}
            </p>
          </div>
          <BadgeStatus v-if="editandoId" :tipo="statusToBadgeType(form.status)" :label="form.status" tamanho="grande" />
        </header>

        <div class="form-tabs">
          <button
            type="button"
            class="form-tab active"
          >
            Dados Básicos
          </button>
          <button
            type="button"
            class="form-tab"
          >
            Acompanhamento
          </button>
          <button
            type="button"
            class="form-tab"
          >
            Histórico
          </button>
        </div>

        <Feedback v-if="mensagemErro" tipo="erro" :mensagem="mensagemErro" :fechavel="true" @fechar="fecharFeedback" />

        <form class="form-body" @submit.prevent="salvarTermo" novalidate>
          <section class="form-section">
            <div class="form-card">
              <h2>Informações principais</h2>
              <div class="form-grid">
                <div class="form-group full">
                  <label for="nome-tr">Nome do Termo de Referência <span>*</span></label>
                  <input
                    id="nome-tr"
                    v-model="form.nome"
                    type="text"
                    placeholder="Ex: TR - Desenvolvimento de Sistema Web"
                    maxlength="255"
                  />
                </div>
                <div class="form-group">
                  <label for="eixo-tr">Eixo <span>*</span></label>
                  <select id="eixo-tr" v-model="form.eixo">
                    <option value="" disabled>Selecione o eixo...</option>
                    <option v-for="eixo in eixosDisponiveis" :key="eixo" :value="eixo">{{ eixo }}</option>
                  </select>
                </div>
                <div class="form-group">
                  <label for="status-tr">Status <span>*</span></label>
                  <select id="status-tr" v-model="form.status">
                    <option value="">Selecione o status...</option>
                    <option v-for="status in statusDisponiveis" :key="status" :value="status">{{ status }}</option>
                  </select>
                </div>
                <div class="form-group full">
                  <label for="processo-sei">Processo SEI <span>*</span></label>
                  <input
                    id="processo-sei"
                    v-model="form.processo_sei"
                    type="text"
                    placeholder="Ex: 2026.12.85.00001-0"
                    maxlength="50"
                  />
                </div>
                <div class="form-group">
                  <label for="prazo-deadline">Prazo / Deadline <span>*</span></label>
                  <input
                    id="prazo-deadline"
                    v-model="form.prazo_deadline"
                    type="date"
                  />
                </div>
              </div>
            </div>

            <div class="form-card">
              <h2>Datas de etapa</h2>
              <div class="form-grid">
                <div class="form-group">
                  <label for="data-inicio">Data de início</label>
                  <input
                    id="data-inicio"
                    v-model="form.data_inicio"
                    type="date"
                  />
                </div>
                <div class="form-group">
                  <label for="data-fim">Data de término prevista</label>
                  <input
                    id="data-fim"
                    v-model="form.data_fim"
                    type="date"
                  />
                </div>
              </div>
            </div>

            <div class="form-card">
              <h2>Observações</h2>
              <div class="form-group">
                <textarea
                  id="observacao-tr"
                  v-model="form.observacao"
                  rows="4"
                  placeholder="Adicione observações, justificativas ou informações adicionais sobre o TR..."
                ></textarea>
              </div>
            </div>

            <div class="form-actions">
              <button type="button" class="btn-secondary" @click="fecharFormulario" :disabled="carregandoFormulario">
                Cancelar
              </button>
              <button type="submit" class="btn-primary" :disabled="carregandoFormulario">
                {{ carregandoFormulario ? 'Salvando...' : 'Salvar Termo de Referência' }}
              </button>
            </div>
          </section>
        </form>
      </div>
    </template>

    <!-- Modal de confirmação de exclusão -->
    
    <div v-if="confirmandoExclusao" class="modal-overlay" @click.self="cancelarExclusao">
      <div class="modal-confirmacao" role="dialog" aria-labelledby="confirmar-titulo">
        <h2 id="confirmar-titulo">Confirmar exclusão</h2>
        <p>Tem certeza que deseja excluir este Termo de Referência?</p>
        <p class="modal-confirmacao-nome">Esta ação não pode ser desfeita.</p>
        <div class="modal-confirmacao-actions">
          <button type="button" class="btn-secondary" @click="cancelarExclusao" :disabled="carregando">
            Cancelar
          </button>
          <button type="button" class="btn-delete" @click="excluirTermo" :disabled="carregando">
            {{ carregando ? 'Excluindo...' : 'Confirmar exclusão' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
  


<script src="../scripts/TermosReferencia.js"></script>
<style scoped src="../../css/TermosReferencia.css"></style>
