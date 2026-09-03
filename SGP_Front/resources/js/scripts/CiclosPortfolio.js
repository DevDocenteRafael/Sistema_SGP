import { podeEditarDados } from './auth';
import { moduloDoPath, salvarCicloContexto } from './cicloContexto';
import CrudPageHeader from '../components/crud/CrudPageHeader.vue';
import CrudAlerts from '../components/crud/CrudAlerts.vue';
import CrudFormShell from '../components/crud/CrudFormShell.vue';
import PageTableCard from '../components/crud/PageTableCard.vue';
import { mixinHistoricoFormulario } from './formularioHistorico';
import {
  combinarValidacoes,
  extrairErroApi,
  tamanhoMaximo,
  textoObrigatorio,
} from '../utils/validacao';

const ENDPOINT = '/api/portfolio-ciclos';

function formVazio() {
  return {
    nome: '',
    observacao: '',
    atual: false,
    origem_id: '',
    marcar_atual: true,
  };
}

export default {
  name: 'CiclosPortfolio',
  mixins: [mixinHistoricoFormulario],
  components: {
    CrudPageHeader,
    CrudAlerts,
    CrudFormShell,
    PageTableCard,
  },
  data() {
    return {
      modo: 'lista',
      carregando: false,
      salvando: false,
      mensagemSucesso: '',
      mensagemErro: '',
      erroFormulario: '',
      filtros: {
        busca: '',
      },
      registros: [],
      registroDetalhe: null,
      editandoId: null,
      form: formVazio(),
      buscaTimeout: null,
    };
  },
  computed: {
    podeEditar() {
      return podeEditarDados();
    },
    temFiltro() {
      return Object.values(this.filtros).some(Boolean);
    },
    totalRegistros() {
      return this.registros.length;
    },
    tituloForm() {
      if (this.modo === 'gerar') {
        return 'Gerar próximo portfólio';
      }

      return this.modo === 'editar' ? 'Editar ciclo de portfólio' : 'Cadastrar ciclo de portfólio';
    },
    subtituloForm() {
      if (this.modo === 'gerar') {
        return 'Copia os cursos do ciclo de origem para um ciclo novo. Plano de Metas, PCA e Eixos entram pelos anos do nome (ex.: 2028).';
      }

      return this.modo === 'editar'
        ? 'Atualize o nome e as observações deste ciclo.'
        : 'Crie um ciclo vazio. Use anos no nome (2028 ou 2028-2029) para ligar Metas, PCA e Eixos.';
    },
    textoBotaoSalvar() {
      if (this.salvando) {
        return this.modo === 'gerar' ? 'Gerando...' : 'Salvando...';
      }

      if (this.modo === 'gerar') {
        return 'Gerar portfólio';
      }

      return this.modo === 'editar' ? 'Salvar Alterações' : 'Cadastrar';
    },
    destinoTroca() {
      const path = this.$route.query.voltar;
      if (!path || path === '/app/ciclos-portfolio') {
        return null;
      }

      return {
        path,
        modulo: this.$route.query.modulo || moduloDoPath(path),
      };
    },
    rotuloDestinoTroca() {
      const nomes = {
        '/app/cursos': 'Cursos',
        '/app/plano-de-metas': 'Plano de Metas',
        '/app/pca': 'PCA',
        '/app/eixos': 'Eixos',
      };

      return nomes[this.destinoTroca?.path] || 'a página anterior';
    },
  },
  mounted() {
    this.carregarRegistros();
  },
  methods: {
    limparFiltros() {
      this.filtros = { busca: '' };
      this.aplicarFiltros();
    },
    aplicarFiltros() {
      clearTimeout(this.buscaTimeout);
      this.buscaTimeout = setTimeout(() => this.carregarRegistros(), 200);
    },

    async carregarRegistros() {
      this.carregando = true;
      this.mensagemErro = '';

      try {
        const params = {};
        Object.entries(this.filtros).forEach(([chave, valor]) => {
          if (valor) {
            params[chave] = valor;
          }
        });

        const { data } = await window.axios.get(ENDPOINT, { params });
        this.registros = data.data ?? [];
      } catch (error) {
        this.mensagemErro = extrairErroApi(error, 'Não foi possível carregar os ciclos de portfólio.');
        this.registros = [];
      } finally {
        this.carregando = false;
      }
    },

    abrirNovo() {
      if (!this.podeEditar) {
        this.mensagemErro = 'Seu perfil não tem permissão para cadastrar ciclos de portfólio.';
        return;
      }

      this.aplicarEstadoNovoLocal();
      this.empilharHistoricoFormulario('novo');
    },

    aplicarEstadoNovoLocal() {
      this.modo = 'novo';
      this.editandoId = null;
      this.form = formVazio();
      this.erroFormulario = '';
      this.registroDetalhe = null;
    },

    abrirGerar() {
      if (!this.podeEditar) {
        this.mensagemErro = 'Seu perfil não tem permissão para gerar ciclos de portfólio.';
        return;
      }

      this.aplicarEstadoGerarLocal();
      this.empilharHistoricoFormulario('gerar');
    },

    aplicarEstadoGerarLocal() {
      const atual = this.registros.find((ciclo) => ciclo.atual) || this.registros[0];
      this.modo = 'gerar';
      this.editandoId = null;
      this.form = {
        ...formVazio(),
        origem_id: atual ? String(atual.id) : '',
        marcar_atual: true,
      };
      this.erroFormulario = '';
      this.registroDetalhe = null;
    },

    abrirEdicao(item) {
      if (!this.podeEditar) {
        this.mensagemErro = 'Seu perfil não tem permissão para editar ciclos de portfólio.';
        return;
      }

      this.aplicarEstadoEdicaoLocal(item);
      this.empilharHistoricoFormulario('editar', item.id);
    },

    aplicarEstadoEdicaoLocal(item) {
      this.modo = 'editar';
      this.editandoId = item.id;
      this.form = {
        ...formVazio(),
        nome: item.nome ?? '',
        observacao: item.observacao ?? '',
        atual: Boolean(item.atual),
      };
      this.erroFormulario = '';
      this.registroDetalhe = null;
    },

    async aplicarEstadoEdicaoPorId(id) {
      let item = this.registros.find((registro) => String(registro.id) === String(id));

      if (!item) {
        try {
          const { data } = await window.axios.get(`${ENDPOINT}/${id}`);
          item = data.ciclo || data.data || null;
        } catch {
          item = null;
        }
      }

      if (!item) {
        this.aplicarEstadoListaLocal();
        this.limparHistoricoFormulario();
        return;
      }

      this.aplicarEstadoEdicaoLocal(item);
    },

    voltarLista() {
      this.aplicarEstadoListaLocal();
      this.limparHistoricoFormulario();
    },

    aplicarEstadoListaLocal() {
      this.modo = 'lista';
      this.editandoId = null;
      this.form = formVazio();
      this.erroFormulario = '';
    },

    abrirDetalhes(item) {
      this.registroDetalhe = item;
    },

    fecharDetalhes() {
      this.registroDetalhe = null;
    },

    escolherCiclo(item) {
      const destino = this.destinoTroca;
      if (destino?.path) {
        salvarCicloContexto(item, destino.modulo);
        this.irPara(destino.path, { ciclo_id: String(item.id) });
        return;
      }

      this.abrirPortfolio(item);
    },

    abrirPortfolio(item) {
      salvarCicloContexto(item, 'cursos');
      this.irPara('/app/cursos', { ciclo_id: String(item.id) });
    },

    abrirModulo(item, path) {
      const modulo = {
        'plano-de-metas': 'metas',
        pca: 'pca',
        eixos: 'eixos',
      }[path];

      salvarCicloContexto(item, modulo);
      this.irPara(`/app/${path}`, { ciclo_id: String(item.id) });
    },

    irPara(path, query = {}) {
      this.$router.push({ path, query }).catch(() => {
        window.location.assign(query && Object.keys(query).length
          ? `${path}?${new URLSearchParams(query).toString()}`
          : path);
      });
    },

    validarFormulario() {
      return combinarValidacoes(
        textoObrigatorio(this.form.nome, 'Informe o nome do ciclo de portfólio.'),
        tamanhoMaximo(this.form.nome, 80, 'O nome deve ter no máximo 80 caracteres.'),
        this.form.observacao
          ? tamanhoMaximo(this.form.observacao, 2000, 'A observação deve ter no máximo 2000 caracteres.')
          : '',
        this.modo === 'gerar' && !this.form.origem_id
          ? 'Selecione o ciclo de origem para gerar o próximo portfólio.'
          : '',
      );
    },

    async salvarRegistro() {
      if (!this.podeEditar) {
        this.erroFormulario = 'Seu perfil não tem permissão para alterar ciclos de portfólio.';
        return;
      }

      const erro = this.validarFormulario();
      if (erro) {
        this.erroFormulario = erro;
        return;
      }

      this.salvando = true;
      this.erroFormulario = '';

      try {
        let data;

        if (this.modo === 'gerar') {
          const response = await window.axios.post(`${ENDPOINT}/gerar-proximo`, {
            origem_id: this.form.origem_id || null,
            nome: this.form.nome.trim(),
            observacao: this.form.observacao.trim() || null,
            marcar_atual: this.form.marcar_atual,
          });
          data = response.data;
        } else if (this.editandoId) {
          const response = await window.axios.put(`${ENDPOINT}/${this.editandoId}`, {
            nome: this.form.nome.trim(),
            observacao: this.form.observacao.trim() || null,
            atual: this.form.atual,
          });
          data = response.data;
        } else {
          const response = await window.axios.post(ENDPOINT, {
            nome: this.form.nome.trim(),
            observacao: this.form.observacao.trim() || null,
            atual: this.form.atual,
          });
          data = response.data;
        }

        this.mensagemSucesso = data.message;

        if ((this.modo === 'novo' || this.modo === 'gerar') && data.ciclo) {
          this.escolherCiclo(data.ciclo);
          return;
        }

        this.voltarLista();
        await this.carregarRegistros();
      } catch (error) {
        this.erroFormulario = extrairErroApi(error, 'Não foi possível salvar o ciclo de portfólio.');
      } finally {
        this.salvando = false;
      }
    },

    async marcarComoAtual(item) {
      if (!this.podeEditar) {
        this.mensagemErro = 'Seu perfil não tem permissão para alterar ciclos de portfólio.';
        return;
      }

      if (item.atual) {
        return;
      }

      try {
        const { data } = await window.axios.post(`${ENDPOINT}/${item.id}/marcar-atual`);
        this.mensagemSucesso = data.message;
        this.fecharDetalhes();
        await this.carregarRegistros();
      } catch (error) {
        this.mensagemErro = extrairErroApi(error, 'Não foi possível definir o ciclo atual.');
      }
    },

    async excluirRegistro(item) {
      if (!this.podeEditar) {
        this.mensagemErro = 'Seu perfil não tem permissão para excluir ciclos de portfólio.';
        return;
      }

      if (!window.confirm(`Excluir o ciclo "${item.nome}"? Esta ação não pode ser desfeita.`)) {
        return;
      }

      try {
        const { data } = await window.axios.delete(`${ENDPOINT}/${item.id}`);
        this.mensagemSucesso = data.message;
        this.fecharDetalhes();
        await this.carregarRegistros();
      } catch (error) {
        this.mensagemErro = extrairErroApi(error, 'Não foi possível excluir o ciclo de portfólio.');
      }
    },

    textoQuantidade(valor, singular, plural) {
      const total = Number(valor || 0);

      return total === 1 ? `1 ${singular}` : `${total} ${plural}`;
    },
  },
};
