<template>
  <div class="cursos-page" :class="{ 'cursos-page-form': modo !== 'lista' }">
    <!-- LISTA -->
    <template v-if="modo === 'lista'">
      <CrudPageHeader
        title="Cursos"
        subtitle="Catálogo de cursos do portfólio — SENAC DF"
        info="Os cursos listados são só deste ciclo. Para abrir outro período, use Ciclos de Portfólio."
        :show-novo="podeEditar"
        novo-label="Novo Curso"
        @novo="abrirNovo"
      />

      <CicloContextoBanner modulo="cursos" :ciclo="cicloAberto" />

      <div v-if="mensagemSucesso" class="alert alert-success">{{ mensagemSucesso }}</div>
      <div v-if="mensagemErro" class="alert alert-error">{{ mensagemErro }}</div>

      <section class="filtros-bar">
        <div class="filtro-busca">
          <input
            v-model="filtros.busca"
            type="search"
            placeholder="Buscar por curso, SIG, SEI, eixo..."
            @input="carregarCursos"
          />
        </div>
        <SearchableSelect
          v-model="filtros.ciclo_id"
          :options="[{ value: 'todos', label: 'Todos os ciclos' }, ...ciclos.map((ciclo) => ({ value: String(ciclo.id), label: ciclo.nome + (ciclo.atual ? ' (atual)' : '') }))]"
          @change="onCicloFiltroChange"
        />
        <SearchableSelect
          v-model="filtros.ano"
          :options="anosDisponiveis"
          empty-option="Todos os anos"
          @change="carregarCursos"
        />
        <SearchableSelect
          v-model="filtros.eixo"
          :options="meta.eixos"
          empty-option="Todos os eixos"
          @change="carregarCursos"
        />
        <SearchableSelect
          v-model="filtros.status"
          :options="meta.status"
          empty-option="Todos os status"
          @change="carregarCursos"
        />
        <SearchableSelect
          v-model="filtros.tipo"
          :options="meta.tipos"
          empty-option="Todos os tipos"
          @change="carregarCursos"
        />
        <SearchableSelect
          v-model="filtros.unidade"
          :options="unidades"
          empty-option="Todas as unidades"
          @change="carregarCursos"
        />
      </section>

      <PageTableCard :total="totalCursos">

        <div v-if="carregando" class="tabela-loading">Carregando...</div>

        <div v-else-if="totalCursos === 0 && !temFiltro" class="tabela-vazia estado-vazio">
          <p class="estado-vazio-titulo">Nenhum curso cadastrado ainda.</p>
          <p class="estado-vazio-texto">Os cursos aparecerão aqui após o cadastro ou importação do portfólio.</p>
        </div>

        <div v-else class="tabela-wrap">
          <table class="cursos-table">
            <thead>
              <tr>
                <th>Curso</th>
                <th>Eixo</th>
                <th>CH</th>
                <th>SIG</th>
                <th>SEI</th>
                <th>Tipo</th>
                <th>Status</th>
                <th>Ano/Revisão</th>
                <th>Unidade</th>
                <th>Observação</th>
                <th class="text-center">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="totalCursos === 0">
                <td colspan="11" class="tabela-vazia">
                  Nenhum curso encontrado para os filtros selecionados.
                </td>
              </tr>
              <tr v-for="curso in cursos" :key="curso.id">
                <td class="col-curso">{{ curso.titulo }}</td>
                <td>{{ curso.eixo || '—' }}</td>
                <td>{{ curso.carga_horaria || '—' }}</td>
                <td>{{ curso.codigo_sig || '—' }}</td>
                <td class="col-sei">{{ curso.processo_sei || '—' }}</td>
                <td>{{ curso.tipo || '—' }}</td>
                <td>
                  <span class="badge" :class="badgeStatus(curso.status)">
                    {{ rotuloStatus(curso.status) }}
                  </span>
                </td>
                <td>{{ curso.ultima_revisao || '—' }}</td>
                <td>{{ textoUnidades(curso) }}</td>
                <td class="col-obs" :title="curso.observacoes || ''">
                  {{ curso.observacoes || '—' }}
                </td>
                <td class="text-center acoes">
                  <button
                    type="button"
                    class="btn-icon btn-view"
                    title="Ver detalhes"
                    @click="abrirDetalhes(curso)"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                  <button
                    v-if="podeEditar"
                    type="button"
                    class="btn-icon btn-edit"
                    title="Editar curso"
                    @click="abrirEdicao(curso)"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                  </button>
                  <button
                    v-if="podeEditar"
                    type="button"
                    class="btn-icon btn-delete"
                    title="Excluir curso"
                    @click="excluirCurso(curso)"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="tabela-footer">
          Exibindo {{ totalCursos }} de {{ totalCursos }} curso{{ totalCursos !== 1 ? 's' : '' }}.
        </div>
      </PageTableCard>

      <!-- Modal detalhes -->
      <div
        v-if="detalheAberto"
        class="modal-overlay"
        @click.self="fecharDetalhes"
      >
        <div class="modal-detalhes" role="dialog" aria-labelledby="detalhes-curso-titulo">
          <div class="modal-detalhes-header">
            <h2 id="detalhes-curso-titulo">Detalhes do Curso</h2>
            <button type="button" class="btn-fechar-x" title="Fechar" @click="fecharDetalhes">
              ×
            </button>
          </div>

          <div v-if="carregandoDetalhe" class="modal-detalhes-loading">
            Carregando detalhes...
          </div>

          <template v-else-if="cursoDetalhe">
            <div v-if="erroDetalhe" class="modal-detalhes-alerta">{{ erroDetalhe }}</div>

            <div class="detalhe-curso-topo">
              <span class="detalhe-curso-icone" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/></svg>
              </span>
              <div>
                <p class="detalhe-curso-nome">{{ cursoDetalhe.titulo }}</p>
                <p class="detalhe-curso-eixo">{{ valorCampo(cursoDetalhe.eixo) }}</p>
                <div class="detalhe-badges">
                  <span class="badge" :class="badgeStatus(cursoDetalhe.status)">
                    {{ rotuloStatus(cursoDetalhe.status) }}
                  </span>
                  <span v-if="cursoDetalhe.tipo" class="badge badge-tipo">{{ cursoDetalhe.tipo }}</span>
                </div>
              </div>
            </div>

            <div class="detalhe-secao">
              <h3>Informações principais</h3>
              <div class="detalhe-grid">
                <div class="detalhe-campo">
                  <span class="detalhe-label">Carga horária</span>
                  <span class="detalhe-valor">{{ valorCampo(cursoDetalhe.carga_horaria) }}</span>
                </div>
                <div class="detalhe-campo">
                  <span class="detalhe-label">Turmas</span>
                  <span class="detalhe-valor">{{ valorCampo(cursoDetalhe.turmas) }}</span>
                </div>
                <div class="detalhe-campo">
                  <span class="detalhe-label">Código do processo</span>
                  <span class="detalhe-valor">{{ valorCampo(cursoDetalhe.codigo_processo) }}</span>
                </div>
                <div class="detalhe-campo">
                  <span class="detalhe-label">Alunos</span>
                  <span class="detalhe-valor">{{ valorCampo(cursoDetalhe.alunos) }}</span>
                </div>
                <div class="detalhe-campo detalhe-campo-full">
                  <span class="detalhe-label">Instrutor(es)</span>
                  <span class="detalhe-valor">{{ valorCampo(cursoDetalhe.instrutor) }}</span>
                </div>
                <div class="detalhe-campo detalhe-campo-full">
                  <span class="detalhe-label">Unidades de oferta</span>
                  <span class="detalhe-valor">{{ textoUnidades(cursoDetalhe) }}</span>
                </div>
                <div class="detalhe-campo detalhe-campo-full">
                  <span class="detalhe-label">Descrição</span>
                  <span class="detalhe-valor detalhe-valor-texto">{{ valorCampo(cursoDetalhe.descricao) }}</span>
                </div>
              </div>
            </div>

            <div class="detalhe-secao">
              <h3>Informações básicas</h3>
              <div class="detalhe-grid">
                <div class="detalhe-campo">
                  <span class="detalhe-label">Modalidade</span>
                  <span class="detalhe-valor">{{ valorCampo(cursoDetalhe.modalidade) }}</span>
                </div>
                <div class="detalhe-campo">
                  <span class="detalhe-label">Tipo</span>
                  <span class="detalhe-valor">{{ valorCampo(cursoDetalhe.tipo) }}</span>
                </div>
                <div class="detalhe-campo">
                  <span class="detalhe-label">Ano / Revisão</span>
                  <span class="detalhe-valor">{{ valorCampo(cursoDetalhe.ultima_revisao) }}</span>
                </div>
                <div class="detalhe-campo">
                  <span class="detalhe-label">Data de início</span>
                  <span class="detalhe-valor">{{ formatarDataExibicao(cursoDetalhe.data_inicio) }}</span>
                </div>
                <div class="detalhe-campo">
                  <span class="detalhe-label">Data de término</span>
                  <span class="detalhe-valor">{{ formatarDataExibicao(cursoDetalhe.data_fim) }}</span>
                </div>
              </div>
            </div>

            <div class="detalhe-secao">
              <h3>Dados técnicos</h3>
              <div class="detalhe-grid">
                <div class="detalhe-campo">
                  <span class="detalhe-label">Cód. DN</span>
                  <span class="detalhe-valor">{{ valorCampo(cursoDetalhe.codigo_dn) }}</span>
                </div>
                <div class="detalhe-campo">
                  <span class="detalhe-label">Cód. SIG</span>
                  <span class="detalhe-valor">{{ valorCampo(cursoDetalhe.codigo_sig) }}</span>
                </div>
                <div class="detalhe-campo">
                  <span class="detalhe-label">Identificação</span>
                  <span class="detalhe-valor">{{ valorCampo(cursoDetalhe.identificacao) }}</span>
                </div>
                <div class="detalhe-campo detalhe-campo-full">
                  <span class="detalhe-label">Processo SEI</span>
                  <span class="detalhe-valor detalhe-valor-mono">{{ valorCampo(cursoDetalhe.processo_sei) }}</span>
                </div>
              </div>
            </div>

            <div class="detalhe-secao">
              <h3>Dados comerciais</h3>
              <div class="detalhe-grid">
                <div class="detalhe-campo">
                  <span class="detalhe-label">Valores</span>
                  <span class="detalhe-valor">{{ valorCampo(cursoDetalhe.valores) }}</span>
                </div>
                <div class="detalhe-campo">
                  <span class="detalhe-label">Compatível com bolsa</span>
                  <span class="detalhe-valor">{{ valorCampo(cursoDetalhe.compativel_bolsa) }}</span>
                </div>
                <div class="detalhe-campo">
                  <span class="detalhe-label">Comercial</span>
                  <span class="detalhe-valor">{{ valorCampo(cursoDetalhe.comercial) }}</span>
                </div>
                <div class="detalhe-campo">
                  <span class="detalhe-label">PCN</span>
                  <span class="detalhe-valor">{{ valorCampo(cursoDetalhe.pcn) }}</span>
                </div>
                <div class="detalhe-campo">
                  <span class="detalhe-label">PCR</span>
                  <span class="detalhe-valor">{{ valorCampo(cursoDetalhe.pcr) }}</span>
                </div>
                <div class="detalhe-campo detalhe-campo-full">
                  <span class="detalhe-label">Observações</span>
                  <span class="detalhe-valor detalhe-valor-texto">{{ valorCampo(cursoDetalhe.observacoes) }}</span>
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
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                Editar Curso
              </button>
              <button type="button" class="btn-secondary" @click="fecharDetalhes">
                Fechar
              </button>
            </div>
          </template>
        </div>
      </div>
    </template>

    <!-- FORMULÁRIO NOVO / EDITAR -->
    <template v-else>
      <div class="form-page">
        <div class="form-top-bar"></div>
        <header class="form-header">
          <button type="button" class="btn-voltar" @click="voltarLista">←</button>
          <div>
            <h1>{{ modo === 'novo' ? 'Cadastrar Novo Curso' : 'Editar Curso' }}</h1>
            <p>
              {{
                modo === 'novo'
                  ? 'Preencha as informações para adicionar um novo curso ao portfólio'
                  : 'Atualize os dados do curso selecionado'
              }}
            </p>
          </div>
          <span class="form-status-badge badge" :class="badgeStatus(form.status)">
            {{ form.status }}
          </span>
        </header>

        <div class="form-tabs">
          <button
            v-for="aba in abasForm"
            :key="aba.id"
            type="button"
            class="form-tab"
            :class="{ active: abaForm === aba.id }"
            @click="abaForm = aba.id"
          >
            {{ aba.label }}
          </button>
        </div>

        <form class="form-body" novalidate @submit.prevent="salvarCurso">
          <div v-if="erroFormulario" class="alert alert-error">{{ erroFormulario }}</div>

          <section v-show="abaForm === 'basico'" class="form-section">
            <div class="form-card">
              <h2>Informações principais</h2>
              <div class="form-grid">
                <div class="form-group full">
                  <label for="eixo">Segmento / Área <span>*</span></label>
                  <SearchableSelect
                    id="eixo"
                    input-id="eixo"
                    v-model="form.eixo"
                    :options="meta.eixos"
                    empty-option="Selecione o segmento..."
                  />
                </div>
                <div class="form-group full">
                  <label for="titulo">Título do curso <span>*</span></label>
                  <input
                    id="titulo"
                    v-model="form.titulo"
                    type="text"
                    placeholder="Ex: Técnico em Gastronomia"
                    maxlength="255"
                  />
                </div>
                <div class="form-group">
                  <label for="ciclo">Ciclo de portfólio</label>
                  <SearchableSelect
                    id="ciclo"
                    input-id="ciclo"
                    v-model="form.ciclo_id"
                    :options="ciclos.map((ciclo) => ({ value: String(ciclo.id), label: ciclo.nome + (ciclo.atual ? ' (atual)' : '') }))"
                  />
                </div>
                <div class="form-group">
                  <label for="carga_horaria">Carga horária (CH) <span>*</span></label>
                  <input
                    id="carga_horaria"
                    v-model="form.carga_horaria"
                    type="number"
                    placeholder="Ex: 800"
                    maxlength="50"
                  />
                </div>
                <div class="form-group">
                  <label for="turmas">Quantidade de turmas</label>
                  <input
                    id="turmas"
                    v-model="form.turmas"
                    type="number"
                    placeholder="Ex: 2"
                    maxlength="20"
                  />
                </div>
                <div class="form-group">
                  <label for="codigo_processo">Código do processo</label>
                  <input
                    id="codigo_processo"
                    v-model="form.codigo_processo"
                    type="text"
                    placeholder="Ex: 2025.12.85"
                    maxlength="100"
                  />
                </div>
                <div class="form-group">
                  <label for="alunos">Alunos (matrículas)</label>
                  <input
                    id="alunos"
                    v-model="form.alunos"
                    type="number"
                    placeholder="Ex: 22"
                    maxlength="20"
                  />
                </div>
                <div class="form-group full">
                  <label for="instrutor">Instrutor(es)</label>
                  <input
                    id="instrutor"
                    v-model="form.instrutor"
                    type="text"
                    placeholder="Nome do(s) instrutor(es)"
                    maxlength="255"
                  />
                </div>
              </div>
            </div>

            <div class="form-card">
              <h2>Unidades de oferta</h2>
              <p class="form-card-hint">Selecione as unidades onde o curso será oferecido</p>
              <div class="unidades-grid">
                <button
                  v-for="unidade in unidades"
                  :key="unidade"
                  type="button"
                  class="unidade-chip"
                  :class="{ selected: unidadeSelecionada(unidade) }"
                  @click="toggleUnidade(unidade)"
                >
                  <span>{{ unidade }}</span>
                  <span v-if="unidadeSelecionada(unidade)" class="unidade-chip-check">✓</span>
                </button>
              </div>
            </div>

            <div class="form-card">
              <h2>Descrição do curso</h2>
              <div class="form-group">
                <textarea
                  id="descricao"
                  v-model="form.descricao"
                  rows="5"
                  placeholder="Descreva os objetivos, conteúdo programático e público-alvo do curso..."
                />
              </div>
            </div>
          </section>

          <section v-show="abaForm === 'tecnico'" class="form-section">
            <div class="form-card">
              <h2>Dados técnicos e cadastrais</h2>
              <div class="form-grid">
                <div class="form-group">
                  <label for="status">Status <span>*</span></label>
                  <SearchableSelect
                    id="status"
                    input-id="status"
                    v-model="form.status"
                    :options="meta.status"
                  />
                </div>
                <div class="form-group">
                  <label for="modalidade">Modalidade <span>*</span></label>
                  <SearchableSelect
                    id="modalidade"
                    input-id="modalidade"
                    v-model="form.modalidade"
                    :options="meta.modalidades"
                    empty-option="Selecione..."
                  />
                </div>
                <div class="form-group">
                  <label for="codigo_dn">Cód. DN</label>
                  <input id="codigo_dn" v-model="form.codigo_dn" type="text" placeholder="Ex: 2437" maxlength="50" />
                </div>
                <div class="form-group">
                  <label for="codigo_sig">Cód. SIG <span>*</span></label>
                  <input
                    id="codigo_sig"
                    v-model="form.codigo_sig"
                    type="text"
                    placeholder="Ex: 129820"
                    maxlength="50"
                  />
                </div>
                <div class="form-group">
                  <label for="identificacao">Ident.</label>
                  <input
                    id="identificacao"
                    v-model="form.identificacao"
                    type="text"
                    placeholder="Ex: 2018"
                    maxlength="50"
                  />
                </div>
                <div class="form-group">
                  <label for="tipo">Tipo de curso <span>*</span></label>
                  <SearchableSelect
                    id="tipo"
                    input-id="tipo"
                    v-model="form.tipo"
                    :options="meta.tipos"
                    empty-option="Selecione..."
                  />
                </div>
                <div class="form-group">
                  <label for="ultima_revisao">Última revisão</label>
                  <input
                    id="ultima_revisao"
                    v-model="form.ultima_revisao"
                    type="text"
                    placeholder="Ex: 2025"
                    maxlength="50"
                  />
                </div>
                <div class="form-group">
                  <label for="processo_sei">Processo SEI</label>
                  <input
                    id="processo_sei"
                    v-model="form.processo_sei"
                    type="text"
                    placeholder="Ex: 2023.000001650-31"
                    maxlength="100"
                  />
                </div>
                <div class="form-group">
                  <label for="data_inicio">Data de início</label>
                  <input id="data_inicio" v-model="form.data_inicio" type="date" />
                </div>
                <div class="form-group">
                  <label for="data_fim">Data de término</label>
                  <input id="data_fim" v-model="form.data_fim" type="date" />
                </div>
              </div>
            </div>
          </section>

          <section v-show="abaForm === 'comercial'" class="form-section">
            <div class="form-card">
              <h2>Informações comerciais e financeiras</h2>
              <div class="form-grid">
                <div class="form-group full">
                  <label for="valores">Valores</label>
                  <input
                    id="valores"
                    v-model="form.valores"
                    type="text"
                    placeholder="Ex: 2025 | R$ 2.405,00"
                    maxlength="255"
                  />
                </div>
                <div class="form-group">
                  <label for="compativel_bolsa">Compatível com bolsa</label>
                  <SearchableSelect
                    id="compativel_bolsa"
                    input-id="compativel_bolsa"
                    v-model="form.compativel_bolsa"
                    :options="meta.sim_nao"
                    empty-option="Selecione..."
                  />
                </div>
                <div class="form-group">
                  <label for="comercial">Comercial</label>
                  <SearchableSelect
                    id="comercial"
                    input-id="comercial"
                    v-model="form.comercial"
                    :options="meta.sim_nao"
                    empty-option="Selecione..."
                  />
                </div>
                <div class="form-group">
                  <label for="pcn">PCN</label>
                  <input
                    id="pcn"
                    v-model="form.pcn"
                    type="text"
                    placeholder="Plano de Curso Nacional"
                    maxlength="255"
                  />
                </div>
                <div class="form-group">
                  <label for="pcr">PCR</label>
                  <input
                    id="pcr"
                    v-model="form.pcr"
                    type="text"
                    placeholder="Plano de Curso Regional"
                    maxlength="255"
                  />
                </div>
                <div class="form-group full">
                  <label for="observacoes">Observações</label>
                  <textarea
                    id="observacoes"
                    v-model="form.observacoes"
                    rows="4"
                    placeholder="Observações adicionais sobre valores, condições comerciais, etc..."
                  />
                </div>
              </div>
            </div>
          </section>

          <div class="form-actions">
            <button type="button" class="btn-secondary" @click="voltarLista">Cancelar</button>
            <button type="submit" class="btn-salvar" :disabled="salvando">
              {{ salvando ? 'Salvando...' : modo === 'novo' ? 'Cadastrar Curso' : 'Salvar Alterações' }}
            </button>
          </div>
        </form>
      </div>
    </template>

    <div v-if="duplicidadeAberta" class="modal-overlay" @click.self="cancelarDuplicidade">
      <div class="modal-detalhes" role="dialog" aria-labelledby="duplicidade-titulo">
        <div class="modal-detalhes-header">
          <h2 id="duplicidade-titulo">Curso semelhante encontrado</h2>
          <button type="button" class="btn-fechar-x" title="Fechar" @click="cancelarDuplicidade">×</button>
        </div>
        <p>Já existe curso com título, SIG ou SEI semelhante neste ciclo. Se precisar cadastrar mesmo assim, informe a justificativa.</p>
        <ul class="duplicidade-lista">
          <li v-for="item in duplicidadeSimilares" :key="item.id">
            <strong>{{ item.titulo }}</strong>
            <span>SIG: {{ item.codigo_sig || '—' }} · SEI: {{ item.processo_sei || '—' }}</span>
          </li>
        </ul>
        <div class="form-group">
          <label for="justificativa-duplicidade">Justificativa <span>*</span></label>
          <textarea
            id="justificativa-duplicidade"
            v-model="justificativaDuplicidade"
            rows="3"
            placeholder="Explique por que este cadastro precisa coexistir com o curso já existente."
          />
        </div>
        <div v-if="erroDuplicidade" class="alert alert-error">{{ erroDuplicidade }}</div>
        <div class="modal-detalhes-actions">
          <button type="button" class="btn-secondary" @click="cancelarDuplicidade">Cancelar</button>
          <button type="button" class="btn-salvar" :disabled="salvando" @click="confirmarDuplicidade">
            {{ salvando ? 'Salvando...' : 'Confirmar cadastro' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script src="../scripts/Cursos.js"></script>
<style scoped src="../../css/Cursos.css"></style>
