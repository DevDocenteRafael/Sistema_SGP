<template>
  <div class="eixos-page">
    <header class="eixos-header">
      <div>
        <h1>Eixos</h1>
        <p class="eixos-subtitle">
          Visão gerencial dos 5 eixos oficiais do ciclo
          <strong>{{ resumo.ciclo_nome || 'atual' }}</strong>.
          O cadastro de cursos fica em Cursos; aqui entram oferta e execução.
        </p>
      </div>
    </header>

    <div v-if="erro" class="alert alert-error">{{ erro }}</div>

    <div
      v-if="!carregando && totalPendentes > 0"
      class="alert alert-warning"
    >
      {{ totalPendentes }} registro(s) ainda sem um dos 5 eixos oficiais
      ({{ resumo.pendentes?.cursos || 0 }} curso(s) e {{ resumo.pendentes?.ofertas || 0 }} oferta(s)).
      Eles não aparecem nos cards e não foram excluídos — precisam de classificação.
    </div>

    <div
      v-if="!carregando && totalSemCorrespondencia > 0"
      class="alert alert-warning"
    >
      {{ totalSemCorrespondencia }} oferta(s) classificada(s) em um eixo, porém sem correspondência no catálogo de Cursos.
      Não viram curso novo — ficam como pendência até alguém cadastrar o curso ou ajustar o título.
    </div>

    <div v-if="carregando" class="eixos-vazio">Carregando eixos...</div>

    <section v-else class="eixos-grid" aria-label="Eixos oficiais">
      <button
        v-for="eixo in resumo.eixos"
        :key="eixo.id"
        type="button"
        class="eixo-card"
        :class="classeEixo(eixo.nome)"
        :aria-pressed="eixoSelecionadoId === eixo.id"
        @click="abrirDetalhes(eixo)"
      >
        <p class="eixo-card-kicker">Eixo oficial</p>
        <h2>{{ eixo.nome }}</h2>
        <dl class="eixo-card-metrics">
          <div>
            <dt>Cursos</dt>
            <dd>{{ eixo.cursos }}</dd>
          </div>
          <div>
            <dt>Ofertas</dt>
            <dd>{{ eixo.ofertas }}</dd>
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
        <span class="eixo-card-acao">Ver detalhes</span>
      </button>
    </section>

    <section
      v-if="!carregando && totalPendentes > 0"
      class="eixos-pendentes"
      aria-label="Registros sem eixo oficial"
    >
      <h2>Não classificados</h2>
      <p>
        Estes registros não entram nos cinco cards. Não foram excluídos —
        ficam pendentes até serem associados a um eixo oficial.
      </p>
      <table class="eixos-table">
        <thead>
          <tr>
            <th>Tipo</th>
            <th>Registro</th>
            <th>Valor original</th>
            <th>Segmento</th>
            <th>Programa</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in (resumo.pendentes?.amostra || [])" :key="item.tipo + '-' + item.id">
            <td>{{ item.tipo === 'curso' ? 'Curso' : 'Oferta' }}</td>
            <td>{{ item.nome || '—' }}</td>
            <td>{{ item.eixo_original || '—' }}</td>
            <td>{{ item.segmento || '—' }}</td>
            <td>{{ item.programa || '—' }}</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section
      v-if="!carregando && totalSemCorrespondencia > 0"
      class="eixos-pendentes"
      aria-label="Ofertas sem correspondência no catálogo"
    >
      <h2>Ofertas sem correspondência</h2>
      <p>
        Estas ofertas já têm eixo oficial, mas o título não bateu de forma inequívoca com um curso do catálogo.
        Não foram excluídas e nenhum curso novo foi criado a partir delas.
      </p>
      <table class="eixos-table">
        <thead>
          <tr>
            <th>Oferta</th>
            <th>Eixo</th>
            <th>Segmento</th>
            <th>Programa</th>
            <th>Código</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="item in (resumo.pendentes?.amostra_sem_correspondencia || [])"
            :key="'sc-' + item.id"
          >
            <td>{{ item.nome || '—' }}</td>
            <td>{{ item.eixo_original || '—' }}</td>
            <td>{{ item.segmento || '—' }}</td>
            <td>{{ item.programa || '—' }}</td>
            <td>{{ item.codigo || '—' }}</td>
          </tr>
        </tbody>
      </table>
    </section>

    <div
      v-if="detalheAberto && detalhes"
      class="modal-overlay"
      @click.self="fecharDetalhes"
    >
      <div class="modal-detalhes" role="dialog" aria-modal="true" :aria-labelledby="'eixo-detalhe-titulo'">
        <div class="modal-detalhes-header">
          <div>
            <p class="eixo-card-kicker">Detalhe do eixo</p>
            <h2 id="eixo-detalhe-titulo">{{ detalhes.eixo?.nome }}</h2>
          </div>
          <button type="button" class="btn-fechar-x" title="Fechar" aria-label="Fechar" @click="fecharDetalhes">×</button>
        </div>

        <div class="modal-form-wrap">
          <dl class="eixo-card-metrics eixo-detalhe-kpis">
            <div>
              <dt>Cursos no catálogo</dt>
              <dd>{{ detalhes.eixo?.cursos ?? 0 }}</dd>
            </div>
            <div>
              <dt>Ofertas</dt>
              <dd>{{ detalhes.eixo?.ofertas ?? 0 }}</dd>
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

          <h3>Segmentos</h3>
          <div v-if="!detalhes.segmentos?.length" class="eixos-vazio-bloco">Nenhum segmento cadastrado neste eixo.</div>
          <table v-else class="eixos-table">
            <thead>
              <tr>
                <th>Segmento</th>
                <th>Cursos</th>
                <th>Ofertas</th>
                <th>Turmas</th>
                <th>Alunos</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="segmento in detalhes.segmentos" :key="segmento.id">
                <td>{{ segmento.nome }}</td>
                <td>{{ segmento.cursos }}</td>
                <td>{{ segmento.ofertas }}</td>
                <td>{{ segmento.turmas }}</td>
                <td>{{ segmento.alunos }}</td>
              </tr>
            </tbody>
          </table>

          <h3>Cursos do catálogo</h3>
          <div v-if="!detalhes.cursos?.length" class="eixos-vazio-bloco">Nenhum curso deste eixo no ciclo.</div>
          <table v-else class="eixos-table">
            <thead>
              <tr>
                <th>Curso</th>
                <th>Segmento</th>
                <th>Programa</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="curso in detalhes.cursos" :key="'c-'+curso.id">
                <td>{{ curso.titulo || '—' }}</td>
                <td>{{ curso.segmento || '—' }}</td>
                <td>{{ curso.programa || '—' }}</td>
                <td>{{ curso.status || '—' }}</td>
              </tr>
            </tbody>
          </table>

          <h3>Ofertas / turmas</h3>
          <div v-if="!detalhes.ofertas?.length" class="eixos-vazio-bloco">
            Nenhuma oferta importada neste ciclo. Importe a aba “Quantidade de cursos por eixo” depois do catálogo de Cursos.
          </div>
          <table v-else class="eixos-table">
            <thead>
              <tr>
                <th>Curso</th>
                <th>Segmento</th>
                <th>Programa</th>
                <th>Código</th>
                <th>CH</th>
                <th>Turmas</th>
                <th>Alunos</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="oferta in detalhes.ofertas" :key="oferta.id">
                <td>{{ oferta.curso || '—' }}</td>
                <td>{{ oferta.segmento || '—' }}</td>
                <td>{{ oferta.programa || '—' }}</td>
                <td>{{ oferta.codigo || '—' }}</td>
                <td>{{ oferta.ch || '—' }}</td>
                <td>{{ oferta.turmas || '—' }}</td>
                <td>{{ oferta.alunos || '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="modal-detalhes-actions">
          <button type="button" class="btn-secondary" @click="fecharDetalhes">Fechar</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script src="../scripts/Eixos.js"></script>
<style scoped src="../../css/Eixos.css"></style>
