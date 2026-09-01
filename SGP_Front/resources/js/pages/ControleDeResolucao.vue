<template>
  <div class="crud-page controle-resolucoes-page" :class="{ 'crud-page-form': modo !== 'lista' }">
    <template v-if="modo === 'lista'">
      <CrudPageHeader
        title="Controle de Resoluções"
        subtitle="Acompanhamento da vigência, status e vencimentos das resoluções institucionais"
        info="Vigência padrão de 5 anos. O semáforo antecipa atenção (6 meses) e crítico (1 mês) antes do vencimento."
        :show-novo="podeEditar"
        novo-label="Nova resolução"
        @novo="abrirNovaResolucao"
      />

      <CrudAlerts :sucesso="mensagemSucesso" :erro="mensagemErro" />

      <section class="filtros-panel">
        <div class="filtros-row">
          <div class="filtro-busca">
            <span class="filtro-busca-icon" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </span>
            <input
              v-model="filtros.busca"
              type="search"
              placeholder="Buscar por número, resumo, relator ou curso"
              aria-label="Buscar resolução"
              @input="aplicarFiltros"
            />
          </div>

          <div class="filtro-campo filtro-dropdown">
            <label for="filtro-resumo">Prazo</label>
            <SearchableSelect
              id="filtro-resumo"
              input-id="filtro-resumo"
              v-model="filtroResumo"
              :options="resumoOptions"
              @change="aplicarResumoFiltro"
            />
          </div>

          <div class="filtro-campo">
            <label for="filtro-setor">Setor</label>
            <SearchableSelect
              id="filtro-setor"
              input-id="filtro-setor"
              v-model="filtros.setor"
              :options="meta.setores"
              empty-option="Todos"
              @change="aplicarFiltros"
            />
          </div>

          <div class="filtro-campo">
            <label for="filtro-categoria">Categoria</label>
            <SearchableSelect
              id="filtro-categoria"
              input-id="filtro-categoria"
              v-model="filtros.categoria"
              :options="meta.categorias"
              empty-option="Todas"
              @change="aplicarFiltros"
            />
          </div>

          <div class="filtro-campo">
            <label for="filtro-status">Status</label>
            <SearchableSelect
              id="filtro-status"
              input-id="filtro-status"
              v-model="filtros.status"
              :options="meta.status.map((status) => ({ value: status, label: labelStatus(status) }))"
              empty-option="Todos"
              @change="aplicarFiltros"
            />
          </div>

          <div class="filtro-campo">
            <label for="filtro-ano">Ano</label>
            <SearchableSelect
              id="filtro-ano"
              input-id="filtro-ano"
              v-model="filtros.ano"
              :options="anosDisponiveis"
              empty-option="Todos"
              @change="aplicarFiltros"
            />
          </div>
        </div>

        <div class="filtros-rodape">
          <div class="filtros-resumo" aria-live="polite">
            <span v-if="filtros.setor" class="resumo-chip">Setor: {{ filtros.setor }}</span>
            <span v-if="filtros.categoria" class="resumo-chip">Categoria: {{ filtros.categoria }}</span>
            <span v-if="filtros.status" class="resumo-chip">Status: {{ labelStatus(filtros.status) }}</span>
            <span v-if="filtros.ano" class="resumo-chip">Ano: {{ filtros.ano }}</span>
          </div>
          <button v-if="temFiltroAtivo" type="button" class="btn-limpar-filtros" @click="limparFiltros">
            Limpar filtros
          </button>
        </div>
      </section>

      <PageTableCard :total="registrosFiltrados.length" aria-label="Tabela de resoluções">
        <div v-if="carregando" class="tabela-loading">Carregando resoluções...</div>

        <div v-else-if="registrosFiltrados.length === 0 && !temFiltroAtivo" class="tabela-vazia estado-vazio">
          <p class="estado-vazio-titulo">Nenhum registro cadastrado ainda.</p>
          <p class="estado-vazio-texto">Os registros aparecerão aqui após o cadastro ou a importação.</p>
        </div>

        <div v-else-if="registrosFiltrados.length === 0" class="tabela-vazia">
          Nenhum registro encontrado para os filtros selecionados.
        </div>

        <div v-else class="tabela-wrap">
          <table class="crud-table">
            <thead>
              <tr>
                <th>Número</th>
                <th>Resumo</th>
                <th>Curso</th>
                <th>Setor</th>
                <th>Vigência</th>
                <th>Status</th>
                <th>Relator</th>
                <th class="text-center">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in registrosFiltrados" :key="item.id">
                <td>
                  <div class="resolucao-numero">{{ item.numero }}</div>
                  <small class="meta-muted">{{ item.categoria || '—' }}</small>
                </td>
                <td>
                  <div class="assunto-texto">{{ item.resumo }}</div>
                </td>
                <td>{{ item.curso_relacionado || '—' }}</td>
                <td>
                  <span class="tag-unidade">{{ item.setor || '—' }}</span>
                </td>
                <td>
                  <div class="vigencia-data">{{ formatarData(item.data_inicio_vigencia) }}</div>
                  <small class="meta-muted">até {{ formatarData(item.data_fim_vigencia) }}</small>
                </td>
                <td>
                  <div class="status-prazo-cell">
                    <span class="badge-status" :class="classeStatus(item.status)">{{ labelStatus(item.status) }}</span>
                    <IndicadorPrazo
                      :status="semaforoDe(item)"
                      :label="labelSemaforo(item.status_vigencia)"
                      :data-prazo="formatarData(item.data_fim_vigencia)"
                      :mostrar-data="false"
                    />
                  </div>
                </td>
                <td>{{ item.relator || '—' }}</td>
                <td class="text-center acoes">
                  <button type="button" class="btn-icon btn-view" title="Ver detalhes" @click="abrirDetalhes(item)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                  <button v-if="podeEditar" type="button" class="btn-icon btn-edit" title="Editar resolução" @click="abrirEdicao(item)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                  </button>
                  <button v-if="podeEditar" type="button" class="btn-icon btn-delete" title="Excluir resolução" @click="excluirResolucao(item)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </PageTableCard>

      <div v-if="detalheAberto" class="modal-overlay" @click.self="fecharDetalhes">
        <div class="modal-detalhes" role="dialog" aria-modal="true" aria-labelledby="detalhes-resolucao-titulo">
          <div class="modal-detalhes-header">
            <h2 id="detalhes-resolucao-titulo">Detalhes da resolução</h2>
            <button type="button" class="btn-fechar-x" title="Fechar" @click="fecharDetalhes">×</button>
          </div>

          <div v-if="resolucaoEmEdicao" class="modal-detalhes-content">
            <div class="detalhe-grid detalhe-grid-2">
              <div class="detalhe-campo">
                <span class="detalhe-label">Número</span>
                <span class="detalhe-valor">{{ resolucaoEmEdicao.numero }}</span>
              </div>
              <div class="detalhe-campo">
                <span class="detalhe-label">Status</span>
                <span class="detalhe-valor">
                  <span class="badge-status" :class="classeStatus(resolucaoEmEdicao.status)">{{ labelStatus(resolucaoEmEdicao.status) }}</span>
                </span>
              </div>
              <div class="detalhe-campo detalhe-campo-full">
                <span class="detalhe-label">Resumo</span>
                <span class="detalhe-valor">{{ resolucaoEmEdicao.resumo }}</span>
              </div>
              <div class="detalhe-campo">
                <span class="detalhe-label">Curso relacionado</span>
                <span class="detalhe-valor">{{ resolucaoEmEdicao.curso_relacionado || '—' }}</span>
              </div>
              <div class="detalhe-campo">
                <span class="detalhe-label">Categoria</span>
                <span class="detalhe-valor">{{ resolucaoEmEdicao.categoria || '—' }}</span>
              </div>
              <div class="detalhe-campo">
                <span class="detalhe-label">Relator</span>
                <span class="detalhe-valor">{{ resolucaoEmEdicao.relator || '—' }}</span>
              </div>
              <div class="detalhe-campo">
                <span class="detalhe-label">Setor</span>
                <span class="detalhe-valor">{{ resolucaoEmEdicao.setor || '—' }}</span>
              </div>
              <div class="detalhe-campo">
                <span class="detalhe-label">Início da vigência</span>
                <span class="detalhe-valor">{{ formatarData(resolucaoEmEdicao.data_inicio_vigencia) }}</span>
              </div>
              <div class="detalhe-campo">
                <span class="detalhe-label">Fim da vigência</span>
                <span class="detalhe-valor">
                  {{ formatarData(resolucaoEmEdicao.data_fim_vigencia) }}
                  <IndicadorPrazo
                    :status="semaforoDe(resolucaoEmEdicao)"
                    :label="labelSemaforo(resolucaoEmEdicao.status_vigencia)"
                    :mostrar-data="false"
                  />
                </span>
              </div>
              <div class="detalhe-campo detalhe-campo-full">
                <span class="detalhe-label">Observações</span>
                <span class="detalhe-valor detalhe-valor-texto">{{ resolucaoEmEdicao.observacoes || '—' }}</span>
              </div>
              <div class="detalhe-campo detalhe-campo-full">
                <span class="detalhe-label">Anexo</span>
                <span class="detalhe-valor">
                  <a
                    v-if="resolucaoEmEdicao.anexo_url"
                    :href="resolucaoEmEdicao.anexo_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="anexo-link"
                  >Abrir anexo</a>
                  <span v-else>—</span>
                </span>
              </div>
            </div>

            <div class="detalhe-secao">
              <h3>Linha do tempo</h3>
              <LinhaDoTempo :eventos="historico" />
            </div>

            <div class="modal-detalhes-actions">
              <button type="button" class="btn-secondary" @click="fecharDetalhes">Fechar</button>
              <button v-if="podeEditar" type="button" class="btn-editar-modal" @click="abrirEdicao(resolucaoEmEdicao)">
                Editar
              </button>
            </div>
          </div>
        </div>
      </div>
    </template>

    <template v-else>
      <CrudFormShell
        :title="modo === 'novo' ? 'Cadastrar Nova Resolução' : 'Editar Resolução'"
        :subtitle="modo === 'novo' ? 'Preencha as informações para adicionar uma nova resolução.' : 'Atualize os dados da resolução selecionada.'"
        @voltar="voltarLista"
      >
        <form class="form-body" @submit.prevent="salvarResolucao">
          <div v-if="erroFormulario" class="alert alert-error">{{ erroFormulario }}</div>

          <section class="form-section">
            <h2>Dados da resolução</h2>
            <div class="form-grid">
              <div class="form-group">
                <label for="resolucao-numero">Número <span>*</span></label>
                <input id="resolucao-numero" v-model="form.numero" type="text" placeholder="Ex: MEC/2026/001" maxlength="100" required />
              </div>
              <div class="form-group">
                <label for="resolucao-status">Status</label>
                <SearchableSelect
                  id="resolucao-status"
                  input-id="resolucao-status"
                  v-model="form.status"
                  :options="meta.status.map((status) => ({ value: status, label: labelStatus(status) }))"
                  empty-option="Automático (pela vigência)"
                />
              </div>
              <div class="form-group full">
                <label for="resolucao-resumo">Resumo <span>*</span></label>
                <input id="resolucao-resumo" v-model="form.resumo" type="text" placeholder="Resumo da resolução" maxlength="1000" required />
              </div>
              <div class="form-group">
                <label for="resolucao-curso">Curso relacionado</label>
                <input id="resolucao-curso" v-model="form.curso_relacionado" type="text" placeholder="Curso ou técnico relacionado" maxlength="255" />
              </div>
              <div class="form-group">
                <label for="resolucao-categoria">Categoria</label>
                <SearchableSelect
                  id="resolucao-categoria"
                  input-id="resolucao-categoria"
                  v-model="form.categoria"
                  :options="meta.categorias"
                  empty-option="Selecione..."
                />
              </div>
              <div class="form-group">
                <label for="resolucao-relator">Relator</label>
                <input id="resolucao-relator" v-model="form.relator" type="text" placeholder="Nome do relator" maxlength="255" />
              </div>
              <div class="form-group">
                <label for="resolucao-setor">Setor</label>
                <SearchableSelect
                  id="resolucao-setor"
                  input-id="resolucao-setor"
                  v-model="form.setor"
                  :options="meta.setores"
                  empty-option="Selecione..."
                />
              </div>
            </div>
          </section>

          <section class="form-section">
            <h2>Vigência</h2>
            <div class="form-grid">
              <div class="form-group">
                <label for="resolucao-inicio">Início da vigência <span>*</span></label>
                <input id="resolucao-inicio" v-model="form.data_inicio_vigencia" type="date" required />
              </div>
              <div class="form-group">
                <label for="resolucao-fim">Fim da vigência</label>
                <input id="resolucao-fim" :value="dataFimCalculada" type="date" disabled />
                <small class="campo-ajuda">Calculado automaticamente ({{ meta.vigencia_anos }} anos).</small>
              </div>
            </div>
          </section>

          <section class="form-section">
            <h2>Observações e anexo</h2>
            <div class="form-grid">
              <div class="form-group full">
                <label for="resolucao-observacoes">Observações</label>
                <textarea id="resolucao-observacoes" v-model="form.observacoes" rows="3" placeholder="Informações complementares"></textarea>
              </div>
              <div class="form-group full">
                <label for="resolucao-anexo">Anexo</label>
                <input id="resolucao-anexo" type="file" accept=".pdf,.doc,.docx,.odt,.jpg,.jpeg,.png" @change="aoEscolherAnexo" />
                <small v-if="form.anexo_url && !form.anexoFile" class="campo-ajuda">
                  Anexo atual:
                  <a :href="form.anexo_url" target="_blank" rel="noopener noreferrer" class="anexo-link">abrir arquivo</a>
                </small>
              </div>
            </div>
          </section>

          <div class="form-actions">
            <button type="button" class="btn-secondary" @click="voltarLista">Cancelar</button>
            <button v-if="podeEditar" type="submit" class="btn-salvar" :disabled="salvando">
              {{ salvando ? 'Salvando...' : modo === 'editar' ? 'Salvar Alterações' : 'Cadastrar' }}
            </button>
          </div>
        </form>
      </CrudFormShell>
    </template>
  </div>
</template>

<script src="../scripts/ControleDeResolucao.js"></script>
<style scoped src="../../css/ControleDeResolucao.css"></style>
