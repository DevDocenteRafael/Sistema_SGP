<template>
  <div class="crud-page" :class="{ 'crud-page-form': modo !== 'lista' }">
    <template v-if="modo === 'lista'">
      <CrudPageHeader
        title="PCA"
        subtitle="Planejamento de cursos abertos por período (2025 e 2026) — visão de gestão"
        :show-novo="podeEditar"
        novo-label="Novo Registro"
        @novo="abrirNovo"
      />

      <CicloContextoBanner modulo="pca" :ciclo="cicloContexto" />

      <CrudAlerts
        :sucesso="mensagemSucesso"
        :erro="erro"
        :bloqueado="acessoBloqueado"
      />

      <section class="filtros-panel" aria-label="Filtros de PCA">
        <div class="filtros-row">
          <div class="filtro-busca">
            <span class="filtro-busca-icon" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </span>
            <input
              v-model="filtros.busca"
              type="search"
              placeholder="Buscar por título, SEI, SIG, eixo, unidade..."
              aria-label="Buscar registros de PCA"
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

          <div class="filtro-campo">
            <label for="filtro-semestre">Semestre</label>
            <select id="filtro-semestre" v-model="filtros.semestre" @change="aplicarFiltros">
              <option value="">Todos</option>
              <option v-for="semestre in semestres" :key="semestre" :value="semestre">{{ semestre }}</option>
            </select>
          </div>

          <div class="filtro-campo">
            <label for="filtro-unidade">Unidade</label>
            <select id="filtro-unidade" v-model="filtros.unidade" @change="aplicarFiltros">
              <option value="">Todos</option>
              <option v-for="unidade in unidades" :key="unidade" :value="unidade">{{ unidade }}</option>
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
        </div>
      </section>

      <PageTableCard :total="totalRegistros" aria-label="Tabela de PCA">

        <div v-if="carregando" class="tabela-loading">Carregando...</div>

        <div v-else-if="totalRegistros === 0 && !temFiltro" class="estado-vazio">
          <p class="estado-vazio-titulo">Nenhum curso PCA cadastrado ainda.</p>
          <p class="estado-vazio-texto">Os registros aparecerão aqui após o cadastro ou a importação.</p>
        </div>

        <div v-else class="tabela-wrap">
          <table class="crud-table">
            <thead>
              <tr>
                <th>Ano</th>
                <th>Semestre</th>
                <th>Titulo / Curso</th>
                <th>Eixo</th>
                <th>Unidade</th>
                <th>CH</th>
                <th>Status</th>
                <th>Observacao</th>
                <th class="text-center">Acoes</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="totalRegistros === 0">
                <td colspan="9" class="tabela-vazia">
                  Nenhum registro encontrado para os filtros selecionados.
                </td>
              </tr>
              <tr v-for="registro in registros" :key="registro.id">
                <td>{{ registro.ano || '—' }}</td>
                <td>{{ registro.semestre || '—' }}</td>
                <td class="col-titulo" :title="registro.titulo || ''">
                  <strong>{{ registro.titulo || '—' }}</strong>
                </td>
                <td>{{ registro.eixo || '—' }}</td>
                <td>{{ registro.unidade || '—' }}</td>
                <td>{{ registro.ch || '—' }}</td>
                <td>
                  <span class="badge-status" :class="badgeStatus(registro.status)" :title="registro.status || ''">
                    {{ registro.status || '—' }}
                  </span>
                </td>
                <td class="col-observacao" :title="registro.observacao || ''">
                  {{ registro.observacao || '—' }}
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

      <div v-if="registroDetalhe" class="modal-overlay" @click.self="fecharDetalhes">
        <div class="modal-detalhes" role="dialog" aria-modal="true" aria-labelledby="detalhe-pca-titulo">
          <div class="modal-detalhes-header">
            <div>
              <h2 id="detalhe-pca-titulo">Detalhes do Registro</h2>
              <p class="modal-detalhes-subtitle">Informações resumidas do PCA selecionado.</p>
            </div>
            <button type="button" class="btn-fechar-x" title="Fechar" @click="fecharDetalhes">×</button>
          </div>

          <div class="modal-form-wrap">
            <div class="detalhe-form-grid">
              <div class="detalhe-form-campo">
                <span>Ano</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.ano || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Semestre</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.semestre || '—' }}</div>
              </div>
              <div class="detalhe-form-campo campo-full">
                <span>Título / Curso</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.titulo || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>SEI</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.sei || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>SIG</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.sig || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Eixo</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.eixo || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Unidade</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.unidade || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>CH</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.ch || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Status</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.status || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Precificação</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.precificacao || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Valor 1º Módulo</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.valor_primeiro_modulo || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Valor Principal</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.valor || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Parcelas Boleto</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.parcelas_boleto || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Valor Parcela Boleto</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.valor_parcela_boleto || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Parcelas Cartão</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.parcelas_cartao || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Valor Cartão</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.valor_cartao || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Parcela com desc. 20%</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.parcela_desc_20 || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Parcela com desc. 15%</span>
                <div class="detalhe-valor-box">{{ registroDetalhe.parcela_desc_15 || '—' }}</div>
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
        :title="modo === 'novo' ? 'Cadastrar Registro PCA' : 'Editar Registro PCA'"
        :subtitle="modo === 'novo' ? 'Registre os dados do curso previsto no planejamento do período.' : 'Atualize as informações do curso previsto no planejamento.'"
        @voltar="voltarLista"
      >
        <form class="form-body" novalidate @submit.prevent="salvarRegistro">
          <div v-if="erroFormulario" class="alert alert-error">{{ erroFormulario }}</div>

          <section class="form-section">
            <h2>Dados do curso</h2>
            <div class="form-grid form-grid-3">
              <div class="form-group">
                <label for="ano">Ano</label>
                <select id="ano" v-model="form.ano">
                  <option value="">Selecione...</option>
                  <option v-for="ano in anos" :key="`form-ano-${ano}`" :value="ano">{{ ano }}</option>
                </select>
              </div>

              <div class="form-group">
                <label for="semestre">Semestre</label>
                <select id="semestre" v-model="form.semestre">
                  <option value="">Selecione...</option>
                  <option v-for="semestre in semestres" :key="`form-sem-${semestre}`" :value="semestre">
                    {{ semestre }}
                  </option>
                </select>
              </div>

              <div class="form-group">
                <label for="numero_sei">SEI</label>
                <input id="numero_sei" v-model="form.numero_sei" type="text" placeholder="Ex.: 0001234.567890/2026-01" />
              </div>

              <div class="form-group">
                <label for="codigo_sig">SIG</label>
                <input id="codigo_sig" v-model="form.codigo_sig" type="text" placeholder="Ex.: SIG-001" />
              </div>

              <div class="form-group full">
                <label for="titulo">Título / Curso <span>*</span></label>
                <input
                  id="titulo"
                  v-model="form.titulo"
                  type="text"
                  maxlength="255"
                  required
                  placeholder="Ex.: Técnico em Administração"
                />
              </div>

              <div class="form-group">
                <label for="eixo">Eixo</label>
                <select id="eixo" v-model="form.eixo">
                  <option value="">Selecione...</option>
                  <option v-for="eixo in eixos" :key="`form-eixo-${eixo}`" :value="eixo">{{ eixo }}</option>
                </select>
              </div>

              <div class="form-group">
                <label for="unidade">Unidade</label>
                <select id="unidade" v-model="form.unidade">
                  <option value="">Selecione...</option>
                  <option v-for="unidade in unidades" :key="`form-uni-${unidade}`" :value="unidade">
                    {{ unidade }}
                  </option>
                </select>
              </div>

              <div class="form-group">
                <label for="carga_horaria">CH</label>
                <input id="carga_horaria" v-model="form.carga_horaria" type="text" placeholder="Ex.: 1200" />
              </div>
            </div>
          </section>

          <section class="form-section">
            <h2>Precificação</h2>
            <div class="form-grid form-grid-3">
              <div class="form-group">
                <label for="precificacao">Precificação</label>
                <input id="precificacao" v-model="form.precificacao" type="text" placeholder="Ex.: R$ 4.800,00" />
              </div>
              <div class="form-group">
                <label for="valor_primeiro_modulo">Valor 1º Módulo</label>
                <input id="valor_primeiro_modulo" v-model="form.valor_primeiro_modulo" type="text" placeholder="Ex.: R$ 800,00" />
              </div>
              <div class="form-group">
                <label for="valor">Valor Principal</label>
                <input id="valor" v-model="form.valor" type="text" placeholder="Ex.: R$ 4.800,00" />
              </div>
              <div class="form-group">
                <label for="parcelas_boleto">Parcelas Boleto</label>
                <input id="parcelas_boleto" v-model="form.parcelas_boleto" type="text" placeholder="Ex.: 12" />
              </div>
              <div class="form-group">
                <label for="valor_parcela_boleto">Valor Parcela Boleto</label>
                <input id="valor_parcela_boleto" v-model="form.valor_parcela_boleto" type="text" placeholder="Ex.: R$ 400,00" />
              </div>
              <div class="form-group">
                <label for="parcelas_cartao">Parcelas Cartão</label>
                <input id="parcelas_cartao" v-model="form.parcelas_cartao" type="text" placeholder="Ex.: 10" />
              </div>
              <div class="form-group">
                <label for="valor_cartao">Valor Cartão</label>
                <input id="valor_cartao" v-model="form.valor_cartao" type="text" placeholder="Ex.: R$ 480,00" />
              </div>
              <div class="form-group">
                <label for="parcela_desc_20">Parcela com desc. 20%</label>
                <input id="parcela_desc_20" v-model="form.parcela_desc_20" type="text" placeholder="Ex.: R$ 320,00" />
              </div>
              <div class="form-group">
                <label for="parcela_desc_15">Parcela com desc. 15%</label>
                <input id="parcela_desc_15" v-model="form.parcela_desc_15" type="text" placeholder="Ex.: R$ 340,00" />
              </div>
            </div>
          </section>

          <section class="form-section">
            <h2>Status e observação</h2>
            <div class="form-grid">
              <div class="form-group">
                <label for="status">Status <span>*</span></label>
                <select id="status" v-model="form.status" required>
                  <option value="" disabled>Selecione...</option>
                  <option v-for="status in statusLista" :key="`form-st-${status}`" :value="status">{{ status }}</option>
                </select>
              </div>
              <div class="form-group full">
                <label for="observacao">Observação</label>
                <textarea
                  id="observacao"
                  v-model="form.observacao"
                  rows="4"
                  placeholder="Observações sobre precificação, status ou validação..."
                />
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

<script src="../scripts/Pca.js"></script>
<style scoped src="../../css/Pca.css"></style>
