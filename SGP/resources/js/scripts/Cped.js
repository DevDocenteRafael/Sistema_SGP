import { podeConsultarDados, podeEditarDados } from './auth';

const TIPOS = ['ordenador', 'assistente', 'responsavel', 'instrutor', 'administrativo'];

const TIPOS_LABELS = {
  ordenador: 'Ordenador',
  assistente: 'Assistente Administrativo',
  responsavel: 'Responsável de Eixo',
  instrutor: 'Instrutor',
  administrativo: 'Administrativo',
};

const TIPOS_GRUPOS = {
  ordenador: { titulo: 'Ordenador', classe: 'grupo-ordenador' },
  assistente: { titulo: 'Assistentes Administrativos', classe: 'grupo-assistente' },
  responsavel: { titulo: 'Responsáveis de Eixo', classe: 'grupo-responsavel' },
  instrutor: { titulo: 'Instrutores Vinculados', classe: 'grupo-instrutor' },
  administrativo: { titulo: 'Administrativos Vinculados', classe: 'grupo-admin' },
};

const FILTRO_TIPO_LABELS = {
  ordenador: 'Ordenador',
  assistente: 'Assistentes',
  responsavel: 'Resp. de Eixo',
  instrutor: 'Instrutores',
  administrativo: 'Administrativos',
};

const OPCOES_FILTRO_TIPO = TIPOS.map((tipo) => ({
  value: tipo,
  label: FILTRO_TIPO_LABELS[tipo] || tipo,
}));

const EIXOS = [
  'Gastronomia',
  'Beleza e Cuidado Pessoal',
  'Gestão e Negócios',
  'Tecnologia e Economia Criativa',
  'Ambiente e Saúde',
  'Gestão e Moda',
];

const SETORES_POR_TIPO = {
  ordenador: ['CPED'],
  assistente: ['CPED', 'Secretaria Geral', 'Secretaria'],
  responsavel: [...EIXOS],
  instrutor: [...EIXOS],
  administrativo: ['CPED', 'Secretaria Geral', 'Secretaria', 'TI / Sistemas', 'Financeiro', 'Patrimônio'],
};

const TEMAS_EIXO = {
  Gastronomia: { bg: '#fff7ed', text: '#9a3412', ring: '#fed7aa' },
  'Beleza e Cuidado Pessoal': { bg: '#fdf2f8', text: '#9d174d', ring: '#fbcfe8' },
  'Gestão e Negócios': { bg: '#eff6ff', text: '#1e40af', ring: '#bfdbfe' },
  'Tecnologia e Economia Criativa': { bg: '#faf5ff', text: '#6b21a8', ring: '#e9d5ff' },
  'Ambiente e Saúde': { bg: '#f0fdf4', text: '#166534', ring: '#bbf7d0' },
  'Gestão e Moda': { bg: '#fff1f2', text: '#9f1239', ring: '#fecdd3' },
};

const CORES_TIPO = {
  ordenador: '#003F7D',
  assistente: '#5C6BC0',
  responsavel: '#E65100',
  instrutor: '#F57C00',
  administrativo: '#00796B',
};

const SETORES = [
  ...EIXOS,
  'CPED',
  'Secretaria Geral',
  'Secretaria',
  'TI / Sistemas',
  'Financeiro',
  'Patrimônio',
];

