import { getPerfil, podeEditarDados, podeConsultarDados } from './auth';
import CrudPageHeader from '../components/crud/CrudPageHeader.vue';
import CrudAlerts from '../components/crud/CrudAlerts.vue';
import CrudFormShell from '../components/crud/CrudFormShell.vue';

/**
 * Factory de páginas CRUD (Vue Options API).
 * Cada módulo só passa endpoint, formulário, validação e extras específicos.
 */
export function createCrudPage(config) {
  const {
    name,
    endpoint,
    listKey = 'registros',
    detailKey = 'registroDetalhe',
    showKey = null,
    errorKey = 'erro',
    formErrorKey = 'erroFormulario',
    useDetalheAberto = false,
    checkConsultar = true,
    carregandoInicial = true,
    debounceOnLoad = false,
    filtrosIniciais = {},
    formVazio,
    montarForm = null,
    montarPayload,
    validarFormulario = () => '',
    normalizarRegistro = (registro) => ({ ...registro }),
    aplicarMeta = null,
    labelExclusao = (registro) => registro?.id ?? '',
    mensagens = {},
    extraData = () => ({}),
    extraComputed = {},
    extraMethods = {},
    methodAliases = {},
    computedAliases = {},
    components = {},
  } = config;

  const msg = {
    semAcessoConsulta: mensagens.semAcessoConsulta ?? 'Seu perfil não possui acesso para consultar estes registros.',
    soConsulta: mensagens.soConsulta ?? 'Seu perfil só permite consultar estes registros.',
    semPermissaoEditar: mensagens.semPermissaoEditar ?? 'Seu perfil não tem permissão para alterar estes registros.',
    falhaCarregar: mensagens.falhaCarregar ?? 'Não foi possível carregar os registros.',
    falhaSalvar: mensagens.falhaSalvar ?? 'Não foi possível salvar o registro.',
    falhaExcluir: mensagens.falhaExcluir ?? 'Não foi possível excluir o registro.',
    falhaDetalhe: mensagens.falhaDetalhe ?? 'Não foi possível carregar os detalhes.',
    confirmarExclusao: mensagens.confirmarExclusao
      ?? ((registro) => `Deseja excluir "${labelExclusao(registro)}"?`),
  };

  const setErro = (vm, texto) => {
    vm[errorKey] = texto;
  };

  const setErroForm = (vm, texto) => {
    vm[formErrorKey] = texto;
  };

  const limparErro = (vm) => {
    vm[errorKey] = '';
  };

  const limparErroForm = (vm) => {
    if (formErrorKey !== errorKey) {
      vm[formErrorKey] = '';
    }
  };

  const methods = {
    formVazio,

    normalizarRegistro(registro) {
      return normalizarRegistro.call(this, registro, this);
    },

    normalizarData(valor) {
      if (!valor) {
        return '';
      }

      return String(valor).slice(0, 10);
    },

    formatarData(data) {
      const normalizada = this.normalizarData(data);

      if (!normalizada) {
        return '—';
      }

      const [ano, mes, dia] = normalizada.split('-');

      if (!ano || !mes || !dia) {
        return data;
      }

      return `${dia}/${mes}/${ano}`;
    },

    aplicarFiltros() {
      clearTimeout(this.buscaTimeout);
      this.buscaTimeout = setTimeout(() => {
        this.carregarRegistros();
      }, 200);
    },

    async carregarRegistros() {
      if (debounceOnLoad) {
        clearTimeout(this.buscaTimeout);

        return new Promise((resolve) => {
          this.buscaTimeout = setTimeout(async () => {
            await this.executarCarregarRegistros();
            resolve();
          }, 200);
        });
      }

      return this.executarCarregarRegistros();
    },

    async executarCarregarRegistros() {
      this.carregando = true;
      limparErro(this);

      if (checkConsultar && !this.podeConsultar) {
        this[listKey] = [];
        this.carregando = false;
        setErro(this, msg.semAcessoConsulta);
        return;
      }

      try {
        const params = {};

        Object.entries(this.filtros).forEach(([chave, valor]) => {
          if (valor !== '' && valor != null) {
            params[chave] = valor;
          }
        });

        const { data } = await window.axios.get(endpoint, { params });
        const lista = Array.isArray(data.data) ? data.data : [];
        this[listKey] = lista.map((item) => this.normalizarRegistro(item));

        if (data.meta) {
          if (this.meta && typeof this.meta === 'object') {
            this.meta = { ...this.meta, ...data.meta };
          }

          if (typeof aplicarMeta === 'function') {
            aplicarMeta.call(this, data.meta, this);
          }
        }
      } catch (error) {
        setErro(this, this.extrairErro(error, msg.falhaCarregar));
        this[listKey] = [];
      } finally {
        this.carregando = false;
      }
    },

    bloquearSemPermissao(mensagem = msg.soConsulta) {
      setErro(this, mensagem);
      this.mensagemSucesso = '';
      limparErroForm(this);
      this.fecharDetalhes();
      this.modo = 'lista';
    },

    abrirNovo() {
      if (!this.podeEditar) {
        this.bloquearSemPermissao();
        return;
      }

      this.modo = 'novo';
      this.editandoId = null;
      this.form = this.formVazio();
      limparErroForm(this);
      this.mensagemSucesso = '';
      limparErro(this);
      this.fecharDetalhes();
    },

    abrirEdicao(registro) {
      if (!this.podeEditar) {
        this.bloquearSemPermissao();
        return;
      }

      const item = this.normalizarRegistro(registro);

      this.modo = 'editar';
      this.editandoId = item.id ?? registro.id ?? null;
      this.form = typeof montarForm === 'function'
        ? montarForm.call(this, item, this)
        : { ...this.formVazio(), ...item };
      limparErroForm(this);
      this.mensagemSucesso = '';
      limparErro(this);
      this.fecharDetalhes();
    },

    voltarLista() {
      this.modo = 'lista';
      this.editandoId = null;
      limparErroForm(this);
      this.form = this.formVazio();
      this.salvando = false;
    },

    fecharFormulario() {
      this.voltarLista();
    },

    editarDoDetalhe() {
      if (!this[detailKey]) {
        return;
      }

      this.abrirEdicao(this[detailKey]);
    },

    validarFormulario() {
      return validarFormulario.call(this, this.form, this) || '';
    },

    async salvarRegistro() {
      if (!this.podeEditar) {
        setErroForm(this, msg.semPermissaoEditar);
        if (formErrorKey !== errorKey) {
          this.modo = 'lista';
        }
        return;
      }

      const erroValidacao = this.validarFormulario();

      if (erroValidacao) {
        setErroForm(this, erroValidacao);
        return;
      }

      this.salvando = true;
      limparErroForm(this);
      this.mensagemSucesso = '';
      limparErro(this);

      const payload = montarPayload.call(this, this.form, this);

      try {
        if ((this.modo === 'editar' || this.editandoId) && this.editandoId) {
          const { data } = await window.axios.put(`${endpoint}/${this.editandoId}`, payload);
          this.mensagemSucesso = data.message;
        } else {
          const { data } = await window.axios.post(endpoint, payload);
          this.mensagemSucesso = data.message;
        }

        this.voltarLista();
        await this.carregarRegistros();
      } catch (error) {
        setErroForm(this, this.extrairErro(error, msg.falhaSalvar));
      } finally {
        this.salvando = false;
      }
    },

    async excluirRegistro(registro) {
      if (!this.podeEditar) {
        this.bloquearSemPermissao();
        return;
      }

      if (!registro) {
        return;
      }

      const confirmar = window.confirm(
        typeof msg.confirmarExclusao === 'function'
          ? msg.confirmarExclusao(registro)
          : msg.confirmarExclusao,
      );

      if (!confirmar) {
        return;
      }

      limparErro(this);
      this.mensagemSucesso = '';

      if (this[detailKey]?.id === registro.id) {
        this.fecharDetalhes();
      }

      try {
        const { data } = await window.axios.delete(`${endpoint}/${registro.id}`);
        this.mensagemSucesso = data.message;
        this.fecharDetalhes();
        await this.carregarRegistros();
      } catch (error) {
        setErro(this, this.extrairErro(error, msg.falhaExcluir));
      }
    },

    async abrirDetalhes(registro) {
      if (useDetalheAberto) {
        this.detalheAberto = true;
      }

      this[detailKey] = this.normalizarRegistro(registro);

      try {
        const { data } = await window.axios.get(`${endpoint}/${registro.id}`);
        const bruto = showKey && data[showKey] != null
          ? data[showKey]
          : (data.data ?? registro);
        this[detailKey] = this.normalizarRegistro(bruto);
      } catch (error) {
        setErro(this, this.extrairErro(error, msg.falhaDetalhe));
      }
    },

    fecharDetalhes() {
      if (useDetalheAberto) {
        this.detalheAberto = false;
      }

      this[detailKey] = null;
    },

    extrairErro(error, fallback) {
      if (error.response?.data?.message) {
        return error.response.data.message;
      }

      const errors = error.response?.data?.errors;

      if (errors) {
        const primeiro = Object.values(errors)[0];

        return Array.isArray(primeiro) ? primeiro[0] : fallback;
      }

      return fallback;
    },

    ...extraMethods,
  };

  Object.entries(methodAliases).forEach(([alias, target]) => {
    if (typeof methods[target] === 'function') {
      methods[alias] = methods[target];
    }
  });

  const computed = {
    perfilUsuario() {
      return getPerfil();
    },

    podeEditar() {
      return podeEditarDados();
    },

    podeConsultar() {
      return podeConsultarDados();
    },

    acessoBloqueado() {
      return checkConsultar ? !this.podeConsultar : false;
    },

    temFiltro() {
      return Object.values(this.filtros).some((valor) => valor !== '' && valor != null);
    },

    totalRegistros() {
      return (this[listKey] ?? []).length;
    },

    listaFiltrada() {
      return this[listKey] ?? [];
    },

    ...extraComputed,
  };

  Object.entries(computedAliases).forEach(([alias, target]) => {
    computed[alias] = function aliasComputed() {
      return this[target];
    };
  });

  return {
    name,

    components: {
      CrudPageHeader,
      CrudAlerts,
      CrudFormShell,
      ...components,
    },

    data() {
      const base = {
        modo: 'lista',
        [listKey]: [],
        filtros: { ...filtrosIniciais },
        buscaTimeout: null,
        carregando: carregandoInicial,
        mensagemSucesso: '',
        [detailKey]: null,
        salvando: false,
        editandoId: null,
        form: formVazio.call(this),
        [errorKey]: '',
        ...extraData.call(this),
      };

      if (formErrorKey !== errorKey) {
        base[formErrorKey] = '';
      }

      if (useDetalheAberto) {
        base.detalheAberto = false;
      }

      return base;
    },

    computed,

    mounted() {
      this.carregarRegistros();
    },

    methods,
  };
}
