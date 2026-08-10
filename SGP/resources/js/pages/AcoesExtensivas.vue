<template>
  <div class="crud-page" :class="{ 'crud-page-form': modo !== 'lista' }">
    <template v-if="modo === 'lista'">
      <CrudPageHeader
        title="Ações Extensivas"
        subtitle="Processos SEI de ações extensivas — atribuições CPED"
        info="Consulte e filtre as ações extensivas por SEI, atribuído, eixo, priorização e status — conforme a planilha de atribuições."
        :show-novo="podeEditar"
        novo-label="Nova Ação"
        @novo="abrirNovo"
      />

      <CrudAlerts
        :sucesso="mensagemSucesso"
        :erro="erro"
        :bloqueado="acessoBloqueado"
      />

      <section class="filtros-panel" aria-label="Filtros de ações extensivas">
        <div class="filtros-row">
          <div class="filtro-busca">
            <span class="filtro-busca-icon" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </span>
            <input
              v-model="filtros.busca"
              type="search"
              placeholder="Buscar por SEI, atribuído, assunto, objetivo ou eixo..."
              aria-label="Buscar ação extensiva"
              @input="aplicarFiltros"
            />
          </div>

          <div class="filtro-campo">
            <label for="filtro-priorizacao">Priorização</label>
            <select id="filtro-priorizacao" v-model="filtros.priorizacao" @change="aplicarFiltros">
              <option value="">Todas</option>
              <option v-for="item in priorizacoes" :key="item" :value="item">{{ item }}</option>
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
            <label for="filtro-status">Status</label>
            <select id="filtro-status" v-model="filtros.status" @change="aplicarFiltros">
              <option value="">Todos</option>
              <option v-for="status in statusLista" :key="status" :value="status">{{ status }}</option>
            </select>
          </div>

          <div class="filtro-campo">
            <label for="filtro-tipo">Tipo</label>
            <select id="filtro-tipo" v-model="filtros.tipo" @change="aplicarFiltros">
              <option value="">Todos</option>
              <option v-for="tipo in tipos" :key="tipo" :value="tipo">{{ tipo }}</option>
            </select>
          </div>
        </div>
      </section>

      <section class="tabela-card" aria-label="Tabela de ações extensivas">
        <div class="tabela-header">
          <span>{{ totalRegistros }} registro{{ totalRegistros !== 1 ? 's' : '' }}</span>
        </div>

        <div v-if="carregando" class="tabela-loading">Carregando...</div>

        <div v-else-if="registros.length === 0 && !temFiltro" class="tabela-vazia estado-vazio">
          <p class="estado-vazio-titulo">Nenhuma ação extensiva cadastrada ainda.</p>
          <p class="estado-vazio-texto">Os registros aparecerão aqui após o cadastro ou a importação da planilha.</p>
        </div>

        <div v-else class="tabela-wrap">
          <table class="crud-table">
            <thead>
              <tr>
                <th>Priorização</th>
                <th>Atribuído</th>
                <th>Eixo</th>
                <th>Número do Processo SEI</th>
                <th>Tipo</th>
                <th>Assunto</th>
                <th>Objetivo</th>
                <th>Status</th>
                <th>Última atualização</th>
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
                <td>
                  <span class="badge-status" :class="badgePriorizacao(item.priorizacao)">
                    {{ item.priorizacao || '—' }}
                  </span>
                </td>
                <td>{{ item.atribuido || '—' }}</td>
                <td>{{ item.eixo || '—' }}</td>
                <td>
                  <strong class="acao-sei">{{ item.numero_processo_sei || '—' }}</strong>
                </td>
                <td>{{ item.tipo || '—' }}</td>
                <td class="col-assunto" :title="item.assunto || ''">{{ item.assunto || '—' }}</td>
                <td class="col-objetivo" :title="item.objetivo || ''">{{ item.objetivo || '—' }}</td>
                <td>
                  <span class="badge-status" :class="badgeStatus(item.status)">
                    {{ item.status || '—' }}
                  </span>
                </td>
                <td>{{ formatarData(item.ultima_atualizacao) }}</td>
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
        <div class="modal-detalhes" role="dialog" aria-modal="true" aria-labelledby="detalhe-acao-titulo">
          <div class="modal-detalhes-header">
            <div>
              <h2 id="detalhe-acao-titulo">Detalhes do Registro</h2>
              <p class="modal-detalhes-subtitle">Informações resumidas da ação extensiva selecionada.</p>
            </div>
            <button type="button" class="btn-fechar-x" title="Fechar" @click="fecharDetalhes">×</button>
          </div>

          <div class="modal-form-wrap">
            <div class="detalhe-form-grid">
              <div class="detalhe-form-campo">
                <span>Processo SEI</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.numero_processo_sei || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Priorização</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.priorizacao || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Atribuído</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.atribuido || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Eixo</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.eixo || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Tipo</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.tipo || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Status</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.status || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Última atualização</span>
                <div class="detalhe-valor-box">{{ formatarData(registroDetalhe.ultima_atualizacao) }}</div>
              </div>
              <div class="detalhe-form-campo campo-full">
                <span>Assunto</span>
                <div class="detalhe-valor-box detalhe-valor-texto">{{ registroDetalhe.assunto || '—' }}</div>
              </div>
              <div class="detalhe-form-campo campo-full">
                <span>Objetivo</span>
                <div class="detalhe-valor-box detalhe-valor-texto">{{ registroDetalhe.objetivo || '—' }}</div>
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
        :title="modo === 'novo' ? 'Cadastrar Ação Extensiva' : 'Editar Ação Extensiva'"
        :subtitle="modo === 'novo' ? 'Preencha os dados no formato da planilha de atribuições SEI.' : 'Atualize as informações da ação extensiva selecionada.'"
        @voltar="voltarLista"
      >
        <form class="form-body" @submit.prevent="salvarRegistro">
          <div v-if="erroFormulario" class="alert alert-error">{{ erroFormulario }}</div>

          <section class="form-section">
            <h2>Dados do processo</h2>
            <div class="form-grid">
              <div class="form-group">
                <label for="numero_processo_sei">Número do Processo SEI <span>*</span></label>
                <input
                  id="numero_processo_sei"
                  v-model="form.numero_processo_sei"
                  type="text"
                  maxlength="50"
                  required
                  placeholder="Ex: 2026.000001381-46"
                />
              </div>
              <div class="form-group">
                <label for="priorizacao">Priorização <span>*</span></label>
                <select id="priorizacao" v-model="form.priorizacao" required>
                  <option value="" disabled>Selecione...</option>
                  <option v-for="item in priorizacoes" :key="item" :value="item">{{ item }}</option>
                </select>
              </div>
              <div class="form-group">
                <label for="atribuido">Atribuído <span>*</span></label>
                <input
                  id="atribuido"
                  v-model="form.atribuido"
                  type="text"
                  maxlength="100"
                  required
                  placeholder="Ex: ana.5041"
                />
              </div>
              <div class="form-group">
                <label for="eixo">Eixo <span>*</span></label>
                <select id="eixo" v-model="form.eixo" required>
                  <option value="" disabled>Selecione...</option>
                  <option v-for="eixo in eixos" :key="eixo" :value="eixo">{{ eixo }}</option>
                </select>
              </div>
              <div class="form-group">
                <label for="tipo">Tipo <span>*</span></label>
                <select id="tipo" v-model="form.tipo" required>
                  <option v-for="tipo in tipos" :key="tipo" :value="tipo">{{ tipo }}</option>
                </select>
              </div>
              <div class="form-group">
                <label for="status">Status <span>*</span></label>
                <select id="status" v-model="form.status" required>
                  <option value="" disabled>Selecione...</option>
                  <option v-for="status in statusLista" :key="status" :value="status">{{ status }}</option>
                </select>
              </div>
              <div class="form-group">
                <label for="ultima_atualizacao">Última atualização</label>
                <input id="ultima_atualizacao" v-model="form.ultima_atualizacao" type="date" />
              </div>
              <div class="form-group full">
                <label for="assunto">Assunto <span>*</span></label>
                <input
                  id="assunto"
                  v-model="form.assunto"
                  type="text"
                  maxlength="500"
                  required
                  placeholder="Assunto da ação extensiva"
                />
              </div>
              <div class="form-group full">
                <label for="objetivo">Objetivo</label>
                <textarea
                  id="objetivo"
                  v-model="form.objetivo"
                  rows="5"
                  placeholder="Descreva o objetivo da ação"
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

<script src="../scripts/AcoesExtensivas.js"></script>
<style scoped src="../../css/AcoesExtensivas.css"></style>
