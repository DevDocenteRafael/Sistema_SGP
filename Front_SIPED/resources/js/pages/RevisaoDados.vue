<template>
  <div class="revisao-page">
    <header class="revisao-header">
      <div>
        <p class="revisao-kicker">Importações</p>
        <h1>Revisão de Dados</h1>
        <p class="revisao-subtitle">
          Resolva inconsistências do ciclo <strong>{{ cicloNome }}</strong>.
          Os registros continuam no sistema até serem classificados ou vinculados.
        </p>
      </div>
      <router-link class="btn-secondary" to="/app/importacoes">Voltar para Importações</router-link>
    </header>

    <div v-if="erro" class="alert alert-error">{{ erro }}</div>
    <div v-if="mensagem" class="alert alert-success">{{ mensagem }}</div>

    <div class="revisao-abas">
      <button type="button" class="revisao-aba" :class="{ 'is-active': tipo === 'sem_eixo' }" @click="trocarTipo('sem_eixo')">
        Cursos sem classificação ({{ totais.sem_eixo }})
      </button>
      <button type="button" class="revisao-aba" :class="{ 'is-active': tipo === 'sem_vinculo' }" @click="trocarTipo('sem_vinculo')">
        Registros importados sem Curso correspondente ({{ totais.sem_vinculo }})
      </button>
    </div>
    <p class="revisao-total">Total: {{ totais.total }} registros</p>

    <div class="revisao-toolbar">
      <input
        v-model="busca"
        type="search"
        class="revisao-busca"
        placeholder="Buscar..."
        aria-label="Buscar registros"
        @input="agendarBusca"
      />
    </div>

    <div v-if="carregando" class="revisao-vazio">Carregando revisão...</div>
    <table v-else class="revisao-table">
      <thead>
        <tr v-if="tipo === 'sem_eixo'">
          <th>Curso</th>
          <th>Valor original</th>
          <th>Segmento</th>
          <th>Ação</th>
        </tr>
        <tr v-else>
          <th>Registro importado</th>
          <th>Eixo / Segmento</th>
          <th>Código</th>
          <th>Ação</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="!itens.length">
          <td colspan="4">Nenhum registro nesta categoria.</td>
        </tr>
        <tr v-for="item in itens" :key="tipo + '-' + item.id">
          <template v-if="tipo === 'sem_eixo'">
            <td>{{ item.nome }}</td>
            <td>{{ item.eixo_original || '—' }}</td>
            <td>{{ item.segmento || '—' }}</td>
            <td>
              <button v-if="podeEditar" type="button" class="btn-secondary" @click="abrirClassificacao(item)">Resolver</button>
            </td>
          </template>
          <template v-else>
            <td>{{ item.nome }}</td>
            <td>{{ [item.eixo, item.segmento].filter(Boolean).join(' · ') || '—' }}</td>
            <td>{{ item.codigo || '—' }}</td>
            <td>
              <button v-if="podeEditar" type="button" class="btn-secondary" @click="abrirVinculo(item)">Resolver</button>
            </td>
          </template>
        </tr>
      </tbody>
    </table>

    <div v-if="meta.total" class="revisao-paginacao">
      <span>Mostrando {{ meta.from || 0 }}-{{ meta.to || 0 }} de {{ meta.total }}</span>
      <div v-if="meta.last_page > 1" class="revisao-paginacao-acoes">
        <button type="button" class="btn-secondary" :disabled="meta.current_page <= 1" @click="irPagina(meta.current_page - 1)">Anterior</button>
        <span>Página {{ meta.current_page }} de {{ meta.last_page }}</span>
        <button type="button" class="btn-secondary" :disabled="meta.current_page >= meta.last_page" @click="irPagina(meta.current_page + 1)">Próxima</button>
      </div>
    </div>

    <div v-if="classificando" class="modal-overlay" @click.self="classificando = null">
      <div class="revisao-modal" role="dialog" aria-modal="true" aria-labelledby="revisao-classificar-titulo">
        <h2 id="revisao-classificar-titulo">Classificar curso</h2>
        <p>{{ classificando.nome }}</p>
        <label>
          Eixo
          <SearchableSelect v-model="formClassificacao.eixo" :options="eixos" required aria-label="Eixo oficial" />
        </label>
        <label>
          Segmento
          <SearchableSelect
            v-model="formClassificacao.segmento"
            :options="segmentosDoEixo"
            empty-option="Selecione"
            aria-label="Segmento"
          />
        </label>
        <div class="revisao-modal-acoes">
          <button type="button" class="btn-secondary" @click="classificando = null">Cancelar</button>
          <button type="button" class="btn-primary" :disabled="salvando" @click="salvarClassificacao">Salvar classificação</button>
        </div>
      </div>
    </div>

    <div v-if="vinculando" class="modal-overlay" @click.self="vinculando = null">
      <div class="revisao-modal" role="dialog" aria-modal="true" aria-labelledby="revisao-vincular-titulo">
        <h2 id="revisao-vincular-titulo">Vincular ao curso oficial</h2>
        <p>{{ vinculando.nome }}</p>
        <p class="revisao-meta">Código: {{ vinculando.codigo || '—' }} · {{ vinculando.segmento || vinculando.eixo || '—' }}</p>
        <div v-if="vinculando.sugestoes?.length" class="revisao-sugestoes">
          <p>Possíveis cursos</p>
          <button
            v-for="sugestao in vinculando.sugestoes"
            :key="sugestao.id"
            type="button"
            class="revisao-sugestao"
            @click="formVinculo.curso_id = String(sugestao.id)"
          >
            {{ Math.round(sugestao.similaridade) }}% {{ sugestao.titulo }}
          </button>
        </div>
        <label>
          Buscar outro curso
          <SearchableSelect
            v-model="formVinculo.curso_id"
            :options="opcoesCursos"
            empty-option="Selecionar curso"
            aria-label="Curso oficial"
          />
        </label>
        <div class="revisao-modal-acoes">
          <button type="button" class="btn-secondary" @click="vinculando = null">Cancelar</button>
          <button type="button" class="btn-primary" :disabled="salvando || !formVinculo.curso_id" @click="salvarVinculo">Vincular</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script src="../scripts/RevisaoDados.js"></script>
<style scoped src="../../css/RevisaoDados.css"></style>
