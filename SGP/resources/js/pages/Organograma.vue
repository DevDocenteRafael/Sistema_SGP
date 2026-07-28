<template>
  <div class="org-page">
    <header class="org-top">
      <div class="org-top-inner">
        <div class="org-top-row">
          <div>
            <router-link to="/app/ferramentas" class="org-back">← Voltar para Ferramentas</router-link>
            <h1>Organograma</h1>
            <p class="org-subtitle">
              Visão hierárquica da CPED — consulta sincronizada com a equipe
            </p>
          </div>

          <router-link v-if="podeEditar" to="/app/cped" class="org-link-cped">
            Gerenciar equipe na CPED
          </router-link>
        </div>

        <div class="org-toolbar">
          <div class="org-busca">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input
              v-model="busca"
              type="search"
              placeholder="Buscar por nome, cargo ou eixo..."
              aria-label="Buscar no organograma"
            />
          </div>

          <div class="org-stats" aria-label="Resumo">
            <div class="org-stat">
              <strong>{{ meta.total || 0 }}</strong>
              <span>Pessoas</span>
            </div>
            <div class="org-stat">
              <strong>{{ meta.total_eixos || 0 }}</strong>
              <span>Eixos</span>
            </div>
            <div class="org-stat">
              <strong>{{ meta.total_instrutores || 0 }}</strong>
              <span>Instrutores</span>
            </div>
          </div>
        </div>
      </div>
    </header>

    <div v-if="erro" class="alert alert-error">{{ erro }}</div>

    <div v-if="acessoBloqueado" class="alert alert-error">
      Você não possui autorização para consultar esta funcionalidade.
    </div>

    <section v-if="!acessoBloqueado" class="org-content">
      <div v-if="carregando" class="org-loading">
        <span class="org-loading-dot" />
        Montando organograma...
      </div>

      <div v-else-if="!temDados" class="org-vazia">
        <p>Nenhum membro ativo encontrado.</p>
        <router-link v-if="podeEditar" to="/app/cped">Cadastrar na CPED</router-link>
      </div>

      <div v-else class="org-layout">
        <div class="org-tree-panel">
          <div v-if="ordenadorVisivel" class="org-nivel org-nivel-raiz">
            <button
              type="button"
              class="org-node org-node-raiz"
              :class="{ destaque: destaca(ordenador), selecionado: selecionado?.id === ordenador.id }"
              @click="selecionar(ordenador)"
            >
              <span class="org-avatar org-avatar-lg" :style="avatarStyle(ordenador)">
                <img v-if="ordenador.foto" :src="ordenador.foto" :alt="ordenador.nome" />
                <span v-else>{{ ordenador.iniciais || '?' }}</span>
              </span>
              <span class="org-node-info">
                <em>{{ labelTipo(ordenador.tipo) }}</em>
                <strong>{{ ordenador.nome }}</strong>
                <small>{{ ordenador.cargo }}</small>
              </span>
            </button>
            <div v-if="assistentesVisiveis.length || ramosVisiveis.length" class="org-stem" aria-hidden="true" />
          </div>

          <div v-if="assistentesVisiveis.length" class="org-nivel org-nivel-assistentes">
            <div class="org-branch-line" aria-hidden="true" />
            <p class="org-nivel-label">Assistência</p>
            <div class="org-row">
              <button
                v-for="pessoa in assistentesVisiveis"
                :key="pessoa.id"
                type="button"
                class="org-node org-node-assistente"
                :class="{ destaque: destaca(pessoa), selecionado: selecionado?.id === pessoa.id }"
                @click="selecionar(pessoa)"
              >
                <span class="org-avatar org-avatar-sm" :style="avatarStyle(pessoa)">
                  <img v-if="pessoa.foto" :src="pessoa.foto" :alt="pessoa.nome" />
                  <span v-else>{{ pessoa.iniciais || '?' }}</span>
                </span>
                <span class="org-node-info">
                  <strong>{{ pessoa.nome }}</strong>
                  <small>{{ pessoa.cargo }}</small>
                </span>
              </button>
            </div>
            <div v-if="ramosVisiveis.length" class="org-stem" aria-hidden="true" />
          </div>

          <div v-if="ramosVisiveis.length" class="org-nivel">
            <div class="org-branch-line" aria-hidden="true" />
            <p class="org-nivel-label">Eixos técnicos</p>
            <div class="org-ramos">
              <article
                v-for="ramo in ramosVisiveis"
                :key="ramo.eixo"
                class="org-ramo"
                :class="{ aberto: eixosAbertos.includes(ramo.eixo) }"
                :style="{ '--ramo-cor': ramo.cor }"
              >
                <button type="button" class="org-ramo-head" @click="toggleEixo(ramo.eixo)">
                  <span class="org-ramo-accent" aria-hidden="true" />
                  <span class="org-ramo-toggle" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                  </span>
                  <strong>{{ ramo.eixo }}</strong>
                  <span class="org-ramo-count">{{ ramo.total }}</span>
                </button>

                <div v-if="eixosAbertos.includes(ramo.eixo)" class="org-ramo-body">
                  <button
                    v-if="ramo.responsavel && destacaFiltro(ramo.responsavel, ramo.eixo)"
                    type="button"
                    class="org-node org-node-responsavel"
                    :class="{ destaque: destaca(ramo.responsavel), selecionado: selecionado?.id === ramo.responsavel.id }"
                    @click="selecionar(ramo.responsavel)"
                  >
                    <span class="org-avatar org-avatar-sm" :style="avatarStyle(ramo.responsavel)">
                      <img v-if="ramo.responsavel.foto" :src="ramo.responsavel.foto" :alt="ramo.responsavel.nome" />
                      <span v-else>{{ ramo.responsavel.iniciais || '?' }}</span>
                    </span>
                    <span class="org-node-info">
                      <em>Responsável</em>
                      <strong>{{ ramo.responsavel.nome }}</strong>
                      <small>{{ ramo.responsavel.cargo }}</small>
                    </span>
                  </button>

                  <div v-if="equipeFiltrada(ramo).length" class="org-equipe">
                    <p class="org-equipe-label">Instrutores</p>
                    <button
                      v-for="pessoa in equipeFiltrada(ramo)"
                      :key="pessoa.id"
                      type="button"
                      class="org-node org-node-equipe"
                      :class="{ destaque: destaca(pessoa), selecionado: selecionado?.id === pessoa.id }"
                      @click="selecionar(pessoa)"
                    >
                      <span class="org-avatar org-avatar-xs" :style="avatarStyle(pessoa)">
                        <img v-if="pessoa.foto" :src="pessoa.foto" :alt="pessoa.nome" />
                        <span v-else>{{ pessoa.iniciais || '?' }}</span>
                      </span>
                      <span class="org-node-info">
                        <strong>{{ pessoa.nome }}</strong>
                        <small>{{ pessoa.cargo }}</small>
                      </span>
                    </button>
                  </div>
                </div>
              </article>
            </div>
          </div>

          <div v-if="administrativosVisiveis.length" class="org-nivel org-nivel-apoio">
            <p class="org-nivel-label">Apoio administrativo</p>
            <div class="org-row org-row-wrap">
              <button
                v-for="pessoa in administrativosVisiveis"
                :key="pessoa.id"
                type="button"
                class="org-node org-node-admin"
                :class="{ destaque: destaca(pessoa), selecionado: selecionado?.id === pessoa.id }"
                @click="selecionar(pessoa)"
              >
                <span class="org-avatar org-avatar-sm" :style="avatarStyle(pessoa)">
                  <img v-if="pessoa.foto" :src="pessoa.foto" :alt="pessoa.nome" />
                  <span v-else>{{ pessoa.iniciais || '?' }}</span>
                </span>
                <span class="org-node-info">
                  <strong>{{ pessoa.nome }}</strong>
                  <small>{{ pessoa.cargo }}</small>
                  <em>{{ pessoa.setor }}</em>
                </span>
              </button>
            </div>
          </div>
        </div>

        <aside class="org-detalhe" :class="{ ativo: selecionado }">
          <template v-if="selecionado">
            <button type="button" class="org-detalhe-fechar" aria-label="Fechar" @click="selecionado = null">×</button>
            <div class="org-detalhe-banner" :style="{ background: selecionado.cor || '#003F7D' }" />
            <div class="org-detalhe-body">
              <span class="org-avatar org-avatar-xl" :style="avatarStyle(selecionado)">
                <img v-if="selecionado.foto" :src="selecionado.foto" :alt="selecionado.nome" />
                <span v-else>{{ selecionado.iniciais || '?' }}</span>
              </span>
              <h2>{{ selecionado.nome }}</h2>
              <p class="org-detalhe-cargo">{{ selecionado.cargo }}</p>
              <div class="org-detalhe-tags">
                <span class="tag-tipo">{{ labelTipo(selecionado.tipo) }}</span>
                <span v-if="selecionado.setor">{{ selecionado.setor }}</span>
                <span v-if="selecionado.eixo_vinculado">{{ selecionado.eixo_vinculado }}</span>
              </div>
              <a
                v-if="selecionado.contato"
                class="org-detalhe-email"
                :href="`mailto:${selecionado.contato}`"
              >
                {{ selecionado.contato }}
              </a>
              <p class="org-detalhe-hint">Cadastro gerenciado na página CPED.</p>
            </div>
          </template>
          <template v-else>
            <div class="org-detalhe-empty">
              <span class="org-detalhe-empty-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect width="8" height="6" x="8" y="2" rx="1"/><path d="M12 8v4"/><path d="M6 20v-4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v4"/><path d="M6 16H4"/><path d="M20 16h-2"/></svg>
              </span>
              <p>Selecione uma pessoa na árvore para ver os detalhes.</p>
            </div>
          </template>
        </aside>
      </div>
    </section>
  </div>
</template>

<script src="../scripts/Organograma.js"></script>
<style scoped src="../../css/Organograma.css"></style>
