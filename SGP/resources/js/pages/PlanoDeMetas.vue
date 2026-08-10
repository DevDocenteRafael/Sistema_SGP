<template>
  <div class="crud-page" :class="{ 'crud-page-form': modo !== 'lista' }">
    <template v-if="modo === 'lista'">
      <CrudPageHeader
        title="Plano de Metas"
        subtitle="Mapeamento de produção, produtividade e estratégias por ano"
        info="Ajuste filtros para visualizar registros de produção, infraestrutura e indicadores do portfólio."
        :show-novo="podeEditar"
        novo-label="Novo Registro"
        @novo="abrirNovo"
      />

      <CrudAlerts
        :sucesso="mensagemSucesso"
        :erro="mensagemErro"
      />

      <section class="filtros-bar" aria-label="Filtros de Plano de Metas">
        <div class="filtro-busca">
          <input
            v-model="filtros.busca"
            type="search"
            placeholder="Buscar por curso, SEI, SIG, observação..."
            aria-label="Buscar registros"
            @input="carregarRegistros"
          />
        </div>

        <select v-model="filtros.ano" aria-label="Filtrar por ano" @change="carregarRegistros">
          <option value="">Todos os anos</option>
          <option v-for="ano in anosDisponiveis" :key="ano" :value="ano">{{ ano }}</option>
        </select>

        <select v-model="filtros.segmento" aria-label="Filtrar por segmento" @change="carregarRegistros">
          <option value="">Todos os segmentos</option>
          <option v-for="segmento in segmentosDisponiveis" :key="segmento" :value="segmento">
            {{ segmento }}
          </option>
        </select>

        <select v-model="filtros.tipo" aria-label="Filtrar por tipo" @change="carregarRegistros">
          <option value="">Todos os tipos</option>
          <option v-for="tipo in tiposDisponiveis" :key="tipo" :value="tipo">{{ tipo }}</option>
        </select>

        <select v-model="filtros.mes" aria-label="Filtrar por mês" @change="carregarRegistros">
          <option value="">Todos os meses</option>
          <option v-for="mes in mesesDisponiveis" :key="mes" :value="mes">{{ mes }}</option>
        </select>

        <select v-model="filtros.status" aria-label="Filtrar por status do registro" @change="carregarRegistros">
          <option value="">Todos os status</option>
          <option v-for="status in statusDisponiveis" :key="status" :value="status">{{ status }}</option>
        </select>

        <select v-model="filtros.situacao" aria-label="Filtrar por situação final" @change="carregarRegistros">
          <option value="">Todas as situações</option>
          <option v-for="situacao in situacoesDisponiveis" :key="situacao" :value="situacao">
            {{ situacao }}
          </option>
        </select>
      </section>

      <PageTableCard :total="totalRegistros" aria-label="Tabela de Plano de Metas">

        <div v-if="carregando" class="tabela-loading">Carregando...</div>

        <div v-else-if="totalRegistros === 0 && !temFiltro" class="tabela-vazia estado-vazio">
          <p class="estado-vazio-titulo">Nenhum registro cadastrado ainda.</p>
          <p class="estado-vazio-texto">Os registros aparecerão aqui após o cadastro ou a importação.</p>
        </div>

        <div v-else class="tabela-wrap">
          <table class="crud-table">
            <thead>
              <tr>
                <th>Segmento</th>
                <th>Curso</th>
                <th>Tipo</th>
                <th>Número SEI</th>
                <th>Código SIG</th>
                <th>Mês de Entrega</th>
                <th>Status do Registro</th>
                <th>Origem</th>
                <th>Observação</th>
                <th>Situação Final</th>
                <th class="text-center">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="totalRegistros === 0">
                <td colspan="11" class="tabela-vazia">
                  Nenhum registro encontrado para os filtros selecionados.
                </td>
              </tr>
              <tr v-for="registro in registros" :key="registro.id">
                <td>{{ registro.segmento || '—' }}</td>
                <td>{{ registro.curso || '—' }}</td>
                <td>{{ registro.tipo || '—' }}</td>
                <td>{{ registro.sei || '—' }}</td>
                <td>{{ registro.sig || '—' }}</td>
                <td>{{ registro.mesEntrega || '—' }}</td>
                <td>{{ registro.status || '—' }}</td>
                <td>{{ registro.origem || '—' }}</td>
                <td>{{ registro.observacao || '—' }}</td>
                <td>
                  <span class="badge" :class="statusClass(registro.statusFinal)">{{ registro.statusFinal || '—' }}</span>
                </td>
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
      </PageTableCard>

      <div v-if="detalheAberto" class="modal-overlay" @click.self="fecharDetalhes">
        <div class="modal-detalhes" role="dialog" aria-labelledby="detalhes-registro-titulo">
          <div class="modal-detalhes-header">
            <div>
              <h2 id="detalhes-registro-titulo">Detalhes do Registro</h2>
              <p class="modal-detalhes-subtitle">Informações resumidas do plano de metas selecionado.</p>
            </div>
            <button type="button" class="btn-fechar-x" title="Fechar" @click="fecharDetalhes">×</button>
          </div>

          <div v-if="registroDetalhe" class="modal-form-wrap">
            <div class="detalhe-form-grid">
              <div class="detalhe-form-campo">
                <span>Segmento</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.segmento || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Curso</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.curso || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Tipo</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.tipo || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Mês de Entrega</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.mesEntrega || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Número SEI</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.sei || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Código SIG</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.sig || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Status Final</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.statusFinal || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Status</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.status || '—' }}</div>
              </div>
              <div class="detalhe-form-campo campo-full">
                <span>Observação</span>
                <div class="detalhe-valor-box detalhe-valor-texto">{{ registroDetalhe.observacao || '—' }}</div>
              </div>
            </div>
          </div>

          <div class="modal-detalhes-actions">
            <button
              v-if="podeEditar && registroDetalhe"
              type="button"
              class="btn-editar-modal"
              @click="abrirEdicao(registroDetalhe)"
            >
              Editar
            </button>
            <button type="button" class="btn-secondary" @click="fecharDetalhes">Fechar</button>
          </div>
        </div>
      </div>
    </template>

    <template v-else>
      <CrudFormShell
        :title="modo === 'novo' ? 'Cadastrar Registro do Plano de Metas' : 'Editar Registro do Plano de Metas'"
        :subtitle="modo === 'novo' ? 'Preencha os dados no mesmo formato da planilha de plano de metas.' : 'Atualize as informações do registro selecionado.'"
        @voltar="voltarLista"
      >
        <form class="form-body" novalidate @submit.prevent="salvarRegistro">
          <div v-if="mensagemErro" class="mensagem-erro mensagem-form">{{ mensagemErro }}</div>

          <section class="form-section">
            <h2>Dados do registro</h2>
            <div class="form-grid form-grid-page">
              <label class="campo">
                <span>Segmento <em>*</em></span>
                <input v-model="form.segmento" type="text" placeholder="Ex.: Infraestrutura" />
              </label>

              <label class="campo">
                <span>Tipo <em>*</em></span>
                <input v-model="form.tipo" type="text" placeholder="Ex.: QUALIFICAÇÃO" />
              </label>

              <label class="campo">
                <span>Mês de Entrega <em>*</em></span>
                <select v-model="form.mes_entrega">
                  <option value="" disabled>Selecione...</option>
                  <option v-for="mes in mesesDisponiveis" :key="`form-${mes}`" :value="mes">{{ mes }}</option>
                </select>
              </label>

              <label class="campo">
                <span>Curso <em>*</em></span>
                <input v-model="form.curso" type="text" placeholder="Nome do curso" />
              </label>

              <label class="campo">
                <span>Número SEI <em>*</em></span>
                <input v-model="form.numero_sei" type="text" placeholder="Ex.: 1234567" />
              </label>

              <label class="campo">
                <span>Código SIG <em>*</em></span>
                <input v-model="form.codigo_sig" type="text" placeholder="Ex.: SIG-001" />
              </label>

              <label class="campo">
                <span>Status do Registro <em>*</em></span>
                <select v-model="form.status">
                  <option value="" disabled>Selecione...</option>
                  <option v-for="status in statusDisponiveis" :key="`st-${status}`" :value="status">{{ status }}</option>
                </select>
              </label>

              <label class="campo">
                <span>Origem</span>
                <input v-model="form.origem" type="text" placeholder="Ex.: Plano de Metas" />
              </label>

              <label class="campo">
                <span>Situação Final <em>*</em></span>
                <select v-model="form.status_final">
                  <option value="" disabled>Selecione...</option>
                  <option v-for="situacao in situacoesDisponiveis" :key="`sf-${situacao}`" :value="situacao">
                    {{ situacao }}
                  </option>
                </select>
              </label>

              <label class="campo campo-full">
                <span>Observação / Justificativa</span>
                <textarea
                  v-model="form.observacao"
                  rows="4"
                  placeholder="Explique o motivo do item estar em análise, pendente ou outra situação relevante..."
                />
              </label>
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

<script src="../scripts/PlanoDeMetas.js"></script>
<style scoped src="../../css/PlanoDeMetas.css"></style>
