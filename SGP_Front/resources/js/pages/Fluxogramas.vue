<template>
  <div class="flux-lista-page">
    <header class="flux-lista-top">
      <div class="flux-lista-top-row">
        <div>
          <router-link to="/app/ferramentas" class="flux-back">
            ← Voltar para Ferramentas
          </router-link>
          <h1>Fluxograma</h1>
          <p class="flux-subtitle">
            Escolha um processo ou crie um novo mapeamento visual
          </p>
        </div>
      </div>
    </header>

    <div v-if="mensagemSucesso" class="alert alert-success">{{ mensagemSucesso }}</div>
    <div v-if="erro" class="alert alert-error">{{ erro }}</div>

    <div v-if="acessoBloqueado" class="alert alert-error">
      Você não possui autorização para consultar esta funcionalidade. Verifique seu perfil de acesso.
    </div>

    <section v-if="!acessoBloqueado" class="flux-lista-content" aria-label="Lista de fluxogramas">
      <div v-if="carregando" class="flux-loading">Carregando fluxogramas...</div>

      <div v-else-if="!fluxogramas.length" class="flux-vazia">
        <p>Nenhum fluxograma ainda.</p>
        <button v-if="podeEditar" type="button" class="btn-novo" @click="abrirNovo">
          Criar primeiro fluxograma
        </button>
      </div>

      <div v-else class="flux-lista-grid">
        <article
          v-for="item in fluxogramas"
          :key="item.id"
          class="flux-card"
          role="link"
          tabindex="0"
          @click="abrirFluxograma(item)"
          @keydown.enter.prevent="abrirFluxograma(item)"
        >
          <div class="flux-card-accent" />
          <div class="flux-card-body">
            <h2>{{ item.titulo }}</h2>
            <p>
              {{ rotuloTipo(item.tipo) }}
              · {{ item.total_nos }} {{ item.total_nos === 1 ? 'etapa' : 'etapas' }}
            </p>
            <p v-if="item.descricao" class="flux-card-desc">{{ item.descricao }}</p>
          </div>

          <div v-if="podeEditar" class="flux-card-actions" @click.stop>
            <button type="button" class="flux-icon-btn" title="Editar dados" @click="abrirEdicao(item)">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
            </button>
            <button type="button" class="flux-icon-btn danger" title="Excluir" @click="excluir(item)">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
            </button>
          </div>
        </article>

        <button
          v-if="podeEditar"
          type="button"
          class="flux-card-add"
          @click="abrirNovo"
        >
          + Criar novo fluxograma
        </button>
      </div>
    </section>

    <div v-if="modalAberto" class="modal-overlay" @click.self="fecharModal">
      <div class="modal-card modal-card-sm" role="dialog" aria-modal="true" :aria-label="modalTitulo">
        <div class="modal-head">
          <div>
            <h2>{{ modalTitulo }}</h2>
            <p>
              {{ itemEmEdicao
                ? 'Altere os dados do fluxograma.'
                : (form.tipo === 'funcional'
                  ? 'Será criado com raias (setores) e o template Início → Processo → Fim.'
                  : 'Será criado com o template Início → Processo → Fim.') }}
            </p>
          </div>
          <button type="button" class="modal-close" aria-label="Fechar" @click="fecharModal">×</button>
        </div>

        <form class="modal-form" @submit.prevent="salvar">
          <div v-if="erroFormulario" class="alert alert-error">{{ erroFormulario }}</div>

          <label>
            Título *
            <input v-model="form.titulo" type="text" required maxlength="100" autofocus placeholder="Ex.: Admissão de aluno" />
          </label>

          <label>
            Tipo
            <SearchableSelect
              v-model="form.tipo"
              :options="[
                { value: 'linear', label: 'Linear' },
                { value: 'funcional', label: 'Funcional (com raias)' },
              ]"
            />
          </label>

          <label>
            Descrição
            <textarea v-model="form.descricao" rows="3" maxlength="2000" placeholder="Opcional: escopo ou objetivo do processo" />
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

<script src="../scripts/Fluxogramas.js"></script>
<style scoped src="../../css/Fluxogramas.css"></style>
