import { createCrudPage } from './createCrudPage';
import {
  combinarValidacoes,
  formatarInteiroInput,
  formatarProcessoSeiInput,
  somenteAlfanumericoProcesso,
  textoObrigatorio,
  validarInteiro,
  validarProcessoSei,
} from '../utils/validacao';

const SEGMENTOS = [
  'Gastronomia',
  'Ambiente e Saúde',
  'Gestão e Moda',
  'Tecnologia e Economia Criativa',
  'Beleza e Cuidado Pessoal',
  'Turismo e Hospitalidade',
  'Comunicação e Audiovisual',
  'Artes e Design',
];

const EIXOS = [...SEGMENTOS];
const STATUS_LISTA = ['Pendente', 'Em andamento', 'Concluída', 'Cancelada'];
const ANOS = ['2024', '2025', '2026', '2027'];

export default createCrudPage({
  name: 'HorasPedagogicas',
  endpoint: '/api/horas-pedagogicas',
  listKey: 'horas',
  detailKey: 'horaDetalhe',
  showKey: 'horaPedagogica',
  filtrosIniciais: {
    busca: '',
    ano: '',
    eixo: '',
    status: '',
    ativo: '',
  },
  formVazio: () => ({
    matricula: '',
    pessoa: '',
    segmento: '',
    eixo: '',
    processo_sei: '',
    ano: '',
    motivo: '',
    status: '',
    ativo: 'true',
    observacao: '',
  }),
  normalizarRegistro(registro) {
    return {
      ...registro,
      processo_sei: registro.processo_sei || registro.processo_SEI || '',
      ano: registro.ano != null ? Number(registro.ano) : null,
      ativo: registro.ativo !== false,
      motivo: registro.motivo || '',
      observacao: registro.observacao || '',
    };
  },
  montarForm(hora) {
    return {
      matricula: hora.matricula ?? '',
      pessoa: hora.pessoa ?? '',
      segmento: hora.segmento ?? '',
      eixo: hora.eixo ?? '',
      processo_sei: hora.processo_sei ?? '',
      ano: hora.ano != null ? String(hora.ano) : '',
      motivo: hora.motivo ?? '',
      status: hora.status ?? '',
      ativo: hora.ativo !== false ? 'true' : 'false',
      observacao: hora.observacao ?? '',
    };
  },
  validarFormulario(form) {
    return combinarValidacoes(
      textoObrigatorio(form.matricula, 'A matrícula é obrigatória.'),
      validarInteiro(form.matricula, { rotulo: 'Matrícula', min: 1, max: 999999999 }),
      textoObrigatorio(form.pessoa, 'O nome da pessoa é obrigatório.'),
      textoObrigatorio(form.segmento, 'O segmento é obrigatório.'),
      textoObrigatorio(form.eixo, 'O eixo é obrigatório.'),
      validarProcessoSei(form.processo_sei, { obrigatorio: true }),
      validarInteiro(form.ano, { obrigatorio: true, rotulo: 'Ano', min: 1900, max: 2100 }),
      textoObrigatorio(form.motivo, 'O motivo é obrigatório.'),
      textoObrigatorio(form.status, 'O status é obrigatório.'),
    );
  },
  montarPayload(form) {
    return {
      matricula: form.matricula.trim(),
      pessoa: form.pessoa.trim(),
      segmento: form.segmento,
      eixo: form.eixo,
      processo_sei: somenteAlfanumericoProcesso(form.processo_sei).trim(),
      ano: Number(form.ano),
      motivo: form.motivo.trim(),
      status: form.status,
      ativo: form.ativo === true || form.ativo === 'true',
      observacao: form.observacao?.trim() || null,
    };
  },
  labelExclusao: (h) => h.pessoa || h.matricula || h.id,
  mensagens: {
    semAcessoConsulta: 'Seu perfil não possui acesso para consultar horas pedagógicas.',
    soConsulta: 'Seu perfil só permite consultar horas pedagógicas.',
    semPermissaoEditar: 'Seu perfil não tem permissão para alterar horas pedagógicas.',
    falhaCarregar: 'Não foi possível carregar as horas pedagógicas.',
    falhaSalvar: 'Não foi possível salvar a hora pedagógica.',
    falhaExcluir: 'Não foi possível excluir a hora pedagógica.',
    confirmarExclusao: (h) => `Deseja excluir o registro de ${h.pessoa || h.matricula || h.id}?`,
  },
  aplicarMeta(meta) {
    if (Array.isArray(meta.anos) && meta.anos.length) this.anos = meta.anos.map(String);
    if (Array.isArray(meta.eixos) && meta.eixos.length) this.eixos = meta.eixos;
    if (Array.isArray(meta.segmentos) && meta.segmentos.length) this.segmentos = meta.segmentos;
    if (Array.isArray(meta.status) && meta.status.length) this.statusLista = meta.status;
  },
  extraData: () => ({
    segmentos: SEGMENTOS,
    eixos: EIXOS,
    statusLista: STATUS_LISTA,
    anos: ANOS,
  }),
  methodAliases: {
    carregarHoras: 'carregarRegistros',
    salvarHora: 'salvarRegistro',
    excluirHora: 'excluirRegistro',
  },
  computedAliases: {
    podeEditarHoras: 'podeEditar',
    podeConsultarHoras: 'podeConsultar',
    totalHoras: 'totalRegistros',
    horasFiltradas: 'listaFiltrada',
  },
  extraComputed: {
    totalAtivos() {
      return this.listaFiltrada.filter((hora) => hora.ativo === true).length;
    },
  },
  extraMethods: {
    formatarProcessoSei: formatarProcessoSeiInput('processo_sei'),
    formatarMatricula: formatarInteiroInput('matricula'),
    rotuloAtivo(ativo) {
      return ativo ? 'Sim' : 'Não';
    },
    statusClass(status) {
      const mapa = {
        Concluída: 'badge-concluida',
        'Em andamento': 'badge-andamento',
        Pendente: 'badge-pendente',
        Cancelada: 'badge-cancelada',
      };
      return mapa[status] || 'badge-pendente';
    },
  },
});
