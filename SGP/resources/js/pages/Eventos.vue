<template>
  <div class="crud-page" :class="{ 'crud-page-form': modo !== 'lista' }">
    <template v-if="modo === 'lista'">
      <CrudPageHeader
        title="Eventos"
        subtitle="Cadastro e acompanhamento de eventos por eixo, unidade e ação extensiva"
        info="Nenhuma planilha oficial de Eventos foi disponibilizada ainda. Os cadastros seguem o modelo do protótipo."
        :show-novo="podeEditar"
        novo-label="Novo Evento"
        @novo="abrirNovo"
      />

      <CrudAlerts
        :sucesso="mensagemSucesso"
        :erro="erro"
        :bloqueado="acessoBloqueado"
      />

      <section class="filtros-panel" aria-label="Filtros de eventos">
        <div class="filtros-row">
          <div class="filtro-busca">
            <span class="filtro-busca-icon" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </span>
            <input
              v-model="filtros.busca"
              type="search"
              placeholder="Buscar por evento, eixo, unidade, equipe..."
              aria-label="Buscar evento"
              @input="aplicarFiltros"
            />
          </div>

          <div class="filtro-campo">
            <label for="filtro-ano">Ano</label>
            <select id="filtro-ano" v-model="filtros.ano" @change="aplicarFiltros">
              <option value="">Todos</option>
              <option v-for="ano in anos" :key="ano" :value="ano">{{ ano }}</option>
            </select>
          </div>

          <div class="filtro-campo filtro-campo-eixo">
            <label for="filtro-eixo">Eixo</label>
            <select id="filtro-eixo" v-model="filtros.eixo" @change="aplicarFiltros">
              <option value="">Todos</option>
              <option v-for="eixo in eixos" :key="eixo" :value="eixo">{{ eixo }}</option>
            </select>
          </div>

          <div class="filtro-campo">
            <label for="filtro-unidade">Unidade</label>
            <select id="filtro-unidade" v-model="filtros.unidade" @change="aplicarFiltros">
              <option value="">Todas</option>
              <option v-for="unidade in unidades" :key="unidade" :value="unidade">{{ unidade }}</option>
            </select>
          </div>

          <div class="filtro-campo">
            <label for="filtro-status">Status</label>
            <select id="filtro-status" v-model="filtros.status" @change="aplicarFiltros">
              <option value="">Todos</option>
              <option v-for="status in statusLista" :key="status" :value="status">{{ status }}</option>
            </select>
          </div>

          <div class="filtro-campo">
            <label for="filtro-acao">Ação Extensiva</label>
            <select id="filtro-acao" v-model="filtros.possui_acao_extensiva" @change="aplicarFiltros">
              <option value="">Todos</option>
              <option v-for="item in opcoesAcao" :key="item" :value="item">{{ item }}</option>
            </select>
          </div>
        </div>
      </section>

      <section class="tabela-card" aria-label="Tabela de eventos">
        <div class="tabela-header">
          <span>{{ totalRegistros }} registro{{ totalRegistros !== 1 ? 's' : '' }}</span>
        </div>

        <div v-if="carregando" class="tabela-loading">Carregando...</div>

        <div v-else-if="registros.length === 0 && !temFiltro" class="tabela-vazia estado-vazio">
          <p class="estado-vazio-titulo">Nenhum evento cadastrado ainda.</p>
          <p class="estado-vazio-texto">Os registros aparecerão aqui após o cadastro.</p>
        </div>

        <div v-else class="tabela-wrap">
          <table class="crud-table">
            <thead>
              <tr>
                <th>Evento</th>
                <th>Data</th>
                <th>Unidade</th>
                <th>Eixo</th>
                <th>Qtd. Pessoas</th>
                <th>Equipe</th>
                <th>Ação Extensiva</th>
                <th>Status</th>
                <th>Observação</th>
                <th class="text-center">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="totalRegistros === 0">
                <td colspan="10" class="tabela-vazia">
                  Nenhum registro encontrado para os filtros selecionados.
                </td>
              </tr>
              <tr v-for="item in registros" :key="item.id">
                <td class="col-evento" :title="item.nome || ''">
                  <strong class="evento-nome">{{ item.nome || '—' }}</strong>
                </td>
                <td>{{ formatarData(item.data) }}</td>
                <td>{{ item.unidade || '—' }}</td>
                <td>{{ item.eixo || '—' }}</td>
                <td>{{ item.quantidade_pessoas ?? '—' }}</td>
                <td>{{ item.equipe || '—' }}</td>
                <td class="col-acao" :title="textoAcaoExtensiva(item)">
                  {{ textoAcaoExtensiva(item) }}
                </td>
                <td>
                  <span class="badge-status" :class="badgeStatus(item.status)">
                    {{ item.status || '—' }}
                  </span>
                </td>
                <td class="col-observacao" :title="item.observacao || ''">{{ item.observacao || '—' }}</td>
                <td class="text-center acoes">
                  <button type="button" class="btn-icon btn-view" title="Visualizar" @click="abrirDetalhes(item)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                  <button v-if="podeEditar" type="button" class="btn-icon btn-edit" title="Editar" @click="abrirEdicao(item)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                  </button>
                  <button v-if="podeEditar" type="button" class="btn-icon btn-delete" title="Excluir" @click="excluirRegistro(item)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <div v-if="registroDetalhe" class="modal-overlay" @click.self="fecharDetalhes">
        <div class="modal-detalhes" role="dialog" aria-modal="true" aria-labelledby="detalhe-evento-titulo">
          <div class="modal-detalhes-header">
            <div>
              <h2 id="detalhe-evento-titulo">Detalhes do Registro</h2>
              <p class="modal-detalhes-subtitle">Informações resumidas do evento selecionado.</p>
            </div>
            <button type="button" class="btn-fechar-x" title="Fechar" @click="fecharDetalhes">×</button>
          </div>

          <div class="modal-form-wrap">
            <div class="detalhe-form-grid">
              <div class="detalhe-form-campo campo-full">
                <span>Evento</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.nome || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Ano</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.ano || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Data</span>
                <div class="detalhe-valor-box">{{ formatarData(registroDetalhe.data) }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Unidade</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.unidade || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Eixo</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.eixo || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Qtd. Pessoas</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.quantidade_pessoas ?? '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Equipe</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.equipe || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Status</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.status || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Possui Ação Extensiva</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.possui_acao_extensiva || '—' }}</div>
              </div>
              <div class="detalhe-form-campo campo-full">
                <span>Ação Extensiva Vinculada</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.acao_vinculada || '—' }}</div>
              </div>
              <div class="detalhe-form-campo campo-full">
                <span>Observação</span>
                <div class="detalhe-valor-box detalhe-valor-texto">{{ registroDetalhe.observacao || '—' }}</div>
              </div>
            </div>
          </div>

          <div class="modal-detalhes-actions">
            <button v-if="podeEditar" type="button" class="btn-editar-modal" @click="abrirEdicao(registroDetalhe)">
              Editar
            </button>
            <button type="button" class="btn-secondary" @click="fecharDetalhes">Fechar</button>
          </div>
        </div>
      </div>
    </template>

    <template v-else>
      <CrudFormShell
        :title="modo === 'novo' ? 'Cadastrar Evento' : 'Editar Evento'"
        :subtitle="modo === 'novo' ? 'Preencha os dados conforme o cadastro de eventos do protótipo.' : 'Atualize as informações do evento selecionado.'"
        @voltar="voltarLista"
      >
        <form class="form-body" @submit.prevent="salvarRegistro">
          <div v-if="erroFormulario" class="alert alert-error">{{ erroFormulario }}</div>

          <section class="form-section">
            <h2>Dados do evento</h2>
            <div class="form-grid">
              <div class="form-group">
                <label for="ano">Ano</label>
                <select id="ano" v-model="form.ano">
                  <option value="">Selecione...</option>
                  <option v-for="ano in anos" :key="ano" :value="ano">{{ ano }}</option>
                </select>
              </div>
              <div class="form-group">
                <label for="data">Data <span>*</span></label>
                <input id="data" v-model="form.data" type="date" required @change="preencherAnoDaData" />
              </div>
              <div class="form-group full">
                <label for="nome">Nome do Evento <span>*</span></label>
                <input
                  id="nome"
                  v-model="form.nome"
                  type="text"
                  maxlength="200"
                  required
                  placeholder="Ex: Feira de Profissões SENAC DF"
                />
              </div>
              <div class="form-group">
                <label for="unidade">Unidade <span>*</span></label>
                <select id="unidade" v-model="form.unidade" required>
                  <option value="" disabled>Selecione...</option>
                  <option v-for="unidade in unidades" :key="unidade" :value="unidade">{{ unidade }}</option>
                </select>
              </div>
              <div class="form-group">
                <label for="eixo">Eixo <span>*</span></label>
                <select id="eixo" v-model="form.eixo" required>
                  <option value="" disabled>Selecione...</option>
                  <option v-for="eixo in eixos" :key="eixo" :value="eixo">{{ eixo }}</option>
                </select>
              </div>
              <div class="form-group">
                <label for="quantidade_pessoas">Quantidade de Pessoas</label>
                <input
                  id="quantidade_pessoas"
                  v-model.number="form.quantidade_pessoas"
                  type="number"
                  min="0"
                  placeholder="Ex: 120"
                />
              </div>
              <div class="form-group">
                <label for="status">Status <span>*</span></label>
                <select id="status" v-model="form.status" required>
                  <option value="" disabled>Selecione...</option>
                  <option v-for="status in statusLista" :key="status" :value="status">{{ status }}</option>
                </select>
              </div>
              <div class="form-group full">
                <label for="equipe">Equipe / Responsáveis</label>
                <input
                  id="equipe"
                  v-model="form.equipe"
                  type="text"
                  maxlength="255"
                  placeholder="Ex: Equipe CPED"
                />
              </div>
              <div class="form-group">
                <label for="possui_acao_extensiva">Possui Ação Extensiva? <span>*</span></label>
                <select id="possui_acao_extensiva" v-model="form.possui_acao_extensiva" required @change="onMudarAcao">
                  <option v-for="item in opcoesAcao" :key="item" :value="item">{{ item }}</option>
                </select>
              </div>
              <div class="form-group">
                <label for="acao_vinculada">Ação Extensiva Vinculada</label>
                <input
                  id="acao_vinculada"
                  v-model="form.acao_vinculada"
                  type="text"
                  list="acoes-vinculaveis-list"
                  maxlength="255"
                  :disabled="form.possui_acao_extensiva !== 'Sim'"
                  placeholder="Selecione ou digite o título da ação"
                />
                <datalist id="acoes-vinculaveis-list">
                  <option v-for="acao in acoesVinculaveis" :key="acao" :value="acao"></option>
                </datalist>
              </div>
              <div class="form-group full">
                <label for="observacao">Observação</label>
                <textarea
                  id="observacao"
                  v-model="form.observacao"
                  rows="4"
                  placeholder="Observações do evento"
                ></textarea>
              </div>
            </div>
          </section>

          <div class="form-actions">
            <button type="button" class="btn-secondary" @click="voltarLista">Cancelar</button>
            <button type="submit" class="btn-salvar" :disabled="salvando">
              {{ salvando ? 'Salvando...' : modo === 'novo' ? 'Cadastrar' : 'Salvar Alterações' }}
            </button>
          </div>
        </form>
      </CrudFormShell>
    </template>
  </div>
</template>

<script src="../scripts/Eventos.js"></script>
<style scoped src="../../css/Eventos.css"></style>
