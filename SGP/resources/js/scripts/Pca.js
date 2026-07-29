import { createCrudPage } from './createCrudPage';
import { UNIDADES } from './unidades';

const STATUS_LISTA = ['Vigente', 'Em análise', 'Suspenso', 'Previsto', 'Publicado', 'Ativo', 'Aprovado'];
const ANOS = ['2025', '2026'];
const SEMESTRES = ['2025/1', '2025/2', '2026/1', '2026/2'];
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

export default createCrudPage({
  name: 'Pca',
  endpoint: '/api/pcas',
  showKey: 'pca',
  carregandoInicial: false,
  filtrosIniciais: {
    busca: '',
    ano: '',
    semestre: '',
    unidade: '',
    eixo: '',
    status: '',
  },
  formVazio: () => ({
    ano: '2025',
    semestre: '',
    numero_sei: '',
    codigo_sig: '',
    titulo: '',
    eixo: '',
    unidade: '',
    carga_horaria: '',
    precificacao: '',
    valor_primeiro_modulo: '',
    valor: '',
    parcelas_boleto: '',
    valor_parcela_boleto: '',
    parcelas_cartao: '',
    valor_cartao: '',
    parcela_desc_20: '',
    parcela_desc_15: '',
    status: 'Vigente',
    observacao: '',
  }),
  normalizarRegistro(registro) {
    return {
      id: registro.id,
      ano: registro.ano ? String(registro.ano) : '',
      semestre: registro.semestre || '',
      numero_sei: registro.numero_sei || registro.sei || '',
      codigo_sig: registro.codigo_sig || registro.sig || '',
      sei: registro.numero_sei || registro.sei || '',
      sig: registro.codigo_sig || registro.sig || '',
      titulo: registro.titulo || registro.curso || '',
      eixo: registro.eixo || '',
      unidade: registro.unidade || '',
      carga_horaria: registro.carga_horaria || registro.ch || '',
      ch: registro.carga_horaria || registro.ch || '',
      precificacao: registro.precificacao || '',
      valor_primeiro_modulo: registro.valor_primeiro_modulo || '',
      valor: registro.valor || '',
      parcelas_boleto: registro.parcelas_boleto || '',
      valor_parcela_boleto: registro.valor_parcela_boleto || '',
      parcelas_cartao: registro.parcelas_cartao || '',
      valor_cartao: registro.valor_cartao || '',
      parcela_desc_20: registro.parcela_desc_20 || '',
      parcela_desc_15: registro.parcela_desc_15 || '',
      status: registro.status || '',
      observacao: registro.observacao || '',
    };
  },
  montarForm(item) {
    return {
      ano: item.ano || '2025',
      semestre: item.semestre || '',
      numero_sei: item.numero_sei || '',
      codigo_sig: item.codigo_sig || '',
      titulo: item.titulo || '',
      eixo: item.eixo || '',
      unidade: item.unidade || '',
      carga_horaria: item.carga_horaria || '',
      precificacao: item.precificacao || '',
      valor_primeiro_modulo: item.valor_primeiro_modulo || '',
      valor: item.valor || '',
      parcelas_boleto: item.parcelas_boleto || '',
      valor_parcela_boleto: item.valor_parcela_boleto || '',
      parcelas_cartao: item.parcelas_cartao || '',
      valor_cartao: item.valor_cartao || '',
      parcela_desc_20: item.parcela_desc_20 || '',
      parcela_desc_15: item.parcela_desc_15 || '',
      status: item.status || 'Vigente',
      observacao: item.observacao || '',
    };
  },
  validarFormulario(form) {
    if (!form.titulo?.trim()) return 'O título / curso é obrigatório.';
    if (!form.status?.trim()) return 'O status é obrigatório.';
    return '';
  },
  montarPayload(form) {
    return {
      ano: form.ano || null,
      semestre: form.semestre?.trim() || null,
      numero_sei: form.numero_sei?.trim() || null,
      codigo_sig: form.codigo_sig?.trim() || null,
      titulo: form.titulo.trim(),
      eixo: form.eixo || null,
      unidade: form.unidade || null,
      carga_horaria: form.carga_horaria?.trim() || null,
      precificacao: form.precificacao?.trim() || null,
      valor_primeiro_modulo: form.valor_primeiro_modulo?.trim() || null,
      valor: form.valor?.trim() || null,
      parcelas_boleto: form.parcelas_boleto?.trim() || null,
      valor_parcela_boleto: form.valor_parcela_boleto?.trim() || null,
      parcelas_cartao: form.parcelas_cartao?.trim() || null,
      valor_cartao: form.valor_cartao?.trim() || null,
      parcela_desc_20: form.parcela_desc_20?.trim() || null,
      parcela_desc_15: form.parcela_desc_15?.trim() || null,
      status: form.status,
      observacao: form.observacao?.trim() || null,
    };
  },
  labelExclusao: (r) => r.titulo || r.id,
  mensagens: {
    semAcessoConsulta: 'Seu perfil não possui acesso para consultar PCA.',
    soConsulta: 'Seu perfil só permite consultar registros.',
    semPermissaoEditar: 'Seu perfil não tem permissão para alterar PCA.',
    falhaCarregar: 'Não foi possível carregar os registros de PCA.',
    falhaSalvar: 'Não foi possível salvar o registro de PCA.',
    falhaExcluir: 'Não foi possível excluir o registro.',
    confirmarExclusao: (r) => `Excluir o registro "${r.titulo}"?`,
  },
  aplicarMeta(meta) {
    if (Array.isArray(meta.anos) && meta.anos.length) this.anos = meta.anos.map(String);
    if (Array.isArray(meta.semestres) && meta.semestres.length) this.semestres = meta.semestres;
    if (Array.isArray(meta.unidades) && meta.unidades.length) this.unidades = meta.unidades;
    if (Array.isArray(meta.eixos) && meta.eixos.length) this.eixos = meta.eixos;
    if (Array.isArray(meta.status) && meta.status.length) this.statusLista = meta.status;
  },
  extraData: () => ({
    anos: ANOS,
    semestres: SEMESTRES,
    unidades: [...UNIDADES],
    eixos: EIXOS,
    statusLista: STATUS_LISTA,
  }),
  extraMethods: {
    badgeStatus(status) {
      const valor = String(status || '').toUpperCase();
      if (valor.includes('VIGENTE') || valor.includes('ATIVO') || valor.includes('PUBLICADO') || valor.includes('APROVADO')) {
        return 'badge-vigente';
      }
      if (valor.includes('ANALISE') || valor.includes('ANÁLISE') || valor.includes('AGUARD')) {
        return 'badge-analise';
      }
      if (valor.includes('SUSPENS') || valor.includes('CANCEL') || valor.includes('INATIV')) {
        return 'badge-suspenso';
      }
      return 'badge-analise';
    },
  },
});
