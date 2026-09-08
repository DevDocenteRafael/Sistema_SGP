import { podeEditarDados } from './auth';
import {
  combinarValidacoes,
  extrairErroApi,
  tamanhoMaximo,
  textoObrigatorio,
  validarData,
  validarOrdemDatas,
} from '../utils/validacao';
import CrudPageHeader from '../components/crud/CrudPageHeader.vue';
import CrudAlerts from '../components/crud/CrudAlerts.vue';
import CrudFormShell from '../components/crud/CrudFormShell.vue';
import PageTableCard from '../components/crud/PageTableCard.vue';
import { mixinHistoricoFormulario } from './formularioHistorico';

const ENDPOINT = '/api/jornadas-pedagogicas';

function formVazio() {
  return {
    titulo: '',
    data_inicio: '',
    data_fim: '',
    tem_pre_jornada: 'Não',
    data_pre_jornada: '',
    local: '',
    espaco: '',
    verba: '',
    custos: '',
    programacao: '',
    setores: '',
    observacoes: '',
    status: 'Rascunho',
    anexoFile: null,
    anexo_url: '',
  };
}

export default {
  name: 'JornadaPedagogica',
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
        status: '',
      },
      registros: [],
      registroDetalhe: null,
      editandoId: null,
      form: formVazio(),
      meta: {
        status: ['Rascunho', 'Consolidado', 'Enviado'],
        sim_nao: ['Sim', 'Não'],
      },
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
  },
  mounted() {
    this.carregarRegistros();
  },
  methods: {
    limparFiltros() {
      this.filtros = { busca: '', status: '' };
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
        this.meta = { ...this.meta, ...(data.meta ?? {}) };
      } catch (error) {
        this.mensagemErro = this.extrairErro(error, 'Não foi possível carregar as jornadas pedagógicas.');
        this.registros = [];
      } finally {
        this.carregando = false;
      }
    },

    abrirNovo() {
      if (!this.podeEditar) {
        this.mensagemErro = 'Seu perfil não tem permissão para criar jornadas pedagógicas.';
        return;
      }

      this.aplicarEstadoNovoLocal();
      this.empilharHistoricoFormulario('novo');
    },

    aplicarEstadoNovoLocal() {
      if (!this.podeEditar) {
        this.mensagemErro = 'Seu perfil não tem permissão para alterar jornadas pedagógicas.';
        this.aplicarEstadoListaLocal();
        return;
      }

      this.modo = 'novo';
      this.editandoId = null;
      this.form = formVazio();
      this.erroFormulario = '';
      this.registroDetalhe = null;
    },

    abrirEdicao(item) {
      if (!this.podeEditar) {
        this.mensagemErro = 'Seu perfil não tem permissão para editar jornadas pedagógicas.';
        return;
      }

      this.aplicarEstadoEdicaoLocal(item);
      this.empilharHistoricoFormulario('editar', item.id);
    },

    aplicarEstadoEdicaoLocal(item) {
      this.modo = 'editar';
      this.editandoId = item.id;
      this.form = {
        titulo: item.titulo ?? '',
        data_inicio: this.normalizarData(item.data_inicio),
        data_fim: this.normalizarData(item.data_fim),
        tem_pre_jornada: item.tem_pre_jornada || 'Não',
        data_pre_jornada: this.normalizarData(item.data_pre_jornada),
        local: item.local ?? '',
        espaco: item.espaco ?? '',
        verba: item.verba ?? '',
        custos: item.custos ?? '',
        programacao: item.programacao ?? '',
        setores: item.setores ?? '',
        observacoes: item.observacoes ?? '',
        status: item.status || 'Rascunho',
        anexoFile: null,
        anexo_url: item.anexo_url || '',
      };
      this.erroFormulario = '';
      this.registroDetalhe = null;
    },

    async aplicarEstadoEdicaoPorId(id) {
      let item = this.registros.find((registro) => String(registro.id) === String(id));

      if (!item) {
        try {
          const { data } = await window.axios.get(`${ENDPOINT}/${id}`);
          item = data.jornada || data.data || null;
        } catch {
          item = null;
        }
      }

      if (!item) {
        this.aplicarEstadoListaLocal();
        this.limparHistoricoFormulario();
        return;
      }

      if (!this.podeEditar) {
        this.mensagemErro = 'Seu perfil não tem permissão para editar jornadas pedagógicas.';
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

    aoEscolherAnexo(event) {
      this.form.anexoFile = event.target.files?.[0] || null;
    },

    validarFormulario() {
      return combinarValidacoes(
        textoObrigatorio(this.form.titulo, 'O título da jornada é obrigatório.'),
        tamanhoMaximo(this.form.titulo, 255, 'O título deve ter no máximo 255 caracteres.'),
        validarData(this.form.data_inicio, { rotulo: 'Data de início' }),
        validarData(this.form.data_fim, { rotulo: 'Data de término' }),
        validarOrdemDatas(this.form.data_inicio, this.form.data_fim),
        this.form.tem_pre_jornada === 'Sim'
          ? validarData(this.form.data_pre_jornada, { obrigatorio: true, rotulo: 'Data da pré-jornada' })
          : '',
        textoObrigatorio(this.form.status, 'O status é obrigatório.'),
        this.form.local
          ? tamanhoMaximo(this.form.local, 255, 'O local deve ter no máximo 255 caracteres.')
          : '',
        this.form.espaco
          ? tamanhoMaximo(this.form.espaco, 255, 'O espaço deve ter no máximo 255 caracteres.')
          : '',
        this.form.verba
          ? tamanhoMaximo(this.form.verba, 100, 'A verba deve ter no máximo 100 caracteres.')
          : '',
        this.form.setores
          ? tamanhoMaximo(this.form.setores, 255, 'Setores deve ter no máximo 255 caracteres.')
          : '',
        this.form.custos
          ? tamanhoMaximo(this.form.custos, 2000, 'Custos deve ter no máximo 2000 caracteres.')
          : '',
        this.form.programacao
          ? tamanhoMaximo(this.form.programacao, 2000, 'A programação deve ter no máximo 2000 caracteres.')
          : '',
        this.form.observacoes
          ? tamanhoMaximo(this.form.observacoes, 2000, 'As observações devem ter no máximo 2000 caracteres.')
          : '',
      );
    },

    async salvarRegistro() {
      if (!this.podeEditar) {
        this.erroFormulario = 'Seu perfil não tem permissão para salvar jornadas pedagógicas.';
        return;
      }

      const erro = this.validarFormulario();

      if (erro) {
        this.erroFormulario = erro;
        return;
      }

      this.salvando = true;
      this.erroFormulario = '';

      const formData = new FormData();
      formData.append('titulo', this.form.titulo);
      formData.append('status', this.form.status);
      formData.append('tem_pre_jornada', this.form.tem_pre_jornada || 'Não');
      formData.append('data_inicio', this.form.data_inicio || '');
      formData.append('data_fim', this.form.data_fim || '');
      formData.append('data_pre_jornada', this.form.tem_pre_jornada === 'Sim' ? (this.form.data_pre_jornada || '') : '');
      formData.append('local', this.form.local || '');
      formData.append('espaco', this.form.espaco || '');
      formData.append('verba', this.form.verba || '');
      formData.append('custos', this.form.custos || '');
      formData.append('programacao', this.form.programacao || '');
      formData.append('setores', this.form.setores || '');
      formData.append('observacoes', this.form.observacoes || '');

      if (this.form.anexoFile) {
        formData.append('anexo', this.form.anexoFile);
      }

      try {
        let url = ENDPOINT;

        if (this.editandoId) {
          url = `${ENDPOINT}/${this.editandoId}`;
          formData.append('_method', 'PUT');
        }

        const { data } = await window.axios.post(url, formData);
        this.mensagemSucesso = data.message;

        this.voltarLista();
        await this.carregarRegistros();
      } catch (error) {
        this.erroFormulario = this.extrairErro(error, 'Não foi possível salvar a jornada pedagógica.');
      } finally {
        this.salvando = false;
      }
    },

    async excluirRegistro(item) {
      if (!this.podeEditar) {
        this.mensagemErro = 'Seu perfil não tem permissão para excluir jornadas pedagógicas.';
        return;
      }

      if (!window.confirm(`Excluir a jornada "${item.titulo}"? Esta ação não pode ser desfeita.`)) {
        return;
      }

      try {
        const { data } = await window.axios.delete(`${ENDPOINT}/${item.id}`);
        this.mensagemSucesso = data.message;
        this.fecharDetalhes();
        await this.carregarRegistros();
      } catch (error) {
        this.mensagemErro = this.extrairErro(error, 'Não foi possível excluir a jornada pedagógica.');
      }
    },

    async baixarPdf(item) {
      try {
        const response = await window.axios.get(`${ENDPOINT}/${item.id}/pdf`, {
          responseType: 'blob',
        });

        const contentType = response.headers['content-type'] || '';

        if (contentType.includes('application/json')) {
          const texto = await response.data.text();
          const json = JSON.parse(texto);
          throw new Error(json.message || 'Falha ao gerar o PDF.');
        }

        const blob = new Blob([response.data], { type: 'application/pdf' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `jornada-pedagogica-${item.id}.pdf`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
      } catch (error) {
        this.mensagemErro = this.extrairErro(error, 'Não foi possível gerar o PDF.');
      }
    },

    textoPeriodo(item) {
      const inicio = this.formatarData(item.data_inicio);
      const fim = this.formatarData(item.data_fim);

      if (inicio === '—' && fim === '—') {
        return '—';
      }

      return `${inicio} a ${fim}`;
    },

    classeStatus(status) {
      const valor = String(status || '').toLowerCase();

      return {
        'badge-rascunho': valor === 'rascunho',
        'badge-consolidado': valor === 'consolidado',
        'badge-enviado': valor === 'enviado',
      };
    },

    normalizarData(valor) {
      return valor ? String(valor).slice(0, 10) : '';
    },

    formatarData(valor) {
      const data = this.normalizarData(valor);

      if (!data) {
        return '—';
      }

      const [ano, mes, dia] = data.split('-');

      return `${dia}/${mes}/${ano}`;
    },

    extrairErro(error, fallback) {
      return extrairErroApi(error, fallback);
    },
  },
};
