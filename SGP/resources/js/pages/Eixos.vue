<template>
  <div class="crud-page" :class="{ 'crud-page-form': modo !== 'lista' }">
    <template v-if="modo === 'lista'">
      <CrudPageHeader
        title="Eixos"
        subtitle="Comparativo entre anos e distribuição por eixo tecnológico"
        :show-novo="podeEditar"
        novo-label="Novo Curso"
        @novo="abrirNovo"
      />

      <CrudAlerts
        :sucesso="mensagemSucesso"
        :erro="mensagemErro"
      />

      <section class="filtros-panel">
        <div class="filtros-row">
          <div class="filtro-busca">
            <span class="filtro-busca-icon" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </span>
            <input
              v-model="filtros.busca"
              type="search"
              placeholder="Buscar por curso, eixo, unidade, código..."
              aria-label="Buscar"
              @input="aplicarFiltros"
            />
          </div>

          <div class="filtro-campo">
            <label for="filtro-ano">Ano</label>
            <select id="filtro-ano" v-model="filtros.ano" @change="aplicarFiltros">
              <option value="">Todos</option>
              <option v-for="ano in anosDisponiveis" :key="ano" :value="ano">{{ ano }}</option>
            </select>
          </div>

          <div class="filtro-campo filtro-campo-eixo">
            <label for="filtro-eixo">Eixo</label>
            <select id="filtro-eixo" v-model="filtros.eixo" @change="aplicarFiltros">
              <option value="">Todos</option>
              <option v-for="eixo in eixosDisponiveis" :key="eixo" :value="eixo">{{ eixo }}</option>
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
        </div>
      </section>

      <section class="tabela-card">
        <div class="tabela-header">
          <span>
            {{ totalFiltrado }} curso{{ totalFiltrado !== 1 ? 's' : '' }}
            — {{ filtros.ano || 'todos os anos' }}
          </span>
          <span v-if="filtros.eixo" class="tabela-header-meta">{{ filtros.eixo }}</span>
        </div>

        <div v-if="carregando" class="tabela-vazia">Carregando...</div>

        <div v-else-if="totalGeral === 0 && !temFiltro" class="tabela-vazia estado-vazio">
          <p class="estado-vazio-titulo">Nenhum registro cadastrado ainda.</p>
          <p class="estado-vazio-texto">
            Os cursos por eixo aparecerão aqui após o cadastro ou a importação do portfólio.
          </p>
        </div>

        <div v-else-if="totalFiltrado === 0" class="tabela-vazia">
          Nenhum curso encontrado para os filtros selecionados.
        </div>

        <div v-else class="tabela-wrap">
          <table class="crud-table">
            <thead>
              <tr>
                <th>Nome do curso</th>
                <th>Eixo tecnológico</th>
                <th>Unidade</th>
                <th>Ano</th>
                <th>CH</th>
                <th>Turmas</th>
                <th>Código</th>
                <th>Alunos</th>
                <th>Instrutores</th>
                <th>Status</th>
                <th>Observação</th>
                <th class="text-center">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in registros" :key="item.id">
                <td class="col-curso">{{ item.curso }}</td>
                <td>
                  <span class="eixo-tag" :class="classeEixo(item.eixo)">{{ item.eixo }}</span>
                </td>
                <td>{{ valorCampo(item.unidade) }}</td>
                <td><span class="ano-chip">{{ item.ano }}</span></td>
                <td>{{ formatarCh(item.ch) }}</td>
                <td>{{ valorCampo(item.turmas) }}</td>
                <td class="col-codigo">{{ valorCampo(item.codigo) }}</td>
                <td>{{ valorCampo(item.alunos) }}</td>
                <td class="col-instrutor" :title="item.instrutores || ''">
                  {{ valorCampo(item.instrutores) }}
                </td>
                <td>
                  <span class="badge" :class="badgeStatus(item.status)">
                    {{ item.status }}
                  </span>
                </td>
                <td class="col-obs" :title="item.observacao || ''">
                  {{ valorCampo(item.observacao) }}
                </td>
                <td class="text-center acoes">
                  <button
                    type="button"
                    class="btn-icon btn-view"
                    title="Ver detalhes"
                    @click="abrirDetalhes(item)"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                  <button
                    v-if="podeEditar"
                    type="button"
                    class="btn-icon btn-edit"
                    title="Editar curso"
                    @click="abrirEdicao(item)"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                  </button>
                  <button
                    v-if="podeEditar"
                    type="button"
                    class="btn-icon btn-delete"
                    title="Excluir curso"
                    @click="excluirRegistro(item)"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <div
        v-if="detalheAberto && registroDetalhe"
        class="modal-overlay"
        @click.self="fecharDetalhes"
      >
        <div class="modal-detalhes" role="dialog" aria-modal="true" aria-labelledby="detalhe-eixo-titulo">
          <div class="modal-detalhes-header">
            <div>
              <h2 id="detalhe-eixo-titulo">Detalhes do Registro</h2>
              <p class="modal-detalhes-subtitle">Informações resumidas do curso por eixo selecionado.</p>
            </div>
            <button type="button" class="btn-fechar-x" title="Fechar" @click="fecharDetalhes">×</button>
          </div>

          <div class="modal-form-wrap">
            <div class="detalhe-form-grid">
              <div class="detalhe-form-campo campo-full">
                <span>Curso</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.curso || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Eixo</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.eixo || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Status</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.status || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Unidade</span>
                <div class="detalhe-valor-box">{{ valorCampo(registroDetalhe.unidade) }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Ano</span>
                <div class="detalhe-valor-box">{{ valorCampo(registroDetalhe.ano) }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Carga horária</span>
                <div class="detalhe-valor-box">{{ formatarCh(registroDetalhe.ch) }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Turmas</span>
                <div class="detalhe-valor-box">{{ valorCampo(registroDetalhe.turmas) }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Código</span>
                <div class="detalhe-valor-box">{{ valorCampo(registroDetalhe.codigo) }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Alunos</span>
                <div class="detalhe-valor-box">{{ valorCampo(registroDetalhe.alunos) }}</div>
              </div>
              <div class="detalhe-form-campo campo-full">
                <span>Instrutores</span>
                <div class="detalhe-valor-box">{{ valorCampo(registroDetalhe.instrutores) }}</div>
              </div>
              <div class="detalhe-form-campo campo-full">
                <span>Observação</span>
                <div class="detalhe-valor-box detalhe-valor-texto">{{ valorCampo(registroDetalhe.observacao) }}</div>
              </div>
            </div>
          </div>

          <div class="modal-detalhes-actions">
            <button
              v-if="podeEditar"
              type="button"
              class="btn-editar-modal"
              @click="editarDoDetalhe"
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
        :title="modo === 'novo' ? 'Cadastrar Curso por Eixo' : 'Editar Curso por Eixo'"
        :subtitle="modo === 'novo' ? 'Preencha os dados para adicionar um novo curso por eixo tecnológico.' : 'Atualize as informações do curso por eixo selecionado.'"
        @voltar="voltarLista"
      >
        <form class="form-body" novalidate @submit.prevent="salvarRegistro">
          <div v-if="erroFormulario" class="alert alert-error form-alert">{{ erroFormulario }}</div>

          <section class="form-section">
            <h2>Dados do curso</h2>
            <div class="form-grid">
              <div class="form-group full">
                <label for="form-curso">Nome do curso <span>*</span></label>
                <input id="form-curso" v-model="form.curso" type="text" maxlength="255" />
              </div>

              <div class="form-group">
                <label for="form-eixo">Eixo tecnológico <span>*</span></label>
                <select id="form-eixo" v-model="form.eixo">
                  <option value="" disabled>Selecione...</option>
                  <option v-for="eixo in eixosFormulario" :key="eixo" :value="eixo">{{ eixo }}</option>
                </select>
              </div>

              <div class="form-group">
                <label for="form-unidade">Unidade</label>
                <select id="form-unidade" v-model="form.unidade">
                  <option value="">Selecione...</option>
                  <option v-for="unidade in unidades" :key="unidade" :value="unidade">{{ unidade }}</option>
                </select>
              </div>

              <div class="form-group">
                <label for="form-ano">Ano <span>*</span></label>
                <select id="form-ano" v-model="form.ano">
                  <option v-for="ano in anosDisponiveis" :key="`form-${ano}`" :value="ano">{{ ano }}</option>
                </select>
              </div>

              <div class="form-group">
                <label for="form-status">Status <span>*</span></label>
                <select id="form-status" v-model="form.status">
                  <option v-for="status in statusLista" :key="`st-${status}`" :value="status">{{ status }}</option>
                </select>
              </div>

              <div class="form-group">
                <label for="form-ch">Carga horária (CH)</label>
                <input id="form-ch" v-model="form.ch" type="text" maxlength="50" placeholder="Ex: 160" />
              </div>

              <div class="form-group">
                <label for="form-turmas">Turmas</label>
                <input id="form-turmas" v-model="form.turmas" type="text" maxlength="20" placeholder="Ex: 2" />
              </div>

              <div class="form-group">
                <label for="form-codigo">Código</label>
                <input id="form-codigo" v-model="form.codigo" type="text" maxlength="100" placeholder="Ex: 2025.12.92" />
              </div>

              <div class="form-group">
                <label for="form-alunos">Alunos</label>
                <input id="form-alunos" v-model="form.alunos" type="text" maxlength="20" placeholder="Ex: 22" />
              </div>

              <div class="form-group full">
                <label for="form-instrutores">Instrutores</label>
                <input id="form-instrutores" v-model="form.instrutores" type="text" maxlength="255" />
              </div>

              <div class="form-group full">
                <label for="form-observacao">Observação</label>
                <textarea id="form-observacao" v-model="form.observacao" rows="3" />
              </div>
            </div>
          </section>

          <div class="form-actions">
            <button type="button" class="btn-secondary" @click="voltarLista">Cancelar</button>
            <button type="submit" class="btn-salvar" :disabled="salvando">
              {{ salvando ? 'Salvando...' : modo === 'editar' ? 'Salvar Alterações' : 'Cadastrar' }}
            </button>
          </div>
        </form>
      </CrudFormShell>
    </template>
  </div>
</template>

<script src="../scripts/Eixos.js"></script>
<style scoped src="../../css/Eixos.css"></style>
