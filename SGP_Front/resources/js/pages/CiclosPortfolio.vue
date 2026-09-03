<template>
  <div class="crud-page" :class="{ 'crud-page-form': modo !== 'lista' }">
    <template v-if="modo === 'lista'">
      <CrudPageHeader
        title="Ciclos de Portfólio"
        subtitle="Cada ciclo é o portfólio de um período — SENAC DF"
        info="Clique no ciclo para abri-lo. Se você veio de Trocar ciclo, a escolha volta para a página de origem."
        :show-novo="podeEditar"
        novo-label="Novo ciclo"
        :show-clear-filters="temFiltro"
        @limpar-filtros="limparFiltros"
        @novo="abrirNovo"
      >
        <template #actions>
          <button
            v-if="podeEditar"
            type="button"
            class="btn-acao-secundaria"
            :disabled="registros.length === 0"
            @click="abrirGerar"
          >
            Gerar próximo portfólio
          </button>
        </template>
      
        <template #filters>
<section class="filtros-panel" aria-label="Filtros de ciclos de portfólio">
        <div class="filtros-row">
          <div class="filtro-busca">
            <span class="filtro-busca-icon" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </span>
            <input
              v-model="filtros.busca"
              type="search"
              placeholder="Buscar por nome ou observação..."
              aria-label="Buscar ciclo de portfólio"
              @input="aplicarFiltros"
            />
          </div>
        </div>
                </section>
        </template>
      </CrudPageHeader>

      <CrudAlerts :sucesso="mensagemSucesso" :erro="mensagemErro" />

      <div v-if="destinoTroca" class="ciclo-volta-banner" role="status">
        <div>
          <strong>Escolha o ciclo de {{ rotuloDestinoTroca }}</strong>
          <p>O clique no card volta para {{ rotuloDestinoTroca }} com esse ciclo. Os outros módulos não mudam.</p>
        </div>
      </div>

      <PageTableCard :total="totalRegistros" aria-label="Ciclos de portfólio">

        <div v-if="carregando" class="tabela-loading">Carregando...</div>

        <div v-else-if="totalRegistros === 0 && !temFiltro" class="tabela-vazia estado-vazio">
          <p class="estado-vazio-titulo">Nenhum ciclo cadastrado ainda.</p>
          <p class="estado-vazio-texto">Gere o próximo portfólio ou cadastre um ciclo para começar.</p>
        </div>

        <div v-else-if="totalRegistros === 0" class="tabela-vazia">
          Nenhum registro encontrado para os filtros selecionados.
        </div>

        <div v-else class="ciclos-grid">
          <article
            v-for="item in registros"
            :key="item.id"
            class="ciclo-card"
            :class="{ 'ciclo-card-atual': item.atual }"
            @click="escolherCiclo(item)"
          >
            <header class="ciclo-card-topo">
              <h2>{{ item.nome }}</h2>
              <span v-if="item.atual" class="badge-status badge-atual">Atual</span>
            </header>

            <p class="ciclo-card-origem">
              {{ item.origem_nome ? `Gerado a partir de ${item.origem_nome}` : 'Ciclo inicial' }}
            </p>
            <p v-if="item.observacao" class="ciclo-card-obs">{{ item.observacao }}</p>

            <ul class="ciclo-card-composicao">
              <li>{{ textoQuantidade(item.composicao?.cursos, 'curso', 'cursos') }}</li>
              <li>{{ textoQuantidade(item.composicao?.plano_de_metas, 'meta', 'metas') }}</li>
              <li>{{ textoQuantidade(item.composicao?.pca, 'PCA', 'PCAs') }}</li>
              <li>{{ textoQuantidade(item.composicao?.eixos, 'eixo', 'eixos') }}</li>
            </ul>

            <div class="ciclo-card-modulos" @click.stop>
              <button type="button" class="ciclo-card-abrir" @click.stop="abrirPortfolio(item)">
                Abrir portfólio
              </button>
              <button type="button" class="ciclo-card-link" @click.stop="abrirModulo(item, 'plano-de-metas')">Metas</button>
              <button type="button" class="ciclo-card-link" @click.stop="abrirModulo(item, 'pca')">PCA</button>
              <button type="button" class="ciclo-card-link" @click.stop="abrirModulo(item, 'eixos')">Eixos</button>
            </div>

            <footer class="ciclo-card-acoes" @click.stop>
              <button type="button" class="btn-icon btn-view" title="Detalhes" aria-label="Detalhes" @click="abrirDetalhes(item)">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
              <button v-if="podeEditar" type="button" class="btn-icon btn-edit" title="Editar" aria-label="Editar" @click="abrirEdicao(item)">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
              </button>
              <button
                v-if="podeEditar && !item.atual"
                type="button"
                class="btn-icon btn-view"
                title="Definir como atual"
                aria-label="Definir como atual"
                @click="marcarComoAtual(item)"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
              </button>
              <button v-if="podeEditar" type="button" class="btn-icon btn-delete" title="Excluir" aria-label="Excluir" @click="excluirRegistro(item)">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
              </button>
            </footer>
          </article>
        </div>
      </PageTableCard>

      <div v-if="registroDetalhe" class="modal-overlay" @click.self="fecharDetalhes">
        <div class="modal-detalhes" role="dialog" aria-modal="true" aria-labelledby="detalhe-ciclo-titulo">
          <div class="modal-detalhes-header">
            <h2 id="detalhe-ciclo-titulo">Detalhes do ciclo de portfólio</h2>
            <button type="button" class="btn-fechar-x" title="Fechar" aria-label="Fechar" @click="fecharDetalhes">×</button>
          </div>
          <div class="detalhe-grid detalhe-grid-2">
            <div class="detalhe-campo">
              <span class="detalhe-label">Ciclo</span>
              <span class="detalhe-valor">{{ registroDetalhe.nome }}</span>
            </div>
            <div class="detalhe-campo">
              <span class="detalhe-label">Situação</span>
              <span class="detalhe-valor">{{ registroDetalhe.atual ? 'Atual' : '—' }}</span>
            </div>
            <div class="detalhe-campo">
              <span class="detalhe-label">Anos</span>
              <span class="detalhe-valor">{{ (registroDetalhe.anos || []).join(', ') || '—' }}</span>
            </div>
            <div class="detalhe-campo">
              <span class="detalhe-label">Origem</span>
              <span class="detalhe-valor">{{ registroDetalhe.origem_nome || '—' }}</span>
            </div>
            <div class="detalhe-campo">
              <span class="detalhe-label">Cursos</span>
              <span class="detalhe-valor">{{ textoQuantidade(registroDetalhe.composicao?.cursos, 'curso', 'cursos') }}</span>
            </div>
            <div class="detalhe-campo">
              <span class="detalhe-label">Criado em</span>
              <span class="detalhe-valor">{{ registroDetalhe.created_at || '—' }}</span>
            </div>
            <div class="detalhe-campo detalhe-campo-full">
              <span class="detalhe-label">Observação</span>
              <span class="detalhe-valor">{{ registroDetalhe.observacao || '—' }}</span>
            </div>
          </div>
          <div class="modal-detalhes-actions">
            <button type="button" class="btn-secondary" @click="escolherCiclo(registroDetalhe)">Abrir ciclo</button>
            <button v-if="podeEditar && !registroDetalhe.atual" type="button" class="btn-editar-modal" @click="marcarComoAtual(registroDetalhe)">
              Definir como atual
            </button>
            <button v-if="podeEditar" type="button" class="btn-editar-modal" @click="abrirEdicao(registroDetalhe)">Editar</button>
            <button type="button" class="btn-salvar" @click="fecharDetalhes">Fechar</button>
          </div>
        </div>
      </div>
    </template>

    <template v-else>
      <CrudFormShell
        :title="tituloForm"
        :subtitle="subtituloForm"
        @voltar="voltarLista"
      >
        <form class="form-body" novalidate @submit.prevent="salvarRegistro">
          <div v-if="erroFormulario" class="alert alert-error">{{ erroFormulario }}</div>

          <section class="form-section">
            <h2>{{ modo === 'gerar' ? 'Novo ciclo a partir de outro' : 'Dados do ciclo' }}</h2>
            <div class="form-grid">
              <div v-if="modo === 'gerar'" class="form-group">
                <label for="ciclo-origem">Ciclo de origem <span>*</span></label>
                <SearchableSelect
                  id="ciclo-origem"
                  input-id="ciclo-origem"
                  v-model="form.origem_id"
                  :options="registros.map((ciclo) => ({ value: String(ciclo.id), label: ciclo.nome + (ciclo.atual ? ' (atual)' : '') }))"
                  :required="true"
                />
              </div>
              <div class="form-group" :class="{ full: modo !== 'gerar' }">
                <label for="ciclo-nome">Nome do ciclo <span>*</span></label>
                <input
                  id="ciclo-nome"
                  v-model="form.nome"
                  type="text"
                  maxlength="80"
                  required
                  placeholder="Ex.: 2028 ou 2028-2029"
                />
                <small class="campo-ajuda">Os anos no nome ligam Plano de Metas, PCA e Eixos deste ciclo.</small>
              </div>
              <div class="form-group full">
                <label for="ciclo-obs">Observação</label>
                <textarea id="ciclo-obs" v-model="form.observacao" rows="3" maxlength="2000" />
              </div>
              <div v-if="modo === 'gerar'" class="form-group full">
                <p class="campo-ajuda">A geração copia os cursos do ciclo de origem. Metas, PCA e Eixos não são duplicados — entram pelos anos do nome novo.</p>
                <label class="campo-check">
                  <input v-model="form.marcar_atual" type="checkbox" />
                  Definir o ciclo gerado como portfólio atual
                </label>
              </div>
              <div v-else class="form-group full">
                <label class="campo-check">
                  <input v-model="form.atual" type="checkbox" :disabled="modo === 'editar' && form.atual" />
                  Definir como ciclo atual
                </label>
              </div>
            </div>
          </section>

          <div class="form-actions">
            <button type="button" class="btn-secondary" @click="voltarLista">Cancelar</button>
            <button v-if="podeEditar" type="submit" class="btn-salvar" :disabled="salvando">
              {{ textoBotaoSalvar }}
            </button>
          </div>
        </form>
      </CrudFormShell>
    </template>
  </div>
</template>

<script src="../scripts/CiclosPortfolio.js"></script>
<style scoped src="../../css/CiclosPortfolio.css"></style>