export default {
  name: 'Cped',

  data() {
    return {
      registros: [],
      carregando: true,
      erro: '',
      mensagemSucesso: '',
      modalAberto: false,
      editandoId: null,
      salvando: false,
      erroFormulario: '',
      detalhe: null,
      eixoSelecionado: null,
      form: this.formVazio(),
      filtroTipo: 'todos',
      filtroEixo: 'todos',
      tipos: TIPOS,
      opcoesFiltroTipo: OPCOES_FILTRO_TIPO,
      tiposLabels: TIPOS_LABELS,
      setoresPorTipo: SETORES_POR_TIPO,
      eixos: EIXOS,
      setores: SETORES,
      temasEixo: TEMAS_EIXO,
      coresTipo: CORES_TIPO,
      contadores: {
        colaboradores: 0,
        eixos: 0,
        instrutores: 0,
        administrativos: 0,
      },
    };
  },

  computed: {
    acessoBloqueado() {
      return !podeConsultarDados();
    },

    podeEditar() {
      return podeEditarDados();
    },

    precisaEixo() {
      return this.form.tipo === 'responsavel' || this.form.tipo === 'instrutor';
    },

    ordenador() {
      return this.registros.find((item) => item.tipo === 'ordenador') || null;
    },

    assistentes() {
      return this.registros.filter((item) => item.tipo === 'assistente');
    },

    responsaveis() {
      return this.registros.filter((item) => item.tipo === 'responsavel');
    },

    gruposPorFuncao() {
      return TIPOS.map((tipo) => ({
        tipo,
        ...TIPOS_GRUPOS[tipo],
        membros: this.registros.filter((item) => item.tipo === tipo),
        larguraTotal: tipo === 'responsavel',
      })).filter((grupo) => grupo.membros.length > 0);
    },

    opcoesFormularioTipo() {
      return TIPOS.map((tipo) => ({
        value: tipo,
        label: this.tiposLabels[tipo] || TIPOS_LABELS[tipo] || tipo,
      }));
    },

    setoresDoFormulario() {
      const opcoes = this.setoresPorTipo[this.form.tipo] || this.setores;
      return [...new Set(opcoes)];
    },

    setoresFiltro() {
      const dosRegistros = this.registros.map((item) => item.setor).filter(Boolean);
      const todos = [...new Set([...this.setores, ...dosRegistros])];
      const eixosOrdenados = this.eixos.filter((eixo) => todos.includes(eixo));
      const outros = todos.filter((setor) => !this.eixos.includes(setor)).sort();
      return [...eixosOrdenados, ...outros];
    },

    membrosFiltrados() {
      return this.registros.filter((item) => {
        if (this.filtroTipo !== 'todos' && item.tipo !== this.filtroTipo) {
          return false;
        }

        if (this.filtroEixo !== 'todos' && item.setor !== this.filtroEixo) {
          return false;
        }

        return true;
      });
    },

    equipeEixoModal() {
      if (!this.eixoSelecionado) {
        return { responsavel: null, instrutores: [], administrativos: [] };
      }

      const eixo = this.eixoSelecionado;

      return {
        responsavel: this.registros.find((item) => item.tipo === 'responsavel' && item.eixo_vinculado === eixo) || null,
        instrutores: this.registros.filter((item) => item.tipo === 'instrutor' && item.eixo_vinculado === eixo),
        administrativos: this.registros.filter((item) => item.tipo === 'administrativo' && item.eixo_vinculado === eixo),
      };
    },
  },

  mounted() {
    if (!this.acessoBloqueado) {
      this.carregarRegistros();
    } else {
      this.carregando = false;
    }
  },

  methods: {
    formVazio() {
      return {
        nome: '',
        cargo: '',
        setor: 'CPED',
        contato: '',
        tipo: 'assistente',
        eixo_vinculado: '',
        iniciais: '',
        foto: '',
        cor: CORES_TIPO.assistente,
        ativo: true,
        observacao: '',
      };
    },

    temaEixo(eixo) {
      return this.temasEixo[eixo] || { bg: '#f9fafb', text: '#374151', ring: '#e5e7eb' };
    },

    estiloEixoCard(eixo) {
      const tema = this.temaEixo(eixo);
      return {
        background: tema.bg,
        color: tema.text,
        borderColor: tema.ring,
        boxShadow: `inset 0 0 0 1px ${tema.ring}`,
      };
    },

    estiloEixoChipAtivo(eixo) {
      const tema = this.temaEixo(eixo);
      return {
        background: tema.bg,
        color: tema.text,
        borderColor: tema.ring,
        boxShadow: `inset 0 0 0 1px ${tema.ring}`,
      };
    },

    avatarStyle(pessoa) {
      const cor = pessoa?.cor || this.temaEixo(pessoa?.eixo_vinculado || pessoa?.setor).text || this.coresTipo[pessoa?.tipo] || '#003F7D';
      return { background: cor };
    },

    nomeCurto(nome) {
      if (!nome) {
        return '';
      }

      const partes = nome.trim().split(/\s+/).filter(Boolean);
      if (partes.length <= 2) {
        return nome;
      }

      return `${partes[0]} ${partes[1]}`;
    },

    gerarIniciais(nome) {
      const partes = (nome || '').trim().split(/\s+/).filter(Boolean);
      if (!partes.length) {
        return '';
      }
      if (partes.length === 1) {
        return partes[0].slice(0, 2).toUpperCase();
      }
      return `${partes[0][0]}${partes[partes.length - 1][0]}`.toUpperCase();
    },

    atualizarIniciais() {
      if (!this.editandoId || !this.form.iniciais) {
        this.form.iniciais = this.gerarIniciais(this.form.nome);
      }
    },

    onTipoChange() {
      this.form.cor = this.coresTipo[this.form.tipo] || '#003F7D';

      const setoresDisponiveis = this.setoresPorTipo[this.form.tipo] || this.setores;
      if (!setoresDisponiveis.includes(this.form.setor)) {
        this.form.setor = setoresDisponiveis[0] || '';
      }

      if (!this.precisaEixo) {
        this.form.eixo_vinculado = '';
      } else if (EIXOS.includes(this.form.setor)) {
        this.form.eixo_vinculado = this.form.setor;
        this.form.cor = this.temaEixo(this.form.eixo_vinculado).text;
      } else {
        this.form.eixo_vinculado = '';
      }
    },

    onSetorChange() {
      if (this.precisaEixo && EIXOS.includes(this.form.setor)) {
        this.form.eixo_vinculado = this.form.setor;
        this.form.cor = this.temaEixo(this.form.eixo_vinculado).text;
      }
    },

    abrirEixo(eixo) {
      this.eixoSelecionado = eixo || null;
    },

    fecharEixo() {
      this.eixoSelecionado = null;
    },

    async carregarRegistros() {
      this.carregando = true;
      this.erro = '';

      try {
        const { data } = await window.axios.get('/api/cped-equipes');
        this.registros = data.data || [];
        this.contadores = data.meta?.contadores || this.contadores;
        this.tipos = Array.isArray(data.meta?.tipos) ? data.meta.tipos : TIPOS;
        this.opcoesFiltroTipo = Array.isArray(data.meta?.tipos_filtro) && data.meta.tipos_filtro.length
          ? data.meta.tipos_filtro
          : OPCOES_FILTRO_TIPO;
        this.tiposLabels = data.meta?.tipos_labels || TIPOS_LABELS;
        this.setoresPorTipo = data.meta?.setores_por_tipo || SETORES_POR_TIPO;
        this.eixos = data.meta?.eixos || EIXOS;
        this.setores = data.meta?.setores || SETORES;
        this.coresTipo = data.meta?.cores_tipo || CORES_TIPO;
      } catch (error) {
        this.erro = error.response?.data?.message || 'Não foi possível carregar a equipe CPED.';
        this.registros = [];
      } finally {
        this.carregando = false;
      }
    },

    abrirNovo() {
      this.editandoId = null;
      this.form = this.formVazio();
      this.erroFormulario = '';
      this.detalhe = null;
      this.eixoSelecionado = null;
      this.modalAberto = true;
    },

    abrirEdicao(pessoa) {
      this.editandoId = pessoa.id;
      this.form = {
        nome: pessoa.nome || '',
        cargo: pessoa.cargo || '',
        setor: pessoa.setor || 'CPED',
        contato: pessoa.contato || '',
        tipo: pessoa.tipo || 'assistente',
        eixo_vinculado: pessoa.eixo_vinculado || '',
        iniciais: pessoa.iniciais || '',
        foto: pessoa.foto || '',
        cor: pessoa.cor || this.coresTipo[pessoa.tipo] || '#003F7D',
        ativo: pessoa.ativo !== false,
        observacao: pessoa.observacao || '',
      };
      this.erroFormulario = '';
      this.detalhe = null;
      this.eixoSelecionado = null;
      this.modalAberto = true;
    },

    abrirDetalhe(pessoa) {
      this.detalhe = pessoa;
    },

    fecharModal() {
      this.modalAberto = false;
      this.editandoId = null;
      this.erroFormulario = '';
      this.form = this.formVazio();
    },

    onFotoSelecionada(event) {
      const arquivo = event.target.files?.[0];
      if (!arquivo) {
        return;
      }

      if (arquivo.size > 2 * 1024 * 1024) {
        this.erroFormulario = 'A foto deve ter no máximo 2 MB.';
        event.target.value = '';
        return;
      }

      const reader = new FileReader();
      reader.onload = () => {
        this.form.foto = String(reader.result || '');
      };
      reader.readAsDataURL(arquivo);
    },

    payloadAtual() {
      return {
        nome: this.form.nome?.trim(),
        cargo: this.form.cargo?.trim(),
        setor: this.form.setor,
        contato: this.form.contato?.trim(),
        tipo: this.form.tipo,
        eixo_vinculado: this.precisaEixo ? this.form.eixo_vinculado || null : null,
        iniciais: (this.form.iniciais || this.gerarIniciais(this.form.nome)).trim(),
        foto: this.form.foto || null,
        cor: this.form.cor || null,
        ativo: !!this.form.ativo,
        observacao: this.form.observacao?.trim() || null,
      };
    },

    async salvar() {
      this.salvando = true;
      this.erroFormulario = '';

      try {
        const payload = this.payloadAtual();

        if (this.editandoId) {
          const { data } = await window.axios.put(`/api/cped-equipes/${this.editandoId}`, payload);
          this.mensagemSucesso = data.message || 'Membro atualizado com sucesso.';
        } else {
          const { data } = await window.axios.post('/api/cped-equipes', payload);
          this.mensagemSucesso = data.message || 'Membro cadastrado com sucesso.';
        }

        this.fecharModal();
        await this.carregarRegistros();
      } catch (error) {
        const erros = error.response?.data?.errors;
        if (erros) {
          this.erroFormulario = Object.values(erros).flat().join(' ');
        } else {
          this.erroFormulario = error.response?.data?.message || 'Não foi possível salvar o membro.';
        }
      } finally {
        this.salvando = false;
      }
    },

    async excluir(pessoa) {
      if (!window.confirm(`Excluir o membro "${pessoa.nome}"?`)) {
        return;
      }

      this.erro = '';

      try {
        const { data } = await window.axios.delete(`/api/cped-equipes/${pessoa.id}`);
        this.mensagemSucesso = data.message || 'Membro excluído com sucesso.';
        if (this.detalhe?.id === pessoa.id) {
          this.detalhe = null;
        }
        if (this.eixoSelecionado) {
          this.fecharEixo();
        }
        await this.carregarRegistros();
      } catch (error) {
        this.erro = error.response?.data?.message || 'Não foi possível excluir o membro.';
      }
    },
  },
};
