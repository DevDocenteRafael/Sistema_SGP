<template>
  <div class="eixos-page">
    <header class="eixos-header eixos-header-detalhe">
      <div>
        <button type="button" class="eixos-voltar" @click="voltar">← Voltar para Eixos</button>
        <p class="eixo-card-kicker">Eixo oficial</p>
        <h1>{{ detalhes.eixo?.nome || 'Eixo' }}</h1>
        <p class="eixos-subtitle">Ciclo de Gestão <strong>{{ detalhes.ciclo_nome || 'atual' }}</strong></p>
      </div>
      <dl class="eixos-totais eixos-totais-3" aria-label="Indicadores do eixo">
        <div>
          <dt>Cursos</dt>
          <dd>{{ detalhes.eixo?.cursos ?? 0 }}</dd>
        </div>
        <div>
          <dt>Turmas</dt>
          <dd>{{ detalhes.eixo?.turmas ?? 0 }}</dd>
        </div>
        <div>
          <dt>Alunos</dt>
          <dd>{{ detalhes.eixo?.alunos ?? 0 }}</dd>
        </div>
      </dl>
    </header>

    <div v-if="erro" class="alert alert-error">{{ erro }}</div>
    <div v-if="carregando" class="eixos-vazio">Carregando detalhe...</div>

    <template v-else>
      <section class="eixos-secao" aria-label="Distribuição por segmento">
        <h2>Distribuição por segmento</h2>
        <div v-if="!detalhes.segmentos?.length" class="eixos-vazio-bloco">Nenhum segmento neste eixo.</div>
        <table v-else class="eixos-table">
          <thead>
            <tr>
              <th>Segmento</th>
              <th>Cursos</th>
              <th>Turmas</th>
              <th>Alunos</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="segmento in detalhes.segmentos" :key="segmento.id">
              <td>{{ segmento.nome }}</td>
              <td>{{ segmento.cursos }}</td>
              <td>{{ segmento.turmas }}</td>
              <td>{{ segmento.alunos }}</td>
            </tr>
          </tbody>
        </table>
      </section>

      <section class="eixos-secao" aria-label="Cursos do eixo">
        <div class="eixos-toolbar">
          <h2>Cursos do eixo</h2>
          <input
            v-model="busca"
            type="search"
            class="eixos-busca"
            placeholder="Buscar curso, segmento, modalidade ou SIG..."
            aria-label="Buscar cursos do eixo"
            @input="agendarBusca"
          />
          <button v-if="podeEditar" type="button" class="btn-primary" @click="cadastrarCurso">
            + Cadastrar curso neste Eixo
          </button>
        </div>

        <div v-if="carregandoCursos" class="eixos-vazio-bloco">Carregando cursos...</div>
        <div v-else-if="!cursos.length" class="eixos-vazio-bloco">Nenhum curso deste eixo no ciclo.</div>
        <table v-else class="eixos-table">
          <thead>
            <tr>
              <th>Curso</th>
              <th>Segmento</th>
              <th>Turmas</th>
              <th>Alunos</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="curso in cursos" :key="curso.id">
              <td>{{ curso.titulo || '—' }}</td>
              <td>{{ curso.segmento || '—' }}</td>
              <td>{{ curso.turmas ?? 0 }}</td>
              <td>{{ curso.alunos ?? 0 }}</td>
              <td>
                <button type="button" class="btn-link" @click="verCurso(curso)">Ver curso</button>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="meta.total" class="eixos-paginacao" role="navigation" aria-label="Paginação dos cursos">
          <span>Mostrando {{ meta.from || 0 }}-{{ meta.to || 0 }} de {{ meta.total }}</span>
          <div v-if="meta.last_page > 1" class="eixos-paginacao-acoes">
            <button type="button" class="btn-secondary" :disabled="meta.current_page <= 1" @click="irPagina(meta.current_page - 1)">Anterior</button>
            <span>Página {{ meta.current_page }} de {{ meta.last_page }}</span>
            <button type="button" class="btn-secondary" :disabled="meta.current_page >= meta.last_page" @click="irPagina(meta.current_page + 1)">Próxima</button>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>

<script src="../scripts/EixoDetalhe.js"></script>
<style scoped src="../../css/Eixos.css"></style>
