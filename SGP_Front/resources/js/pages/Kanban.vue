<template>
  <div class="kanban-page">
    <header class="kanban-top">
      <div class="kanban-top-row">
        <div>
          <router-link to="/app/ferramentas/kanban" class="kanban-back">
            ← Voltar aos quadros
          </router-link>
          <h1>{{ quadro?.nome || 'Kanban' }}</h1>
          <p class="kanban-subtitle">
            Colunas e cartões deste quadro
          </p>
        </div>

        <div class="kanban-top-actions">
          <span v-if="salvando" class="kanban-saving" aria-live="polite">Salvando...</span>
        </div>
      </div>

      <div v-if="!podeEditar && !acessoBloqueado" class="kanban-info">
        Modo consulta — você pode visualizar o quadro, mas não criar nem mover cartões.
      </div>
    </header>

    <div v-if="mensagemSucesso" class="alert alert-success">{{ mensagemSucesso }}</div>
    <div v-if="erro" class="alert alert-error">{{ erro }}</div>

    <div v-if="acessoBloqueado" class="alert alert-error">
      Você não possui autorização para consultar esta funcionalidade. Verifique seu perfil de acesso.
    </div>

    <section v-if="!acessoBloqueado" class="kanban-content" aria-label="Quadro Kanban">
      <div v-if="carregando" class="kanban-loading">Carregando quadro...</div>

      <div v-else class="kanban-board">
        <article
          v-for="coluna in colunas"
          :key="coluna.id"
          class="kanban-coluna"
          :aria-label="coluna.titulo"
        >
          <header class="kanban-coluna-head" :style="{ borderTopColor: coluna.cor || '#64748B' }">
            <div class="kanban-coluna-title">
              <span class="kanban-coluna-dot" :style="{ background: coluna.cor || '#64748B' }" />

              <template v-if="colunaEditandoId === coluna.id">
                <input
                  v-model="tituloColunaEditando"
                  class="kanban-coluna-input"
                  type="text"
                  maxlength="80"
                  @keydown.enter.prevent="salvarEdicaoColuna(coluna)"
                  @keydown.esc.prevent="cancelarEdicaoColuna"
                />
              </template>
              <h2 v-else>{{ coluna.titulo }}</h2>
            </div>

            <div class="kanban-coluna-meta">
              <span class="kanban-coluna-count">{{ coluna.cartoes.length }}</span>

              <div v-if="podeEditar" class="kanban-coluna-actions">
                <template v-if="colunaEditandoId === coluna.id">
                  <button type="button" class="kanban-icon-btn" title="Salvar nome" aria-label="Salvar nome" @click="salvarEdicaoColuna(coluna)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                  </button>
                  <button type="button" class="kanban-icon-btn" title="Cancelar" aria-label="Cancelar" @click="cancelarEdicaoColuna">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                  </button>
                </template>
                <template v-else>
                  <button type="button" class="kanban-icon-btn" title="Renomear coluna" aria-label="Renomear coluna" @click="iniciarEdicaoColuna(coluna)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                  </button>
                  <button type="button" class="kanban-icon-btn danger" title="Excluir coluna" aria-label="Excluir coluna" @click="excluirColuna(coluna)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                  </button>
                </template>
              </div>
            </div>
          </header>

          <draggable
            v-model="coluna.cartoes"
            class="kanban-coluna-lista"
            :group="{ name: 'kanban', pull: podeEditar, put: podeEditar }"
            item-key="id"
            :disabled="!podeEditar || salvando"
            ghost-class="kanban-cartao-ghost"
            drag-class="kanban-cartao-drag"
            @start="guardarSnapshot"
            @change="onCartaoMovido($event, coluna)"
          >
            <template #item="{ element: cartao }">
              <article
                class="kanban-cartao"
                :class="{ 'kanban-cartao-editable': podeEditar }"
                @click="abrirDetalhe(cartao)"
              >
                <h3>{{ cartao.titulo }}</h3>
                <p v-if="cartao.descricao">{{ cartao.descricao }}</p>

                <div v-if="podeEditar" class="kanban-cartao-actions" @click.stop>
                  <button type="button" class="kanban-icon-btn" title="Editar" aria-label="Editar" @click="abrirEdicao(cartao)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                  </button>
                  <button type="button" class="kanban-icon-btn danger" title="Excluir" aria-label="Excluir" @click="excluir(cartao)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                  </button>
                </div>
              </article>
            </template>
          </draggable>

          <p v-if="!coluna.cartoes.length" class="kanban-coluna-empty">
            Nenhum cartão nesta coluna
          </p>

          <button
            v-if="podeEditar"
            type="button"
            class="kanban-add-card"
            @click="abrirNovo(coluna)"
          >
            + Adicionar cartão
          </button>
        </article>

        <button
          v-if="podeEditar"
          type="button"
          class="kanban-add-coluna"
          @click="abrirNovaColuna"
        >
          + Nova coluna
        </button>

        <div v-if="!podeEditar && !colunas.length" class="kanban-vazia">
          Nenhuma coluna neste quadro.
        </div>
      </div>
    </section>

    <div v-if="modalAberto" class="modal-overlay" @click.self="fecharModal">
      <div class="modal-card" role="dialog" aria-modal="true" :aria-label="modalTitulo">
        <div class="modal-head">
          <div>
            <h2>{{ modalTitulo }}</h2>
            <p>{{ cartaoEmEdicao ? 'Atualize as informações do cartão.' : 'Preencha os dados do novo cartão.' }}</p>
          </div>
          <button type="button" class="modal-close" aria-label="Fechar" @click="fecharModal">×</button>
        </div>

        <form class="modal-form" @submit.prevent="salvar">
          <div v-if="erroFormulario" class="alert alert-error">{{ erroFormulario }}</div>

          <label>
            Título *
            <input v-model="form.titulo" type="text" required maxlength="150" autofocus />
          </label>

          <label>
            Descrição
            <textarea v-model="form.descricao" rows="4" maxlength="2000" placeholder="Detalhes opcionais da atividade..." />
          </label>

          <label v-if="!cartaoEmEdicao">
            Coluna *
            <input
              v-model="form.coluna_titulo"
              list="kanban-colunas-sugeridas"
              type="text"
              required
              maxlength="80"
              placeholder="Digite o nome da coluna"
              autocomplete="off"
            />
            <datalist id="kanban-colunas-sugeridas">
              <option v-for="coluna in colunas" :key="coluna.id" :value="coluna.titulo" />
            </datalist>
            <span class="field-hint">
              Se o nome ainda não existir, uma nova lista será criada no quadro.
            </span>
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

    <div v-if="modalColunaAberto" class="modal-overlay" @click.self="fecharModalColuna">
      <div class="modal-card modal-card-sm" role="dialog" aria-modal="true" aria-label="Nova coluna">
        <div class="modal-head">
          <div>
            <h2>Nova coluna</h2>
            <p>Crie uma lista vazia no quadro.</p>
          </div>
          <button type="button" class="modal-close" aria-label="Fechar" @click="fecharModalColuna">×</button>
        </div>

        <form class="modal-form" @submit.prevent="salvarNovaColuna">
          <div v-if="erroFormularioColuna" class="alert alert-error">{{ erroFormularioColuna }}</div>

          <label>
            Nome da coluna *
            <input
              v-model="formColuna.titulo"
              type="text"
              required
              maxlength="80"
              placeholder="Ex.: Em revisão, Bloqueado..."
              autofocus
            />
          </label>

          <div class="modal-actions">
            <button type="button" class="btn-sec" @click="fecharModalColuna">Cancelar</button>
            <button type="submit" class="btn-pri" :disabled="salvando">
              {{ salvando ? 'Salvando...' : 'Criar coluna' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script src="../scripts/Kanban.js"></script>
<style scoped src="../../css/Kanban.css"></style>
