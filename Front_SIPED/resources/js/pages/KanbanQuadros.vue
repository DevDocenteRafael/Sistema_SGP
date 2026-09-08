<template>
  <div class="kanban-quadros-page">
    <header class="kanban-quadros-top">
      <div class="kanban-quadros-top-row">
        <div>
          <router-link to="/app/ferramentas" class="kanban-back">
            ← Voltar para Ferramentas
          </router-link>
          <h1>Kanban</h1>
          <p class="kanban-subtitle">
            Escolha um quadro ou crie um novo para organizar as atividades
          </p>
        </div>
      </div>
    </header>

    <div v-if="mensagemSucesso" class="alert alert-success">{{ mensagemSucesso }}</div>
    <div v-if="erro" class="alert alert-error">{{ erro }}</div>

    <div v-if="acessoBloqueado" class="alert alert-error">
      Você não possui autorização para consultar esta funcionalidade. Verifique seu perfil de acesso.
    </div>

    <section v-if="!acessoBloqueado" class="kanban-quadros-content" aria-label="Lista de quadros">
      <div v-if="carregando" class="kanban-loading">Carregando quadros...</div>

      <div v-else-if="!quadros.length" class="kanban-vazia">
        <p>Nenhum quadro ainda.</p>
        <button v-if="podeEditar" type="button" class="btn-novo" @click="abrirNovo">
          Criar primeiro quadro
        </button>
      </div>

      <div v-else class="kanban-quadros-grid">
        <article
          v-for="quadro in quadros"
          :key="quadro.id"
          class="kanban-quadro-card"
          role="link"
          tabindex="0"
          @click="abrirQuadro(quadro)"
          @keydown.enter.prevent="abrirQuadro(quadro)"
        >
          <div class="kanban-quadro-card-accent" />
          <div class="kanban-quadro-card-body">
            <h2>{{ quadro.nome }}</h2>
            <p>
              {{ quadro.total_colunas }} coluna{{ quadro.total_colunas === 1 ? '' : 's' }}
              · {{ quadro.total_cartoes }} {{ quadro.total_cartoes === 1 ? 'cartão' : 'cartões' }}
            </p>
          </div>

          <div v-if="podeEditar" class="kanban-quadro-card-actions" @click.stop>
            <button type="button" class="kanban-icon-btn" title="Renomear" aria-label="Renomear" @click="abrirEdicao(quadro)">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
            </button>
            <button type="button" class="kanban-icon-btn danger" title="Excluir" aria-label="Excluir" @click="excluir(quadro)">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
            </button>
          </div>
        </article>

        <button
          v-if="podeEditar"
          type="button"
          class="kanban-quadro-add"
          @click="abrirNovo"
        >
          + Criar novo quadro
        </button>
      </div>
    </section>

    <div v-if="modalAberto" class="modal-overlay" @click.self="fecharModal">
      <div class="modal-card modal-card-sm" role="dialog" aria-modal="true" :aria-label="modalTitulo">
        <div class="modal-head">
          <div>
            <h2>{{ modalTitulo }}</h2>
            <p>{{ quadroEmEdicao ? 'Altere o nome do quadro.' : 'O quadro será criado com as colunas padrão.' }}</p>
          </div>
          <button type="button" class="modal-close" aria-label="Fechar" @click="fecharModal">×</button>
        </div>

        <form class="modal-form" @submit.prevent="salvar">
          <div v-if="erroFormulario" class="alert alert-error">{{ erroFormulario }}</div>

          <label>
            Nome do quadro *
            <input v-model="form.nome" type="text" required maxlength="100" autofocus placeholder="Ex.: Eventos 2026" />
          </label>

          <div class="modal-actions">
            <button type="button" class="btn-sec" @click="fecharModal">Cancelar</button>
            <button type="submit" class="btn-pri" :disabled="salvando">
              {{ salvando ? 'Salvando...' : 'Salvar' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script src="../scripts/KanbanQuadros.js"></script>
<style scoped src="../../css/KanbanQuadros.css"></style>
