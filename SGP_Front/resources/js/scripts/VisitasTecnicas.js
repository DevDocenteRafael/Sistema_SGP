import { createCrudPage } from './createCrudPage';
import { UNIDADES } from './unidades';
import {
  combinarValidacoes,
  formatarProcessoSeiInput,
  somenteAlfanumericoProcesso,
  tamanhoMaximo,
  textoObrigatorio,
  validarData,
  validarOrdemDatas,
  validarProcessoSei,
} from '../utils/validacao';

const EIXOS = [
  'Gastronomia',
  'Ambiente e Saúde',
  'Gestão e Moda',
  'Tecnologia e Economia Criativa',
  'Beleza e Cuidado Pessoal',
  'Turismo e Hospitalidade',
  'Comunicação e Audiovisual',
  'Artes e Design',
];

const STATUS_LISTA = ['Pendente', 'Em andamento', 'Realizada', 'Cancelada', 'Atrasada'];
const ANOS = ['2024', '2025', '2026', '2027'];
const PRAZO_LISTA = [
  { value: 'dentro', label: 'Dentro do prazo' },
  { value: 'fora', label: 'Fora do prazo' },
];

export default createCrudPage({
  name: 'VisitasTecnicas',
  endpoint: '/api/visitas-tecnicas',
  listKey: 'visitas',
  detailKey: 'visitaDetalhe',
  showKey: 'visitaTecnica',
  filtrosIniciais: {
    busca: '',
    ano: '',
    unidade: '',
    eixo: '',
    status: '',
    prazo: '',
  },
  formVazio: () => ({
    unidade: '',
    eixo: '',
    processo_sei: '',
    data_solicitacao: '',
    data_visita_prevista: '',
    prazo_limite: '',
    status: '',
    responsavel: '',
    relatorio: '',
    observacao: '',
  }),
  normalizarRegistro(registro) {
    return {
      ...registro,
      processo_sei: registro.processo_sei || registro.processo_SEI || '',
      data_solicitacao: this.normalizarData(registro.data_solicitacao),
      data_visita_prevista: this.normalizarData(registro.data_visita_prevista),
      prazo_limite: this.normalizarData(registro.prazo_limite),
      relatorio: registro.relatorio || '',
      observacao: registro.observacao || '',
    };
  },
  montarForm(visita) {
    return {
      unidade: visita.unidade ?? '',
      eixo: visita.eixo ?? '',
      processo_sei: visita.processo_sei ?? '',
      data_solicitacao: this.normalizarData(visita.data_solicitacao),
      data_visita_prevista: this.normalizarData(visita.data_visita_prevista),
      prazo_limite: this.normalizarData(visita.prazo_limite),
      status: visita.status ?? '',
      responsavel: visita.responsavel ?? '',
      relatorio: visita.relatorio ?? '',
      observacao: visita.observacao ?? '',
    };
  },
  validarFormulario(form) {
    return combinarValidacoes(
      textoObrigatorio(form.unidade, 'A unidade é obrigatória.'),
      textoObrigatorio(form.eixo, 'O eixo é obrigatório.'),
      validarProcessoSei(form.processo_sei, { obrigatorio: true }),
      validarData(form.data_solicitacao, { obrigatorio: true, rotulo: 'Data de solicitação' }),
      validarData(form.data_visita_prevista, { obrigatorio: true, rotulo: 'Data prevista da visita' }),
      validarData(form.prazo_limite, { obrigatorio: true, rotulo: 'Prazo limite' }),
      validarOrdemDatas(
        form.data_solicitacao,
        form.data_visita_prevista,
        'A data prevista da visita deve ser igual ou posterior à data de solicitação.',
      ),
      validarOrdemDatas(
        form.data_solicitacao,
        form.prazo_limite,
        'O prazo limite deve ser igual ou posterior à data de solicitação.',
      ),
      textoObrigatorio(form.status, 'O status é obrigatório.'),
      textoObrigatorio(form.responsavel, 'O responsável é obrigatório.'),
      tamanhoMaximo(form.responsavel, 150, 'O responsável deve ter no máximo 150 caracteres.'),
      form.relatorio
        ? tamanhoMaximo(form.relatorio, 2000, 'O relatório deve ter no máximo 2000 caracteres.')
        : '',
      form.observacao
        ? tamanhoMaximo(form.observacao, 2000, 'A observação deve ter no máximo 2000 caracteres.')
        : '',
    );
  },
  montarPayload(form) {
    return {
      unidade: form.unidade,
      eixo: form.eixo,
      processo_sei: somenteAlfanumericoProcesso(form.processo_sei).trim(),
      data_solicitacao: form.data_solicitacao,
      data_visita_prevista: form.data_visita_prevista,
      prazo_limite: form.prazo_limite,
      status: form.status,
      responsavel: form.responsavel.trim(),
      relatorio: form.relatorio?.trim() || null,
      observacao: form.observacao?.trim() || null,
    };
  },
  labelExclusao: (v) => v.processo_sei || v.id,
  mensagens: {
    semAcessoConsulta: 'Seu perfil não possui acesso para consultar visitas técnicas.',
    soConsulta: 'Seu perfil só permite consultar visitas técnicas.',
    semPermissaoEditar: 'Seu perfil não tem permissão para alterar visitas técnicas.',
    falhaCarregar: 'Não foi possível carregar as visitas técnicas.',
    falhaSalvar: 'Não foi possível salvar a visita técnica.',
    falhaExcluir: 'Não foi possível excluir a visita técnica.',
    falhaDetalhe: 'Não foi possível carregar os detalhes da visita.',
    confirmarExclusao: (v) => `Deseja excluir a visita do processo ${v.processo_sei || v.id}?`,
  },
  aplicarMeta(meta) {
    if (Array.isArray(meta.anos) && meta.anos.length) this.anosDisponiveis = meta.anos;
    if (Array.isArray(meta.eixos) && meta.eixos.length) this.eixos = meta.eixos;
    if (Array.isArray(meta.status) && meta.status.length) this.statusLista = meta.status;
    if (Array.isArray(meta.unidades) && meta.unidades.length) this.unidades = meta.unidades;
    if (Array.isArray(meta.prazos) && meta.prazos.length) this.prazoLista = meta.prazos;
  },
  extraData: () => ({
    unidades: [...UNIDADES],
    eixos: EIXOS,
    statusLista: STATUS_LISTA,
    anosDisponiveis: ANOS,
    prazoLista: PRAZO_LISTA,
  }),
  methodAliases: {
    carregarVisitas: 'carregarRegistros',
    salvarVisita: 'salvarRegistro',
    excluirVisita: 'excluirRegistro',
  },
  computedAliases: {
    podeEditarVisita: 'podeEditar',
    podeConsultarVisita: 'podeConsultar',
    totalVisitas: 'totalRegistros',
    visitasFiltradas: 'listaFiltrada',
  },
  extraMethods: {
    formatarProcessoSei: formatarProcessoSeiInput('processo_sei'),
    statusClass(status) {
      const mapa = {
        Realizada: 'badge-realizada',
        'Em andamento': 'badge-andamento',
        Pendente: 'badge-pendente',
        Atrasada: 'badge-atrasada',
        Cancelada: 'badge-cancelada',
      };
      return mapa[status] || 'badge-pendente';
    },
  },
});
