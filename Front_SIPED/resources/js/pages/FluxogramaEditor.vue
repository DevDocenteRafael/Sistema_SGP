<template>
  <div class="flux-editor-page">
    <header class="flux-editor-top">
      <div class="flux-editor-top-row">
        <div class="flux-editor-heading">
          <router-link to="/app/ferramentas/fluxograma" class="flux-back">
            ← Voltar para a lista
          </router-link>
          <h1>{{ tituloPagina }}</h1>
          <p class="flux-subtitle">
            {{ rotuloTipo }}
            <span v-if="metaDescricao"> · {{ metaDescricao }}</span>
            <span v-if="!podeEditar && !acessoBloqueado"> · Somente leitura</span>
          </p>
        </div>

        <div v-if="!acessoBloqueado && !carregando" class="flux-editor-actions">
          <span class="flux-save-status" :class="statusClass">{{ statusTexto }}</span>

          <div v-if="podeEditar" class="flux-modo-group" role="group" aria-label="Modo de edição">
            <button
              type="button"
              class="btn-modo"
              :class="{ ativo: modoEdicao === 'selecionar' }"
              title="Selecionar e mover símbolos"
              @click="definirModo('selecionar')"
            >
              Mover
            </button>
            <button
              type="button"
              class="btn-modo btn-modo-seta"
              :class="{ ativo: modoEdicao === 'conectar' }"
              title="Ligar símbolos com seta (atalho C)"
              @click="definirModo('conectar')"
            >
              ↗ Conectar seta
            </button>
          </div>

          <button
            v-if="podeEditar"
            type="button"
            class="btn-sec"
            title="Desfazer (Ctrl+Z)"
            :disabled="!podeDesfazer"
            @click="desfazer"
          >
            Desfazer
          </button>

          <div class="flux-export-group">
            <button
              type="button"
              class="btn-sec"
              :disabled="exportando || !nodes.length"
              @click="exportar('png')"
            >
              {{ exportando === 'png' ? 'Exportando...' : 'PNG' }}
            </button>
            <button
              type="button"
              class="btn-sec"
              :disabled="exportando || !nodes.length"
              @click="exportar('svg')"
            >
              {{ exportando === 'svg' ? 'Exportando...' : 'SVG' }}
            </button>
          </div>

          <button
            v-if="podeEditar"
            type="button"
            class="btn-pri"
            title="Salvar (Ctrl+S)"
            :disabled="salvando || !sujo"
            @click="salvar()"
          >
            {{ salvando ? 'Salvando...' : 'Salvar' }}
          </button>
        </div>
      </div>
    </header>

    <div v-if="erro" class="alert alert-error">{{ erro }}</div>

    <div v-if="acessoBloqueado" class="alert alert-error">
      Você não possui autorização para consultar esta funcionalidade. Verifique seu perfil de acesso.
    </div>

    <div v-else-if="carregando" class="flux-loading">Carregando fluxograma...</div>

    <div v-else class="flux-editor-layout" :class="{ 'has-palette': podeEditar }">
      <aside v-if="podeEditar" class="flux-palette" aria-label="Símbolos do fluxograma">
        <h2>Símbolos</h2>
        <p class="flux-palette-hint">Arraste para o canvas · cores padronizadas</p>

        <div class="flux-palette-group">
          <p class="flux-palette-group-title">Essenciais</p>
          <button
            v-for="item in paletaEssencial"
            :key="item.type"
            type="button"
            class="flux-palette-item"
            :style="estiloItem(item.type)"
            draggable="true"
            @dragstart="onDragStart($event, item.type)"
          >
            <span class="flux-palette-shape" :class="`shape-${item.type}`" />
            <span>
              <strong>{{ item.label }}</strong>
              <small>{{ item.descricao }}</small>
            </span>
          </button>
        </div>

        <div class="flux-palette-group">
          <p class="flux-palette-group-title">Complementares</p>
          <button
            v-for="item in paletaExtra"
            :key="item.type"
            type="button"
            class="flux-palette-item"
            :style="estiloItem(item.type)"
            draggable="true"
            @dragstart="onDragStart($event, item.type)"
          >
            <span class="flux-palette-shape" :class="`shape-${item.type}`" />
            <span>
              <strong>{{ item.label }}</strong>
              <small>{{ item.descricao }}</small>
            </span>
          </button>
        </div>

        <div class="flux-palette-group">
          <p class="flux-palette-group-title">Conexões</p>
          <button
            type="button"
            class="flux-palette-item flux-connect-tool"
            :class="{ ativo: modoEdicao === 'conectar' }"
            @click="definirModo(modoEdicao === 'conectar' ? 'selecionar' : 'conectar')"
          >
            <span class="flux-connect-icon" aria-hidden="true">↗</span>
            <span>
              <strong>{{ modoEdicao === 'conectar' ? 'Modo seta ativo' : 'Conectar seta' }}</strong>
            </span>
          </button>
        </div>

        <div v-if="isFuncional" class="flux-palette-group">
          <p class="flux-palette-group-title">Raias</p>
          <p class="flux-palette-hint">Faixas por responsável / setor</p>
          <button
            type="button"
            class="flux-palette-item flux-raia-add"
            @click="adicionarRaia"
          >
            <span>+ Nova raia</span>
          </button>
        </div>
      </aside>

      <div
        ref="canvasWrap"
        class="flux-canvas-wrap"
        :class="{
          'modo-conectar': modoEdicao === 'conectar',
          'tem-origem': !!origemConexaoId,
        }"
        @dragover="onDragOver"
        @drop="onDrop"
      >
        <div v-if="podeEditar" class="flux-canvas-tools">
          <button type="button" class="flux-tool-btn" title="Enquadrar todo o fluxo" @click="enquadrarFluxo">
            Enquadrar
          </button>
        </div>

        <VueFlow
          :id="flowId"
          v-model:nodes="nodes"
          v-model:edges="edges"
          :node-types="nodeTypes"
          :default-edge-options="defaultEdgeOptions"
          :connection-mode="ConnectionMode.Loose"
          :nodes-draggable="podeEditar && modoEdicao === 'selecionar'"
          :nodes-connectable="podeEditar"
          :elements-selectable="true"
          :select-nodes-on-drag="false"
          :edges-focusable="true"
          :edges-updatable="podeEditar"
          :elevate-edges-on-select="true"
          :edge-updater-radius="20"
          :connect-on-click="false"
          :connection-line-style="{ stroke: '#0d9488', strokeWidth: 2.5 }"
          :delete-key-code="null"
          :fit-view-on-init="true"
          :fit-view-padding="0.18"
          :snap-to-grid="podeEditar"
          :snap-grid="[20, 20]"
          :min-zoom="0.2"
          :max-zoom="1.75"
          :default-viewport="{ x: 0, y: 0, zoom: 0.85 }"
          class="flux-canvas"
          @connect="onConnect"
          @edge-update="onEdgeUpdate"
          @node-click="onNodeClick"
          @node-drag-stop="onNodeDragStop"
          @edge-click="onEdgeClick"
          @pane-click="onPaneClick"
          @nodes-change="onNodesChange"
          @edges-change="onEdgesChange"
          @viewport-change-end="onViewportChangeEnd"
        >
          <Background :gap="18" pattern-color="#cbd5e1" />
          <Controls :show-interactive="false" />
          <MiniMap pannable zoomable />
        </VueFlow>
      </div>

      <aside class="flux-props" aria-label="Propriedades">
        <h2>Propriedades</h2>

        <div v-if="noSelecionado && noSelecionado.type === 'raia'" class="flux-props-body">
          <p class="flux-props-type">Raia</p>
          <label>
            Nome do setor / responsável
            <input
              v-model="labelEditavel"
              type="text"
              maxlength="80"
              :disabled="!podeEditar"
              @input="aplicarNomeRaia"
            />
          </label>
          <label>
            Altura da faixa
            <input
              v-model.number="alturaRaiaEditavel"
              type="number"
              :min="alturaRaiaMin"
              :max="alturaRaiaMax"
              step="20"
              :disabled="!podeEditar"
              @change="aplicarAlturaRaia"
            />
          </label>
          <button
            v-if="podeEditar && totalRaias > 1"
            type="button"
            class="btn-danger"
            @click="excluirRaiaSelecionada"
          >
            Excluir raia
          </button>
        </div>

        <div v-else-if="noSelecionado" class="flux-props-body">
          <p class="flux-props-type">{{ rotuloTipoNo(noSelecionado.type) }}</p>
          <label>
            Texto
            <input
              v-model="labelEditavel"
              type="text"
              maxlength="120"
              :disabled="!podeEditar"
              @input="aplicarDadosNo"
            />
          </label>
          <label v-if="isFuncional">
            Raia
            <SearchableSelect
              v-model="raiaEditavel"
              :options="listaRaias.map((raia) => ({ value: raia.id, label: raia.nome }))"
              :disabled="!podeEditar"
              @change="aplicarRaiaNo"
            />
          </label>
          <label>
            Responsável
            <input
              v-model="responsavelEditavel"
              type="text"
              maxlength="120"
              :disabled="!podeEditar"
              placeholder="Quem executa esta etapa"
              @input="aplicarDadosNo"
            />
          </label>
          <label>
            Observação
            <textarea
              v-model="observacaoEditavel"
              rows="3"
              maxlength="500"
              :disabled="!podeEditar"
              placeholder="Insumos, regras ou detalhe operacional"
              @input="aplicarDadosNo"
            />
          </label>
          <button
            v-if="podeEditar && noSelecionado.type !== 'raia'"
            type="button"
            class="btn-conectar-de"
            @click="iniciarConexaoDe(noSelecionado.id)"
          >
            ↗ Ligar seta a outro símbolo
          </button>
          <button
            v-if="podeEditar"
            type="button"
            class="btn-danger"
            @click="removerSelecionado"
          >
            Excluir etapa
          </button>
        </div>

        <div v-else-if="edgeSelecionada" class="flux-props-body">
          <p class="flux-props-type">Fluxo (seta)</p>
          <label>
            Rótulo (ex.: Sim / Não)
            <input
              v-model="edgeLabelEditavel"
              type="text"
              maxlength="40"
              :disabled="!podeEditar"
              @input="aplicarLabelEdge"
            />
          </label>
          <label>
            Sai de
            <SearchableSelect
              v-model="edgeSourceEditavel"
              :options="etapasParaConexao.map((etapa) => ({ value: etapa.id, label: rotuloEtapa(etapa) }))"
              :disabled="!podeEditar"
              @change="aplicarReligacaoEdge"
            />
          </label>
          <label>
            Lado de saída
            <SearchableSelect
              v-model="edgeSourceHandleEditavel"
              :options="ladosSaidaDisponiveis"
              :disabled="!podeEditar"
              @change="aplicarReligacaoEdge"
            />
          </label>
          <label>
            Entra em
            <SearchableSelect
              v-model="edgeTargetEditavel"
              :options="etapasParaConexao.map((etapa) => ({ value: etapa.id, label: rotuloEtapa(etapa) }))"
              :disabled="!podeEditar"
              @change="aplicarReligacaoEdge"
            />
          </label>
          <label>
            Lado de entrada
            <SearchableSelect
              v-model="edgeTargetHandleEditavel"
              :options="[
                { value: 'top', label: 'Cima' },
                { value: 'right', label: 'Direita' },
                { value: 'bottom', label: 'Baixo' },
                { value: 'left', label: 'Esquerda' },
              ]"
              :disabled="!podeEditar"
              @change="aplicarReligacaoEdge"
            />
          </label>
          <button
            v-if="podeEditar"
            type="button"
            class="btn-danger"
            @click="removerSelecionado"
          >
            Excluir conexão
          </button>
        </div>

        <div v-else class="flux-props-empty">
          <p>Selecione uma etapa ou conexão para editar.</p>
          <p class="flux-props-meta">{{ totalEtapas }} etapa(s) · {{ edges.length }} conexão(ões)<span v-if="isFuncional"> · {{ totalRaias }} raia(s)</span></p>
          <button
            v-if="podeEditar && !totalEtapas && !isFuncional"
            type="button"
            class="btn-template"
            @click="aplicarTemplate"
          >
            Inserir template Início → Processo → Fim
          </button>
        </div>

        <div class="flux-legenda" aria-label="Legenda dos símbolos">
          <h3>Legenda</h3>
          <div
            v-for="item in legenda"
            :key="item.type"
            class="flux-legenda-item"
            :style="estiloItem(item.type)"
          >
            <span class="flux-legenda-swatch" :class="`shape-${item.type}`" />
            <span>{{ item.label }}</span>
          </div>
          <p class="flux-legenda-note">
            Cada símbolo tem cor fixa para facilitar a leitura do processo.
          </p>
        </div>
      </aside>
    </div>
  </div>
