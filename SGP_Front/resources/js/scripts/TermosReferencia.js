import IndicadorPrazo from '../components/ciclo-vida/IndicadorPrazo.vue';
import LinhaDoTempo from '../components/ciclo-vida/LinhaDoTempo.vue';
import ProcessoSeiLink from '../components/ciclo-vida/ProcessoSeiLink.vue';
import BadgeStatus from '../components/termos-referencia/BadgeStatus.vue';
import {
  combinarValidacoes,
  extrairErroApi,
  formatarProcessoSeiInput,
  somenteAlfanumericoProcesso,
  tamanhoMaximo,
  textoObrigatorio,
  validarData,
  validarOrdemDatas,
  validarProcessoSei,
} from '../utils/validacao';
import { podeEditarDados } from './auth';
import Loading from '../components/termos-referencia/Loading.vue';
import Feedback from '../components/termos-referencia/Feedback.vue';
import CrudAlerts from '../components/crud/CrudAlerts.vue';
import CrudPageHeader from '../components/crud/CrudPageHeader.vue';
import CrudFormShell from '../components/crud/CrudFormShell.vue';
import PageTableCard from '../components/crud/PageTableCard.vue';
import { mixinHistoricoFormulario } from './formularioHistorico';

const ENDPOINT_API = '/api/termos-referencia';
const STATUS_TRAMITACAO = 'Em tramitação (fora da CPED)';

const FORM_VAZIO = {
  nome: '',
  eixo: '',
  processo_sei: '',
  prazo_deadline: '',
  status: 'Planejamento',
  observacao: '',
  data_inicio: '',
  data_fim: '',
};

