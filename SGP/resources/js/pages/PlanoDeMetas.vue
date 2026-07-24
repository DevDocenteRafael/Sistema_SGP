<template>
  <div class="planos-meta-page">
    <header class="planos-meta-top">
      <div class="planos-meta-top-row">
        <div>
          <h1>Plano de Metas</h1>
          <p class="planos-meta-subtitle">Mapeamento de produção, produtividade e estratégias por ano</p>
        </div>

        <button v-if="podeEditar" type="button" class="btn-novo" @click="abrirModalNovo">
          <span class="btn-novo-icon">+</span>
          Novo Registro
        </button>
      </div>

      <div class="planos-meta-info">
        Ajuste filtros para visualizar registros de produção, infraestrutura e indicadores do portfólio.
      </div>
    </header>

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

    <section class="tabela-card" aria-label="Tabela de Plano de Metas">
      <div class="tabela-header">
        <span>
          {{ totalRegistros }} registro{{ totalRegistros !== 1 ? 's' : '' }}
          — {{ filtros.ano || 'todos os anos' }}
        </span>
      </div>

      <div v-if="carregando" class="tabela-loading">Carregando...</div>

      <div v-else-if="totalRegistros === 0 && !temFiltro" class="tabela-vazia estado-vazio">
        <p class="estado-vazio-titulo">Nenhum registro cadastrado ainda.</p>
        <p class="estado-vazio-texto">Os registros aparecerão aqui após o cadastro ou a importação.</p>
      </div>

      <div v-else class="tabela-wrap">
        <div v-if="mensagemSucesso" class="mensagem-sucesso">{{ mensagemSucesso }}</div>
        <div v-if="mensagemErro" class="mensagem-erro">{{ mensagemErro }}</div>

        <table class="planos-meta-table">
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
    </section>

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
          <div class="form-grid">
            <div class="campo">
              <span>Segmento</span>
              <div class="detalhe-valor-box">{{ registroDetalhe.segmento || '—' }}</div>
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
              <span>Mês de Entrega</span>
              <div class="detalhe-valor-box">{{ registroDetalhe.mesEntrega || '—' }}</div>
            </div>
            <div class="campo">
              <span>Número SEI</span>
              <div class="detalhe-valor-box">{{ registroDetalhe.sei || '—' }}</div>
            </div>
            <div class="campo">
              <span>Código SIG</span>
              <div class="detalhe-valor-box">{{ registroDetalhe.sig || '—' }}</div>
            </div>
            <div class="campo">
              <span>Status Final</span>
              <div class="detalhe-valor-box">{{ registroDetalhe.statusFinal || '—' }}</div>
            </div>
            <div class="campo">
              <span>Status</span>
              <div class="detalhe-valor-box">{{ registroDetalhe.status || '—' }}</div>
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
      <div class="modal-detalhes" role="dialog" aria-labelledby="novo-registro-titulo">
        <div class="modal-detalhes-header">
          <div>
            <h2 id="novo-registro-titulo">{{ modalModo === 'editar' ? 'Editar Registro' : 'Novo Registro' }}</h2>
            <p class="modal-detalhes-subtitle">Registre os dados do plano de metas no mesmo formato da planilha.</p>
            <p class="modal-detalhes-subtitle">Exemplo: infraestrutura · QUALIFICAÇÃO · Janeiro · SIG-001 · PUBLICADO.</p>
          </div>
          <button type="button" class="btn-fechar-x" title="Fechar" @click="fecharModalNovo">×</button>
        </div>

        <div class="modal-form-wrap">
          <div v-if="mensagemErro" class="mensagem-erro mensagem-modal">{{ mensagemErro }}</div>
          <div class="form-grid">
            <label class="campo">
              <span>Segmento</span>
              <input v-model="novoRegistroForm.segmento" type="text" placeholder="Ex.: Infraestrutura" />
            </label>

            <label class="campo">
              <span>Tipo</span>
              <input v-model="novoRegistroForm.tipo" type="text" placeholder="Ex.: QUALIFICAÇÃO" />
            </label>

            <label class="campo">
              <span>Mês de Entrega</span>
              <input v-model="novoRegistroForm.mes_entrega" type="text" placeholder="Ex.: Janeiro" />
            </label>

            <label class="campo">
              <span>Curso</span>
              <input v-model="novoRegistroForm.curso" type="text" placeholder="Nome do curso" />
            </label>

            <label class="campo">
              <span>Número SEI</span>
              <input v-model="novoRegistroForm.numero_sei" type="text" placeholder="Ex.: 1234567" />
            </label>

            <label class="campo">
              <span>Código SIG</span>
              <input v-model="novoRegistroForm.codigo_sig" type="text" placeholder="Ex.: SIG-001" />
            </label>

            <label class="campo">
              <span>Status do Registro</span>
              <input v-model="novoRegistroForm.status" type="text" placeholder="Ex.: EM ANÁLISE" />
            </label>

            <label class="campo">
              <span>Origem</span>
              <input v-model="novoRegistroForm.origem" type="text" placeholder="Ex.: Plano de Metas" />
            </label>

            <label class="campo">
              <span>Situação Final</span>
              <input v-model="novoRegistroForm.status_final" type="text" placeholder="Ex.: PUBLICADO" />
            </label>

            <label class="campo campo-full">
              <span>Observação / Justificativa</span>
              <textarea
                v-model="novoRegistroForm.observacao"
                rows="4"
                placeholder="Explique o motivo do item estar em análise, pendente, CPFD, CPED ou outra situação relevante..."
              />
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

<script src="../scripts/PlanoDeMetas.js"></script>
<style scoped src="../../css/PlanoDeMetas.css"></style>
