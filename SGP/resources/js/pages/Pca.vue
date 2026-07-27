<template>
  <div class="pca-page">
    <header class="pca-top">
      <div class="pca-top-row">
        <div>
          <h1>PCA</h1>
          <p class="pca-subtitle">Acompanhamento de planos de ação e controle pedagógico por ano.</p>
        </div>

        <button v-if="podeEditar" type="button" class="btn-novo" @click="abrirModalNovo">
          <span class="btn-novo-icon">+</span>
          Novo PCA
        </button>
      </div>

      <div class="pca-info">
        Use os filtros para encontrar os planos de ação por curso, unidade e status.
      </div>
    </header>

    <section class="filtros-bar" aria-label="Filtros de PCA">
      <div class="filtro-busca">
        <input
          v-model="filtros.busca"
          type="search"
          placeholder="Buscar por curso, unidade, SEI, SIG..."
          aria-label="Buscar registros de PCA"
        />
      </div>

      <label class="filtro-select">
        <span>Ano</span>
        <select v-model="filtros.ano" aria-label="Filtrar por ano">
          <option value="">Todos</option>
          <option v-for="ano in anosDisponiveis" :key="ano" :value="ano">{{ ano }}</option>
        </select>
      </label>

      <label class="filtro-select">
        <span>Tipo</span>
        <select v-model="filtros.tipo" aria-label="Filtrar por tipo">
          <option value="">Todos</option>
          <option v-for="tipo in tiposDisponiveis" :key="tipo" :value="tipo">{{ tipo }}</option>
        </select>
      </label>

      <label class="filtro-select">
        <span>Unidade</span>
        <select v-model="filtros.unidade" aria-label="Filtrar por unidade">
          <option value="">Todas</option>
          <option v-for="unidade in unidadesDisponiveis" :key="unidade" :value="unidade">{{ unidade }}</option>
        </select>
      </label>

      <label class="filtro-select">
        <span>Status</span>
        <select v-model="filtros.status" aria-label="Filtrar por status">
          <option value="">Todos</option>
          <option value="Planejado">Planejado</option>
          <option value="Em andamento">Em andamento</option>
          <option value="Concluído">Concluído</option>
        </select>
      </label>

      <button type="button" class="btn-filtrar" @click="aplicarFiltros">Filtrar</button>
      <button v-if="temFiltro" type="button" class="btn-limpar" @click="limparFiltros">Limpar</button>
    </section>

    <section class="tabela-card" aria-label="Tabela de PCA">
      <div class="tabela-header">
        <span>{{ totalRegistros }} registro{{ totalRegistros !== 1 ? 's' : '' }} encontrados</span>
      </div>

      <div v-if="carregando" class="tabela-loading">Carregando...</div>

      <div v-else class="tabela-wrap">
        <div v-if="mensagemSucesso" class="mensagem-sucesso">{{ mensagemSucesso }}</div>
        <div v-if="mensagemErro" class="mensagem-erro">{{ mensagemErro }}</div>

        <table class="pca-table">
          <thead>
            <tr>
              <th>Unidade</th>
              <th>Curso</th>
              <th>Tipo</th>
              <th>Período</th>
              <th>Número SEI</th>
              <th>Código SIG</th>
              <th>Status</th>
              <th>Observação</th>
              <th class="text-center">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="totalRegistros === 0">
              <td colspan="9" class="tabela-vazia">Nenhum registro encontrado.</td>
            </tr>
            <tr v-for="registro in registrosExibidos" :key="registro.id">
              <td>{{ registro.unidade || '—' }}</td>
              <td>{{ registro.curso || '—' }}</td>
              <td>{{ registro.tipo || '—' }}</td>
              <td>{{ registro.periodo || '—' }}</td>
              <td>{{ registro.sei || '—' }}</td>
              <td>{{ registro.sig || '—' }}</td>
              <td>
                <span class="badge" :class="badgeStatus(registro.status)">{{ registro.status || '—' }}</span>
              </td>
              <td>{{ registro.observacao || '—' }}</td>
              <td class="text-center acoes">
                <button type="button" class="btn-icon btn-view" title="Visualizar" @click="abrirDetalhes(registro)">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
                <button v-if="podeEditar" type="button" class="btn-icon btn-edit" title="Editar" @click="abrirEdicao(registro)">
                  <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                </button>
                <button v-if="podeEditar" type="button" class="btn-icon btn-delete" title="Excluir" @click="excluirRegistro(registro)">
                  <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <div v-if="detalheAberto" class="modal-overlay" @click.self="fecharDetalhes">
      <div class="modal-detalhes" role="dialog" aria-labelledby="detalhes-pca-titulo">
        <div class="modal-detalhes-header">
          <div>
            <h2 id="detalhes-pca-titulo">Detalhes do PCA</h2>
            <p class="modal-detalhes-subtitle">Veja as informações completas do plano cadastrado.</p>
          </div>
          <button type="button" class="btn-fechar-x" title="Fechar" @click="fecharDetalhes">×</button>
        </div>

        <div class="modal-form-wrap">
          <div class="form-grid">
            <div class="campo">
              <span>Unidade</span>
              <div class="detalhe-valor-box">{{ registroDetalhe.unidade || '—' }}</div>
            </div>
            <div class="campo">
              <span>Curso</span>
              <div class="detalhe-valor-box">{{ registroDetalhe.curso || '—' }}</div>
            </div>
            <div class="campo">
              <span>Tipo</span>
              <div class="detalhe-valor-box">{{ registroDetalhe.tipo || '—' }}</div>
            </div>
            <div class="campo">
              <span>Período</span>
              <div class="detalhe-valor-box">{{ registroDetalhe.periodo || '—' }}</div>
            </div>
            <div class="campo">
              <span>Número SEI</span>
              <div class="detalhe-valor-box">{{ registroDetalhe.sei || '—' }}</div>
            </div>
            <div class="campo">
              <span>Código SIG</span>
              <div class="detalhe-valor-box">{{ registroDetalhe.sig || '—' }}</div>
            </div>
            <div class="campo campo-full">
              <span>Observação</span>
              <div class="detalhe-valor-box detalhe-valor-texto">{{ registroDetalhe.observacao || '—' }}</div>
            </div>
          </div>
        </div>

        <div class="modal-detalhes-actions">
          <button type="button" class="btn-secondary" @click="fecharDetalhes">Fechar</button>
        </div>
      </div>
    </div>

    <div v-if="mostrarModalNovo" class="modal-overlay" @click.self="fecharModalNovo">
      <div class="modal-detalhes" role="dialog" aria-labelledby="novo-pca-titulo">
        <div class="modal-detalhes-header">
          <div>
            <h2 id="novo-pca-titulo">{{ modalModo === 'editar' ? 'Editar Registro PCA' : 'Novo Registro PCA' }}</h2>
            <p class="modal-detalhes-subtitle">Preencha os dados do plano de ação pedagógica no mesmo padrão do cadastro de cursos.</p>
          </div>
          <button type="button" class="btn-fechar-x" title="Fechar" @click="fecharModalNovo">×</button>
        </div>

        <div class="modal-form-wrap">
          <div v-if="mensagemErro" class="mensagem-erro mensagem-modal">{{ mensagemErro }}</div>
          <div class="form-grid">
            <label class="campo">
              <span>Ano <em>*</em></span>
              <select v-model="novoRegistroForm.ano" aria-label="Ano do PCA">
                <option value="" disabled>Selecione...</option>
                <option v-for="ano in anosDisponiveis" :key="ano" :value="ano">{{ ano }}</option>
              </select>
            </label>
            <label class="campo">
              <span>Status <em>*</em></span>
              <select v-model="novoRegistroForm.status" aria-label="Status do PCA">
                <option value="" disabled>Selecione...</option>
                <option value="Planejado">Planejado</option>
                <option value="Em andamento">Em andamento</option>
                <option value="Concluído">Concluído</option>
              </select>
            </label>
            <label class="campo">
              <span>Unidade <em>*</em></span>
              <input v-model="novoRegistroForm.unidade" type="text" placeholder="Ex.: DF" />
            </label>
            <label class="campo">
              <span>Curso <em>*</em></span>
              <input v-model="novoRegistroForm.curso" type="text" placeholder="Ex.: Educação Profissional" />
            </label>
            <label class="campo">
              <span>Tipo</span>
              <input v-model="novoRegistroForm.tipo" type="text" placeholder="Ex.: Presencial" />
            </label>
            <label class="campo">
              <span>Período</span>
              <input v-model="novoRegistroForm.periodo" type="text" placeholder="Ex.: 1º Semestre" />
            </label>
            <label class="campo">
              <span>Número SEI</span>
              <input v-model="novoRegistroForm.sei" type="text" placeholder="Ex.: 1234567" />
            </label>
            <label class="campo">
              <span>Código SIG</span>
              <input v-model="novoRegistroForm.sig" type="text" placeholder="Ex.: SIG-001" />
            </label>
            <label class="campo">
              <span>Responsável</span>
              <input v-model="novoRegistroForm.responsavel" type="text" placeholder="Ex.: Ana Paula" />
            </label>
            <label class="campo">
              <span>Data de início</span>
              <input v-model="novoRegistroForm.data_inicio" type="date" />
            </label>
            <label class="campo">
              <span>Data de término</span>
              <input v-model="novoRegistroForm.data_fim" type="date" />
            </label>
            <label class="campo campo-full">
              <span>Objetivo</span>
              <textarea v-model="novoRegistroForm.objetivo" rows="3" placeholder="Descreva o objetivo do plano de ação." />
            </label>
            <label class="campo campo-full">
              <span>Observação</span>
              <textarea v-model="novoRegistroForm.observacao" rows="4" placeholder="Descreva ações prioritárias, encaminhamentos ou observações relevantes." />
            </label>
          </div>
        </div>

        <div class="modal-detalhes-actions">
          <button type="button" class="btn-secondary" @click="fecharModalNovo">Cancelar</button>
          <button v-if="podeEditar" type="button" class="btn-editar-modal" :disabled="salvando" @click="salvarNovoRegistro">
            {{ salvando ? 'Salvando...' : 'Salvar' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script src="../scripts/Pca.js"></script>
<style scoped src="../../css/Pca.css"></style>
