<template>
  <div class="crud-page termos-referencia-page" :class="{ 'crud-page-form': modo !== 'lista' }">
    <template v-if="modo === 'lista'">
      <CrudPageHeader
        title="Termos de Referência"
        subtitle="Acompanhamento do ciclo de vida e tramitação"
        info="Consulte e acompanhe os Termos de Referência por eixo, status, prazo e processo SEI — inclusive após a saída da CPED."
        :show-novo="podeEditar"
        novo-label="Novo TR"
        @novo="abrirNovo"
      />

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
        <SearchableSelect
          v-model="filtros.eixo"
          :options="eixosDisponiveis"
          empty-option="Todos os eixos"
          @change="aplicarFiltros"
        />
        <SearchableSelect
          v-model="filtros.status"
          :options="statusDisponiveis"
          empty-option="Todos os status"
          @change="aplicarFiltros"
        />
        <SearchableSelect
          v-model="filtros.prazo"
          :options="[
            { value: 'proximo', label: 'Próximos 30 dias' },
            { value: 'vencido', label: 'Vencidos' },
          ]"
          empty-option="Todos os prazos"
          @change="aplicarFiltros"
        />
      </section>

      <PageTableCard :total="totalTermos" aria-label="Tabela de Termos de Referência">
        <div v-if="carregando" class="tabela-loading">
          <Loading tamanho="padrao" texto="Carregando Termos de Referência..." />
        </div>

        <div v-else-if="totalTermos === 0 && !temFiltro" class="tabela-vazia estado-vazio">
          <p class="estado-vazio-titulo">Nenhum registro cadastrado ainda.</p>
          <p class="estado-vazio-texto">Os registros aparecerão aqui após o cadastro ou a importação.</p>
        </div>

        <div v-else-if="totalTermos === 0" class="tabela-vazia">
          Nenhum registro encontrado para os filtros selecionados.
        </div>

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
                <td class="mono">
                  <ProcessoSeiLink :valor="termo.processo_sei" />
                </td>
                <td>
                  <IndicadorPrazo
                    :status="semaforoDe(termo)"
                    :label="labelPrazo(termo)"
                    :dataPrazo="formatarData(termo.prazo_deadline)"
                    :mostrarData="true"
                  />
                </td>
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
      </PageTableCard>

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
                <IndicadorPrazo
                  :status="semaforoDe(termoSelecionado)"
                  :label="labelPrazo(termoSelecionado)"
                  :dataPrazo="formatarData(termoSelecionado.prazo_deadline)"
                  :mostrarData="true"
                />
              </div>
            </div>
          </div>

          <div v-if="termoSelecionado" class="detalhe-secao">
            <h3>Informações principais</h3>
            <div class="detalhe-grid">
              <div class="detalhe-campo">
                <span class="detalhe-label">Processo SEI</span>
                <span class="detalhe-valor detalhe-valor-mono">
                  <ProcessoSeiLink :valor="termoSelecionado.processo_sei" />
                </span>
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
              Editar TR
            </button>
            <button
              v-if="podeEditar && termoSelecionado"
              type="button"
              class="btn-delete"
              @click="iniciarExclusao(termoSelecionado)"
            >
              Excluir TR
            </button>
            <button type="button" class="btn-secondary" @click="fecharDetalhes">
              Fechar
            </button>
          </div>
        </div>
      </div>
    </template>

    <template v-else>
      <CrudFormShell
        :title="modo === 'novo' ? 'Cadastrar Novo Termo de Referência' : 'Editar Termo de Referência'"
        :subtitle="modo === 'novo' ? 'Preencha as informações para adicionar um novo TR' : 'Atualize os dados, o acompanhamento e o histórico do TR — inclusive após a saída da CPED.'"
        @voltar="fecharFormulario"
      >
        <div class="form-tabs">
          <button
            v-for="aba in abasForm"
            :key="aba.id"
            type="button"
            class="form-tab"
            :class="{ active: abaForm === aba.id }"
            @click="abaForm = aba.id"
          >
            {{ aba.label }}
          </button>
        </div>

        <form class="form-body" @submit.prevent="salvarTermo" novalidate>
          <Feedback v-if="mensagemErro" tipo="erro" :mensagem="mensagemErro" :fechavel="true" @fechar="fecharFeedback" />

          <section v-show="abaForm === 'basico'" class="form-section">
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
                  <SearchableSelect
                    id="eixo-tr"
                    input-id="eixo-tr"
                    v-model="form.eixo"
                    :options="eixosDisponiveis"
                    empty-option="Selecione o eixo..."
                  />
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
              </div>
            </div>
          </section>

          <section v-show="abaForm === 'acompanhamento'" class="form-section">
            <div class="form-card">
              <h2>Tramitação e prazos</h2>
              <div class="form-grid">
                <div class="form-group">
                  <label for="status-tr">Status <span>*</span></label>
                  <SearchableSelect
                    id="status-tr"
                    input-id="status-tr"
                    v-model="form.status"
                    :options="statusDisponiveis"
                    empty-option="Selecione o status..."
                  />
                </div>
                <div class="form-group">
                  <label for="prazo-deadline">Prazo / Deadline <span>*</span></label>
                  <input id="prazo-deadline" v-model="form.prazo_deadline" type="date" />
                </div>
                <div class="form-group">
                  <label for="data-inicio">Data de início</label>
                  <input id="data-inicio" v-model="form.data_inicio" type="date" />
                </div>
                <div class="form-group">
                  <label for="data-fim">Data de término prevista</label>
                  <input id="data-fim" v-model="form.data_fim" type="date" />
                </div>
                <div class="form-group full">
                  <label for="observacao-tr">Observações</label>
                  <textarea
                    id="observacao-tr"
                    v-model="form.observacao"
                    rows="4"
                    placeholder="Adicione observações, justificativas ou informações adicionais sobre o TR..."
                  ></textarea>
                </div>
              </div>
            </div>
          </section>

          <section v-show="abaForm === 'historico'" class="form-section">
            <div class="form-card">
              <h2>Histórico de tramitação</h2>
              <p v-if="modo === 'novo'" class="form-card-hint">
                O histórico aparece depois que o TR for salvo.
              </p>
              <LinhaDoTempo :eventos="historico" />
            </div>
          </section>

          <div class="form-actions">
            <button type="button" class="btn-secondary" @click="fecharFormulario" :disabled="carregandoFormulario">
              Cancelar
            </button>
            <button v-if="podeEditar" type="submit" class="btn-salvar" :disabled="carregandoFormulario">
              {{ carregandoFormulario ? 'Salvando...' : 'Salvar Termo de Referência' }}
            </button>
          </div>
        </form>
      </CrudFormShell>
    </template>

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
