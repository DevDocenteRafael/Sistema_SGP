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

const ENDPOINT_ESTRUTURAS = '/api/unidades-oferta';

const TIPOS_PADRAO = [
  { value: 'faculdade', label: 'Faculdade' },
  { value: 'polo', label: 'Polo' },
  { value: 'unidade', label: 'Unidade' },
];

function formVazio() {
  return {
    nome: '',
    tipo: '',
    localidade: '',
    ativo: true,
    motivo_inativacao: '',
  };
}

function modalInativacaoVazio() {
  return {
    aberto: false,
    item: null,
    motivo: '',
    erro: '',
    salvando: false,
  };
}

export default {
  name: 'EstruturasInstitucionais',
  components: { PageTableCard, CrudPageHeader },
  data() {
    return {
      modo: 'lista',
      carregando: false,
      salvando: false,
      mensagemSucesso: '',
      mensagemErro: '',
      erroFormulario: '',
      editandoId: null,
      registros: [],
      meta: {
        total: 0,
        per_page: 10,
        current_page: 1,
        last_page: 1,
      },
      tiposMeta: { ...Object.fromEntries(TIPOS_PADRAO.map((t) => [t.value, t.label])) },
      filtros: {
        busca: '',
        ativo: '',
        tipo: '',
      },
      form: formVazio(),
      modalInativacao: modalInativacaoVazio(),
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
    tituloForm() {
      return this.modo === 'novo' ? 'Nova Estrutura Institucional' : 'Editar Estrutura Institucional';
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
      if (tipo === 'cep') return 'Unidade';
      return this.tiposMeta[tipo] || tipo || '—';
    },

    classeBadgeTipo(tipo) {
      const chave = tipo === 'cep' ? 'unidade' : tipo;
      return `badge-tipo-${chave || 'padrao'}`;
    },

    limparFiltros() {
      this.filtros = { busca: '', ativo: '', tipo: '' };
      this.recarregarLista();
    },

    recarregarLista() {
      if (this.buscaTimeout) {
        clearTimeout(this.buscaTimeout);
      }
      this.meta.current_page = 1;
      this.buscaTimeout = setTimeout(() => {
        this.carregarLista();
      }, 250);
    },

    irParaPagina(pagina) {
      const destino = Number(pagina);
      if (!Number.isInteger(destino) || destino < 1 || destino > this.meta.last_page || this.carregando) return;
      clearTimeout(this.buscaTimeout);
      this.meta.current_page = destino;
      this.carregarLista();
    },

    alterarRegistrosPorPagina(quantidade) {
      const tamanho = Number(quantidade);
      if (!Number.isInteger(tamanho) || tamanho < 1 || this.carregando) return;
      clearTimeout(this.buscaTimeout);
      this.meta.per_page = tamanho;
      this.meta.current_page = 1;
      this.carregarLista();
    },

    async carregarLista() {
      this.carregando = true;
      this.mensagemErro = '';
      try {
        const params = {};
        if (this.filtros.busca) params.busca = this.filtros.busca;
        if (this.filtros.ativo !== '') params.ativo = this.filtros.ativo;
        if (this.filtros.tipo) params.tipo = this.filtros.tipo;
        params.page = this.meta.current_page;
        params.per_page = this.meta.per_page;

        let { data } = await window.axios.get(ENDPOINT_ESTRUTURAS, { params });
        if (data.meta?.current_page > data.meta?.last_page) {
          params.page = Math.max(1, Number(data.meta.last_page) || 1);
          ({ data } = await window.axios.get(ENDPOINT_ESTRUTURAS, { params }));
        }
        this.registros = Array.isArray(data.data) ? data.data : [];
        this.meta = { ...this.meta, ...(data.meta || {}) };

        if (data.meta?.tipos && typeof data.meta.tipos === 'object') {
          this.tiposMeta = { ...data.meta.tipos };
        }
      } catch (erro) {
        this.mensagemErro = extrairErroApi(erro, 'Não foi possível carregar as estruturas institucionais.');
        this.registros = [];
        this.meta.total = 0;
      } finally {
        this.carregando = false;
      }
    },

    abrirNovo() {
      if (!this.podeEditar) return;
      this.modo = 'novo';
      this.editandoId = null;
      this.erroFormulario = '';
      this.mensagemSucesso = '';
      this.form = formVazio();
    },

    abrirEdicao(item) {
      if (!this.podeEditar) return;
      this.modo = 'edicao';
      this.editandoId = item.id;
      this.erroFormulario = '';
      this.mensagemSucesso = '';
      this.form = {
        nome: item.nome ?? '',
        tipo: item.tipo === 'cep' ? 'unidade' : (item.tipo ?? ''),
        localidade: item.regiao_administrativa?.nome ?? '',
        ativo: item.ativo !== false && item.ativo !== 0 && item.ativo !== '0',
        motivo_inativacao: item.motivo_inativacao ?? '',
      };
    },

    voltarLista() {
      this.modo = 'lista';
      this.editandoId = null;
      this.erroFormulario = '';
      this.form = formVazio();
      this.carregarLista();
    },

    validarForm() {
      return combinarValidacoes(
        textoObrigatorio(this.form.nome, 'Informe o nome.'),
        tamanhoMaximo(this.form.nome, 180, 'O nome deve ter no máximo 180 caracteres.'),
        textoObrigatorio(this.form.tipo, 'Selecione o tipo de estrutura.'),
        textoObrigatorio(this.form.localidade, 'Informe a localidade/região.'),
        tamanhoMaximo(this.form.localidade, 100, 'A localidade deve ter no máximo 100 caracteres.'),
        !this.form.ativo
          ? textoObrigatorio(this.form.motivo_inativacao, 'Informe o motivo da inativação.')
          : '',
        !this.form.ativo
          ? tamanhoMaximo(this.form.motivo_inativacao, 2000, 'O motivo deve ter no máximo 2000 caracteres.')
          : '',
      );
    },

    montarPayload() {
      const ativo = Boolean(this.form.ativo);
      return {
        nome: this.form.nome.trim(),
        tipo: this.form.tipo,
        localidade: this.form.localidade.trim(),
        ativo,
        motivo_inativacao: ativo ? null : (this.form.motivo_inativacao.trim() || null),
      };
    },

    async salvar() {
      if (!this.podeEditar) return;
      this.erroFormulario = this.validarForm();
      if (this.erroFormulario) return;

      this.salvando = true;
      try {
        const payload = this.montarPayload();
        if (this.modo === 'novo') {
          await window.axios.post(ENDPOINT_ESTRUTURAS, payload);
          this.mensagemSucesso = 'Estrutura institucional cadastrada com sucesso.';
        } else {
          await window.axios.put(`${ENDPOINT_ESTRUTURAS}/${this.editandoId}`, payload);
          this.mensagemSucesso = 'Estrutura institucional atualizada com sucesso.';
        }
        limparCacheUnidadesNomes();
        this.voltarLista();
      } catch (erro) {
        this.erroFormulario = extrairErroApi(erro, 'Não foi possível salvar a estrutura institucional.');
      } finally {
        this.salvando = false;
      }
    },

    payloadStatus(item, { ativo, motivo_inativacao = null }) {
      return {
        nome: item.nome,
        tipo: item.tipo === 'cep' ? 'unidade' : item.tipo,
        localidade: item.regiao_administrativa?.nome || undefined,
        regiao_administrativa_id: item.regiao_administrativa_id || item.regiao_administrativa?.id,
        ativo,
        motivo_inativacao,
      };
    },

    pedirInativacao(item) {
      if (!this.podeEditar || !item?.ativo) return;
      this.modalInativacao = {
        aberto: true,
        item,
        motivo: '',
        erro: '',
        salvando: false,
      };
    },

    fecharModalInativacao() {
      if (this.modalInativacao.salvando) return;
      this.modalInativacao = modalInativacaoVazio();
    },

    async confirmarInativacao() {
      const item = this.modalInativacao.item;
      if (!item) return;

      const motivo = String(this.modalInativacao.motivo || '').trim();
      if (!motivo) {
        this.modalInativacao.erro = 'Informe o motivo da inativação.';
        return;
      }
      if (motivo.length > 2000) {
        this.modalInativacao.erro = 'O motivo deve ter no máximo 2000 caracteres.';
        return;
      }

      this.modalInativacao.erro = '';
      this.modalInativacao.salvando = true;
      this.mensagemErro = '';

      try {
        await window.axios.put(
          `${ENDPOINT_ESTRUTURAS}/${item.id}`,
          this.payloadStatus(item, { ativo: false, motivo_inativacao: motivo }),
        );
        limparCacheUnidadesNomes();
        this.mensagemSucesso = 'Estrutura institucional inativada.';
        this.modalInativacao = modalInativacaoVazio();
        await this.carregarLista();
      } catch (erro) {
        this.modalInativacao.erro = extrairErroApi(erro, 'Não foi possível inativar a estrutura.');
        this.modalInativacao.salvando = false;
      }
    },

    async reativar(item) {
      if (!this.podeEditar || item?.ativo) return;
      const ok = window.confirm(`Reativar a estrutura "${item.nome}"?`);
      if (!ok) return;

      this.mensagemErro = '';
      try {
        await window.axios.put(
          `${ENDPOINT_ESTRUTURAS}/${item.id}`,
          this.payloadStatus(item, { ativo: true, motivo_inativacao: null }),
        );
        limparCacheUnidadesNomes();
        this.mensagemSucesso = 'Estrutura institucional reativada.';
        await this.carregarLista();
      } catch (erro) {
        this.mensagemErro = extrairErroApi(erro, 'Não foi possível reativar a estrutura.');
      }
    },
  },
};
