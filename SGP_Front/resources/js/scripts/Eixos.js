import { createCrudPage } from './createCrudPage';
import { UNIDADES } from './unidades';

export default createCrudPage({
  name: 'Eixos',
  endpoint: '/api/curso-por-eixos',
  showKey: 'cursoPorEixo',
  errorKey: 'mensagemErro',
  formErrorKey: 'erroFormulario',
  useDetalheAberto: true,
  checkConsultar: false,
  carregandoInicial: false,
  usarCicloContexto: true,
  cicloModulo: 'eixos',
  filtrosIniciais: {
    busca: '',
    ano: '',
    eixo: '',
    unidade: '',
    status: '',
  },
  formVazio: () => ({
    curso: '',
    eixo: '',
    unidade: '',
    ano: '2025',
    ch: '',
    turmas: '',
    codigo: '',
    alunos: '',
    instrutores: '',
    status: 'Ativo',
    observacao: '',
  }),
  normalizarRegistro: (registro) => ({ ...registro }),
  montarForm(registro) {
    return {
      curso: registro.curso ?? '',
      eixo: registro.eixo ?? '',
      unidade: registro.unidade ?? '',
      ano: registro.ano ?? '2025',
      ch: registro.ch ?? '',
      turmas: registro.turmas ?? '',
      codigo: registro.codigo ?? '',
      alunos: registro.alunos ?? '',
      instrutores: registro.instrutores ?? '',
      status: registro.status ?? 'Ativo',
      observacao: registro.observacao ?? '',
    };
  },
  validarFormulario(form) {
    if (!form.curso?.trim()) return 'Informe o nome do curso.';
    if (!form.eixo) return 'Selecione o eixo tecnológico.';
    if (!form.ano) return 'Selecione o ano.';
    if (!form.status) return 'Selecione o status.';
    return '';
  },
  montarPayload(form) {
    return {
      curso: form.curso.trim(),
      eixo: form.eixo,
      unidade: form.unidade || null,
      ano: form.ano,
      ch: form.ch || null,
      turmas: form.turmas || null,
      codigo: form.codigo || null,
      alunos: form.alunos || null,
      instrutores: form.instrutores || null,
      status: form.status,
      observacao: form.observacao || null,
      is_novo: false,
    };
  },
  labelExclusao: (r) => `${r.curso} (${r.ano})`,
  mensagens: {
    soConsulta: 'Seu perfil só permite consultar cursos por eixo.',
    falhaCarregar: 'Não foi possível carregar os cursos por eixo.',
    falhaSalvar: 'Não foi possível salvar o registro.',
    falhaExcluir: 'Não foi possível excluir o registro.',
    falhaDetalhe: 'Não foi possível carregar os detalhes.',
    confirmarExclusao: (r) => `Excluir o curso "${r.curso}" (${r.ano})? Esta ação não pode ser desfeita.`,
  },
  aplicarMeta(meta) {
    if (Array.isArray(meta.unidades) && meta.unidades.length) {
      this.unidades = meta.unidades;
    }
  },
  extraData: () => ({
    meta: {
      eixos: [],
      status: ['Ativo', 'Suspenso', 'Inativo'],
      anos: ['2026', '2025', '2024', '2023'],
      unidades: [...UNIDADES],
    },
    unidades: [...UNIDADES],
  }),
  computedAliases: {
    totalFiltrado: 'totalRegistros',
  },
  extraComputed: {
    statusLista() {
      return this.meta.status?.length ? this.meta.status : ['Ativo', 'Suspenso', 'Inativo'];
    },
    anosDisponiveis() {
      return this.meta.anos?.length ? this.meta.anos : ['2026', '2025', '2024', '2023'];
    },
    eixosDisponiveis() {
      if (this.meta.eixos?.length) {
        return [...this.meta.eixos].sort((a, b) => a.localeCompare(b, 'pt-BR'));
      }
      const set = new Set(this.registros.map((r) => r.eixo).filter(Boolean));
      return [...set].sort((a, b) => a.localeCompare(b, 'pt-BR'));
    },
    eixosFormulario() {
      return this.eixosDisponiveis;
    },
    totalGeral() {
      return this.meta.total_geral ?? this.registros.length;
    },
  },
  extraMethods: {
    formatarCh(valor) {
      if (!valor) return '—';
      const texto = String(valor).trim();
      if (/h$/i.test(texto)) return texto;
      return `${texto}h`;
    },
    valorCampo(valor) {
      if (valor === null || valor === undefined || valor === '') return '—';
      return valor;
    },
    badgeStatus(status) {
      const valor = String(status ?? '').toLowerCase();
      return {
        'badge-ativo': valor === 'ativo',
        'badge-suspenso': valor === 'suspenso',
        'badge-inativo': valor === 'inativo',
      };
    },
    classeEixo(eixo) {
      const nome = String(eixo || '').toLowerCase();
      if (nome.includes('gastronomia') || nome.includes('bebidas') || nome.includes('panif') || nome.includes('confeit')) {
        return 'eixo-tag--laranja';
      }
      if (nome.includes('turismo') || nome.includes('hospitalidade')) return 'eixo-tag--azul';
      if (nome.includes('tecnologia') || nome.includes('informação') || nome.includes('infoma')) {
        return 'eixo-tag--roxo';
      }
      if (nome.includes('enfermagem') || nome.includes('saúde') || nome.includes('nutri') || nome.includes('farm') || nome.includes('radio') || nome.includes('estética')) {
        return 'eixo-tag--verde';
      }
      if (nome.includes('beleza') || nome.includes('moda')) return 'eixo-tag--rosa';
      if (nome.includes('gestão') || nome.includes('educação') || nome.includes('vendas') || nome.includes('segurança')) {
        return 'eixo-tag--cinza';
      }
      return 'eixo-tag--padrao';
    },
  },
});
