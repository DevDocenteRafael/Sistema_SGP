<template>
  <div class="cped-page">
    <header class="cped-hero">
      <div class="cped-hero-inner">
        <div class="cped-hero-main">
          <div class="cped-hero-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
          </div>
          <div>
            <p class="cped-hero-kicker">SENAC DF — CPED</p>
            <h1>CPED</h1>
            <p class="cped-hero-desc">
              Coordenação de Educação Profissional e Desenvolvimento. Responsável pelo planejamento,
              supervisão e execução das atividades de ensino profissional no SENAC DF.
            </p>
          </div>
        </div>

        <div class="cped-hero-side">
          <div class="cped-stats" aria-label="Indicadores da equipe">
            <div class="cped-stat">
              <strong>{{ contadores.colaboradores }}</strong>
              <span>Colaboradores</span>
            </div>
            <div class="cped-stat">
              <strong>{{ contadores.eixos }}</strong>
              <span>Eixos</span>
            </div>
            <div class="cped-stat">
              <strong>{{ contadores.instrutores }}</strong>
              <span>Instrutores</span>
            </div>
            <div class="cped-stat">
              <strong>{{ contadores.administrativos }}</strong>
              <span>Administrativos</span>
            </div>
          </div>

          <button v-if="podeEditar" type="button" class="btn-novo" @click="abrirNovo">
            <span class="btn-novo-icon">+</span>
            Novo Membro
          </button>
        </div>
      </div>
    </header>

    <div class="cped-body">
      <div v-if="mensagemSucesso" class="alert alert-success">{{ mensagemSucesso }}</div>
      <div v-if="erro" class="alert alert-error">{{ erro }}</div>

      <div v-if="acessoBloqueado" class="alert alert-error">
        Você não possui autorização para consultar esta funcionalidade. Verifique seu perfil de acesso.
      </div>

      <template v-if="!acessoBloqueado">
        <div v-if="carregando" class="cped-loading">Carregando equipe CPED...</div>

        <template v-else>
          <section class="cped-section">
            <div class="cped-section-head">
              <span class="cped-section-bar bar-laranja"></span>
              <h2>Organograma da Equipe</h2>
            </div>

            <div class="org-card">
              <div v-if="!ordenador && !assistentes.length && !colunasOrganograma.length && !administrativos.length" class="cped-empty">
                Nenhum membro cadastrado. Use &quot;Novo Membro&quot; para montar o organograma.
              </div>

              <div v-else class="org-tree">
                <div v-if="ordenador" class="org-level">
                  <div class="org-pill org-pill-ordenador">
                    <span class="avatar avatar-md ring" :style="avatarStyle(ordenador)">
                      <img v-if="ordenador.foto" :src="ordenador.foto" :alt="ordenador.nome" />
                      <span v-else>{{ ordenador.iniciais || '?' }}</span>
                    </span>
                    <div>
                      <strong>{{ ordenador.nome }}</strong>
                      <small>{{ ordenador.cargo }}</small>
                    </div>
                  </div>
                  <div class="org-vline"></div>
                </div>

                <div v-if="assistentes.length" class="org-level">
                  <div class="org-assistentes-row">
                    <div v-for="pessoa in assistentes" :key="pessoa.id" class="org-pill org-pill-assistente">
                      <span class="avatar avatar-sm ring" :style="avatarStyle(pessoa)">
                        <img v-if="pessoa.foto" :src="pessoa.foto" :alt="pessoa.nome" />
                        <span v-else>{{ pessoa.iniciais || '?' }}</span>
                      </span>
                      <div>
                        <strong>{{ pessoa.nome }}</strong>
                        <small>{{ pessoa.cargo }}</small>
                      </div>
                    </div>
                  </div>
                  <div class="org-vline"></div>
                </div>

                <div v-if="colunasOrganograma.length" class="org-level org-level-eixos">
                  <div class="org-eixos-wrap">
                    <div class="org-hline"></div>
                    <div class="org-eixos">
                      <div v-for="coluna in colunasOrganograma" :key="coluna.eixo" class="org-eixo-col">
                        <div class="org-vline org-vline-short"></div>
                        <button
                          type="button"
                          class="org-eixo-card"
                          :class="{ active: eixoSelecionado === coluna.eixo }"
                          :style="estiloEixoCard(coluna.eixo)"
                          @click="abrirEixo(coluna.eixo)"
                        >
                          <span
                            v-if="coluna.responsavel"
                            class="avatar avatar-sm ring"
                            :style="avatarStyle(coluna.responsavel)"
                          >
                            <img v-if="coluna.responsavel.foto" :src="coluna.responsavel.foto" :alt="coluna.responsavel.nome" />
                            <span v-else>{{ coluna.responsavel.iniciais || '?' }}</span>
                          </span>
                          <span v-else class="avatar avatar-sm ring org-eixo-placeholder" :style="avatarStyle({ eixo_vinculado: coluna.eixo })">
                            ?
                          </span>
                          <strong class="org-eixo-nome">{{ coluna.eixo }}</strong>
                          <span class="org-eixo-pessoa">
                            {{ coluna.responsavel ? nomeCurto(coluna.responsavel.nome) : 'A definir' }}
                          </span>
                          <span v-if="coluna.totalEquipe" class="org-eixo-equipe" aria-label="Membros da equipe">
                            <span
                              v-for="membro in coluna.equipe.slice(0, 3)"
                              :key="membro.id"
                              class="avatar avatar-xs ring org-eixo-mini"
                              :style="avatarStyle(membro)"
                              :title="membro.nome"
                            >
                              <img v-if="membro.foto" :src="membro.foto" :alt="membro.nome" />
                              <span v-else>{{ membro.iniciais || '?' }}</span>
                            </span>
                            <span v-if="coluna.totalEquipe > 3" class="org-eixo-mais">+{{ coluna.totalEquipe - 3 }}</span>
                          </span>
                          <span class="org-eixo-link">
                            <svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                            ver equipe<span v-if="coluna.totalEquipe"> ({{ coluna.totalEquipe }})</span>
                          </span>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <div v-if="administrativos.length" class="org-level org-level-apoio">
                  <div class="org-vline"></div>
                  <p class="org-nivel-label">Apoio administrativo</p>
                  <div class="org-administrativos-row">
                    <div
                      v-for="pessoa in administrativos"
                      :key="pessoa.id"
                      class="org-pill org-pill-administrativo"
                      role="button"
                      tabindex="0"
                      @click="abrirDetalhe(pessoa)"
                      @keydown.enter="abrirDetalhe(pessoa)"
                    >
                      <span class="avatar avatar-sm ring" :style="avatarStyle(pessoa)">
                        <img v-if="pessoa.foto" :src="pessoa.foto" :alt="pessoa.nome" />
                        <span v-else>{{ pessoa.iniciais || '?' }}</span>
                      </span>
                      <div>
                        <strong>{{ pessoa.nome }}</strong>
                        <small>{{ pessoa.cargo }}</small>
                        <em>{{ pessoa.setor }}</em>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <p class="org-hint">
              Instrutores aparecem nos cards dos eixos. Assistentes e administrativos aparecem no organograma e ao clicar em &quot;ver equipe&quot;.
            </p>
          </section>

          <section class="cped-section">
            <div class="cped-section-head">
              <span class="cped-section-bar bar-azul"></span>
              <h2>Equipe por Função</h2>
            </div>

            <div class="funcao-grid-layout">
              <div
                v-for="grupo in gruposPorFuncao"
                :key="grupo.tipo"
                class="funcao-panel"
                :class="[grupo.classe, { 'funcao-panel-wide': grupo.larguraTotal }]"
              >
                <h3>
                  {{ grupo.titulo }}
                  <span v-if="grupo.tipo === 'instrutor' || grupo.tipo === 'administrativo'">
                    ({{ grupo.membros.length }})
                  </span>
                </h3>

                <div v-if="grupo.tipo === 'ordenador'" class="funcao-ordenador">
                  <article
                    v-for="pessoa in grupo.membros"
                    :key="pessoa.id"
                    class="membro-card membro-card-full"
                    @click="abrirDetalhe(pessoa)"
                  >
                    <div v-if="podeEditar" class="card-actions" @click.stop>
                      <button type="button" class="icon-btn edit" title="Editar" aria-label="Editar" @click="abrirEdicao(pessoa)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                      </button>
                      <button type="button" class="icon-btn delete" title="Excluir" aria-label="Excluir" @click="excluir(pessoa)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                      </button>
                    </div>
                    <span class="avatar avatar-lg ring" :style="avatarStyle(pessoa)">
                      <img v-if="pessoa.foto" :src="pessoa.foto" :alt="pessoa.nome" />
                      <span v-else>{{ pessoa.iniciais || '?' }}</span>
                    </span>
                    <strong>{{ pessoa.nome }}</strong>
                    <small>{{ pessoa.cargo }}</small>
                    <span class="setor-badge" :style="estiloEixoCard(pessoa.setor)">{{ pessoa.setor }}</span>
                    <a v-if="pessoa.contato" class="membro-email" :href="`mailto:${pessoa.contato}`" @click.stop>{{ pessoa.contato }}</a>
                  </article>
                </div>

                <div v-else-if="grupo.tipo === 'responsavel'" class="funcao-responsaveis">
                  <article
                    v-for="pessoa in grupo.membros"
                    :key="pessoa.id"
                    class="membro-card membro-card-compact"
                    @click="abrirEixo(pessoa.eixo_vinculado || pessoa.setor)"
                  >
                    <div v-if="podeEditar" class="card-actions" @click.stop>
                      <button type="button" class="icon-btn edit" title="Editar" aria-label="Editar" @click="abrirEdicao(pessoa)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                      </button>
                      <button type="button" class="icon-btn delete" title="Excluir" aria-label="Excluir" @click="excluir(pessoa)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                      </button>
                    </div>
                    <span class="avatar avatar-sm ring" :style="avatarStyle(pessoa)">
                      <img v-if="pessoa.foto" :src="pessoa.foto" :alt="pessoa.nome" />
                      <span v-else>{{ pessoa.iniciais || '?' }}</span>
                    </span>
                    <div class="membro-card-body">
                      <strong>{{ pessoa.nome }}</strong>
                      <small>{{ pessoa.cargo }}</small>
                    </div>
                  </article>
                </div>

                <div v-else class="funcao-lista">
                  <article
                    v-for="pessoa in grupo.membros"
                    :key="pessoa.id"
                    class="membro-card membro-card-compact"
                    @click="abrirDetalhe(pessoa)"
                  >
                    <div v-if="podeEditar" class="card-actions" @click.stop>
                      <button type="button" class="icon-btn edit" title="Editar" aria-label="Editar" @click="abrirEdicao(pessoa)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                      </button>
                      <button type="button" class="icon-btn delete" title="Excluir" aria-label="Excluir" @click="excluir(pessoa)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                      </button>
                    </div>
                    <span class="avatar avatar-sm ring" :style="avatarStyle(pessoa)">
                      <img v-if="pessoa.foto" :src="pessoa.foto" :alt="pessoa.nome" />
                      <span v-else>{{ pessoa.iniciais || '?' }}</span>
                    </span>
                    <div class="membro-card-body">
                      <strong>{{ pessoa.nome }}</strong>
                      <small>{{ pessoa.cargo }}</small>
                    </div>
                  </article>
                </div>
              </div>
            </div>
          </section>

          <section class="cped-section">
            <div class="cped-section-head">
              <span class="cped-section-bar bar-verde"></span>
              <h2>Carômetro da Equipe</h2>
            </div>

            <div class="carometro-panel">
              <div class="carometro-filtros">
                <div class="filtro-chips filtro-chips-principal" aria-label="Filtrar por tipo e setor">
                  <button
                    type="button"
                    class="chip"
                    :class="{ active: filtroTipo === 'todos' }"
                    @click="filtroTipo = 'todos'"
                  >
                    Todos
                  </button>
                  <button
                    v-for="opcao in opcoesFiltroTipo"
                    :key="opcao.value"
                    type="button"
                    class="chip"
                    :class="{ active: filtroTipo === opcao.value }"
                    @click="filtroTipo = opcao.value"
                  >
                    {{ opcao.label }}
                  </button>

                  <span class="filtro-divider" aria-hidden="true"></span>

                  <button
                    type="button"
                    class="chip chip-eixo chip-eixo-todos"
                    :class="{ active: filtroEixo === 'todos' }"
                    @click="filtroEixo = 'todos'"
                  >
                    Todos os eixos
                  </button>
                  <button
                    v-for="setor in setoresFiltro"
                    :key="setor"
                    type="button"
                    class="chip chip-eixo"
                    :class="{ active: filtroEixo === setor, 'chip-setor-admin': !eixos.includes(setor) }"
                    :style="filtroEixo === setor && eixos.includes(setor) ? estiloEixoChipAtivo(setor) : {}"
                    @click="filtroEixo = setor"
                  >
                    {{ setor }}
                  </button>
                </div>
              </div>

              <div v-if="membrosFiltrados.length" class="carometro-grid">
                <button
                  v-for="pessoa in membrosFiltrados"
                  :key="pessoa.id"
                  type="button"
                  class="carometro-card"
                  @click="abrirDetalhe(pessoa)"
                >
                  <div v-if="podeEditar" class="card-actions" @click.stop>
                    <button type="button" class="icon-btn edit" title="Editar" aria-label="Editar" @click="abrirEdicao(pessoa)">
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                    </button>
                    <button type="button" class="icon-btn delete" title="Excluir" aria-label="Excluir" @click="excluir(pessoa)">
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    </button>
                  </div>

                  <span class="avatar avatar-lg ring" :style="avatarStyle(pessoa)">
                    <img v-if="pessoa.foto" :src="pessoa.foto" :alt="pessoa.nome" />
                    <span v-else>{{ pessoa.iniciais || '?' }}</span>
                  </span>
                  <strong>{{ pessoa.nome }}</strong>
                  <small>{{ pessoa.cargo }}</small>
                  <span class="setor-badge" :style="estiloEixoCard(pessoa.setor)">{{ pessoa.setor }}</span>
                  <a v-if="pessoa.contato" class="membro-email" :href="`mailto:${pessoa.contato}`" @click.stop>{{ pessoa.contato }}</a>
                  <span class="carometro-detalhe">Ver detalhes →</span>
                </button>
              </div>

              <p v-else class="cped-empty">Nenhum membro encontrado para os filtros selecionados.</p>
            </div>
          </section>
        </template>
      </template>
    </div>

    <div v-if="eixoSelecionado" class="modal-overlay" @click.self="fecharEixo">
      <div class="modal-eixo" role="dialog" aria-modal="true">
        <div class="modal-eixo-accent" :style="{ background: equipeEixoModal.responsavel?.cor || temaEixo(eixoSelecionado).text }"></div>
        <div class="modal-eixo-head" :style="{ background: temaEixo(eixoSelecionado).bg }">
          <div>
            <p class="modal-eixo-kicker">Eixo Tecnológico</p>
            <h2 :style="{ color: temaEixo(eixoSelecionado).text }">{{ eixoSelecionado }}</h2>
          </div>
          <button type="button" class="modal-close" aria-label="Fechar" @click="fecharEixo">×</button>
        </div>

        <div class="modal-eixo-body">
          <div v-if="equipeEixoModal.responsavel" class="eixo-grupo">
            <h3>Responsável de Eixo</h3>
            <article class="eixo-membro-card">
              <span class="avatar avatar-md ring" :style="avatarStyle(equipeEixoModal.responsavel)">
                <img v-if="equipeEixoModal.responsavel.foto" :src="equipeEixoModal.responsavel.foto" :alt="equipeEixoModal.responsavel.nome" />
                <span v-else>{{ equipeEixoModal.responsavel.iniciais || '?' }}</span>
              </span>
              <div>
                <strong>{{ equipeEixoModal.responsavel.nome }}</strong>
                <small>{{ equipeEixoModal.responsavel.cargo }}</small>
                <a class="membro-email" :href="`mailto:${equipeEixoModal.responsavel.contato}`">{{ equipeEixoModal.responsavel.contato }}</a>
              </div>
            </article>
          </div>

          <div v-if="equipeEixoModal.instrutores.length" class="eixo-grupo">
            <h3>Instrutores ({{ equipeEixoModal.instrutores.length }})</h3>
            <div class="eixo-membros-grid">
              <article v-for="pessoa in equipeEixoModal.instrutores" :key="pessoa.id" class="eixo-membro-mini">
                <span class="avatar avatar-sm ring" :style="avatarStyle(pessoa)">
                  <img v-if="pessoa.foto" :src="pessoa.foto" :alt="pessoa.nome" />
                  <span v-else>{{ pessoa.iniciais || '?' }}</span>
                </span>
                <div>
                  <strong>{{ pessoa.nome }}</strong>
                  <small>{{ pessoa.cargo }}</small>
                </div>
              </article>
            </div>
          </div>

          <div v-if="equipeEixoModal.assistentes.length" class="eixo-grupo">
            <h3>Assistentes Administrativos ({{ equipeEixoModal.assistentes.length }})</h3>
            <div class="eixo-membros-grid">
              <article v-for="pessoa in equipeEixoModal.assistentes" :key="pessoa.id" class="eixo-membro-mini">
                <span class="avatar avatar-sm ring" :style="avatarStyle(pessoa)">
                  <img v-if="pessoa.foto" :src="pessoa.foto" :alt="pessoa.nome" />
                  <span v-else>{{ pessoa.iniciais || '?' }}</span>
                </span>
                <div>
                  <strong>{{ pessoa.nome }}</strong>
                  <small>{{ pessoa.cargo }}</small>
                </div>
              </article>
            </div>
          </div>

          <div v-if="equipeEixoModal.administrativos.length" class="eixo-grupo">
            <h3>Apoio Administrativo ({{ equipeEixoModal.administrativos.length }})</h3>
            <div class="eixo-membros-grid">
              <article v-for="pessoa in equipeEixoModal.administrativos" :key="pessoa.id" class="eixo-membro-mini">
                <span class="avatar avatar-sm ring" :style="avatarStyle(pessoa)">
                  <img v-if="pessoa.foto" :src="pessoa.foto" :alt="pessoa.nome" />
                  <span v-else>{{ pessoa.iniciais || '?' }}</span>
                </span>
                <div>
                  <strong>{{ pessoa.nome }}</strong>
                  <small>{{ pessoa.cargo }}</small>
                  <em class="eixo-membro-setor">{{ pessoa.setor }}</em>
                </div>
              </article>
            </div>
          </div>

          <p
            v-if="!equipeEixoModal.responsavel && !equipeEixoModal.instrutores.length && !equipeEixoModal.assistentes.length && !equipeEixoModal.administrativos.length"
            class="cped-empty"
          >
            Nenhum membro vinculado a este eixo.
          </p>
        </div>
      </div>
    </div>

    <div v-if="modalAberto" class="modal-overlay" @click.self="fecharModal">
      <div class="modal-card" role="dialog" aria-modal="true">
        <div class="modal-head">
          <div>
            <h2>{{ editandoId ? 'Editar Membro' : 'Novo Membro' }}</h2>
            <p>Cadastre ou atualize dados do organograma e carômetro CPED.</p>
          </div>
          <button type="button" class="modal-close" aria-label="Fechar" @click="fecharModal">×</button>
        </div>

        <form class="modal-form" @submit.prevent="salvar">
          <div v-if="erroFormulario" class="alert alert-error">{{ erroFormulario }}</div>

          <div class="foto-field">
            <span class="avatar avatar-xl ring" :style="avatarStyle(form)">
              <img v-if="form.foto" :src="form.foto" alt="Prévia da foto" />
              <span v-else>{{ form.iniciais || '?' }}</span>
            </span>
            <div>
              <label class="btn-foto">
                Enviar foto
                <input type="file" accept="image/*" @change="onFotoSelecionada" />
              </label>
              <button v-if="form.foto" type="button" class="link-btn" @click="limparFoto">Remover foto</button>
            </div>
          </div>

          <div class="modal-form-grid">
            <label>
              Nome completo *
              <input v-model="form.nome" type="text" required maxlength="100" @input="atualizarIniciais" />
            </label>

            <label>
              Cargo / Função *
              <input v-model="form.cargo" type="text" required maxlength="100" />
            </label>

            <label>
              Tipo *
              <SearchableSelect
                v-model="form.tipo"
                :options="opcoesFormularioTipo"
                :required="true"
                @change="onTipoChange"
              />
            </label>

            <label>
              Setor / Eixo *
              <SearchableSelect
                v-model="form.setor"
                :options="setoresDoFormulario"
                :required="true"
                @change="onSetorChange"
              />
            </label>

            <label v-if="precisaEixo">
              Eixo vinculado *
              <SearchableSelect
                v-model="form.eixo_vinculado"
                :options="eixos"
                empty-option="Selecione"
                :required="true"
              />
            </label>

            <label>
              E-mail de contato *
              <input v-model="form.contato" type="email" required maxlength="100" />
            </label>

            <label>
              Iniciais (avatar sem foto)
              <input v-model="form.iniciais" type="text" maxlength="20" />
            </label>

            <label>
              Cor do avatar
              <input v-model="form.cor" type="color" />
            </label>
          </div>

          <label class="check-field">
            <input v-model="form.ativo" type="checkbox" />
            Membro ativo
          </label>

          <label>
            Observação
            <textarea v-model="form.observacao" rows="3"></textarea>
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

    <div v-if="detalhe" class="modal-overlay" @click.self="detalhe = null">
      <div class="modal-perfil" role="dialog" aria-modal="true">
        <div class="modal-perfil-banner" :style="{ background: detalhe.cor || avatarStyle(detalhe).background }"></div>
        <div class="modal-perfil-body">
          <span class="avatar avatar-xl ring avatar-perfil" :style="avatarStyle(detalhe)">
            <img v-if="detalhe.foto" :src="detalhe.foto" :alt="detalhe.nome" />
            <span v-else>{{ detalhe.iniciais || '?' }}</span>
          </span>
          <h3>{{ detalhe.nome }}</h3>
          <p class="modal-perfil-cargo">{{ detalhe.cargo }}</p>
          <div class="modal-perfil-badges">
            <span class="setor-badge" :style="estiloEixoCard(detalhe.setor)">{{ detalhe.setor }}</span>
            <span class="tipo-badge">{{ tiposLabels[detalhe.tipo] || detalhe.tipo }}</span>
          </div>
          <a v-if="detalhe.contato" class="membro-email membro-email-lg" :href="`mailto:${detalhe.contato}`">{{ detalhe.contato }}</a>
        </div>
        <div class="modal-perfil-footer">
          <div v-if="podeEditar" class="modal-perfil-actions">
            <button type="button" class="btn-sec" @click="abrirEdicao(detalhe)">Editar</button>
            <button type="button" class="btn-danger" @click="excluir(detalhe)">Excluir</button>
          </div>
          <button type="button" class="link-btn" @click="detalhe = null">Fechar</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script src="../scripts/Cped.js"></script>
<style scoped src="../../css/Cped.css"></style>
