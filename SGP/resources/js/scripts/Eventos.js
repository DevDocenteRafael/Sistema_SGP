import { createCrudPage } from './createCrudPage';
import { UNIDADES } from './unidades';

const STATUS_LISTA = ['Planejado', 'Realizado', 'Cancelado'];
const ANOS = ['2024', '2025', '2026', '2027'];
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
const OPCOES_ACAO = ['Sim', 'Não'];

export default createCrudPage({
  name: 'Eventos',
  endpoint: '/api/eventos',
  showKey: 'evento',
  filtrosIniciais: {
    busca: '',
    ano: '',
    eixo: '',
    unidade: '',
    status: '',
    possui_acao_extensiva: '',
  },
  formVazio: () => ({
    nome: '',
    ano: '2025',
    data: '',
    unidade: '',
    eixo: '',
    quantidade_pessoas: null,
    equipe: '',
    possui_acao_extensiva: 'Não',
    acao_vinculada: '',
    status: 'Planejado',
    observacao: '',
  }),
  normalizarRegistro(registro) {
    return {
      ...registro,
      nome: registro.nome || '',
      ano: registro.ano ? String(registro.ano) : '',
      data: this.normalizarData(registro.data),
      unidade: registro.unidade || '',
      eixo: registro.eixo || '',
      quantidade_pessoas: registro.quantidade_pessoas ?? null,
      equipe: registro.equipe || '',
      possui_acao_extensiva: registro.possui_acao_extensiva || 'Não',
      acao_vinculada: registro.acao_vinculada || '',
      status: registro.status || '',
      observacao: registro.observacao || '',
    };
  },
  montarForm(registro) {
    return {
      nome: registro.nome ?? '',
      ano: registro.ano ? String(registro.ano) : '',
      data: this.normalizarData(registro.data),
      unidade: registro.unidade ?? '',
      eixo: registro.eixo ?? '',
      quantidade_pessoas: registro.quantidade_pessoas ?? null,
      equipe: registro.equipe ?? '',
      possui_acao_extensiva: registro.possui_acao_extensiva || 'Não',
      acao_vinculada: registro.acao_vinculada ?? '',
      status: registro.status || 'Planejado',
      observacao: registro.observacao ?? '',
    };
  },
  validarFormulario(form) {
    if (!form.nome?.trim() || !form.data) {
      return 'Preencha o nome e a data do evento.';
    }
    if (!form.unidade) return 'A unidade é obrigatória.';
    if (!form.eixo) return 'O eixo é obrigatório.';
    if (!form.status) return 'O status é obrigatório.';
    if (!form.possui_acao_extensiva) return 'Informe se possui ação extensiva.';
    return '';
  },
  montarPayload(form) {
    return {
      nome: form.nome.trim(),
      ano: form.ano || form.data.slice(0, 4),
      data: form.data,
      unidade: form.unidade,
      eixo: form.eixo,
      quantidade_pessoas: form.quantidade_pessoas === '' || form.quantidade_pessoas === null
        ? null
        : Number(form.quantidade_pessoas),
      equipe: form.equipe?.trim() || null,
      possui_acao_extensiva: form.possui_acao_extensiva,
      acao_vinculada: form.possui_acao_extensiva === 'Sim'
        ? (form.acao_vinculada?.trim() || null)
        : null,
      status: form.status,
      observacao: form.observacao?.trim() || null,
    };
  },
  labelExclusao: (r) => r.nome || r.id,
  mensagens: {
    semAcessoConsulta: 'Seu perfil não possui acesso para consultar eventos.',
    soConsulta: 'Seu perfil só permite consultar eventos.',
    semPermissaoEditar: 'Seu perfil não tem permissão para alterar eventos.',
    falhaCarregar: 'Não foi possível carregar os eventos.',
    falhaSalvar: 'Não foi possível salvar o evento.',
    falhaExcluir: 'Não foi possível excluir o evento.',
    confirmarExclusao: (r) => `Deseja excluir o evento "${r.nome || r.id}"?`,
  },
  aplicarMeta(meta) {
    if (Array.isArray(meta.status) && meta.status.length) this.statusLista = meta.status;
    if (Array.isArray(meta.anos) && meta.anos.length) this.anos = meta.anos.map(String);
    if (Array.isArray(meta.eixos) && meta.eixos.length) this.eixos = meta.eixos;
    if (Array.isArray(meta.unidades) && meta.unidades.length) this.unidades = meta.unidades;
    if (Array.isArray(meta.possui_acao_extensiva) && meta.possui_acao_extensiva.length) {
      this.opcoesAcao = meta.possui_acao_extensiva;
    }
    if (Array.isArray(meta.acoes_vinculaveis)) {
      this.acoesVinculaveis = meta.acoes_vinculaveis;
    }
  },
  extraData: () => ({
    statusLista: STATUS_LISTA,
    anos: ANOS,
    eixos: EIXOS,
    unidades: [...UNIDADES],
    opcoesAcao: OPCOES_ACAO,
    acoesVinculaveis: [],
  }),
  extraMethods: {
    preencherAnoDaData() {
      if (this.form.data && !this.form.ano) {
        this.form.ano = this.form.data.slice(0, 4);
      }
    },
    onMudarAcao() {
      if (this.form.possui_acao_extensiva !== 'Sim') {
        this.form.acao_vinculada = '';
      }
    },
    textoAcaoExtensiva(item) {
      if (item.possui_acao_extensiva === 'Sim') {
        return item.acao_vinculada ? `Sim - ${item.acao_vinculada}` : 'Sim';
      }
      return item.possui_acao_extensiva || 'Não';
    },
    badgeStatus(valor) {
      const mapa = {
        Planejado: 'badge-planejado',
        Realizado: 'badge-realizado',
        Cancelado: 'badge-cancelado',
      };
      return mapa[valor] || 'badge-planejado';
    },
  },
});
