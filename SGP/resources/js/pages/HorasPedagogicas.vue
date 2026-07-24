<template>
  <div class="horas-page">
    <template v-if="modo === 'lista'">
      <header class="horas-top">
        <div class="horas-top-row">
          <div>
            <h1>Horas Pedagógicas</h1>
            <p class="horas-subtitle">Gestão e acompanhamento das horas pedagógicas e processos vinculados ao portfólio institucional.</p>
          </div>

          <button v-if="podeEditarHoras" type="button" class="btn-novo" @click="abrirNovo">
            <span class="btn-novo-icon">+</span>
            Nova Hora
          </button>
        </div>

        <div class="horas-info">
          Acompanhe registros em andamento, concluídos e pendentes com foco em unidade, responsável, atividade e status.
        </div>
      </header>

      <div v-if="mensagemSucesso" class="alert alert-success">{{ mensagemSucesso }}</div>
      <div v-if="erro" class="alert alert-error">{{ erro }}</div>

      <div v-if="acessoBloqueado" class="alert alert-error">
        Você não possui autorização para consultar esta funcionalidade. Verifique seu perfil de acesso.
      </div>

      <section class="horas-summary-grid" aria-label="Resumo de horas pedagógicas">
        <article v-for="card in resumoCards" :key="card.label" class="horas-summary-card">
          <p class="horas-summary-label">{{ card.label }}</p>
          <p class="horas-summary-value">{{ card.value }}</p>
          <p class="horas-summary-help">{{ card.help }}</p>
        </article>
      </section>

      <section class="filtros-bar">
        <div class="filtro-busca">
          <input
            v-model.trim="filtroBusca"
            type="search"
            placeholder="Buscar identificação, responsável, unidade ou atividade..."
            aria-label="Buscar hora pedagógica"
          />
        </div>

        <select v-model="filtroStatus" aria-label="Filtrar por status">
          <option value="">Todos os status</option>
          <option value="Planejada">Planejada</option>
          <option value="Em Andamento">Em Andamento</option>
          <option value="Concluída">Concluída</option>
          <option value="Cancelada">Cancelada</option>
        </select>

        <button v-if="temFiltro" type="button" class="btn-limpar" @click="limparFiltros">
          Limpar
        </button>
      </section>

      <section class="tabela-card">
        <div class="tabela-header">
          <span>{{ horasFiltradas.length }} hora{{ horasFiltradas.length !== 1 ? 's' : '' }} pedagógica{{ horasFiltradas.length !== 1 ? 's' : '' }}</span>
        </div>

        <div v-if="carregando" class="tabela-loading">Carregando horas pedagógicas...</div>

        <div v-else-if="horasFiltradas.length === 0" class="tabela-vazia estado-vazio">
          <p class="estado-vazio-titulo">Nenhuma hora pedagógica encontrada.</p>
          <p class="estado-vazio-texto">Tente ajustar a busca ou limpar os filtros para localizar o registro desejado.</p>
        </div>

        <div v-else class="tabela-wrap">
          <table class="horas-table">
            <thead>
              <tr>
                <th>Identificação</th>
                <th>Unidade</th>
                <th>Responsável</th>
                <th>Atividade</th>
                <th>Período</th>
                <th>Status</th>
                <th class="text-center">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="hora in horasFiltradas" :key="hora.id">
                <td>
                  <strong class="hora-identificador">{{ hora.identificador }}</strong>
                </td>
                <td>{{ hora.unidade }}</td>
                <td>{{ hora.responsavel }}</td>
                <td>{{ hora.atividade }}</td>
                <td>{{ formatarData(hora.data) }}</td>
                <td>
                  <span class="badge-status" :class="statusClass(hora.status)">{{ hora.status }}</span>
                </td>
                <td class="text-center acoes">
                  <button type="button" class="btn-icon btn-view" title="Visualizar detalhes" @click="abrirDetalhes(hora)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                  <button v-if="podeEditarHoras" type="button" class="btn-icon btn-edit" title="Editar hora" @click="abrirEdicao(hora)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                  </button>
                  <button v-if="podeEditarHoras" type="button" class="btn-icon btn-delete" title="Cancelar hora" @click="cancelarHora(hora)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <div v-if="horaDetalhe" class="modal-overlay" @click.self="fecharDetalhes">
        <div class="modal-detalhes" role="dialog" aria-modal="true" aria-labelledby="detalhe-hora-titulo">
          <div class="modal-detalhes-header">
            <h2 id="detalhe-hora-titulo">Detalhes da Hora Pedagógica</h2>
            <button type="button" class="btn-fechar-x" title="Fechar" @click="fecharDetalhes">
              ×
            </button>
          </div>

          <div class="detalhe-grid">
            <div class="detalhe-campo">
              <span class="detalhe-label">Identificação</span>
              <span class="detalhe-valor">{{ horaDetalhe.identificador }}</span>
            </div>
            <div class="detalhe-campo">
              <span class="detalhe-label">Status</span>
              <span class="detalhe-valor">
                <span class="badge-status" :class="statusClass(horaDetalhe.status)">{{ horaDetalhe.status }}</span>
              </span>
            </div>
            <div class="detalhe-campo">
              <span class="detalhe-label">Responsável</span>
              <span class="detalhe-valor">{{ horaDetalhe.responsavel }}</span>
            </div>
            <div class="detalhe-campo">
              <span class="detalhe-label">Unidade</span>
              <span class="detalhe-valor">{{ horaDetalhe.unidade }}</span>
            </div>
            <div class="detalhe-campo">
              <span class="detalhe-label">Atividade</span>
              <span class="detalhe-valor">{{ horaDetalhe.atividade }}</span>
            </div>
            <div class="detalhe-campo">
              <span class="detalhe-label">Data</span>
              <span class="detalhe-valor">{{ formatarData(horaDetalhe.data) }}</span>
            </div>
            <div class="detalhe-campo detalhe-campo-full">
              <span class="detalhe-label">Objetivo</span>
              <span class="detalhe-valor detalhe-valor-texto">{{ horaDetalhe.objetivo }}</span>
            </div>
            <div class="detalhe-campo detalhe-campo-full">
              <span class="detalhe-label">Observações</span>
              <span class="detalhe-valor detalhe-valor-texto">{{ horaDetalhe.observacoes }}</span>
            </div>
          </div>

          <div class="modal-detalhes-actions">
            <button v-if="podeEditarHoras" type="button" class="btn-editar-modal" @click="abrirEdicao(horaDetalhe)">Editar Hora</button>
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
            <h1>{{ modo === 'novo' ? 'Cadastrar Nova Hora Pedagógica' : 'Editar Hora Pedagógica' }}</h1>
            <p>
              {{
                modo === 'novo'
                  ? 'Preencha os dados para registrar uma nova hora pedagógica no sistema.'
                  : 'Atualize as informações da hora pedagógica selecionada.'
              }}
            </p>
          </div>
        </header>

        <form class="form-body" @submit.prevent="salvarHora">
          <div v-if="erroFormulario" class="alert alert-error">{{ erroFormulario }}</div>

          <section class="form-section">
            <h2>Dados da Hora</h2>
            <div class="form-grid">
              <div class="form-group full">
                <label for="identificador">Identificador <span>*</span></label>
                <input id="identificador" v-model="form.identificador" type="text" maxlength="50" required placeholder="Ex: HP-2026-010" />
              </div>
              <div class="form-group">
                <label for="unidade">Unidade <span>*</span></label>
                <select id="unidade" v-model="form.unidade" required>
                  <option value="" disabled>Selecione a unidade</option>
                  <option v-for="unidade in unidades" :key="unidade" :value="unidade">{{ unidade }}</option>
                </select>
              </div>
              <div class="form-group">
                <label for="responsavel">Responsável <span>*</span></label>
                <input id="responsavel" v-model="form.responsavel" type="text" maxlength="100" required placeholder="Nome do responsável" />
              </div>
              <div class="form-group">
                <label for="atividade">Atividade <span>*</span></label>
                <input id="atividade" v-model="form.atividade" type="text" maxlength="120" required placeholder="Atividade pedagógica" />
              </div>
              <div class="form-group">
                <label for="data">Data <span>*</span></label>
                <input id="data" v-model="form.data" type="date" required />
              </div>
              <div class="form-group">
                <label for="status">Status <span>*</span></label>
                <select id="status" v-model="form.status" required>
                  <option value="" disabled>Selecione o status</option>
                  <option value="Planejada">Planejada</option>
                  <option value="Em Andamento">Em Andamento</option>
                  <option value="Concluída">Concluída</option>
                  <option value="Cancelada">Cancelada</option>
                </select>
              </div>
            </div>
          </section>

          <section class="form-section">
            <h2>Detalhes da Hora</h2>
            <div class="form-grid">
              <div class="form-group full">
                <label for="objetivo">Objetivo <span>*</span></label>
                <textarea id="objetivo" v-model="form.objetivo" rows="4" required placeholder="Descreva o objetivo da hora pedagógica"></textarea>
              </div>
              <div class="form-group full">
                <label for="observacoes">Observações</label>
                <textarea id="observacoes" v-model="form.observacoes" rows="4" placeholder="Informações adicionais, pendências ou observações relevantes"></textarea>
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
      </div>
    </template>
  </div>
</template>

<script src="../scripts/HorasPedagogicas.js"></script>
<style scoped src="../../css/HorasPedagogicas.css"></style>
