import PageTableCard from '../components/crud/PageTableCard.vue';
import CrudPageHeader from '../components/crud/CrudPageHeader.vue';
import { podeEditarDados } from './auth';
import { limparCacheUnidadesNomes } from './unidadesApi';
import {
  combinarValidacoes,
  extrairErroApi,
  tamanhoMaximo,
  textoObrigatorio,
} from '../utils/validacao';

const ENDPOINT_REGIOES = '/api/regioes-administrativas';
const ENDPOINT_UNIDADES = '/api/unidades-oferta';

const TIPOS_PADRAO = [
  { value: 'cep', label: 'CEP — Centro de Educação Profissional' },
  { value: 'polo', label: 'Polo' },
  { value: 'faculdade', label: 'Faculdade' },
];

function formVazio() {
  return {
    nome: '',
    tipo: '',
    regiao_administrativa_id: '',
    ativo: true,
  };
}

export default {
  name: 'Unidades',
  components: { PageTableCard, CrudPageHeader },
  data() {
    return {
      modo: 'lista',
      abaLista: 'regioes',
      carregando: false,
      salvando: false,
      mensagemSucesso: '',
      mensagemErro: '',
      erroFormulario: '',
      editandoId: null,
      registros: [],
      regioesSelect: [],
      tiposMeta: { ...Object.fromEntries(TIPOS_PADRAO.map((t) => [t.value, t.label])) },
      filtros: {
        busca: '',
        ativo: '',
        tipo: '',
      },
      form: formVazio(),
      buscaTimeout: null,
    };
  },
  computed: {
    podeEditar() {
      return podeEditarDados();
    },
    temFiltro() {
      return Object.values(this.filtros).some((valor) => valor !== '' && valor != null);
    },
    opcoesTipo() {
      return Object.entries(this.tiposMeta).map(([value, label]) => ({ value, label }));
    },
    opcoesRegiao() {
      return this.regioesSelect
        .filter((r) => r.ativo || String(r.id) === String(this.form.regiao_administrativa_id))
        .map((r) => ({
          value: String(r.id),
          label: r.ativo ? r.nome : `${r.nome} (inativa)`,
        }));
    },
    tituloForm() {
      const entidade = this.abaLista === 'regioes' ? 'Região' : 'Unidade';
      if (this.modo === 'novo') {
        return `Nova ${entidade}`;
      }
      return `Editar ${entidade}`;
    },
  },
  mounted() {
    this.recarregarLista();
  },
  beforeUnmount() {
    if (this.buscaTimeout) {
      clearTimeout(this.buscaTimeout);
    }
  },
  methods: {
    labelTipo(tipo) {
      return this.tiposMeta[tipo] || tipo || '—';
    },

    limparFiltros() {
      this.filtros = { busca: '', ativo: '', tipo: '' };
      this.recarregarLista();
    },

    trocarAbaLista(aba) {
      if (this.abaLista === aba) {
        return;
      }
      this.abaLista = aba;
      this.filtros = { busca: '', ativo: '', tipo: '' };
      this.mensagemSucesso = '';
      this.mensagemErro = '';
      this.recarregarLista();
    },

    recarregarLista() {
      if (this.buscaTimeout) {
        clearTimeout(this.buscaTimeout);
      }
      this.buscaTimeout = setTimeout(() => {
        this.carregarLista();
      }, 250);
    },

    endpointAtual() {
      return this.abaLista === 'regioes' ? ENDPOINT_REGIOES : ENDPOINT_UNIDADES;
    },

    async carregarLista() {
      this.carregando = true;
      this.mensagemErro = '';
      try {
        const params = {};
        if (this.filtros.busca) {
          params.busca = this.filtros.busca;
        }
        if (this.filtros.ativo !== '') {
          params.ativo = this.filtros.ativo;
        }
        if (this.abaLista === 'unidades' && this.filtros.tipo) {
          params.tipo = this.filtros.tipo;
        }

        const { data } = await window.axios.get(this.endpointAtual(), { params });
        this.registros = Array.isArray(data.data) ? data.data : [];

        if (this.abaLista === 'unidades' && data.meta) {
          if (data.meta.tipos && typeof data.meta.tipos === 'object') {
            this.tiposMeta = { ...data.meta.tipos };
          }
          if (Array.isArray(data.meta.regioes)) {
            this.regioesSelect = data.meta.regioes;
          }
        }
      } catch (erro) {
        this.mensagemErro = extrairErroApi(erro, 'Não foi possível carregar os registros.');
        this.registros = [];
      } finally {
        this.carregando = false;
      }
    },

    async carregarRegioesSelect() {
      try {
        const { data } = await window.axios.get(ENDPOINT_REGIOES);
        this.regioesSelect = Array.isArray(data.data) ? data.data : [];
      } catch {
        this.regioesSelect = [];
      }
    },

    abrirNovo() {
      if (!this.podeEditar) {
        return;
      }
      this.modo = 'novo';
      this.editandoId = null;
      this.erroFormulario = '';
      this.mensagemSucesso = '';
      this.form = formVazio();
      if (this.abaLista === 'unidades') {
        this.carregarRegioesSelect();
      }
    },

    abrirEdicao(item) {
      if (!this.podeEditar) {
        return;
      }
      this.modo = 'edicao';
      this.editandoId = item.id;
      this.erroFormulario = '';
      this.mensagemSucesso = '';
      this.form = {
        nome: item.nome ?? '',
        tipo: item.tipo ?? '',
        regiao_administrativa_id: item.regiao_administrativa_id
          ? String(item.regiao_administrativa_id)
          : item.regiao_administrativa?.id
            ? String(item.regiao_administrativa.id)
            : '',
        ativo: item.ativo !== false,
      };
      if (this.abaLista === 'unidades') {
        this.carregarRegioesSelect();
      }
    },

    voltarLista() {
      this.modo = 'lista';
      this.editandoId = null;
      this.erroFormulario = '';
      this.form = formVazio();
      this.carregarLista();
    },

    validarForm() {
      const erros = [
        textoObrigatorio(this.form.nome, 'Informe o nome.'),
        tamanhoMaximo(this.form.nome, 100, 'O nome deve ter no máximo 100 caracteres.'),
      ];
      if (this.abaLista === 'unidades') {
        erros.push(textoObrigatorio(this.form.tipo, 'Selecione o tipo.'));
        erros.push(textoObrigatorio(this.form.regiao_administrativa_id, 'Selecione a região administrativa.'));
      }
      return combinarValidacoes(...erros);
    },

    montarPayload() {
      if (this.abaLista === 'regioes') {
        return {
          nome: this.form.nome.trim(),
          ativo: Boolean(this.form.ativo),
        };
      }
      return {
        nome: this.form.nome.trim(),
        tipo: this.form.tipo,
        regiao_administrativa_id: Number(this.form.regiao_administrativa_id),
        ativo: Boolean(this.form.ativo),
      };
    },

    async salvar() {
      if (!this.podeEditar) {
        return;
      }
      this.erroFormulario = this.validarForm();
      if (this.erroFormulario) {
        return;
      }

      this.salvando = true;
      try {
        const payload = this.montarPayload();
        const endpoint = this.endpointAtual();
        if (this.modo === 'novo') {
          await window.axios.post(endpoint, payload);
          this.mensagemSucesso = this.abaLista === 'regioes'
            ? 'Região cadastrada com sucesso.'
            : 'Unidade cadastrada com sucesso.';
        } else {
          await window.axios.put(`${endpoint}/${this.editandoId}`, payload);
          this.mensagemSucesso = this.abaLista === 'regioes'
            ? 'Região atualizada com sucesso.'
            : 'Unidade atualizada com sucesso.';
        }
        limparCacheUnidadesNomes();
        this.voltarLista();
      } catch (erro) {
        this.erroFormulario = extrairErroApi(erro, 'Não foi possível salvar.');
      } finally {
        this.salvando = false;
      }
    },

    async alternarAtivo(item) {
      if (!this.podeEditar) {
        return;
      }
      const ativar = !item.ativo;
      const rotulo = this.abaLista === 'regioes' ? 'região' : 'unidade';
      const ok = window.confirm(
        ativar
          ? `Reativar a ${rotulo} "${item.nome}"?`
          : `Inativar a ${rotulo} "${item.nome}"? Ela deixará de aparecer nas listas de seleção.`,
      );
      if (!ok) {
        return;
      }

      this.mensagemErro = '';
      try {
        const endpoint = this.endpointAtual();
        const payload = this.abaLista === 'regioes'
          ? { nome: item.nome, ativo: ativar }
          : {
            nome: item.nome,
            tipo: item.tipo,
            regiao_administrativa_id: item.regiao_administrativa_id
              || item.regiao_administrativa?.id,
            ativo: ativar,
          };

        await window.axios.put(`${endpoint}/${item.id}`, payload);
        limparCacheUnidadesNomes();
        this.mensagemSucesso = ativar
          ? `${rotulo.charAt(0).toUpperCase() + rotulo.slice(1)} reativada.`
          : `${rotulo.charAt(0).toUpperCase() + rotulo.slice(1)} inativada.`;
        await this.carregarLista();
      } catch (erro) {
        this.mensagemErro = extrairErroApi(erro, 'Não foi possível alterar o status.');
      }
    },
  },
};
