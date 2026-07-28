<template>
  <div class="caro-page">
    <header class="caro-top">
      <div class="caro-top-inner">
        <div class="caro-top-row">
          <div>
            <router-link to="/app/ferramentas" class="caro-back">← Voltar para Ferramentas</router-link>
            <h1>Carômetro</h1>
            <p class="caro-subtitle">
              Álbum da equipe CPED — consulte fotos, cargos e contatos
            </p>
          </div>

          <router-link v-if="podeEditar" to="/app/cped" class="caro-link-cped">
            Gerenciar equipe na CPED
          </router-link>
        </div>

        <div class="caro-busca-wrap">
          <div class="caro-busca">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input
              v-model="busca"
              type="search"
              placeholder="Buscar por nome, cargo, setor ou e-mail..."
              aria-label="Buscar no carômetro"
            />
          </div>
          <div class="caro-resultado">
            <strong>{{ membrosFiltrados.length }}</strong>
            <span>{{ membrosFiltrados.length === 1 ? 'pessoa' : 'pessoas' }}</span>
          </div>
        </div>
      </div>
    </header>

    <div v-if="erro" class="alert alert-error">{{ erro }}</div>

    <div v-if="acessoBloqueado" class="alert alert-error">
      Você não possui autorização para consultar esta funcionalidade.
    </div>

    <section v-if="!acessoBloqueado" class="caro-content">
      <div v-if="carregando" class="caro-loading">
        <span class="caro-loading-bar" />
        Carregando álbum...
      </div>

      <div v-else class="caro-layout">
        <aside class="caro-filtros" aria-label="Filtros">
          <div class="caro-filtros-head">
            <h2>Filtros</h2>
            <button
              v-if="filtroTipo !== 'todos' || filtroArea !== 'todos' || busca"
              type="button"
              class="caro-limpar"
              @click="limparFiltros"
            >
              Limpar
            </button>
          </div>

          <div class="caro-filtro-bloco">
            <h3>Função</h3>
            <button
              type="button"
              class="caro-filtro-btn"
              :class="{ ativo: filtroTipo === 'todos' }"
              @click="filtroTipo = 'todos'"
            >
              <span>Todos</span>
              <strong>{{ meta.total || 0 }}</strong>
            </button>
            <button
              v-for="opcao in tiposFiltro"
              :key="opcao.value"
              type="button"
              class="caro-filtro-btn"
              :class="{ ativo: filtroTipo === opcao.value }"
              @click="filtroTipo = opcao.value"
            >
              <span class="caro-filtro-dot" :style="{ background: corTipo(opcao.value) }" />
              <span>{{ opcao.label }}</span>
              <strong>{{ contagemTipo(opcao.value) }}</strong>
            </button>
          </div>

          <div class="caro-filtro-bloco">
            <h3>Área / Eixo</h3>
            <button
              type="button"
              class="caro-filtro-btn"
              :class="{ ativo: filtroArea === 'todos' }"
              @click="filtroArea = 'todos'"
            >
              Todas
            </button>
            <button
              v-for="area in areasFiltro"
              :key="area"
              type="button"
              class="caro-filtro-btn caro-filtro-area"
              :class="{ ativo: filtroArea === area }"
              :style="filtroArea === area ? estiloAreaAtiva(area) : null"
              @click="filtroArea = area"
            >
              <span class="caro-filtro-dot" :style="{ background: corArea(area) }" />
              <span>{{ area }}</span>
            </button>
          </div>
        </aside>

        <div class="caro-main">
          <div v-if="!membrosFiltrados.length" class="caro-vazia">
            <span class="caro-vazia-icon" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </span>
            <p>Nenhuma pessoa encontrada com esses filtros.</p>
          </div>

          <div v-else class="caro-album">
            <article
              v-for="grupo in gruposVisiveis"
              :key="grupo.tipo"
              class="caro-grupo"
            >
              <header class="caro-grupo-head">
                <div>
                  <span class="caro-grupo-dot" :style="{ background: corTipo(grupo.tipo) }" />
                  <h2>{{ grupo.label }}</h2>
                </div>
                <span>{{ grupo.membros.length }}</span>
              </header>

              <div class="caro-grid">
                <button
                  v-for="pessoa in grupo.membros"
                  :key="pessoa.id"
                  type="button"
                  class="caro-card"
                  @click="selecionar(pessoa)"
                >
                  <div class="caro-card-foto" :style="fotoStyle(pessoa)">
                    <img v-if="pessoa.foto" :src="pessoa.foto" :alt="pessoa.nome" />
                    <span v-else class="caro-iniciais">{{ pessoa.iniciais || '?' }}</span>
                    <div class="caro-card-overlay">
                      <span class="caro-card-badge">{{ labelTipo(pessoa.tipo) }}</span>
                      <strong>{{ pessoa.nome }}</strong>
                      <small>{{ pessoa.cargo }}</small>
                    </div>
                  </div>
                  <div class="caro-card-foot">
                    <em>{{ pessoa.eixo_vinculado || pessoa.setor || '—' }}</em>
                    <span>Ver ficha →</span>
                  </div>
                </button>
              </div>
            </article>
          </div>
        </div>
      </div>
    </section>

    <div v-if="selecionado" class="caro-lightbox" @click.self="selecionado = null">
      <div class="caro-lightbox-card" role="dialog" aria-modal="true" :aria-label="selecionado.nome">
        <button type="button" class="caro-lightbox-fechar" aria-label="Fechar" @click="selecionado = null">×</button>

        <div class="caro-lightbox-foto" :style="fotoStyle(selecionado)">
          <img v-if="selecionado.foto" :src="selecionado.foto" :alt="selecionado.nome" />
          <span v-else class="caro-iniciais caro-iniciais-lg">{{ selecionado.iniciais || '?' }}</span>
        </div>

        <div class="caro-lightbox-info">
          <p class="caro-lightbox-kicker" :style="{ color: corPessoa(selecionado) }">
            {{ labelTipo(selecionado.tipo) }}
          </p>
          <h2>{{ selecionado.nome }}</h2>
          <p class="caro-lightbox-cargo">{{ selecionado.cargo }}</p>

          <dl class="caro-lightbox-meta">
            <div v-if="selecionado.setor">
              <dt>Setor</dt>
              <dd>{{ selecionado.setor }}</dd>
            </div>
            <div v-if="selecionado.eixo_vinculado">
              <dt>Eixo</dt>
              <dd>{{ selecionado.eixo_vinculado }}</dd>
            </div>
            <div v-if="selecionado.contato">
              <dt>Contato</dt>
              <dd>
                <a :href="`mailto:${selecionado.contato}`">{{ selecionado.contato }}</a>
              </dd>
            </div>
          </dl>

          <p class="caro-lightbox-hint">Cadastro gerenciado na página CPED.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script src="../scripts/Carometro.js"></script>
<style scoped src="../../css/Carometro.css"></style>
