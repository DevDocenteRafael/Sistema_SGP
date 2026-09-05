import { createCrudPage } from './createCrudPage';
import {
  combinarValidacoes,
  formatarDecimalInput,
  formatarInteiroInput,
  formatarProcessoSeiInput,
  normalizarTextoMonetario,
  somenteAlfanumericoProcesso,
  somenteDecimal,
  somenteNumeros,
  textoObrigatorio,
  validarDecimal,
  validarInteiro,
  validarProcessoSei,
  tamanhoMaximo,
} from '../utils/validacao';

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
  carregarUnidadesApi: true,
  endpoint: '/api/pcas',
  showKey: 'pca',
  carregandoInicial: false,
  usarCicloContexto: true,
  cicloModulo: 'pca',
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
    const limparMonetario = (valor) => somenteDecimal(normalizarTextoMonetario(valor));
    const limparInteiro = (valor) => {
      const texto = String(valor ?? '').trim();
      if (!texto || texto === '-' || texto === '—') return '';
      return somenteNumeros(texto);
    };

    return {
      ano: item.ano || '2025',
      semestre: item.semestre || '',
      numero_sei: item.numero_sei || '',
      codigo_sig: item.codigo_sig || '',
      titulo: item.titulo || '',
      eixo: item.eixo || '',
      unidade: item.unidade || '',
      carga_horaria: limparInteiro(item.carga_horaria),
      precificacao: item.precificacao || '',
      valor_primeiro_modulo: limparMonetario(item.valor_primeiro_modulo),
      valor: limparMonetario(item.valor),
      parcelas_boleto: limparInteiro(item.parcelas_boleto),
      valor_parcela_boleto: limparMonetario(item.valor_parcela_boleto),
      parcelas_cartao: limparInteiro(item.parcelas_cartao),
      valor_cartao: limparMonetario(item.valor_cartao),
      parcela_desc_20: limparMonetario(item.parcela_desc_20),
      parcela_desc_15: limparMonetario(item.parcela_desc_15),
      status: item.status || 'Vigente',
      observacao: item.observacao || '',
    };
  },
  validarFormulario(form) {
    return combinarValidacoes(
      textoObrigatorio(form.titulo, 'O título / curso é obrigatório.'),
      tamanhoMaximo(form.titulo, 255, 'O título / curso deve ter no máximo 255 caracteres.'),
      textoObrigatorio(form.status, 'O status é obrigatório.'),
      form.numero_sei ? validarProcessoSei(form.numero_sei, { rotulo: 'Número SEI' }) : '',
      form.codigo_sig
        ? tamanhoMaximo(form.codigo_sig, 100, 'O código SIG deve ter no máximo 100 caracteres.')
        : '',
      form.precificacao
        ? tamanhoMaximo(form.precificacao, 100, 'A precificação deve ter no máximo 100 caracteres.')
        : '',
      form.observacao
        ? tamanhoMaximo(form.observacao, 2000, 'A observação deve ter no máximo 2000 caracteres.')
        : '',
      form.ano ? validarInteiro(form.ano, { rotulo: 'Ano', min: 1900, max: 2100 }) : '',
      form.carga_horaria ? validarInteiro(form.carga_horaria, { rotulo: 'Carga horária', min: 1, max: 99999 }) : '',
      form.parcelas_boleto ? validarInteiro(form.parcelas_boleto, { rotulo: 'Parcelas boleto', min: 1, max: 999 }) : '',
      form.parcelas_cartao ? validarInteiro(form.parcelas_cartao, { rotulo: 'Parcelas cartão', min: 1, max: 999 }) : '',
      form.valor ? validarDecimal(form.valor, { rotulo: 'Valor' }) : '',
      form.valor_primeiro_modulo ? validarDecimal(form.valor_primeiro_modulo, { rotulo: 'Valor do 1º módulo' }) : '',
      form.valor_parcela_boleto ? validarDecimal(form.valor_parcela_boleto, { rotulo: 'Valor parcela boleto' }) : '',
      form.valor_cartao ? validarDecimal(form.valor_cartao, { rotulo: 'Valor cartão' }) : '',
      form.parcela_desc_20 ? validarDecimal(form.parcela_desc_20, { rotulo: 'Parcela com desconto 20%' }) : '',
      form.parcela_desc_15 ? validarDecimal(form.parcela_desc_15, { rotulo: 'Parcela com desconto 15%' }) : '',
    );
  },
  montarPayload(form) {
    const normalizarInteiro = (valor) => {
      const digitos = somenteNumeros(valor);
      return digitos || null;
    };
    const normalizarMonetario = (valor) => {
      const texto = somenteDecimal(normalizarTextoMonetario(valor));
      return texto || null;
    };
    const normalizarProcesso = (valor) => somenteAlfanumericoProcesso(valor).trim();

    return {
      ano: form.ano || null,
      semestre: form.semestre?.trim() || null,
      numero_sei: normalizarProcesso(form.numero_sei) || null,
      codigo_sig: form.codigo_sig?.trim() || null,
      titulo: form.titulo.trim(),
      eixo: form.eixo || null,
      unidade: form.unidade || null,
      carga_horaria: normalizarInteiro(form.carga_horaria),
      precificacao: form.precificacao?.trim() || null,
      valor_primeiro_modulo: normalizarMonetario(form.valor_primeiro_modulo),
      valor: normalizarMonetario(form.valor),
      parcelas_boleto: normalizarInteiro(form.parcelas_boleto),
      valor_parcela_boleto: normalizarMonetario(form.valor_parcela_boleto),
      parcelas_cartao: normalizarInteiro(form.parcelas_cartao),
      valor_cartao: normalizarMonetario(form.valor_cartao),
      parcela_desc_20: normalizarMonetario(form.parcela_desc_20),
      parcela_desc_15: normalizarMonetario(form.parcela_desc_15),
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
    if (Array.isArray(meta.eixos) && meta.eixos.length) this.eixos = meta.eixos;
    if (Array.isArray(meta.status) && meta.status.length) this.statusLista = meta.status;
  },
  extraData: () => ({
    anos: ANOS,
    semestres: SEMESTRES,
    unidades: [],
    eixos: EIXOS,
    statusLista: STATUS_LISTA,
  }),
  extraMethods: {
    formatarNumeroSei: formatarProcessoSeiInput('numero_sei'),
    formatarCargaHoraria: formatarInteiroInput('carga_horaria', { maxDigitos: 5 }),
    formatarParcelasBoleto: formatarInteiroInput('parcelas_boleto', { maxDigitos: 3 }),
    formatarParcelasCartao: formatarInteiroInput('parcelas_cartao', { maxDigitos: 3 }),
    formatarValor: formatarDecimalInput('valor'),
    formatarValorPrimeiroModulo: formatarDecimalInput('valor_primeiro_modulo'),
    formatarValorParcelaBoleto: formatarDecimalInput('valor_parcela_boleto'),
    formatarValorCartao: formatarDecimalInput('valor_cartao'),
    formatarParcelaDesc20: formatarDecimalInput('parcela_desc_20'),
    formatarParcelaDesc15: formatarDecimalInput('parcela_desc_15'),
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
