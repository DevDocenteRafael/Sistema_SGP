<template>
  <div class="eixos-page">
    <header class="eixos-header">
      <div class="eixos-header-texto">
        <h1>Eixos</h1>
        <p class="eixos-subtitle">
          Distribuição dos cursos nos cinco Eixos oficiais.
          Ciclo de Gestão <strong>{{ resumo.ciclo_nome || 'atual' }}</strong>.
        </p>
      </div>
      <dl class="eixos-totais" aria-label="Totais do ciclo">
        <div>
          <dt>Eixos</dt>
          <dd>{{ resumo.totais?.eixos ?? 5 }}</dd>
        </div>
        <div>
          <dt>Cursos classificados</dt>
          <dd>{{ resumo.totais?.cursos_classificados ?? resumo.totais?.cursos ?? 0 }}</dd>
        </div>
        <div>
          <dt>Turmas</dt>
          <dd>{{ resumo.totais?.turmas ?? 0 }}</dd>
        </div>
        <div>
          <dt>Alunos</dt>
          <dd>{{ resumo.totais?.alunos ?? 0 }}</dd>
        </div>
      </dl>
    </header>

    <div v-if="erro" class="alert alert-error">{{ erro }}</div>
    <div v-if="carregando" class="eixos-vazio">Carregando eixos...</div>

    <section v-else class="eixos-grid" aria-label="Eixos oficiais">
      <button
        v-for="eixo in resumo.eixos"
        :key="eixo.id"
        type="button"
        class="eixo-card"
        :class="classeEixo(eixo.nome)"
        @click="abrirDetalhe(eixo)"
      >
        <p class="eixo-card-kicker">Eixo oficial</p>
        <h2>{{ eixo.nome }}</h2>
        <dl class="eixo-card-metrics">
          <div>
            <dt>Cursos</dt>
            <dd>{{ eixo.cursos }}</dd>
          </div>
          <div>
            <dt>Turmas</dt>
            <dd>{{ eixo.turmas }}</dd>
          </div>
          <div>
            <dt>Alunos</dt>
            <dd>{{ eixo.alunos }}</dd>
          </div>
        </dl>
        <span class="eixo-card-acao">Ver detalhes →</span>
      </button>
    </section>
  </div>
</template>

<script src="../scripts/Eixos.js"></script>
<style scoped src="../../css/Eixos.css"></style>
