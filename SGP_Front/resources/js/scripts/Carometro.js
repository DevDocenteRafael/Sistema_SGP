import { podeConsultarDados, podeEditarDados } from './auth';

const ORDEM_TIPOS = ['ordenador', 'assistente', 'responsavel', 'instrutor', 'administrativo'];

export default {
  name: 'Carometro',

  data() {
    return {
      membros: [],
      meta: {},
      carregando: true,
      erro: '',
      busca: '',
      filtroTipo: 'todos',
      filtroArea: 'todos',
      selecionado: null,
      podeEditarApi: false,
    };
  },

  computed: {
    acessoBloqueado() {
      return !podeConsultarDados();
    },

    podeEditar() {
      return podeEditarDados() && this.podeEditarApi;
    },

    tiposFiltro() {
      return Array.isArray(this.meta.tipos_filtro) ? this.meta.tipos_filtro : [];
    },

    areasFiltro() {
      const eixos = Array.isArray(this.meta.eixos) ? this.meta.eixos : [];
      const extras = this.membros
        .map((pessoa) => pessoa.eixo_vinculado || pessoa.setor)
        .filter(Boolean)
        .filter((area) => !eixos.includes(area));

      return [...eixos, ...[...new Set(extras)].sort()];
    },

    membrosFiltrados() {
      const termo = this.busca.trim().toLowerCase();

      return this.membros.filter((pessoa) => {
        if (this.filtroTipo !== 'todos' && pessoa.tipo !== this.filtroTipo) {
          return false;
        }

        if (this.filtroArea !== 'todos') {
          const area = pessoa.eixo_vinculado || pessoa.setor;
          if (area !== this.filtroArea) {
            return false;
          }
        }

        if (!termo) {
          return true;
        }

        const texto = [
          pessoa.nome,
          pessoa.cargo,
          pessoa.setor,
          pessoa.eixo_vinculado,
          pessoa.contato,
        ].filter(Boolean).join(' ').toLowerCase();

        return texto.includes(termo);
      });
    },

    gruposVisiveis() {
      const labels = this.meta.tipos_labels || {};

      return ORDEM_TIPOS
        .map((tipo) => {
          const membros = this.membrosFiltrados.filter((pessoa) => pessoa.tipo === tipo);

          return {
            tipo,
            label: labels[tipo] || tipo,
            membros,
          };
        })
        .filter((grupo) => grupo.membros.length > 0);
    },
  },

  mounted() {
    this.carregar();
  },

  methods: {
    async carregar() {
      this.carregando = true;
      this.erro = '';

      if (!podeConsultarDados()) {
        this.carregando = false;
        return;
      }

      try {
        const { data } = await window.axios.get('/api/carometro');
        this.membros = Array.isArray(data.data) ? data.data : [];
        this.meta = data.meta || {};
        this.podeEditarApi = Boolean(data.meta?.pode_editar);
      } catch (error) {
        this.erro = error.response?.data?.message
          || 'Não foi possível carregar o carômetro.';
        this.membros = [];
      } finally {
        this.carregando = false;
      }
    },

    contagemTipo(tipo) {
      return this.meta.por_tipo?.[tipo] || 0;
    },

    labelTipo(tipo) {
      return this.meta.tipos_labels?.[tipo] || tipo;
    },

    corPessoa(pessoa) {
      return pessoa?.cor
        || this.meta.cores_eixo?.[pessoa?.eixo_vinculado]
        || this.meta.cores_tipo?.[pessoa?.tipo]
        || '#003F7D';
    },

    corTipo(tipo) {
      return this.meta.cores_tipo?.[tipo] || '#003F7D';
    },

    corArea(area) {
      return this.meta.cores_eixo?.[area] || '#64748B';
    },

    fotoStyle(pessoa) {
      const cor = this.corPessoa(pessoa);

      return {
        background: `linear-gradient(165deg, ${cor} 0%, #0b1220 130%)`,
      };
    },

    estiloAreaAtiva(area) {
      const cor = this.corArea(area);

      return {
        background: cor,
        borderColor: cor,
        color: '#fff',
      };
    },

    limparFiltros() {
      this.busca = '';
      this.filtroTipo = 'todos';
      this.filtroArea = 'todos';
    },

    selecionar(pessoa) {
      this.selecionado = pessoa;
    },
  },
};
