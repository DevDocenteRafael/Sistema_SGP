<template>
  <div class="crud-page" :class="{ 'crud-page-form': modo !== 'lista' }">
    <template v-if="modo === 'lista'">
      <CrudPageHeader
        title="Horas Pedagógicas"
        subtitle="Controle de horas pedagógicas e processos SEI — SENAC DF"
        info="Consulte e filtre os registros de horas pedagógicas por SEI, eixo, pessoa, matrícula, ano e situação."
        :show-novo="podeEditarHoras"
        novo-label="Nova Hora"
        @novo="abrirNovo"
      />

      <CrudAlerts
        :sucesso="mensagemSucesso"
        :erro="erro"
        :bloqueado="acessoBloqueado"
      />

      <section class="filtros-panel" aria-label="Filtros de horas pedagógicas">
        <div class="filtros-row">
          <div class="filtro-busca">
            <span class="filtro-busca-icon" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </span>
            <input
              v-model="filtros.busca"
              type="search"
              placeholder="Buscar por SEI, eixo, pessoa, matrícula ou motivo..."
              aria-label="Buscar hora pedagógica"
              @input="aplicarFiltros"
            />
          </div>

          <div class="filtro-campo">
            <label for="filtro-ano-hora">Ano</label>
            <select id="filtro-ano-hora" v-model="filtros.ano" @change="aplicarFiltros">
              <option value="">Todos</option>
              <option v-for="ano in anos" :key="ano" :value="ano">{{ ano }}</option>
            </select>
          </div>

          <div class="filtro-campo filtro-campo-eixo">
            <label for="filtro-eixo-hora">Eixo Tecnológico</label>
            <select id="filtro-eixo-hora" v-model="filtros.eixo" @change="aplicarFiltros">
              <option value="">Todos</option>
              <option v-for="eixo in eixos" :key="eixo" :value="eixo">{{ eixo }}</option>
            </select>
          </div>

          <div class="filtro-campo">
            <label for="filtro-status-hora">Status</label>
            <select id="filtro-status-hora" v-model="filtros.status" @change="aplicarFiltros">
              <option value="">Todos</option>
              <option v-for="status in statusLista" :key="status" :value="status">{{ status }}</option>
            </select>
          </div>

          <div class="filtro-campo">
            <label for="filtro-situacao-hora">Situação</label>
            <select id="filtro-situacao-hora" v-model="filtros.ativo" @change="aplicarFiltros">
              <option value="">Todas</option>
              <option value="true">Ativos</option>
              <option value="false">Inativos</option>
            </select>
          </div>
        </div>
      </section>

      <PageTableCard :total="totalHoras" aria-label="Tabela de horas pedagógicas">

        <div v-if="carregando" class="tabela-loading">Carregando...</div>

        <div v-else-if="horas.length === 0" class="tabela-vazia estado-vazio">
          <p class="estado-vazio-titulo">Nenhuma hora pedagógica cadastrada ainda.</p>
          <p class="estado-vazio-texto">Os registros aparecerão aqui após o cadastro ou a importação.</p>
        </div>

        <div v-else class="tabela-wrap">
          <table class="crud-table">
            <thead>
              <tr>
                <th>Pessoa</th>
                <th>Matrícula</th>
                <th>Segmento</th>
                <th>Eixo</th>
                <th>Processo SEI</th>
                <th>Ano</th>
                <th>Motivo</th>
                <th>Status</th>
                <th>Ativo</th>
                <th class="text-center">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="totalHoras === 0">
                <td colspan="10" class="tabela-vazia">
                  Nenhum registro encontrado para os filtros selecionados.
                </td>
              </tr>
              <tr v-for="hora in horasFiltradas" :key="hora.id">
                <td>
                  <strong class="hora-pessoa">{{ hora.pessoa || '—' }}</strong>
                </td>
                <td>{{ hora.matricula || '—' }}</td>
                <td>{{ hora.segmento || '—' }}</td>
                <td>{{ hora.eixo || '—' }}</td>
                <td>{{ hora.processo_sei || '—' }}</td>
                <td>{{ hora.ano || '—' }}</td>
                <td class="col-motivo" :title="hora.motivo || ''">{{ hora.motivo || '—' }}</td>
                <td>
                  <span class="badge-status" :class="statusClass(hora.status)">{{ hora.status }}</span>
                </td>
                <td>
                  <span class="badge-ativo" :class="hora.ativo ? 'ativo-sim' : 'ativo-nao'">
                    {{ rotuloAtivo(hora.ativo) }}
                  </span>
                </td>
                <td class="text-center acoes">
                  <button type="button" class="btn-icon btn-view" title="Visualizar" @click="abrirDetalhes(hora)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                  <button v-if="podeEditarHoras" type="button" class="btn-icon btn-edit" title="Editar" @click="abrirEdicao(hora)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                  </button>
                  <button v-if="podeEditarHoras" type="button" class="btn-icon btn-delete" title="Excluir" @click="excluirHora(hora)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </PageTableCard>

      <div v-if="horaDetalhe" class="modal-overlay" @click.self="fecharDetalhes">
        <div class="modal-detalhes" role="dialog" aria-modal="true" aria-labelledby="detalhe-hora-titulo">
          <div class="modal-detalhes-header">
            <div>
              <h2 id="detalhe-hora-titulo">Detalhes do Registro</h2>
              <p class="modal-detalhes-subtitle">Informações resumidas da hora pedagógica selecionada.</p>
            </div>
            <button type="button" class="btn-fechar-x" title="Fechar" @click="fecharDetalhes">×</button>
          </div>

          <div class="modal-form-wrap">
            <div class="detalhe-form-grid">
              <div class="detalhe-form-campo">
                <span>Pessoa</span>
                <div class="detalhe-valor-box">{{ horaDetalhe.pessoa || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Matrícula</span>
                <div class="detalhe-valor-box">{{ horaDetalhe.matricula || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Segmento</span>
                <div class="detalhe-valor-box">{{ horaDetalhe.segmento || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Eixo</span>
                <div class="detalhe-valor-box">{{ horaDetalhe.eixo || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Processo SEI</span>
                <div class="detalhe-valor-box">{{ horaDetalhe.processo_sei || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Ano</span>
                <div class="detalhe-valor-box">{{ horaDetalhe.ano || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Status</span>
                <div class="detalhe-valor-box">{{ horaDetalhe.status || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Ativo</span>
                <div class="detalhe-valor-box">{{ rotuloAtivo(horaDetalhe.ativo) }}</div>
              </div>
              <div class="detalhe-form-campo campo-full">
                <span>Motivo</span>
                <div class="detalhe-valor-box detalhe-valor-texto">{{ horaDetalhe.motivo || '—' }}</div>
              </div>
              <div class="detalhe-form-campo campo-full">
                <span>Observação</span>
                <div class="detalhe-valor-box detalhe-valor-texto">{{ horaDetalhe.observacao || '—' }}</div>
              </div>
            </div>
          </div>

          <div class="modal-detalhes-actions">
            <button v-if="podeEditarHoras" type="button" class="btn-editar-modal" @click="abrirEdicao(horaDetalhe)">
              Editar
            </button>
            <button type="button" class="btn-secondary" @click="fecharDetalhes">Fechar</button>
          </div>
        </div>
      </div>
    </template>

    <template v-else>
      <CrudFormShell
        :title="modo === 'novo' ? 'Cadastrar Nova Hora Pedagógica' : 'Editar Hora Pedagógica'"
        :subtitle="modo === 'novo' ? 'Preencha os dados para registrar uma nova hora pedagógica.' : 'Atualize as informações da hora pedagógica selecionada.'"
        @voltar="voltarLista"
      >
        <form class="form-body" @submit.prevent="salvarHora">
          <div v-if="erroFormulario" class="alert alert-error">{{ erroFormulario }}</div>

          <section class="form-section">
            <h2>Dados da pessoa</h2>
            <div class="form-grid">
              <div class="form-group">
                <label for="pessoa">Pessoa <span>*</span></label>
                <input id="pessoa" v-model="form.pessoa" type="text" maxlength="100" required placeholder="Nome completo" />
              </div>
              <div class="form-group">
                <label for="matricula">Matrícula <span>*</span></label>
                <input id="matricula" v-model="form.matricula" type="text" maxlength="20" required placeholder="Ex: 2026001" />
              </div>
              <div class="form-group">
                <label for="segmento">Segmento <span>*</span></label>
                <select id="segmento" v-model="form.segmento" required>
                  <option value="" disabled>Selecione o segmento</option>
                  <option v-for="segmento in segmentos" :key="segmento" :value="segmento">{{ segmento }}</option>
                </select>
              </div>
              <div class="form-group">
                <label for="eixo">Eixo <span>*</span></label>
                <select id="eixo" v-model="form.eixo" required>
                  <option value="" disabled>Selecione o eixo</option>
                  <option v-for="eixo in eixos" :key="eixo" :value="eixo">{{ eixo }}</option>
                </select>
              </div>
            </div>
          </section>

          <section class="form-section">
            <h2>Processo</h2>
            <div class="form-grid">
              <div class="form-group">
                <label for="processo_sei">Processo SEI <span>*</span></label>
                <input
                  id="processo_sei"
                  v-model="form.processo_sei"
                  type="text"
                  maxlength="50"
                  required
                  placeholder="Ex: 00002.000111/2026-01"
                />
              </div>
              <div class="form-group">
                <label for="ano">Ano <span>*</span></label>
                <select id="ano" v-model="form.ano" required>
                  <option value="" disabled>Selecione o ano</option>
                  <option v-for="ano in anos" :key="ano" :value="ano">{{ ano }}</option>
                </select>
              </div>
              <div class="form-group">
                <label for="status">Status <span>*</span></label>
                <select id="status" v-model="form.status" required>
                  <option value="" disabled>Selecione o status</option>
                  <option v-for="status in statusLista" :key="status" :value="status">{{ status }}</option>
                </select>
              </div>
              <div class="form-group">
                <label for="ativo">Ativo</label>
                <select id="ativo" v-model="form.ativo">
                  <option value="true">Sim</option>
                  <option value="false">Não</option>
                </select>
              </div>
              <div class="form-group full">
                <label for="motivo">Motivo <span>*</span></label>
                <input
                  id="motivo"
                  v-model="form.motivo"
                  type="text"
                  maxlength="200"
                  required
                  placeholder="Motivo da solicitação de horas"
                />
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
              {{ salvando ? 'Salvando...' : modo === 'novo' ? 'Cadastrar Hora' : 'Salvar Alterações' }}
            </button>
          </div>
        </form>
      </CrudFormShell>
    </template>
  </div>
</template>

<script src="../scripts/HorasPedagogicas.js"></script>
<style scoped src="../../css/HorasPedagogicas.css"></style>