</template>

<script setup>
import { computed, markRaw, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { MarkerType, VueFlow, useVueFlow, ConnectionMode } from '@vue-flow/core';
import { Background } from '@vue-flow/background';
import { Controls } from '@vue-flow/controls';
import { MiniMap } from '@vue-flow/minimap';

import '@vue-flow/core/dist/style.css';
import '@vue-flow/core/dist/theme-default.css';
import '@vue-flow/controls/dist/style.css';
import '@vue-flow/minimap/dist/style.css';

import { podeConsultarDados, podeEditarDados } from '../scripts/auth';
import InicioFimNode from '../components/fluxograma/nodes/InicioFimNode.vue';
import ProcessoNode from '../components/fluxograma/nodes/ProcessoNode.vue';
import DecisaoNode from '../components/fluxograma/nodes/DecisaoNode.vue';
import DocumentoNode from '../components/fluxograma/nodes/DocumentoNode.vue';
import PreDefinidoNode from '../components/fluxograma/nodes/PreDefinidoNode.vue';
import ManualNode from '../components/fluxograma/nodes/ManualNode.vue';
import EsperaNode from '../components/fluxograma/nodes/EsperaNode.vue';
import ConectorNode from '../components/fluxograma/nodes/ConectorNode.vue';
import RaiaNode from '../components/fluxograma/nodes/RaiaNode.vue';
import {
  CORES_SIMBOLOS,
  LEGENDA_SIMBOLOS,
  TIPOS_PALETA,
  criarDiagramaTemplate,
  criarNo,
  normalizarEdges,
  normalizarNos,
  paletaPorGrupo,
  serializarDiagrama,
} from '../components/fluxograma/tipos';
import {
  ALTURA_RAIA_MAX,
  ALTURA_RAIA_MIN,
  ALTURA_RAIA_PADRAO,
  criarRaiasPadrao,
  estabilizarLayoutRaias,
  extrairRaiasDosNos,
  montarNosComRaias,
  normalizarRaias,
  proximoIdRaia,
  raiaEmY,
} from '../components/fluxograma/raias';
import { exportarFluxograma } from '../components/fluxograma/exportar';
import {
  escolherHandlesPorPosicao,
  handleSaidaDecisao,
  normalizarHandleId,
} from '../components/fluxograma/conexoes';

const FLOW_ID = 'sgp-fluxograma-editor';
const MAX_HISTORICO = 30;

const route = useRoute();
const router = useRouter();

const fluxograma = ref(null);
const carregando = ref(true);
const salvando = ref(false);
const exportando = ref('');
const erro = ref('');
const podeEditarApi = ref(false);
const sujo = ref(false);
const nodes = ref([]);
const edges = ref([]);
const viewport = ref({ x: 0, y: 0, zoom: 1 });
const selectedNodeId = ref(null);
const selectedEdgeId = ref(null);
const labelEditavel = ref('');
const responsavelEditavel = ref('');
const observacaoEditavel = ref('');
const edgeLabelEditavel = ref('');
const edgeSourceEditavel = ref('');
const edgeTargetEditavel = ref('');
const edgeSourceHandleEditavel = ref('bottom');
const edgeTargetHandleEditavel = ref('top');
const raiaEditavel = ref('');
const alturaRaiaEditavel = ref(ALTURA_RAIA_PADRAO);
const alturaRaiaMin = ALTURA_RAIA_MIN;
const alturaRaiaMax = ALTURA_RAIA_MAX;
const ignorarMudancas = ref(false);
const canvasWrap = ref(null);
const historico = ref([]);
const modoEdicao = ref('selecionar');
const origemConexaoId = ref(null);
const saidaDecisaoPendente = ref(null);
let autosaveTimer = null;
let historicoTimer = null;
let ultimaChaveHistorico = '';

const paletaEssencial = paletaPorGrupo('essencial');
const paletaExtra = paletaPorGrupo('extra');
const legenda = LEGENDA_SIMBOLOS;
const flowId = FLOW_ID;

const nodeTypes = {
  inicio: markRaw(InicioFimNode),
  fim: markRaw(InicioFimNode),
  processo: markRaw(ProcessoNode),
  decisao: markRaw(DecisaoNode),
  documento: markRaw(DocumentoNode),
  predefinido: markRaw(PreDefinidoNode),
  manual: markRaw(ManualNode),
  espera: markRaw(EsperaNode),
  conector: markRaw(ConectorNode),
  raia: markRaw(RaiaNode),
};

const defaultEdgeOptions = {
  type: 'smoothstep',
  markerEnd: MarkerType.ArrowClosed,
  updatable: true,
  selectable: true,
  focusable: true,
  interactionWidth: 40,
  pathOptions: { borderRadius: 16, offset: 24 },
  style: { stroke: '#0f766e', strokeWidth: 2 },
};

const { project, addNodes, addEdges, removeNodes, removeEdges, toObject, setViewport, fitView, vueFlowRef, getNodes, updateEdge } = useVueFlow({
  id: FLOW_ID,
});

const acessoBloqueado = computed(() => !podeConsultarDados());
const podeEditar = computed(() => podeEditarDados() && podeEditarApi.value);
const tituloPagina = computed(() => fluxograma.value?.titulo || 'Fluxograma');
const isFuncional = computed(() => fluxograma.value?.tipo === 'funcional');
const rotuloTipo = computed(() => (isFuncional.value ? 'Funcional (raias)' : 'Linear'));
const metaDescricao = computed(() => fluxograma.value?.descricao || '');

const noSelecionado = computed(() => nodes.value.find((n) => n.id === selectedNodeId.value) || null);
const edgeSelecionada = computed(() => edges.value.find((e) => e.id === selectedEdgeId.value) || null);
const listaRaias = computed(() => extrairRaiasDosNos(nodes.value));
const totalRaias = computed(() => listaRaias.value.length);
const totalEtapas = computed(() => nodes.value.filter((n) => n.type !== 'raia').length);
const etapasParaConexao = computed(() => nodes.value.filter((n) => n.type !== 'raia'));
const ladosSaidaDisponiveis = computed(() => {
  const origem = nodes.value.find((n) => n.id === edgeSourceEditavel.value);
  if (origem?.type === 'decisao') {
    return [
      { value: 'sim', label: 'Direita (Sim)' },
      { value: 'nao', label: 'Baixo (Não)' },
      { value: 'left', label: 'Esquerda' },
      { value: 'top', label: 'Cima' },
    ];
  }
  return [
    { value: 'top', label: 'Cima' },
    { value: 'right', label: 'Direita' },
    { value: 'bottom', label: 'Baixo' },
    { value: 'left', label: 'Esquerda' },
  ];
});

const podeDesfazer = computed(() => podeEditar.value && historico.value.length > 1);

const statusTexto = computed(() => {
  if (salvando.value) return 'Salvando...';
  if (sujo.value) return 'Alterações não salvas';
  return 'Salvo';
});

const statusClass = computed(() => {
  if (salvando.value) return 'is-saving';
  if (sujo.value) return 'is-dirty';
  return 'is-saved';
});

function rotuloTipoNo(type) {
  return TIPOS_PALETA.find((t) => t.type === type)?.label || type;
}

function rotuloEtapa(etapa) {
  const texto = etapa?.data?.label || rotuloTipoNo(etapa?.type);
  return `${texto}`;
}

function estiloItem(type) {
  const cor = CORES_SIMBOLOS[type] || CORES_SIMBOLOS.processo;
  return {
    '--item-borda': cor.borda,
    '--item-fundo': cor.fundo,
  };
}

function limparSelecao() {
  selectedNodeId.value = null;
  selectedEdgeId.value = null;
  labelEditavel.value = '';
  responsavelEditavel.value = '';
  observacaoEditavel.value = '';
  edgeLabelEditavel.value = '';
  edgeSourceEditavel.value = '';
  edgeTargetEditavel.value = '';
  edgeSourceHandleEditavel.value = 'bottom';
  edgeTargetHandleEditavel.value = 'top';
  raiaEditavel.value = '';
  alturaRaiaEditavel.value = ALTURA_RAIA_PADRAO;
}

function cancelarConexaoPendente() {
  origemConexaoId.value = null;
  saidaDecisaoPendente.value = null;
}

function definirModo(modo) {
  if (!podeEditar.value) return;
  modoEdicao.value = modo === 'conectar' ? 'conectar' : 'selecionar';
  cancelarConexaoPendente();
  if (modoEdicao.value === 'conectar') {
    limparSelecao();
  }
}

function iniciarConexaoDe(nodeId) {
  if (!podeEditar.value || !nodeId) return;
  modoEdicao.value = 'conectar';
  origemConexaoId.value = nodeId;
  saidaDecisaoPendente.value = null;
  selectedNodeId.value = nodeId;
  selectedEdgeId.value = null;
}

function criarConexaoEntre(sourceId, targetId, sourceHandle = null, targetHandle = null) {
  if (!sourceId || !targetId || sourceId === targetId) return;

  const origem = nodes.value.find((n) => n.id === sourceId);
  const destino = nodes.value.find((n) => n.id === targetId);
  if (!origem || !destino) return;

  let sh = sourceHandle;
  let th = targetHandle;

  if (!sh || !th) {
    const auto = escolherHandlesPorPosicao(origem, destino);
    sh = sh || auto.sourceHandle;
    th = th || auto.targetHandle;
  }

  if (origem.type === 'decisao' && (sh === 'right' || !sh)) {
    sh = handleSaidaDecisao(origem, saidaDecisaoPendente.value || sh, edges.value);
  }

  sh = normalizarHandleId(sh, 'bottom');
  th = normalizarHandleId(th, 'top');

  const jaExiste = edges.value.some(
    (edge) => edge.source === sourceId
      && edge.target === targetId
      && normalizarHandleId(edge.sourceHandle, 'bottom') === sh,
  );
  if (jaExiste) {
    return;
  }

  let label = '';
  if (sh === 'sim') label = 'Sim';
  if (sh === 'nao') label = 'Não';

  addEdges([
    {
      id: `e-${sourceId}-${sh}-${targetId}-${Date.now()}`,
      source: sourceId,
      target: targetId,
      sourceHandle: sh,
      targetHandle: th,
      label,
      type: 'smoothstep',
      updatable: true,
      selectable: true,
      focusable: true,
      interactionWidth: 48,
      markerEnd: MarkerType.ArrowClosed,
    },
  ]);
  onGraphChange();
}

function resolverHandleOrigem(origem, destino) {
  if (origem?.type === 'decisao') {
    return handleSaidaDecisao(origem, saidaDecisaoPendente.value, edges.value);
  }
  if (destino) {
    return escolherHandlesPorPosicao(origem, destino).sourceHandle;
  }
  return 'bottom';
}

function snapshotEstado() {
  return {
    nodes: JSON.parse(JSON.stringify(nodes.value)),
    edges: JSON.parse(JSON.stringify(edges.value)),
  };
}

function chaveSnapshot(snap) {
  return JSON.stringify({
    nodes: snap.nodes,
    edges: snap.edges,
  });
}

function reiniciarHistorico() {
  const snap = snapshotEstado();
  historico.value = [snap];
  ultimaChaveHistorico = chaveSnapshot(snap);
}

function agendarHistorico() {
  if (!podeEditar.value || ignorarMudancas.value) return;

  window.clearTimeout(historicoTimer);
  historicoTimer = window.setTimeout(() => {
    const snap = snapshotEstado();
    const chave = chaveSnapshot(snap);
    if (chave === ultimaChaveHistorico) return;

    historico.value.push(snap);
    if (historico.value.length > MAX_HISTORICO) {
      historico.value.shift();
    }
    ultimaChaveHistorico = chave;
  }, 450);
}

function aplicarSnapshot(snap) {
  ignorarMudancas.value = true;
  nodes.value = snap.nodes || [];
  edges.value = normalizarEdges(snap.edges).map((edge) => ({
    ...edge,
    updatable: true,
    markerEnd: MarkerType.ArrowClosed,
  }));
  limparSelecao();
  nextTick(() => {
    ignorarMudancas.value = false;
  });
}

function desfazer() {
  if (!podeDesfazer.value) return;

  historico.value.pop();
  const anterior = historico.value[historico.value.length - 1];
  if (!anterior) return;

  aplicarSnapshot(anterior);
  ultimaChaveHistorico = chaveSnapshot(anterior);
  sujo.value = true;
  agendarAutosave();
}

function aplicarTemplate() {
  if (!podeEditar.value || isFuncional.value) return;

  const template = criarDiagramaTemplate();
  aplicarSnapshot({
    nodes: normalizarNos(template.nodes),
    edges: template.edges,
  });
  viewport.value = template.viewport;
  reiniciarHistorico();
  sujo.value = true;
  agendarAutosave();
  nextTick(() => fitView({ padding: 0.25 }));
}

function primeiraRaiaId() {
  return listaRaias.value[0]?.id || null;
}

function adicionarRaia() {
  if (!podeEditar.value || !isFuncional.value) return;

  const raiasAtuais = listaRaias.value;
  const nova = {
    id: proximoIdRaia(),
    nome: `Área / Setor ${raiasAtuais.length + 1}`,
    ordem: raiasAtuais.length,
    altura: ALTURA_RAIA_PADRAO,
  };
  const etapas = nodes.value
    .filter((n) => n.type !== 'raia')
    .map((n) => ({
      ...n,
      data: {
        ...n.data,
        raiaId: n.parentNode || n.data?.raiaId || '',
      },
    }));

  nodes.value = montarNosComRaias(etapas, [...raiasAtuais, nova]);
  selectedNodeId.value = nova.id;
  labelEditavel.value = nova.nome;
  onGraphChange();
  nextTick(() => fitView({ padding: 0.2 }));
}

function aplicarNomeRaia() {
  if (!podeEditar.value || !selectedNodeId.value) return;

  nodes.value = nodes.value.map((node) => {
    if (node.id !== selectedNodeId.value || node.type !== 'raia') return node;
    return {
      ...node,
      data: {
        ...node.data,
        label: labelEditavel.value,
      },
    };
  });
  onGraphChange();
}

function aplicarAlturaRaia() {
  if (!podeEditar.value || !selectedNodeId.value || !isFuncional.value) return;

  const altura = Math.min(
    ALTURA_RAIA_MAX,
    Math.max(ALTURA_RAIA_MIN, Number(alturaRaiaEditavel.value) || ALTURA_RAIA_PADRAO),
  );
  alturaRaiaEditavel.value = altura;

  nodes.value = nodes.value.map((node) => {
    if (node.id !== selectedNodeId.value || node.type !== 'raia') return node;
    return {
      ...node,
      data: {
        ...node.data,
        altura,
      },
      style: {
        ...(node.style || {}),
        height: `${altura}px`,
      },
    };
  });

  nodes.value = estabilizarLayoutRaias(nodes.value);
  onGraphChange();
}

function aplicarRaiaNo() {
  if (!podeEditar.value || !selectedNodeId.value || !raiaEditavel.value) return;

  nodes.value = nodes.value.map((node) => {
    if (node.id !== selectedNodeId.value || node.type === 'raia') return node;
    return {
      ...node,
      parentNode: raiaEditavel.value,
      extent: 'parent',
      expandParent: false,
      position: {
        x: Math.max(40, node.position?.x ?? 40),
        y: Math.max(40, Math.min(node.position?.y ?? 40, 120)),
      },
      data: {
        ...node.data,
        raiaId: raiaEditavel.value,
      },
    };
  });
  nodes.value = estabilizarLayoutRaias(nodes.value);
  onGraphChange();
}

function excluirRaiaSelecionada() {
  if (!podeEditar.value || !selectedNodeId.value || totalRaias.value <= 1) return;

  const idRemovido = selectedNodeId.value;
  const destino = listaRaias.value.find((r) => r.id !== idRemovido)?.id;
  if (!destino) return;

  const raiasRestantes = listaRaias.value.filter((r) => r.id !== idRemovido);
  const etapas = nodes.value
    .filter((n) => n.type !== 'raia')
    .map((n) => {
      const raiaId = (n.parentNode || n.data?.raiaId) === idRemovido
        ? destino
        : (n.parentNode || n.data?.raiaId || destino);

      return {
        ...n,
        data: {
          ...n.data,
          raiaId,
        },
      };
    });

  nodes.value = montarNosComRaias(etapas, raiasRestantes);
  limparSelecao();
  onGraphChange();
}

function onGraphChange() {
  if (ignorarMudancas.value) return;
  sujo.value = true;
  agendarAutosave();
  agendarHistorico();
}

function onNodesChange(changes) {
  if (ignorarMudancas.value) return;
  if (Array.isArray(changes) && changes.length && changes.every((c) => c.type === 'select')) {
    return;
  }
  onGraphChange();
}

function onEdgesChange(changes) {
  if (ignorarMudancas.value) return;
  if (Array.isArray(changes) && changes.length && changes.every((c) => c.type === 'select')) {
    return;
  }
  onGraphChange();
}

function sincronizarSelecaoVisual({ nodeId = null, edgeId = null } = {}) {
  ignorarMudancas.value = true;
  nodes.value = nodes.value.map((node) => ({
    ...node,
    selected: Boolean(nodeId && node.id === nodeId),
  }));
  edges.value = edges.value.map((edge) => ({
    ...edge,
    selected: Boolean(edgeId && edge.id === edgeId),
    selectable: true,
    focusable: true,
    updatable: true,
    interactionWidth: edge.interactionWidth || 48,
  }));
  nextTick(() => {
    ignorarMudancas.value = false;
  });
}

function selecionarAresta(edge) {
  if (!edge) return;

  modoEdicao.value = 'selecionar';
  cancelarConexaoPendente();
  selectedEdgeId.value = edge.id;
  selectedNodeId.value = null;
  edgeLabelEditavel.value = edge.label || '';
  edgeSourceEditavel.value = edge.source || '';
  edgeTargetEditavel.value = edge.target || '';
  edgeSourceHandleEditavel.value = normalizarHandleId(edge.sourceHandle, 'bottom');
  edgeTargetHandleEditavel.value = normalizarHandleId(edge.targetHandle, 'top');
  sincronizarSelecaoVisual({ edgeId: edge.id });
}

function estaEditandoTexto(alvo) {
  if (!alvo || !(alvo instanceof HTMLElement)) return false;
  const tag = alvo.tagName;
  return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || alvo.isContentEditable;
}

function onAtalhoTeclado(event) {
  if (acessoBloqueado.value || carregando.value) return;

  const ctrl = event.ctrlKey || event.metaKey;
  const tecla = event.key.toLowerCase();

  if ((tecla === 'delete' || tecla === 'backspace') && !estaEditandoTexto(event.target)) {
    if (podeEditar.value && (selectedEdgeId.value || selectedNodeId.value)) {
      event.preventDefault();
      removerSelecionado();
    }
    return;
  }

  if (tecla === 'escape' && modoEdicao.value === 'conectar') {
    if (origemConexaoId.value) {
      cancelarConexaoPendente();
    } else {
      definirModo('selecionar');
    }
    return;
  }

  if (!ctrl && tecla === 'c' && !estaEditandoTexto(event.target) && podeEditar.value) {
    event.preventDefault();
    definirModo(modoEdicao.value === 'conectar' ? 'selecionar' : 'conectar');
    return;
  }

  if (ctrl && tecla === 's') {
    event.preventDefault();
    if (podeEditar.value) {
      salvar();
    }
    return;
  }

  if (ctrl && tecla === 'z' && !event.shiftKey) {
    if (estaEditandoTexto(event.target)) return;
    event.preventDefault();
    desfazer();
  }
}

function agendarAutosave() {
  if (!podeEditar.value) return;
  window.clearTimeout(autosaveTimer);
  autosaveTimer = window.setTimeout(() => {
    if (sujo.value && !salvando.value) {
      salvar({ silencioso: true });
    }
  }, 1800);
}

function enquadrarFluxo() {
  fitView({ padding: 0.16, duration: 280 });
}

function onDragStart(event, type) {
  if (!podeEditar.value) return;
  event.dataTransfer?.setData('application/vueflow', type);
  event.dataTransfer.effectAllowed = 'move';
}

function onDragOver(event) {
  if (!podeEditar.value) return;
  event.preventDefault();
  if (event.dataTransfer) {
    event.dataTransfer.dropEffect = 'move';
  }
}

function onDrop(event) {
  if (!podeEditar.value) return;
  event.preventDefault();

  const type = event.dataTransfer?.getData('application/vueflow');
  if (!type) return;

  const bounds = (canvasWrap.value || event.currentTarget).getBoundingClientRect();
  const position = project({
    x: event.clientX - bounds.left,
    y: event.clientY - bounds.top,
  });

  let raiaId = '';
  if (isFuncional.value) {
    raiaId = raiaEmY(nodes.value, position.y) || primeiraRaiaId() || '';
  }

  const novo = criarNo(type, {
    x: Math.max(40, position.x),
    y: Math.max(40, isFuncional.value ? 50 : position.y),
  }, {
    raiaId,
  });

  // Em modo funcional, position do drop é absoluta; converte para relativo à raia
  if (isFuncional.value && novo.parentNode) {
    const pai = nodes.value.find((n) => n.id === novo.parentNode);
    if (pai) {
      const alturaPai = Number(pai.data?.altura || ALTURA_RAIA_PADRAO);
      novo.position = {
        x: Math.max(40, position.x - (pai.position?.x ?? 0)),
        y: Math.max(40, Math.min(alturaPai - 72, position.y - (pai.position?.y ?? 0))),
      };
      novo.expandParent = false;
    }
  }

  addNodes([novo]);
  if (isFuncional.value) {
    nextTick(() => {
      nodes.value = estabilizarLayoutRaias(nodes.value);
    });
  }
  selectedNodeId.value = novo.id;
  selectedEdgeId.value = null;
  labelEditavel.value = novo.data.label;
  responsavelEditavel.value = '';
  observacaoEditavel.value = '';
  raiaEditavel.value = novo.data.raiaId || '';
  onGraphChange();
}

function onConnect(params) {
  if (!podeEditar.value) return;

  const sourceHandle = normalizarHandleId(params.sourceHandle, 'bottom');
  const targetHandle = normalizarHandleId(params.targetHandle, 'top');

  let label = '';
  if (sourceHandle === 'sim') label = 'Sim';
  if (sourceHandle === 'nao') label = 'Não';

  addEdges([
    {
      ...params,
      id: `e-${params.source}-${sourceHandle}-${params.target}-${Date.now()}`,
      sourceHandle,
      targetHandle,
      label,
      type: 'smoothstep',
      updatable: true,
      selectable: true,
      focusable: true,
      interactionWidth: 48,
      markerEnd: MarkerType.ArrowClosed,
    },
  ]);
  onGraphChange();
}

function onEdgeUpdate({ edge, connection }) {
  if (!podeEditar.value || !edge || !connection) return;

  const sourceHandle = normalizarHandleId(connection.sourceHandle, edge.sourceHandle || 'bottom');
  const targetHandle = normalizarHandleId(connection.targetHandle, edge.targetHandle || 'top');

  let label = edge.label || '';
  if (sourceHandle === 'sim') label = label || 'Sim';
  if (sourceHandle === 'nao') label = label || 'Não';

  updateEdge(edge, {
    ...connection,
    sourceHandle,
    targetHandle,
  });

  // Garante rótulo e updatable após religar
  edges.value = edges.value.map((item) => {
    if (item.id !== edge.id) return item;
    return {
      ...item,
      source: connection.source,
      target: connection.target,
      sourceHandle,
      targetHandle,
      label,
      updatable: true,
      markerEnd: MarkerType.ArrowClosed,
    };
  });

  if (selectedEdgeId.value === edge.id) {
    edgeSourceEditavel.value = connection.source;
    edgeTargetEditavel.value = connection.target;
    edgeSourceHandleEditavel.value = sourceHandle;
    edgeTargetHandleEditavel.value = targetHandle;
    edgeLabelEditavel.value = label;
  }

  onGraphChange();
}

function onNodeDragStop({ node }) {
  if (!isFuncional.value || !podeEditar.value) return;

  // Raias nunca devem se mover; símbolos não podem expandir a faixa
  if (node?.type === 'raia') {
    nodes.value = estabilizarLayoutRaias(nodes.value);
    return;
  }

  nodes.value = nodes.value.map((item) => {
    if (item.id !== node.id) {
      if (item.type === 'raia') {
        return {
          ...item,
          draggable: false,
          expandParent: false,
        };
      }
      return {
        ...item,
        expandParent: false,
      };
    }

    return {
      ...item,
      ...node,
      expandParent: false,
      extent: 'parent',
      parentNode: item.parentNode || item.data?.raiaId,
      data: {
        ...item.data,
        ...(node.data || {}),
        raiaId: item.parentNode || item.data?.raiaId || '',
      },
    };
  });

  nodes.value = estabilizarLayoutRaias(nodes.value);
  onGraphChange();
}

function onNodeClick({ node }) {
  if (modoEdicao.value === 'conectar' && podeEditar.value) {
    if (node.type === 'raia') return;

    if (!origemConexaoId.value) {
      origemConexaoId.value = node.id;
      saidaDecisaoPendente.value = null;
      selectedNodeId.value = node.id;
      selectedEdgeId.value = null;
      labelEditavel.value = node.data?.label || '';
      sincronizarSelecaoVisual({ nodeId: node.id });
      return;
    }

    if (node.id === origemConexaoId.value) {
      cancelarConexaoPendente();
      return;
    }

    const origem = nodes.value.find((n) => n.id === origemConexaoId.value);
    const destino = node;
    const auto = escolherHandlesPorPosicao(origem, destino);
    const handle = origem?.type === 'decisao'
      ? resolverHandleOrigem(origem, destino)
      : auto.sourceHandle;
    criarConexaoEntre(origemConexaoId.value, node.id, handle, auto.targetHandle);
    // Mantém modo seta para ligar várias em sequência
    origemConexaoId.value = null;
    saidaDecisaoPendente.value = null;
    selectedNodeId.value = node.id;
    selectedEdgeId.value = null;
    labelEditavel.value = node.data?.label || '';
    responsavelEditavel.value = node.data?.responsavel || '';
    observacaoEditavel.value = node.data?.observacao || '';
    raiaEditavel.value = node.parentNode || node.data?.raiaId || '';
    sincronizarSelecaoVisual({ nodeId: node.id });
    return;
  }

  selectedNodeId.value = node.id;
  selectedEdgeId.value = null;
  labelEditavel.value = node.data?.label || '';
  responsavelEditavel.value = node.data?.responsavel || '';
  observacaoEditavel.value = node.data?.observacao || '';
  raiaEditavel.value = node.parentNode || node.data?.raiaId || '';
  if (node.type === 'raia') {
    alturaRaiaEditavel.value = Number(node.data?.altura || ALTURA_RAIA_PADRAO);
  }
  sincronizarSelecaoVisual({ nodeId: node.id });
}

function onEdgeClick({ edge }) {
  // Sempre permite selecionar a seta (inclusive saindo do modo conectar)
  selecionarAresta(edge);
}

function onPaneClick() {
  if (modoEdicao.value === 'conectar') {
    cancelarConexaoPendente();
    return;
  }
  limparSelecao();
  sincronizarSelecaoVisual({});
}

function onViewportChangeEnd(view) {
  if (!view) return;
  viewport.value = {
    x: view.x,
    y: view.y,
    zoom: view.zoom,
  };
}

function aplicarDadosNo() {
  if (!podeEditar.value || !selectedNodeId.value) return;

  nodes.value = nodes.value.map((node) => {
    if (node.id !== selectedNodeId.value) return node;
    return {
      ...node,
      data: {
        ...node.data,
        label: labelEditavel.value,
        responsavel: responsavelEditavel.value,
        observacao: observacaoEditavel.value,
      },
    };
  });
  onGraphChange();
}

function aplicarLabelEdge() {
  if (!podeEditar.value || !selectedEdgeId.value) return;

  edges.value = edges.value.map((edge) => {
    if (edge.id !== selectedEdgeId.value) return edge;
    return {
      ...edge,
      label: edgeLabelEditavel.value,
    };
  });
  onGraphChange();
}

function aplicarReligacaoEdge() {
  if (!podeEditar.value || !selectedEdgeId.value) return;

  const source = edgeSourceEditavel.value;
  const target = edgeTargetEditavel.value;
  if (!source || !target || source === target) {
    erro.value = 'Origem e destino da seta precisam ser símbolos diferentes.';
    return;
  }

  let sourceHandle = normalizarHandleId(edgeSourceHandleEditavel.value, 'bottom');
  let targetHandle = normalizarHandleId(edgeTargetHandleEditavel.value, 'top');

  const origem = nodes.value.find((n) => n.id === source);
  if (origem?.type === 'decisao' && !['sim', 'nao', 'left', 'top'].includes(sourceHandle)) {
    sourceHandle = 'sim';
    edgeSourceHandleEditavel.value = sourceHandle;
  }

  let label = edgeLabelEditavel.value;
  if (sourceHandle === 'sim' && !label) label = 'Sim';
  if (sourceHandle === 'nao' && !label) label = 'Não';
  edgeLabelEditavel.value = label;

  edges.value = edges.value.map((edge) => {
    if (edge.id !== selectedEdgeId.value) return edge;
    return {
      ...edge,
      source,
      target,
      sourceHandle,
      targetHandle,
      label,
      updatable: true,
      markerEnd: MarkerType.ArrowClosed,
    };
  });
  onGraphChange();
}

function removerSelecionado() {
  if (!podeEditar.value) return;

  if (selectedEdgeId.value) {
    const id = selectedEdgeId.value;
    removeEdges([id]);
    limparSelecao();
    sincronizarSelecaoVisual({});
    onGraphChange();
    return;
  }

  if (selectedNodeId.value) {
    const no = nodes.value.find((n) => n.id === selectedNodeId.value);
    if (no?.type === 'raia') {
      excluirRaiaSelecionada();
      return;
    }

    removeNodes([selectedNodeId.value]);
    limparSelecao();
    sincronizarSelecaoVisual({});
    onGraphChange();
  }
}

async function exportar(formato) {
  if (exportando.value || !nodes.value.length) return;

  exportando.value = formato;
  erro.value = '';

  try {
    await nextTick();

    const el = vueFlowRef.value;
    if (!el) {
      throw new Error('Canvas do fluxograma não encontrado.');
    }

    await exportarFluxograma(el, {
      formato,
      titulo: tituloPagina.value,
      backgroundColor: '#f8fafc',
      nodes: getNodes.value?.length ? getNodes.value : nodes.value,
      edges: edges.value,
      raias: isFuncional.value ? listaRaias.value : [],
    });

  } catch (error) {
    erro.value = error?.message || `Não foi possível exportar em ${formato.toUpperCase()}.`;
  } finally {
    exportando.value = '';
  }
}

async function carregar() {
  carregando.value = true;
  erro.value = '';
  fluxograma.value = null;
  limparSelecao();
  sujo.value = false;
  window.clearTimeout(autosaveTimer);

  if (!podeConsultarDados()) {
    carregando.value = false;
    return;
  }

  try {
    const { data } = await window.axios.get(`/api/fluxogramas/${route.params.slug}`);
    fluxograma.value = data.data || null;
    podeEditarApi.value = Boolean(data.meta?.pode_editar);

    ignorarMudancas.value = true;
    const etapas = normalizarNos(fluxograma.value?.diagrama?.nodes);
    const raias = isFuncional.value
      ? normalizarRaias(fluxograma.value?.diagrama?.raias || criarRaiasPadrao())
      : [];

    nodes.value = isFuncional.value
      ? montarNosComRaias(etapas, raias)
      : etapas;

    edges.value = normalizarEdges(fluxograma.value?.diagrama?.edges).map((edge) => ({
      ...edge,
      updatable: true,
      selectable: true,
      focusable: true,
      interactionWidth: 48,
      markerEnd: MarkerType.ArrowClosed,
    }));
    viewport.value = {
      x: Number(fluxograma.value?.diagrama?.viewport?.x ?? 0),
      y: Number(fluxograma.value?.diagrama?.viewport?.y ?? 0),
      zoom: Number(fluxograma.value?.diagrama?.viewport?.zoom ?? 1),
    };

    await nextTick();
    await nextTick();

    if (nodes.value.length) {
      try {
        await fitView({ padding: 0.15, duration: 200 });
      } catch {
        await fitView({ padding: 0.15 });
      }
    }
  } catch (error) {
    erro.value = error.response?.data?.message
      || 'Não foi possível carregar o fluxograma.';
    if (error.response?.status === 404) {
      router.push('/app/ferramentas/fluxograma');
    }
  } finally {
    carregando.value = false;
    await nextTick();
    ignorarMudancas.value = false;
    sujo.value = false;
    reiniciarHistorico();
  }
}

async function salvar({ silencioso = false } = {}) {
  if (!podeEditar.value || salvando.value) return;

  salvando.value = true;
  if (!silencioso) {
    erro.value = '';
  }

  try {
    const estado = toObject();
    const raias = isFuncional.value ? extrairRaiasDosNos(estado.nodes || nodes.value) : [];
    const diagrama = serializarDiagrama(
      estado.nodes || nodes.value,
      estado.edges || edges.value,
      estado.viewport || viewport.value,
      raias
    );

    const { data } = await window.axios.put(`/api/fluxogramas/${route.params.slug}`, {
      diagrama,
    });

    fluxograma.value = {
      ...(fluxograma.value || {}),
      ...(data.fluxograma || {}),
    };
    viewport.value = diagrama.viewport;
    sujo.value = false;

  } catch (error) {
    erro.value = error.response?.data?.message
      || 'Não foi possível salvar o fluxograma.';
  } finally {
    salvando.value = false;
  }
}

watch(
  () => route.params.slug,
  () => {
    carregar();
  }
);

window.addEventListener('keydown', onAtalhoTeclado);

carregar();

onBeforeUnmount(() => {
  window.clearTimeout(autosaveTimer);
  window.clearTimeout(historicoTimer);
  window.removeEventListener('keydown', onAtalhoTeclado);
});
</script>

<style scoped src="../../css/FluxogramaEditor.css"></style>
<style src="../../css/FluxogramaNodes.css"></style>
