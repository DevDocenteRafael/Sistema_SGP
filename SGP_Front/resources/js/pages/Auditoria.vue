<template>
  <div class="auditoria-page crud-page">
    <CrudPageHeader
      title="Auditoria"
      subtitle="Histórico de cadastros, edições, exclusões e importações"
      info="Registro automático de quem alterou cada módulo do SGP. Disponível apenas para administradores."
      :show-clear-filters="temFiltro"
      filters-aria-label="Filtros de auditoria"
      @limpar-filtros="limparFiltros"
    >
      <template #filters>
        <section class="filtros-bar" aria-label="Filtros de auditoria">
          <div class="filtro-busca">
            <input
              v-model="filtros.busca"
              type="search"
              placeholder="Buscar no resumo, módulo ou ação..."
              aria-label="Buscar eventos de auditoria"
              @input="carregar(1)"
            />
          </div>
          <SearchableSelect
            v-model="filtros.modulo"
            :options="meta.modulos"
            empty-option="Todos os módulos"
            aria-label="Filtrar por módulo"
            @change="carregar(1)"
          />
          <SearchableSelect
            v-model="filtros.acao"
            :options="meta.acoes.map((acao) => ({ value: acao, label: labelAcao(acao) }))"
            empty-option="Todas as ações"
            aria-label="Filtrar por ação"
            @change="carregar(1)"
          />
          <input
            v-model="filtros.data_inicio"
            type="date"
            title="Data início"
            aria-label="Data início"
            @change="carregar(1)"
          />
          <input
            v-model="filtros.data_fim"
            type="date"
            title="Data fim"
            aria-label="Data fim"
            @change="carregar(1)"
          />
        </section>
      </template>
    </CrudPageHeader>

    <div v-if="mensagemErro" class="alert alert-error" role="alert">{{ mensagemErro }}</div>

    <PageTableCard :total="meta.total" aria-label="Tabela de auditoria">

      <div v-if="carregando" class="tabela-loading">Carregando...</div>

      <div v-else class="tabela-wrap">
        <table class="auditoria-table">
          <thead>
            <tr>
              <th>Quando</th>
              <th>Usuário</th>
              <th>Ação</th>
              <th>Módulo</th>
              <th>Resumo</th>
              <th class="text-center">Detalhes</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="registros.length === 0">
              <td colspan="6" class="tabela-vazia">
                Nenhum evento de auditoria encontrado para os filtros selecionados.
              </td>
            </tr>
            <tr v-for="item in registros" :key="item.id">
              <td>{{ formatarData(item.created_at) }}</td>
              <td>
                <div class="user-cell">
                  <div>
                    <p class="user-nome">{{ item.usuario?.nome ?? 'Sistema' }}</p>
                    <p class="user-email">{{ item.usuario?.email ?? '—' }}</p>
                  </div>
                </div>
              </td>
              <td>
                <span class="badge-acao" :class="`acao-${item.acao}`">{{ labelAcao(item.acao) }}</span>
              </td>
              <td>{{ item.modulo }}</td>
              <td class="resumo-cell">{{ item.resumo }}</td>
              <td class="text-center">
                <button type="button" class="btn-link" aria-label="Ver detalhes do evento" @click="abrirDetalhe(item)">Ver</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="meta.last_page > 1" class="paginacao" role="navigation" aria-label="Paginação da auditoria">
        <button type="button" :disabled="meta.current_page <= 1" aria-label="Página anterior" @click="paginaAnterior">Anterior</button>
        <span aria-live="polite">Página {{ meta.current_page }} de {{ meta.last_page }}</span>
        <button type="button" :disabled="meta.current_page >= meta.last_page" aria-label="Próxima página" @click="paginaProxima">Próxima</button>
      </div>
    </PageTableCard>

    <div v-if="detalhe" class="modal-overlay" @click.self="fecharDetalhe">
      <div class="modal-card">
        <header class="modal-header">
          <h2>Detalhe do evento</h2>
          <button type="button" class="btn-fechar" aria-label="Fechar detalhe" @click="fecharDetalhe">×</button>
        </header>
        <div class="modal-body">
          <dl class="detalhe-lista">
            <div>
              <dt>Quando</dt>
              <dd>{{ formatarData(detalhe.created_at) }}</dd>
            </div>
            <div>
              <dt>Usuário</dt>
              <dd>{{ detalhe.usuario?.nome ?? 'Sistema' }} ({{ detalhe.usuario?.email ?? '—' }})</dd>
            </div>
            <div>
              <dt>Ação</dt>
              <dd>{{ labelAcao(detalhe.acao) }}</dd>
            </div>
            <div>
              <dt>Módulo</dt>
              <dd>{{ detalhe.modulo }}</dd>
            </div>
            <div>
              <dt>Resumo</dt>
              <dd>{{ detalhe.resumo }}</dd>
            </div>
            <div>
              <dt>Registro</dt>
              <dd>
                <template v-if="detalhe.registro_id">
                  {{ detalhe.registro_tipo }} #{{ detalhe.registro_id }}
                </template>
                <template v-else>—</template>
              </dd>
            </div>
            <div>
              <dt>IP</dt>
              <dd>{{ detalhe.ip ?? '—' }}</dd>
            </div>
            <div v-if="detalhe.dados">
              <dt>Dados</dt>
              <dd>
                <pre>{{ JSON.stringify(detalhe.dados, null, 2) }}</pre>
              </dd>
            </div>
          </dl>
        </div>
      </div>
    </div>
  </div>
</template>

<script src="../scripts/Auditoria.js"></script>
<style scoped src="../../css/Auditoria.css"></style>
