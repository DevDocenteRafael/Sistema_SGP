import IndicadorPrazo from '../components/ciclo-vida/IndicadorPrazo.vue';
import DocumentosVinculados from '../components/documentos/DocumentosVinculados.vue';
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
import Pagination from '../components/crud/Pagination.vue';
import { mixinHistoricoFormulario } from './formularioHistorico';
import {
  buscarRegistroDoAlerta,
  idDaNotificacao,
  limparQueryNotificacao,
  metaListaUnitaria,
} from './filtroNotificacao';

const ENDPOINT_API = '/api/termos-referencia';
const STATUS_TRAMITACAO = 'Em tramitação (fora da CPED)';

const FORM_VAZIO = {
  nome: '',
  numero_tr: '',
  eixo: '',
  processo_sei: '',
  numero_ata: '',
  data_vencimento_ata: '',
  ata_renovada: 'nao',
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
    DocumentosVinculados,
    LinhaDoTempo,
    ProcessoSeiLink,
    BadgeStatus,
    Loading,
    Feedback,
    CrudAlerts,
    CrudPageHeader,
    CrudFormShell,
    PageTableCard,
    Pagination,
  },
  data() {
    return {
      modo: 'lista',
      abaForm: 'basico',
      detalheAberto: false,
      carregando: false,
      carregandoFormulario: false,
      termos: [],
      meta: { total: 0, per_page: 10, current_page: 1, last_page: 1, from: 0, to: 0 },
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
    prazoOptions() {
      const totais = this.meta.contagens || {};
      return [
        { value: 'no_prazo', label: `Vigente (${totais.no_prazo ?? 0})` },
        { value: 'atencao', label: `Atenção (${totais.atencao ?? 0})` },
        { value: 'vencido', label: `Vencida (${totais.vencidos ?? 0})` },
      ];
    },
    totalTermos() {
      return this.termos.length;
    },
    temFiltro() {
      return Object.values(this.filtros).some((v) => v)
        || Boolean(idDaNotificacao(this.$route));
    },
    podeEditar() {
      return podeEditarDados();
    },
    abasForm() {
      const abas = [
        { id: 'basico', label: 'Dados Básicos' },
        { id: 'acompanhamento', label: 'Acompanhamento' },
      ];

      if (this.modo !== 'novo') {
        abas.push({ id: 'historico', label: 'Histórico' });
      }

      return abas;
    },
    indiceAbaForm() {
      return this.abasForm.findIndex((aba) => aba.id === this.abaForm);
    },
    ehPrimeiraAbaForm() {
      return this.indiceAbaForm <= 0;
    },
    ehUltimaAbaForm() {
      return this.indiceAbaForm >= this.abasForm.length - 1;
    },
  },
  methods: {
    limparFiltros() {
      this.filtros = {
        busca: '',
        eixo: '',
        status: '',
        prazo: '',
      };
      this.meta.current_page = 1;
      if (limparQueryNotificacao(this)) {
        return;
      }
      this.carregarTermos();
    },

    aoMudarAlerta() {
      this.filtros = {
        busca: '',
        eixo: '',
        status: '',
        prazo: '',
      };
      this.meta.current_page = 1;
      this.carregarTermos();
    },

    /**
     * Carrega a lista de Termos de Referência do backend com filtros
     */
    async carregarTermos() {
      this.carregando = true;
      this.mensagemErro = '';

      try {
        const alertaId = idDaNotificacao(this.$route);
        if (alertaId) {
          const registro = await buscarRegistroDoAlerta(ENDPOINT_API, alertaId, ['termo', 'data']);
          this.termos = registro ? [registro] : [];
          this.meta = metaListaUnitaria(this.meta, this.termos.length);
          if (!registro) {
            this.mensagemErro = 'Não foi possível localizar o termo do alerta.';
          }
          return;
        }

        const params = { page: this.meta.current_page || 1, per_page: this.meta.per_page || 10 };
        Object.entries(this.filtros).forEach(([chave, valor]) => {
          if (valor !== '' && valor != null) {
            params[chave] = valor;
          }
        });

        const response = await window.axios.get(ENDPOINT_API, { params });
        const dados = response.data;

        this.termos = Array.isArray(dados.data) ? dados.data : [];
        this.meta = { ...this.meta, ...(dados.meta ?? {}) };

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
      this.meta.current_page = 1;
      this.debounceTimeout = setTimeout(() => {
        this.carregarTermos();
      }, 300);
    },

    irParaPagina(page) {
      const pagina = Number(page);
      if (!Number.isInteger(pagina) || pagina < 1 || pagina > (this.meta.last_page || 1) || this.carregando) return;
      this.meta.current_page = pagina;
      this.carregarTermos();
    },

    alterarRegistrosPorPagina(perPage) {
      const quantidade = Number(perPage);
      if (!Number.isInteger(quantidade) || quantidade < 1 || this.carregando) return;
      this.meta.per_page = quantidade;
      this.meta.current_page = 1;
      this.carregarTermos();
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
        numero_tr: termo.numero_tr || '',
        eixo: termo.eixo || '',
        processo_sei: termo.processo_sei || '',
        numero_ata: termo.numero_ata || '',
        data_vencimento_ata: this.normalizarData(termo.data_vencimento_ata) || '',
        ata_renovada: termo.ata_renovada ? 'sim' : 'nao',
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
    validarAba(abaId) {
      if (abaId === 'basico') {
        return combinarValidacoes(
          textoObrigatorio(this.form.nome, 'O nome do Termo de Referência é obrigatório.'),
          tamanhoMaximo(this.form.nome, 255, 'O nome deve ter no máximo 255 caracteres.'),
          textoObrigatorio(this.form.eixo, 'O eixo é obrigatório.'),
          validarProcessoSei(this.form.processo_sei, { obrigatorio: true }),
        );
      }

      if (abaId === 'acompanhamento') {
        return combinarValidacoes(
          textoObrigatorio(this.form.observacao, 'Informe as observações.'),
          validarData(this.form.prazo_deadline, { obrigatorio: true, rotulo: 'Prazo/deadline' }),
          textoObrigatorio(this.form.status, 'O status é obrigatório.'),
          validarData(this.form.data_inicio, { obrigatorio: true, rotulo: 'Data de início' }),
          validarData(this.form.data_fim, { obrigatorio: true, rotulo: 'Data de término' }),
          this.form.data_vencimento_ata
            ? validarData(this.form.data_vencimento_ata, { obrigatorio: false, rotulo: 'Vencimento da Ata' })
            : '',
          this.form.numero_ata
            ? tamanhoMaximo(this.form.numero_ata, 100, 'O número da Ata deve ter no máximo 100 caracteres.')
            : '',
          validarOrdemDatas(
            this.form.data_inicio,
            this.form.data_fim,
            'A data de término deve ser posterior ou igual à data de início.',
          ),
          this.form.observacao
            ? tamanhoMaximo(this.form.observacao, 2000, 'A observação deve ter no máximo 2000 caracteres.')
            : '',
        );
      }

      return '';
    },

    validarFormulario() {
      for (const aba of this.abasForm) {
        const erro = this.validarAba(aba.id);
        if (erro) {
          this.abaForm = aba.id;
          this.erroFormulario = erro;
          return false;
        }
      }

      this.erroFormulario = '';
      return true;
    },

    selecionarAbaForm(abaId) {
      const destino = this.abasForm.findIndex((aba) => aba.id === abaId);
      if (destino < 0) {
        return;
      }

      const atual = Math.max(this.indiceAbaForm, 0);
      if (destino > atual) {
        for (let i = atual; i < destino; i += 1) {
          const erro = this.validarAba(this.abasForm[i].id);
          if (erro) {
            this.abaForm = this.abasForm[i].id;
            this.erroFormulario = erro;
            return;
          }
        }
      }

      this.erroFormulario = '';
      this.abaForm = abaId;
    },

    irAbaAnterior() {
      if (this.ehPrimeiraAbaForm) {
        return;
      }

      this.erroFormulario = '';
      this.abaForm = this.abasForm[this.indiceAbaForm - 1].id;
    },

    irAbaProxima() {
      if (this.ehUltimaAbaForm) {
        return;
      }

      const erro = this.validarAba(this.abaForm);
      if (erro) {
        this.erroFormulario = erro;
        return;
      }

      this.erroFormulario = '';
      this.abaForm = this.abasForm[this.indiceAbaForm + 1].id;
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
          numero_tr: this.form.numero_tr?.trim() || null,
          numero_ata: this.form.numero_ata?.trim() || null,
          data_vencimento_ata: this.form.data_vencimento_ata || null,
          ata_renovada: this.form.ata_renovada === 'sim',
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
        no_prazo: 'Vigente',
        atencao: 'Atenção',
        vencido: 'Vencida',
      };
      return mapa[termo?.status_prazo] || null;
    },
    rotuloDataPrazo(termo) {
      return termo?.origem_prazo === 'ata' ? 'Vencimento da Ata' : 'Prazo do TR';
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

  watch: {
    '$route.query.alerta'() {
      this.aoMudarAlerta();
    },
    '$route.query.id'() {
      this.aoMudarAlerta();
    },
  },

  mounted() {
    this.carregarTermos();
  },
};
