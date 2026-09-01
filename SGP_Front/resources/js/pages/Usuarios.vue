<template>
  <div class="usuarios-page">
    <!-- LISTA -->
    <template v-if="modo === 'lista'">
      <header class="usuarios-top">
        <div class="usuarios-top-row">
          <div>
            <h1>Usuários</h1>
            <p class="usuarios-subtitle">Controle de acesso e perfis do SGP — SENAC DF</p>
          </div>
          <button type="button" class="btn-novo" @click="abrirNovo">
            <span class="btn-novo-icon">+</span>
            Novo Usuário
          </button>
        </div>
        <div class="usuarios-info">
          O administrador cadastra o colaborador e define o e-mail e a senha de acesso ao sistema.
        </div>
      </header>

      <div v-if="mensagemSucesso" class="alert alert-success">{{ mensagemSucesso }}</div>
      <div v-if="mensagemErro" class="alert alert-error">{{ mensagemErro }}</div>

      <section class="filtros-bar">
        <div class="filtro-busca">
          <input
            v-model="filtros.busca"
            type="search"
            placeholder="Buscar por nome, e-mail, telefone ou unidade..."
            @input="carregarUsuarios"
          />
        </div>
        <SearchableSelect
          v-model="filtros.perfil"
          :options="[
            { value: 'Administrador', label: 'Administrador' },
            { value: 'Editor', label: 'Editor' },
            { value: 'Consultor', label: 'Consultor' },
          ]"
          empty-option="Todos os perfis"
          @change="carregarUsuarios"
        />
        <SearchableSelect
          v-model="filtros.status"
          :options="[
            { value: 'true', label: 'Ativo' },
            { value: 'false', label: 'Inativo' },
          ]"
          empty-option="Todos os status"
          @change="carregarUsuarios"
        />
      </section>

      <PageTableCard :total="usuarios.length">

        <div v-if="carregando" class="tabela-loading">Carregando...</div>

        <div v-else class="tabela-wrap">
          <table class="usuarios-table">
            <thead>
              <tr>
                <th>Usuário</th>
                <th>Telefone</th>
                <th>Perfil</th>
                <th>Unidade</th>
                <th class="text-center">Status</th>
                <th class="text-center">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="usuarios.length === 0">
                <td colspan="6" class="tabela-vazia">
                  Nenhum usuário encontrado para os filtros selecionados.
                </td>
              </tr>
              <tr v-for="usuario in usuarios" :key="usuario.id">
                <td>
                  <div class="user-cell">
                    <span class="avatar" :class="avatarClass(usuario.perfil)">
                      {{ iniciais(usuario.nome) }}
                    </span>
                    <div>
                      <p class="user-nome">{{ usuario.nome }}</p>
                      <p class="user-email">{{ usuario.email }}</p>
                    </div>
                  </div>
                </td>
                <td>{{ usuario.telefone || '—' }}</td>
                <td>
                  <span class="badge" :class="badgePerfil(usuario.perfil)">
                    {{ usuario.perfil }}
                  </span>
                </td>
                <td>{{ usuario.unidade || '—' }}</td>
                <td class="text-center">
                  <span class="badge" :class="usuario.status ? 'badge-ativo' : 'badge-inativo'">
                    {{ usuario.status ? 'Ativo' : 'Inativo' }}
                  </span>
                </td>
                <td class="text-center acoes">
                  <button
                    type="button"
                    class="btn-icon btn-view"
                    title="Ver detalhes"
                    @click="abrirDetalhes(usuario)"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                  <button
                    type="button"
                    class="btn-icon btn-edit"
                    title="Editar usuário"
                    @click="abrirEdicao(usuario)"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                  </button>
                  <button
                    type="button"
                    class="btn-icon btn-delete"
                    title="Excluir usuário"
                    @click="excluirUsuario(usuario)"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="tabela-footer">
          {{ usuarios.length }} usuário{{ usuarios.length !== 1 ? 's' : '' }} listado{{ usuarios.length !== 1 ? 's' : '' }}
        </div>
      </PageTableCard>

      <!-- Modal detalhes -->
      <div
        v-if="usuarioDetalhe"
        class="modal-overlay"
        @click.self="fecharDetalhes"
      >
        <div class="modal-detalhes" role="dialog" aria-labelledby="detalhes-titulo">
          <div class="modal-detalhes-header">
            <div>
              <h2 id="detalhes-titulo">Detalhes do Registro</h2>
              <p class="modal-detalhes-subtitle">Informações resumidas do usuário selecionado.</p>
            </div>
            <button type="button" class="btn-fechar-x" title="Fechar" @click="fecharDetalhes">
              ×
            </button>
          </div>

          <div class="modal-form-wrap">
            <div class="detalhe-form-grid">
              <div class="detalhe-form-campo campo-full">
                <span>Nome</span>
                <div class="detalhe-valor-box">{{ usuarioDetalhe.nome || '—' }}</div>
              </div>
              <div class="detalhe-form-campo campo-full">
                <span>E-mail</span>
                <div class="detalhe-valor-box">{{ usuarioDetalhe.email || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Perfil</span>
                <div class="detalhe-valor-box">{{ usuarioDetalhe.perfil || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Status</span>
                <div class="detalhe-valor-box">{{ usuarioDetalhe.status ? 'Ativo' : 'Inativo' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Telefone</span>
                <div class="detalhe-valor-box">{{ usuarioDetalhe.telefone || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>CPF</span>
                <div class="detalhe-valor-box">{{ usuarioDetalhe.cpf || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Unidade</span>
                <div class="detalhe-valor-box">{{ usuarioDetalhe.unidade || '—' }}</div>
              </div>
              <div class="detalhe-form-campo">
                <span>Área</span>
                <div class="detalhe-valor-box">{{ usuarioDetalhe.area || '—' }}</div>
              </div>
            </div>
          </div>

          <div class="modal-detalhes-actions">
            <button type="button" class="btn-editar-modal" @click="editarDoDetalhe">
              Editar
            </button>
            <button type="button" class="btn-secondary" @click="fecharDetalhes">
              Fechar
            </button>
          </div>
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
            <h1>{{ modo === 'novo' ? 'Cadastrar Novo Usuário' : 'Editar Usuário' }}</h1>
            <p>
              {{
                modo === 'novo'
                  ? 'Preencha as informações para criar um novo acesso ao SGP'
                  : 'Atualize os dados do colaborador. Deixe a senha em branco para manter a atual.'
              }}
            </p>
          </div>
        </header>

        <form class="form-body" @submit.prevent="salvarUsuario">
          <div v-if="erroFormulario" class="alert alert-error">{{ erroFormulario }}</div>

          <section class="form-section">
            <h2>Dados Pessoais</h2>
            <div class="form-grid">
              <div class="form-group full">
                <label for="nome">Nome Completo <span>*</span></label>
                <input id="nome" v-model="form.nome" type="text" placeholder="Ex: Ana Paula Souza" required maxlength="100" />
              </div>
              <div class="form-group">
                <label for="email">E-mail (login) <span>*</span></label>
                <input id="email" v-model="form.email" type="email" placeholder="nome@df.senac.br" required maxlength="100" />
              </div>
              <div class="form-group">
                <label for="telefone">Telefone</label>
                <input id="telefone" v-model="form.telefone" type="text" placeholder="(61) 99999-9999" maxlength="20" @input="formatarTelefone" />
              </div>
              <div class="form-group">
                <label for="cpf">CPF</label>
                <input id="cpf" v-model="form.cpf" type="text" placeholder="000.000.000-00" maxlength="14" @input="formatarCpf" />
              </div>
              <div class="form-group">
                <label for="area">Área de atuação</label>
                <input id="area" v-model="form.area" type="text" placeholder="Ex: Coordenação Pedagógica" maxlength="100" />
              </div>
            </div>
          </section>

          <section class="form-section">
            <h2>Nível de Acesso</h2>
            <div class="form-grid">
              <div class="form-group">
                <label for="perfil">Perfil <span>*</span></label>
                <SearchableSelect
                  id="perfil"
                  input-id="perfil"
                  v-model="form.perfil"
                  :options="[
                    { value: 'Administrador', label: 'Administrador — acesso total e gestão de usuários' },
                    { value: 'Editor', label: 'Editor — cria e altera dados do portfólio' },
                    { value: 'Consultor', label: 'Consultor — somente leitura' },
                  ]"
                  empty-option="Selecione o nível de acesso"
                  :required="true"
                />
              </div>
              <div class="form-group">
                <label for="unidade">Unidade <span>*</span></label>
                <SearchableSelect
                  id="unidade"
                  input-id="unidade"
                  v-model="form.unidade"
                  :options="unidades"
                  empty-option="Selecione a unidade"
                  :required="true"
                />
              </div>
              <div class="form-group">
                <label for="senha">Senha <span v-if="modo === 'novo'">*</span></label>
                <input
                  id="senha"
                  v-model="form.senha"
                  type="password"
                  :required="modo === 'novo'"
                  minlength="6"
                  maxlength="100"
                  :placeholder="modo === 'novo' ? 'Mínimo 6 caracteres' : 'Manter senha atual'"
                />
              </div>
              <div class="form-group">
                <label for="confirmarSenha">Confirmar senha <span v-if="modo === 'novo'">*</span></label>
                <input
                  id="confirmarSenha"
                  v-model="form.confirmarSenha"
                  type="password"
                  :required="modo === 'novo' || !!form.senha"
                  minlength="6"
                  maxlength="100"
                  placeholder="Repita a senha"
                />
              </div>
            </div>

            <label class="form-check">
              <input v-model="form.status" type="checkbox" />
              Usuário ativo
            </label>
          </section>

          <div class="form-actions">
            <button type="button" class="btn-secondary" @click="voltarLista">Cancelar</button>
            <button type="submit" class="btn-salvar" :disabled="salvando">
              {{ salvando ? 'Salvando...' : modo === 'novo' ? 'Cadastrar Usuário' : 'Salvar Alterações' }}
            </button>
          </div>
        </form>
      </div>
    </template>
  </div>
</template>

<script src="../scripts/Usuarios.js"></script>
<style scoped src="../../css/Usuarios.css"></style>
