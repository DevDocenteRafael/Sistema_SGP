import { createCrudPage } from './createCrudPage';

const FILTROS_VAZIOS = {
  busca: '',
  ano: '',
  segmento: '',
  tipo: '',
  mes: '',
  status: '',
  situacao: '',
};

export default createCrudPage({
  name: 'PlanoDeMetas',
  endpoint: '/api/plano-de-metas',
  showKey: 'planoDeMeta',
  errorKey: 'mensagemErro',
  formErrorKey: 'mensagemErro',
  useDetalheAberto: true,
  checkConsultar: false,
  carregandoInicial: false,
  debounceOnLoad: true,
  usarCicloContexto: true,
  cicloModulo: 'metas',
  filtrosIniciais: FILTROS_VAZIOS,
  formVazio: () => ({
    segmento: '',
    tipo: '',
    mes_entrega: '',
    curso: '',
    numero_sei: '',
    codigo_sig: '',
    status: '',
    origem: 'Plano de Metas',
    status_final: '',
    observacao: '',
  }),
  normalizarRegistro(registro) {
    return {
      id: registro.id,
      segmento: registro.segmento || '—',
      curso: registro.curso || '—',
      tipo: registro.tipo || '—',
      sei: registro.numero_sei || registro.sei || '—',
      sig: registro.codigo_sig || registro.sig || '—',
      mesEntrega: registro.mes_entrega || registro.mesEntrega || '—',
      status: registro.status || '—',
      origem: registro.origem || 'Plano de Metas',
      observacao: registro.observacao || '—',
      statusFinal: registro.status_final || registro.statusFinal || '—',
      numero_sei: registro.numero_sei || registro.sei || '',
      codigo_sig: registro.codigo_sig || registro.sig || '',
      mes_entrega: registro.mes_entrega || registro.mesEntrega || '',
      status_final: registro.status_final || registro.statusFinal || '',
    };
  },
  montarForm(registro) {
    return {
      segmento: registro.segmento === '—' ? '' : registro.segmento || '',
      tipo: registro.tipo === '—' ? '' : registro.tipo || '',
      mes_entrega: registro.mesEntrega === '—' ? (registro.mes_entrega || '') : registro.mesEntrega || '',
      curso: registro.curso === '—' ? '' : registro.curso || '',
      numero_sei: registro.sei === '—' ? (registro.numero_sei || '') : registro.sei || '',
      codigo_sig: registro.sig === '—' ? (registro.codigo_sig || '') : registro.sig || '',
      status: registro.status === '—' ? '' : registro.status || '',
      origem: registro.origem || 'Plano de Metas',
      status_final: registro.statusFinal === '—'
        ? (registro.status_final || '')
        : registro.statusFinal || '',
      observacao: registro.observacao === '—' ? '' : registro.observacao || '',
    };
  },
  validarFormulario(form) {
    if (!form.segmento?.trim()) return 'O segmento é obrigatório.';
    if (!form.curso?.trim()) return 'O curso é obrigatório.';
    if (!form.tipo?.trim()) return 'O tipo é obrigatório.';
    if (!form.numero_sei?.trim()) return 'Informe o número SEI.';
    if (!form.codigo_sig?.trim()) return 'Informe o código SIG.';
    if (!form.mes_entrega?.trim()) return 'Informe o mês de entrega.';
    if (!form.status?.trim()) return 'Informe o status do registro.';
    if (!form.status_final?.trim()) return 'Informe o status final.';
    return '';
  },
  montarPayload(form) {
    return {
      segmento: form.segmento,
      curso: form.curso,
      tipo: form.tipo,
      numero_sei: form.numero_sei,
      codigo_sig: form.codigo_sig,
      mes_entrega: form.mes_entrega,
      status: form.status,
      origem: form.origem,
      observacao: form.observacao,
      status_final: form.status_final,
      ano: Number(this.filtros.ano || new Date().getFullYear()),
    };
  },
  labelExclusao: (r) => r.curso || r.id,
  mensagens: {
    soConsulta: 'Seu perfil só permite consultar registros de Plano de Metas.',
    semPermissaoEditar: 'Seu perfil só permite consultar registros de Plano de Metas.',
    falhaCarregar: 'Não foi possível carregar os registros de Plano de Metas.',
    falhaSalvar: 'Não foi possível salvar o registro de Plano de Metas.',
    falhaExcluir: 'Não foi possível excluir o registro.',
    falhaDetalhe: 'Não foi possível carregar os detalhes do registro.',
    confirmarExclusao: (r) => `Excluir o registro "${r.curso}"?`,
  },
  aplicarMeta(meta) {
    if (Array.isArray(meta.anos) && meta.anos.length) this.anosDisponiveis = meta.anos;
    if (Array.isArray(meta.segmentos) && meta.segmentos.length) this.segmentosDisponiveis = meta.segmentos;
    if (Array.isArray(meta.tipos) && meta.tipos.length) this.tiposDisponiveis = meta.tipos;
    if (Array.isArray(meta.meses) && meta.meses.length) this.mesesDisponiveis = meta.meses;
    if (Array.isArray(meta.status) && meta.status.length) this.statusDisponiveis = meta.status;
    if (Array.isArray(meta.situacoes) && meta.situacoes.length) this.situacoesDisponiveis = meta.situacoes;
  },
  extraData: () => ({
    anosDisponiveis: ['2024', '2025', '2026', '2027'],
    segmentosDisponiveis: ['Infraestrutura', 'Educação'],
    tiposDisponiveis: ['QUALIFICAÇÃO', 'PRESENCIAL', 'HÍBRIDO'],
    mesesDisponiveis: [
      'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
      'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro',
    ],
    statusDisponiveis: ['PLANEJADO', 'EM ANÁLISE', 'EM ANDAMENTO', 'CONCLUÍDO'],
    situacoesDisponiveis: ['PENDENTE', 'EM ANALISE', 'ENTREGUE', 'PUBLICADO'],
  }),
  extraMethods: {
    statusClass(status) {
      const mapa = {
        PUBLICADO: 'badge-ativo',
        ENTREGUE: 'badge-revisao',
        'EM ANALISE': 'badge-suspenso',
        PENDENTE: 'badge-inativo',
      };
      return mapa[status] || 'badge-inativo';
    },
  },
});
