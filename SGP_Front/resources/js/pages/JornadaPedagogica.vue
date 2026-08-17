<template>
  <div class="crud-page" :class="{ 'crud-page-form': modo !== 'lista' }">
    <template v-if="modo === 'lista'">
      <CrudPageHeader
        title="Jornada Pedagógica"
        subtitle="Planejamento documental da jornada: período, local, verba, programação e anexos"
        info="MVP documental. Status leve: rascunho, consolidado ou enviado. Gere o PDF com os dados do plano."
        :show-novo="podeEditar"
        novo-label="Nova Jornada"
        @novo="abrirNovo"
      />

      <CrudAlerts :sucesso="mensagemSucesso" :erro="mensagemErro" />

      <section class="filtros-panel" aria-label="Filtros de jornadas pedagógicas">
        <div class="filtros-row">
          <div class="filtro-busca">
            <span class="filtro-busca-icon" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </span>
            <input
              v-model="filtros.busca"
              type="search"
              placeholder="Buscar por título, local, espaço ou setores..."
              aria-label="Buscar jornada pedagógica"
              @input="aplicarFiltros"
            />
          </div>
          <div class="filtro-campo">
            <label for="filtro-status-jornada">Status</label>
            <select id="filtro-status-jornada" v-model="filtros.status" @change="aplicarFiltros">
              <option value="">Todos</option>
              <option v-for="status in meta.status" :key="status" :value="status">{{ status }}</option>
            </select>
          </div>
        </div>
      </section>

      <PageTableCard :total="totalRegistros" aria-label="Tabela de jornadas pedagógicas">
        <div v-if="carregando" class="tabela-loading">Carregando...</div>

        <div v-else-if="totalRegistros === 0 && !temFiltro" class="tabela-vazia estado-vazio">
          <p class="estado-vazio-titulo">Nenhum registro cadastrado ainda.</p>
          <p class="estado-vazio-texto">Os registros aparecerão aqui após o cadastro.</p>
        </div>

        <div v-else class="tabela-wrap">
          <table class="crud-table">
            <thead>
              <tr>
                <th>Título</th>
                <th>Período</th>
                <th>Pré-jornada</th>
                <th>Local</th>
                <th>Verba</th>
                <th>Status</th>
                <th class="text-center">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="totalRegistros === 0">
                <td colspan="7" class="tabela-vazia">
                  Nenhum registro encontrado para os filtros selecionados.
                </td>
              </tr>
              <tr v-for="item in registros" :key="item.id">
                <td>{{ item.titulo }}</td>
                <td>{{ textoPeriodo(item) }}</td>
                <td>{{ item.tem_pre_jornada || 'Não' }}</td>
                <td>{{ item.local || '—' }}</td>
                <td>{{ item.verba || '—' }}</td>
                <td>
                  <span class="badge-status" :class="classeStatus(item.status)">{{ item.status }}</span>
                </td>
                <td class="text-center acoes">
                  <button type="button" class="btn-icon btn-view" title="Visualizar" @click="abrirDetalhes(item)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                  <button v-if="podeEditar" type="button" class="btn-icon btn-edit" title="Editar" @click="abrirEdicao(item)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                  </button>
                  <button type="button" class="btn-icon btn-view" title="Gerar PDF" @click="baixarPdf(item)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M12 18v-6"/><path d="m9 15 3 3 3-3"/></svg>
                  </button>
                  <button v-if="podeEditar" type="button" class="btn-icon btn-delete" title="Excluir" @click="excluirRegistro(item)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </PageTableCard>

      <div v-if="registroDetalhe" class="modal-overlay" @click.self="fecharDetalhes">
        <div class="modal-detalhes" role="dialog" aria-modal="true" aria-labelledby="detalhe-jornada-titulo">
          <div class="modal-detalhes-header">
            <h2 id="detalhe-jornada-titulo">Detalhes da Jornada Pedagógica</h2>
            <button type="button" class="btn-fechar-x" title="Fechar" @click="fecharDetalhes">×</button>
          </div>
          <div class="detalhe-grid detalhe-grid-2">
            <div class="detalhe-campo detalhe-campo-full">
              <span class="detalhe-label">Título</span>
              <span class="detalhe-valor">{{ registroDetalhe.titulo }}</span>
            </div>
            <div class="detalhe-campo">
              <span class="detalhe-label">Status</span>
              <span class="detalhe-valor">{{ registroDetalhe.status }}</span>
            </div>
            <div class="detalhe-campo">
              <span class="detalhe-label">Período</span>
              <span class="detalhe-valor">{{ textoPeriodo(registroDetalhe) }}</span>
            </div>
            <div class="detalhe-campo">
              <span class="detalhe-label">Pré-jornada</span>
              <span class="detalhe-valor">{{ registroDetalhe.tem_pre_jornada }} {{ registroDetalhe.data_pre_jornada ? `— ${formatarData(registroDetalhe.data_pre_jornada)}` : '' }}</span>
            </div>
            <div class="detalhe-campo">
              <span class="detalhe-label">Local</span>
              <span class="detalhe-valor">{{ registroDetalhe.local || '—' }}</span>
            </div>
            <div class="detalhe-campo">
              <span class="detalhe-label">Espaço</span>
              <span class="detalhe-valor">{{ registroDetalhe.espaco || '—' }}</span>
            </div>
            <div class="detalhe-campo">
              <span class="detalhe-label">Verba</span>
              <span class="detalhe-valor">{{ registroDetalhe.verba || '—' }}</span>
            </div>
            <div class="detalhe-campo">
              <span class="detalhe-label">Setores</span>
              <span class="detalhe-valor">{{ registroDetalhe.setores || '—' }}</span>
            </div>
            <div class="detalhe-campo detalhe-campo-full">
              <span class="detalhe-label">Custos</span>
              <span class="detalhe-valor detalhe-valor-texto">{{ registroDetalhe.custos || '—' }}</span>
            </div>
            <div class="detalhe-campo detalhe-campo-full">
              <span class="detalhe-label">Programação</span>
              <span class="detalhe-valor detalhe-valor-texto">{{ registroDetalhe.programacao || '—' }}</span>
            </div>
            <div class="detalhe-campo detalhe-campo-full">
              <span class="detalhe-label">Observações</span>
              <span class="detalhe-valor detalhe-valor-texto">{{ registroDetalhe.observacoes || '—' }}</span>
            </div>
            <div v-if="registroDetalhe.anexo_url" class="detalhe-campo detalhe-campo-full">
              <span class="detalhe-label">Anexo</span>
              <span class="detalhe-valor">
                <a :href="registroDetalhe.anexo_url" target="_blank" rel="noopener noreferrer">Abrir anexo</a>
              </span>
            </div>
          </div>
          <div class="modal-detalhes-actions">
            <button v-if="podeEditar" type="button" class="btn-editar-modal" @click="abrirEdicao(registroDetalhe)">Editar</button>
            <button type="button" class="btn-salvar" @click="baixarPdf(registroDetalhe)">Gerar PDF</button>
            <button type="button" class="btn-secondary" @click="fecharDetalhes">Fechar</button>
          </div>
        </div>
      </div>
    </template>

    <template v-else>
      <CrudFormShell
        :title="modo === 'novo' ? 'Cadastrar Jornada Pedagógica' : 'Editar Jornada Pedagógica'"
        :subtitle="modo === 'novo' ? 'Registre o plano documental da jornada.' : 'Atualize o plano, o status e os anexos.'"
        @voltar="voltarLista"
      >
        <form class="form-body" novalidate @submit.prevent="salvarRegistro">
          <div v-if="erroFormulario" class="alert alert-error">{{ erroFormulario }}</div>

          <section class="form-section">
            <h2>Dados do plano</h2>
            <div class="form-grid">
              <div class="form-group full">
                <label for="jornada-titulo">Título <span>*</span></label>
                <input id="jornada-titulo" v-model="form.titulo" type="text" maxlength="255" required />
              </div>
              <div class="form-group">
                <label for="jornada-status">Status <span>*</span></label>
                <select id="jornada-status" v-model="form.status" required>
                  <option v-for="status in meta.status" :key="status" :value="status">{{ status }}</option>
                </select>
              </div>
              <div class="form-group">
                <label for="jornada-inicio">Data de início</label>
                <input id="jornada-inicio" v-model="form.data_inicio" type="date" />
              </div>
              <div class="form-group">
                <label for="jornada-fim">Data de término</label>
                <input id="jornada-fim" v-model="form.data_fim" type="date" />
              </div>
              <div class="form-group">
                <label for="jornada-pre">Há pré-jornada?</label>
                <select id="jornada-pre" v-model="form.tem_pre_jornada">
                  <option v-for="opcao in meta.sim_nao" :key="opcao" :value="opcao">{{ opcao }}</option>
                </select>
              </div>
              <div class="form-group">
                <label for="jornada-pre-data">Data da pré-jornada</label>
                <input id="jornada-pre-data" v-model="form.data_pre_jornada" type="date" :disabled="form.tem_pre_jornada !== 'Sim'" />
              </div>
              <div class="form-group">
                <label for="jornada-local">Local</label>
                <input id="jornada-local" v-model="form.local" type="text" maxlength="255" />
              </div>
              <div class="form-group">
                <label for="jornada-espaco">Espaço</label>
                <input id="jornada-espaco" v-model="form.espaco" type="text" maxlength="255" />
              </div>
              <div class="form-group">
                <label for="jornada-verba">Verba</label>
                <input id="jornada-verba" v-model="form.verba" type="text" maxlength="100" placeholder="Ex.: R$ 12.000,00" />
              </div>
              <div class="form-group">
                <label for="jornada-setores">Setores</label>
                <input id="jornada-setores" v-model="form.setores" type="text" maxlength="255" placeholder="Ex.: CPED, Coordenação" />
              </div>
              <div class="form-group full">
                <label for="jornada-custos">Custos</label>
                <textarea id="jornada-custos" v-model="form.custos" rows="3" />
              </div>
              <div class="form-group full">
                <label for="jornada-programacao">Programação</label>
                <textarea id="jornada-programacao" v-model="form.programacao" rows="5" />
              </div>
              <div class="form-group full">
                <label for="jornada-obs">Observações</label>
                <textarea id="jornada-obs" v-model="form.observacoes" rows="3" />
              </div>
              <div class="form-group full">
                <label for="jornada-anexo">Anexo</label>
                <input id="jornada-anexo" type="file" accept=".pdf,.doc,.docx,.odt,.jpg,.jpeg,.png" @change="aoEscolherAnexo" />
                <small v-if="form.anexo_url && !form.anexoFile" class="campo-ajuda">
                  Anexo atual:
                  <a :href="form.anexo_url" target="_blank" rel="noopener noreferrer">abrir arquivo</a>
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

<script src="../scripts/JornadaPedagogica.js"></script>
<style scoped src="../../css/JornadaPedagogica.css"></style>