export default {
  name: 'TermosReferencia',
  mixins: [mixinHistoricoFormulario],
  components: {
    IndicadorPrazo,
    LinhaDoTempo,
    ProcessoSeiLink,
    BadgeStatus,
    Loading,
    Feedback,
    CrudAlerts,
    CrudPageHeader,
    CrudFormShell,
    PageTableCard,
  },
  data() {
    return {
      modo: 'lista',
      abaForm: 'basico',
      detalheAberto: false,
      carregando: false,
      carregandoFormulario: false,
      termos: [],
      termoSelecionado: null,
      editandoId: null,
      form: { ...FORM_VAZIO },
      filtros: {
        busca: '',
        eixo: '',
        status: '',
        prazo: '',
      },
      historico: [],
      mensagemSucesso: '',
      mensagemErro: '',
      erroFormulario: '',
      debounceTimeout: null,
      eixosDisponiveis: [],
      statusDisponiveis: ['Planejamento', 'Em Andamento', STATUS_TRAMITACAO, 'Concluído', 'Arquivado'],
      confirmandoExclusao: false,
      confirmandoId: null,
    };
  },
  computed: {
    totalTermos() {
      return this.termos.length;
    },
    temFiltro() {
      return Object.values(this.filtros).some((v) => v);
    },
    podeEditar() {
      return podeEditarDados();
    },
    abasForm() {
      return [
        { id: 'basico', label: 'Dados Básicos' },
        { id: 'acompanhamento', label: 'Acompanhamento' },
        { id: 'historico', label: 'Histórico' },
      ];
    },
  },
  methods: {
    /**
     * Carrega a lista de Termos de Referência do backend com filtros
     */
    async carregarTermos() {
      this.carregando = true;
      this.mensagemErro = '';

      try {
        const params = {};
        Object.entries(this.filtros).forEach(([chave, valor]) => {
          if (valor !== '' && valor != null) {
            params[chave] = valor;
          }
        });

        const response = await window.axios.get(ENDPOINT_API, { params });
        const dados = response.data;

        this.termos = Array.isArray(dados.data) ? dados.data : [];

        // Aplicar meta data do backend
        if (dados.meta) {
          if (Array.isArray(dados.meta.eixos)) {
            this.eixosDisponiveis = dados.meta.eixos;
          }
          if (Array.isArray(dados.meta.status)) {
            this.statusDisponiveis = dados.meta.status;
          }
        }
      } catch (error) {
        this.mensagemErro = extrairErroApi(error, 'Não foi possível carregar os Termos de Referência.');
        this.termos = [];
      } finally {
        this.carregando = false;
      }
    },

    /**
     * Aplica filtros com debounce
     */
    aplicarFiltros() {
      clearTimeout(this.debounceTimeout);
      this.debounceTimeout = setTimeout(() => {
        this.carregarTermos();
      }, 300);
    },

    /**
     * Abre modal de detalhes de um TR
     */
    async abrirDetalhes(termo) {
      this.termoSelecionado = termo;
      this.detalheAberto = true;
      this.historico = [];

      try {
        const { data } = await window.axios.get(`${ENDPOINT_API}/${termo.id}`);
        const detalhe = data.termo || data.data || termo;
        this.termoSelecionado = detalhe;
        this.historico = Array.isArray(detalhe.historicos) ? detalhe.historicos : [];
      } catch (error) {
        this.mensagemErro = extrairErroApi(error, 'Não foi possível carregar o histórico do TR.');
      }
    },

    /**
     * Fecha modal de detalhes
     */
    fecharDetalhes() {
      this.detalheAberto = false;
      this.termoSelecionado = null;
    },

    /**
     * Abre formulário para novo TR
     */
    abrirNovo() {
      if (!this.podeEditar) {
        this.mensagemErro = 'Seu perfil não tem permissão para criar Termos de Referência.';
        return;
      }

      this.aplicarEstadoNovoLocal();
      this.empilharHistoricoFormulario('novo');
    },

    aplicarEstadoNovoLocal() {
      this.modo = 'novo';
      this.editandoId = null;
      this.abaForm = 'basico';
      this.form = { ...FORM_VAZIO };
      this.historico = [];
      this.mensagemErro = '';
      this.erroFormulario = '';
      this.mensagemSucesso = '';
      this.fecharDetalhes();
    },

    /**
     * Abre formulário para editar um TR existente
     */
    abrirEdicao(termo) {
      if (!this.podeEditar) {
        this.mensagemErro = 'Seu perfil não tem permissão para editar Termos de Referência.';
        return;
      }

      this.aplicarEstadoEdicaoLocal(termo);
      this.empilharHistoricoFormulario('edicao', termo.id);
    },

    aplicarEstadoEdicaoLocal(termo) {
      this.modo = 'edicao';
      this.editandoId = termo.id;
      this.abaForm = 'basico';
      this.form = {
        nome: termo.nome || '',
        eixo: termo.eixo || '',
        processo_sei: termo.processo_sei || '',
        prazo_deadline: this.normalizarData(termo.prazo_deadline) || '',
        status: termo.status || 'Planejamento',
        observacao: termo.observacao || '',
        data_inicio: this.normalizarData(termo.data_inicio) || '',
        data_fim: this.normalizarData(termo.data_fim) || '',
      };
      this.mensagemErro = '';
      this.erroFormulario = '';
      this.mensagemSucesso = '';
      this.fecharDetalhes();
      this.carregarHistorico(termo.id);
    },

    async aplicarEstadoEdicaoPorId(id) {
      let termo = this.termos.find((item) => String(item.id) === String(id));

      if (!termo) {
        try {
          const { data } = await window.axios.get(`${ENDPOINT_API}/${id}`);
          termo = data.termo || data.data || null;
        } catch {
          termo = null;
        }
      }

      if (!termo) {
        this.aplicarEstadoListaLocal();
        this.limparHistoricoFormulario();
        return;
      }

      if (!this.podeEditar) {
        this.mensagemErro = 'Seu perfil não tem permissão para editar Termos de Referência.';
        this.aplicarEstadoListaLocal();
        this.limparHistoricoFormulario();
        return;
      }

      this.aplicarEstadoEdicaoLocal(termo);
    },

    /**
     * Valida o formulário antes de enviar
     */
    validarFormulario() {
      const erro = combinarValidacoes(
        textoObrigatorio(this.form.nome, 'O nome do Termo de Referência é obrigatório.'),
        tamanhoMaximo(this.form.nome, 255, 'O nome deve ter no máximo 255 caracteres.'),
        textoObrigatorio(this.form.eixo, 'O eixo é obrigatório.'),
        validarProcessoSei(this.form.processo_sei, { obrigatorio: true }),
        validarData(this.form.prazo_deadline, { obrigatorio: true, rotulo: 'Prazo/deadline' }),
        textoObrigatorio(this.form.status, 'O status é obrigatório.'),
        validarData(this.form.data_inicio, { rotulo: 'Data de início' }),
        validarData(this.form.data_fim, { rotulo: 'Data de término' }),
        validarOrdemDatas(
          this.form.data_inicio,
          this.form.data_fim,
          'A data de término deve ser posterior ou igual à data de início.',
        ),
      );

      this.erroFormulario = erro;
      return !erro;
    },

    formatarProcessoSei: formatarProcessoSeiInput('processo_sei'),

    /**
     * Normaliza data do formato DD/MM/YYYY para YYYY-MM-DD e vice-versa
     */
    normalizarData(valor) {
      if (!valor) return '';
      const str = String(valor);

      // Se já está em YYYY-MM-DD, retorna como é
      if (/^\d{4}-\d{2}-\d{2}/.test(str)) {
        return str.slice(0, 10);
      }

      // Tenta converter DD/MM/YYYY para YYYY-MM-DD
      if (/^\d{2}\/\d{2}\/\d{4}/.test(str)) {
        const [dia, mes, ano] = str.split('/');
        return `${ano}-${mes}-${dia}`;
      }

      return str.slice(0, 10);
    },

    /**
     * Salva um novo TR ou atualiza um existente
     */
    async salvarTermo() {
      if (!this.podeEditar) {
        this.mensagemErro = 'Seu perfil não tem permissão para alterar Termos de Referência.';
        return;
      }

      this.mensagemErro = '';
      this.erroFormulario = '';
      this.mensagemSucesso = '';

      if (!this.validarFormulario()) {
        return;
      }

      this.carregandoFormulario = true;

      try {
        let response;
        const payload = {
          nome: this.form.nome.trim(),
          eixo: this.form.eixo,
          processo_sei: somenteAlfanumericoProcesso(this.form.processo_sei).trim(),
          prazo_deadline: this.form.prazo_deadline,
          status: this.form.status,
          observacao: this.form.observacao?.trim() || null,
          data_inicio: this.form.data_inicio || null,
          data_fim: this.form.data_fim || null,
        };

        if (this.modo === 'novo') {
          response = await window.axios.post(ENDPOINT_API, payload);
          this.mensagemSucesso = 'Termo de Referência criado com sucesso!';
        } else {
          response = await window.axios.put(`${ENDPOINT_API}/${this.editandoId}`, payload);
          this.mensagemSucesso = 'Termo de Referência atualizado com sucesso!';
        }

        // Recarregar lista e fechar formulário
        await this.carregarTermos();
        this.fecharFormulario();

        // Limpar mensagem de sucesso após 5 segundos
        setTimeout(() => {
          this.mensagemSucesso = '';
        }, 5000);
      } catch (error) {
        this.erroFormulario = extrairErroApi(error, 'Não foi possível salvar o Termo de Referência.');
      } finally {
        this.carregandoFormulario = false;
      }
    },

    /**
     * Inicia processo de exclusão com confirmação
     */
    iniciarExclusao(termo) {
      if (!this.podeEditar) {
        this.mensagemErro = 'Seu perfil não tem permissão para excluir Termos de Referência.';
        return;
      }

      this.confirmandoId = termo.id;
      this.confirmandoExclusao = true;
    },

    /**
     * Cancela exclusão
     */
    cancelarExclusao() {
      this.confirmandoExclusao = false;
      this.confirmandoId = null;
    },

    /**
     * Deleta um TR (após confirmação)
     */
    async excluirTermo() {
      if (!this.confirmandoId) return;

      this.mensagemErro = '';
      this.mensagemSucesso = '';
      this.carregando = true;

      try {
        await window.axios.delete(`${ENDPOINT_API}/${this.confirmandoId}`);
        this.mensagemSucesso = 'Termo de Referência excluído com sucesso!';

        // Recarregar lista
        await this.carregarTermos();
        this.fecharDetalhes();

        // Limpar mensagem após 5 segundos
        setTimeout(() => {
          this.mensagemSucesso = '';
        }, 5000);
      } catch (error) {
        this.mensagemErro = extrairErroApi(error, 'Não foi possível excluir o Termo de Referência.');
      } finally {
        this.carregando = false;
        this.confirmandoExclusao = false;
        this.confirmandoId = null;
      }
    },

    /**
     * Fecha formulário e volta para lista
     */
    async carregarHistorico(id) {
      this.historico = [];

      if (!id) {
        return;
      }

      try {
        const { data } = await window.axios.get(`${ENDPOINT_API}/${id}`);
        const detalhe = data.termo || data.data || {};
        this.historico = Array.isArray(detalhe.historicos) ? detalhe.historicos : [];
      } catch {
        this.historico = [];
      }
    },

    fecharFormulario() {
      this.aplicarEstadoListaLocal();
      this.limparHistoricoFormulario();
    },

    aplicarEstadoListaLocal() {
      this.modo = 'lista';
      this.form = { ...FORM_VAZIO };
      this.editandoId = null;
      this.abaForm = 'basico';
      this.historico = [];
      this.mensagemErro = '';
      this.erroFormulario = '';
    },

    /**
     * Fecha mensagem de feedback
     */
    fecharFeedback() {
      this.mensagemSucesso = '';
      this.mensagemErro = '';
      this.erroFormulario = '';
    },

    /**
     * Converte status para tipo de badge (para BadgeStatus)
     */
    statusToBadgeType(status) {
      const mapa = {
        Planejamento: 'planejamento',
        'Em Andamento': 'andamento',
        [STATUS_TRAMITACAO]: 'tramitacao',
        Concluído: 'concluido',
        Arquivado: 'arquivado',
      };
      return mapa[status] || 'padrao';
    },

    /**
     * Calcula status do prazo para o indicador (verde/amarelo/vermelho)
     */
    semaforoDe(termo) {
      return termo?.semaforo || 'amarelo';
    },
    labelPrazo(termo) {
      const mapa = {
        no_prazo: 'No prazo',
        atencao: 'Atenção',
        critico: 'Crítico',
        vencido: 'Vencido',
      };
      return mapa[termo?.status_prazo] || null;
    },

    /**
     * Formata data de YYYY-MM-DD para DD/MM/YYYY
     */
    formatarData(data) {
      if (!data) return '';
      const str = String(data).slice(0, 10);
      const [ano, mes, dia] = str.split('-');
      if (!ano || !mes || !dia) return data;
      return `${dia}/${mes}/${ano}`;
    },
  },

  mounted() {
    // Carrega dados iniciais
    this.carregarTermos();
  },
};
