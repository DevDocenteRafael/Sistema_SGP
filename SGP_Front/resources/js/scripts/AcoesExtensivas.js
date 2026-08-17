import { createCrudPage } from './createCrudPage';

const PRIORIZACOES = ['Baixa', 'Média', 'Alta', 'Resolvido'];
const STATUS_LISTA = ['CPED', 'DEP', 'DIREG', 'NC'];
const TIPOS = ['Ação Extensiva'];
const EIXOS = [
  'Gastronomia e Turismo',
  'Gestão e Negócios',
  'Gestão e Comércio',
  'Saúde e Segurança',
  'Segurança',
];

export default createCrudPage({
  name: 'AcoesExtensivas',
  endpoint: '/api/acoes-extensivas',
  showKey: 'acaoExtensiva',
  filtrosIniciais: {
    busca: '',
    priorizacao: '',
    eixo: '',
    status: '',
    tipo: '',
  },
  formVazio: () => ({
    priorizacao: '',
    atribuido: '',
    eixo: '',
    numero_processo_sei: '',
    tipo: 'Ação Extensiva',
    assunto: '',
    objetivo: '',
    status: '',
    ultima_atualizacao: '',
  }),
  normalizarRegistro(registro) {
    return {
      ...registro,
      priorizacao: registro.priorizacao || '',
      atribuido: registro.atribuido || '',
      eixo: registro.eixo || '',
      numero_processo_sei: registro.numero_processo_sei || '',
      tipo: registro.tipo || 'Ação Extensiva',
      assunto: registro.assunto || '',
      objetivo: registro.objetivo || '',
      status: registro.status || '',
      ultima_atualizacao: this.normalizarData(registro.ultima_atualizacao),
    };
  },
  montarForm(registro) {
    return {
      priorizacao: registro.priorizacao ?? '',
      atribuido: registro.atribuido ?? '',
      eixo: registro.eixo ?? '',
      numero_processo_sei: registro.numero_processo_sei ?? '',
      tipo: registro.tipo || 'Ação Extensiva',
      assunto: registro.assunto ?? '',
      objetivo: registro.objetivo ?? '',
      status: registro.status ?? '',
      ultima_atualizacao: this.normalizarData(registro.ultima_atualizacao),
    };
  },
  validarFormulario(form) {
    if (!form.priorizacao) return 'A priorização é obrigatória.';
    if (!form.atribuido?.trim()) return 'Informe o responsável atribuído.';
    if (!form.eixo) return 'O eixo é obrigatório.';
    if (!form.numero_processo_sei?.trim()) return 'O número do processo SEI é obrigatório.';
    if (!form.tipo) return 'O tipo é obrigatório.';
    if (!form.assunto?.trim()) return 'O assunto é obrigatório.';
    if (!form.status) return 'O status é obrigatório.';
    return '';
  },
  montarPayload(form) {
    return {
      priorizacao: form.priorizacao,
      atribuido: form.atribuido.trim(),
      eixo: form.eixo,
      numero_processo_sei: form.numero_processo_sei.trim(),
      tipo: form.tipo || 'Ação Extensiva',
      assunto: form.assunto.trim(),
      objetivo: form.objetivo?.trim() || null,
      status: form.status,
      ultima_atualizacao: form.ultima_atualizacao || null,
    };
  },
  labelExclusao: (r) => r.numero_processo_sei || r.id,
  mensagens: {
    semAcessoConsulta: 'Seu perfil não possui acesso para consultar ações extensivas.',
    soConsulta: 'Seu perfil só permite consultar ações extensivas.',
    semPermissaoEditar: 'Seu perfil não tem permissão para alterar ações extensivas.',
    falhaCarregar: 'Não foi possível carregar as ações extensivas.',
    falhaSalvar: 'Não foi possível salvar a ação extensiva.',
    falhaExcluir: 'Não foi possível excluir a ação extensiva.',
    confirmarExclusao: (r) => `Deseja excluir a ação do processo ${r.numero_processo_sei || r.id}?`,
  },
  aplicarMeta(meta) {
    if (Array.isArray(meta.priorizacoes) && meta.priorizacoes.length) this.priorizacoes = meta.priorizacoes;
    if (Array.isArray(meta.status) && meta.status.length) this.statusLista = meta.status;
    if (Array.isArray(meta.tipos) && meta.tipos.length) this.tipos = meta.tipos;
    if (Array.isArray(meta.eixos) && meta.eixos.length) this.eixos = meta.eixos;
  },
  extraData: () => ({
    priorizacoes: PRIORIZACOES,
    statusLista: STATUS_LISTA,
    tipos: TIPOS,
    eixos: EIXOS,
  }),
  extraMethods: {
    badgePriorizacao(valor) {
      const mapa = {
        Baixa: 'badge-baixa',
        Média: 'badge-media',
        Alta: 'badge-alta',
        Resolvido: 'badge-resolvido',
      };
      return mapa[valor] || 'badge-media';
    },
    badgeStatus(valor) {
      const mapa = {
        CPED: 'badge-cped',
        DEP: 'badge-dep',
        DIREG: 'badge-direg',
        NC: 'badge-nc',
      };
      return mapa[valor] || 'badge-cped';
    },
  },
});
