<template>
  <div class="notif-sino" ref="raiz">
    <button
      type="button"
      class="notif-sino-botao"
      :aria-expanded="aberto ? 'true' : 'false'"
      aria-haspopup="dialog"
      aria-controls="painel-notificacoes"
      :aria-label="rotuloBotao"
      @click="alternar"
    >
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" />
        <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" />
      </svg>
      <span v-if="naoLidas > 0" class="notif-sino-badge">{{ badgeTexto }}</span>
    </button>

    <div
      v-if="aberto"
      id="painel-notificacoes"
      class="notif-painel"
      role="dialog"
      aria-labelledby="notif-titulo"
    >
      <div class="notif-painel-cabecalho">
        <div>
          <h2 id="notif-titulo">Notificações</h2>
          <p>{{ subtitulo }}</p>
        </div>
        <button
          v-if="naoLidas > 0"
          type="button"
          class="notif-link"
          :disabled="salvando"
          @click="marcarTodas"
        >
          Marcar como lidas
        </button>
      </div>

      <div class="notif-ferramentas">
        <label class="notif-busca">
          <span class="sr-only">Buscar notificação</span>
          <input
            ref="busca"
            v-model="busca"
            type="search"
            class="notif-busca-input"
            placeholder="Buscar nome, SEI, número..."
            autocomplete="off"
            @input="agendarBusca"
            @keydown.enter.prevent
          >
        </label>

        <div class="notif-chips" role="group" aria-label="Filtrar por tipo">
          <button
            v-for="opcao in opcoesModulo"
            :key="opcao.valor || 'todos-modulos'"
            type="button"
            class="notif-chip"
            :class="{ 'is-active': moduloFiltro === opcao.valor }"
            :aria-pressed="moduloFiltro === opcao.valor ? 'true' : 'false'"
            @click="escolherModulo(opcao.valor)"
          >
            {{ opcao.rotulo }}
            <span v-if="contagemModulo(opcao.valor)" class="notif-chip-qtd">{{ contagemModulo(opcao.valor) }}</span>
          </button>
        </div>

        <div class="notif-chips" role="group" aria-label="Filtrar por urgência">
          <button
            v-for="opcao in opcoesNivel"
            :key="opcao.valor || 'todos-niveis'"
            type="button"
            class="notif-chip"
            :class="[`notif-chip--${opcao.valor || 'todos'}`, { 'is-active': nivelFiltro === opcao.valor }]"
            :aria-pressed="nivelFiltro === opcao.valor ? 'true' : 'false'"
            @click="escolherNivel(opcao.valor)"
          >
            {{ opcao.rotulo }}
            <span v-if="contagemNivel(opcao.valor)" class="notif-chip-qtd">{{ contagemNivel(opcao.valor) }}</span>
          </button>
        </div>

        <button
          v-if="temFiltro"
          type="button"
          class="notif-link notif-limpar"
          @click="limparFiltros"
        >
          Limpar busca e filtros
        </button>
      </div>

      <div class="notif-corpo">
        <p v-if="erro" class="notif-erro">{{ erro }}</p>
        <p v-else-if="carregando" class="notif-vazio">Carregando prazos...</p>
        <p v-else-if="!itens.length" class="notif-vazio">{{ mensagemVazio }}</p>

        <ul v-else class="notif-lista">
          <li v-for="item in itens" :key="item.chave">
            <button
              type="button"
              class="notif-item"
              :class="[`notif-item--${item.nivel}`, { 'notif-item--lida': item.lida }]"
              @click="abrirItem(item)"
            >
              <span class="notif-item-nivel">{{ rotuloNivel(item.nivel) }}</span>
              <strong>{{ item.titulo }}</strong>
              <span class="notif-item-msg">{{ item.mensagem }}</span>
              <span class="notif-item-meta">{{ item.rotulo_modulo }}{{ item.detalhe ? ` · ${item.detalhe}` : '' }}</span>
            </button>
          </li>
        </ul>

        <div v-if="!carregando && itens.length && temMais" class="notif-mais">
          <button
            type="button"
            class="notif-link"
            :disabled="carregandoMais"
            @click="carregarMais"
          >
            {{ carregandoMais ? 'Carregando...' : `Carregar mais (${restantes})` }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script src="../scripts/NotificacaoSino.js"></script>
<style scoped src="../../css/NotificacaoSino.css"></style>
