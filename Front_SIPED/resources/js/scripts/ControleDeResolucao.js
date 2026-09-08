import CrudPageHeader from '../components/crud/CrudPageHeader.vue';
import CrudAlerts from '../components/crud/CrudAlerts.vue';
import CrudFormShell from '../components/crud/CrudFormShell.vue';
import PageTableCard from '../components/crud/PageTableCard.vue';
import IndicadorPrazo from '../components/ciclo-vida/IndicadorPrazo.vue';
import LinhaDoTempo from '../components/ciclo-vida/LinhaDoTempo.vue';
import { podeEditarDados } from './auth';
import { mixinHistoricoFormulario } from './formularioHistorico';
import {
  combinarValidacoes,
  extrairErroApi,
  tamanhoMaximo,
  textoObrigatorio,
  validarData,
} from '../utils/validacao';

const ENDPOINT_API = '/api/resolucoes';

const STATUS_LABELS = {
  vigente: 'Vigente',
  atencao: 'Atenção',
  critico: 'Crítico',
  vencida: 'Vencida',
  concluida: 'Concluída',
};

const SEMAFORO_LABELS = {
  vigente: 'No prazo',
  atencao: 'Atenção',
  critico: 'Crítico',
  vencida: 'Vencida',
  concluida: 'Concluída',
};

function formVazio() {
  return {
    numero: '',
    curso_relacionado: '',
    categoria: '',
    resumo: '',
    relator: '',
    setor: '',
    data_inicio_vigencia: '',
    status: '',
    observacoes: '',
    anexoFile: null,
    anexo_path: '',
    anexo_url: '',
  };
}

export default {
  name: 'ControleDeResolucao',
  mixins: [mixinHistoricoFormulario],
  components: {
    CrudPageHeader,
    CrudAlerts,
    CrudFormShell,
    PageTableCard,
    IndicadorPrazo,
    LinhaDoTempo,
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
        setor: '',
        status: '',
        categoria: '',
        ano: '',
      },
      filtroResumo: 'todos',
      debounceTimeout: null,
      detalheAberto: false,
      resolucaoEmEdicao: null,
      editandoId: null,
      historico: [],
      form: formVazio(),
      registros: [],
      meta: {
        vigencia_anos: 5,
        status: ['vigente', 'atencao', 'critico', 'vencida', 'concluida'],
        categorias: ['Normativa', 'Operacional', 'Regulamentação', 'Interna'],
        setores: ['CPED', 'Gabinete', 'Coordenação', 'Diretoria'],
        contagens: {
          no_prazo: 0,
          atencao: 0,
          critico: 0,
          vencidos: 0,
        },
      },
    };
  },
  computed: {
    podeEditar() {
      return podeEditarDados();
    },
    registrosFiltrados() {
      if (this.filtroResumo === 'todos') {
        return this.registros;
      }

      return this.registros.filter((item) => item.status_vigencia === this.filtroResumo);
    },
    temFiltroAtivo() {
      return Boolean(
        this.filtros.busca
        || this.filtros.setor
        || this.filtros.status
        || this.filtros.categoria
        || this.filtros.ano
        || this.filtroResumo !== 'todos',
      );
    },
    anosDisponiveis() {
      const anos = new Set();
      const atual = new Date().getFullYear();
      anos.add(String(atual));
      this.registros.forEach((item) => {
        const inicio = String(item.data_inicio_vigencia || '').slice(0, 4);
        const fim = String(item.data_fim_vigencia || '').slice(0, 4);
        if (/^\d{4}$/.test(inicio)) anos.add(inicio);
        if (/^\d{4}$/.test(fim)) anos.add(fim);
      });
      return Array.from(anos).sort((a, b) => b.localeCompare(a));
    },
    resumoOptions() {
      const totais = this.meta.contagens || {};
      return [
        { value: 'todos', label: `Todos (${this.meta.total_geral ?? this.registros.length})` },
        { value: 'vigente', label: `No prazo (${totais.no_prazo ?? 0})` },
        { value: 'atencao', label: `Atenção (${totais.atencao ?? 0})` },
        { value: 'critico', label: `Crítico (${totais.critico ?? 0})` },
        { value: 'vencida', label: `Vencidas (${totais.vencidos ?? 0})` },
      ];
    },
    dataFimCalculada() {
      return this.calcularFimVigencia(this.form.data_inicio_vigencia);
    },
  },
  mounted() {
    this.carregarResolucoes();
  },
  methods: {
    async carregarResolucoes() {
      this.carregando = true;
      this.mensagemErro = '';

      try {
        const params = {};
        Object.entries(this.filtros).forEach(([chave, valor]) => {
          if (valor !== '' && valor != null) {
            params[chave] = valor;
          }
        });

        const { data } = await window.axios.get(ENDPOINT_API, { params });
        this.registros = Array.isArray(data.data) ? data.data : [];
        if (data.meta) {
          this.meta = { ...this.meta, ...data.meta };
        }
      } catch (error) {
        this.mensagemErro = this.extrairErro(error, 'Não foi possível carregar as resoluções.');
        this.registros = [];
      } finally {
        this.carregando = false;
      }
    },
    aplicarFiltros() {
      clearTimeout(this.debounceTimeout);
      this.debounceTimeout = setTimeout(() => {
        this.carregarResolucoes();
      }, 300);
    },
    limparFiltros() {
      this.filtros = {
        busca: '',
        setor: '',
        status: '',
        categoria: '',
        ano: '',
      };
      this.filtroResumo = 'todos';
      this.carregarResolucoes();
    },
    aplicarResumoFiltro() {
      if (this.filtroResumo === 'todos') {
        return;
      }
      this.filtros.status = '';
    },
    abrirNovaResolucao() {
      if (!this.podeEditar) {
        this.mensagemErro = 'Seu perfil não tem permissão para criar resoluções.';
        return;
      }

      this.aplicarEstadoNovoLocal();
      this.empilharHistoricoFormulario('novo');
    },
    aplicarEstadoNovoLocal() {
      this.modo = 'novo';
      this.resolucaoEmEdicao = null;
      this.editandoId = null;
      this.historico = [];
      this.form = formVazio();
      this.mensagemErro = '';
      this.erroFormulario = '';
      this.mensagemSucesso = '';
      this.fecharDetalhes();
    },
    async abrirDetalhes(item) {
      this.detalheAberto = true;
      this.resolucaoEmEdicao = item;
      this.historico = [];

      try {
        const { data } = await window.axios.get(`${ENDPOINT_API}/${item.id}`);
        const resolucao = data.resolucao || data.data || item;
        this.resolucaoEmEdicao = resolucao;
        this.historico = Array.isArray(resolucao.historicos) ? resolucao.historicos : [];
      } catch (error) {
        this.mensagemErro = this.extrairErro(error, 'Não foi possível carregar o detalhe da resolução.');
      }
    },
    abrirEdicao(item) {
      if (!this.podeEditar) {
        this.mensagemErro = 'Seu perfil não tem permissão para editar resoluções.';
        return;
      }

      this.aplicarEstadoEdicaoLocal(item);
      this.empilharHistoricoFormulario('editar', item.id);
    },
    aplicarEstadoEdicaoLocal(item) {
      this.modo = 'editar';
      this.resolucaoEmEdicao = item;
      this.editandoId = item.id;
      this.form = this.preencherForm(item);
      this.mensagemErro = '';
      this.erroFormulario = '';
      this.mensagemSucesso = '';
      this.fecharDetalhes();
    },
    async aplicarEstadoEdicaoPorId(id) {
      let item = this.registros.find((registro) => String(registro.id) === String(id));

      if (!item) {
        try {
          const { data } = await window.axios.get(`${ENDPOINT_API}/${id}`);
          item = data.resolucao || data.data || null;
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
        this.mensagemErro = 'Seu perfil não tem permissão para editar resoluções.';
        this.aplicarEstadoListaLocal();
        this.limparHistoricoFormulario();
        return;
      }

      this.aplicarEstadoEdicaoLocal(item);
    },
    fecharDetalhes() {
      this.detalheAberto = false;
      if (this.modo === 'lista') {
        this.resolucaoEmEdicao = null;
        this.historico = [];
      }
    },
    voltarLista() {
      this.aplicarEstadoListaLocal();
      this.limparHistoricoFormulario();
    },
    aplicarEstadoListaLocal() {
      this.modo = 'lista';
      this.form = formVazio();
      this.resolucaoEmEdicao = null;
      this.editandoId = null;
      this.historico = [];
      this.salvando = false;
      this.mensagemErro = '';
      this.erroFormulario = '';
    },
    preencherForm(item) {
      return {
        numero: item.numero || '',
        curso_relacionado: item.curso_relacionado || '',
        categoria: item.categoria || '',
        resumo: item.resumo || '',
        relator: item.relator || '',
        setor: item.setor || '',
        data_inicio_vigencia: this.normalizarData(item.data_inicio_vigencia),
        status: item.status || '',
        observacoes: item.observacoes || '',
        anexoFile: null,
        anexo_path: item.anexo_path || '',
        anexo_url: item.anexo_url || '',
      };
    },
    aoEscolherAnexo(event) {
      const arquivo = event.target.files?.[0] || null;
      this.form.anexoFile = arquivo;
    },

    validarFormulario() {
      return combinarValidacoes(
        textoObrigatorio(this.form.numero, 'O número da resolução é obrigatório.'),
        tamanhoMaximo(this.form.numero, 100, 'O número deve ter no máximo 100 caracteres.'),
        textoObrigatorio(this.form.resumo, 'O resumo da resolução é obrigatório.'),
        tamanhoMaximo(this.form.resumo, 1000, 'O resumo deve ter no máximo 1000 caracteres.'),
        this.form.curso_relacionado
          ? tamanhoMaximo(this.form.curso_relacionado, 255, 'O curso relacionado deve ter no máximo 255 caracteres.')
          : '',
        this.form.relator
          ? tamanhoMaximo(this.form.relator, 255, 'O relator deve ter no máximo 255 caracteres.')
          : '',
        this.form.observacoes
          ? tamanhoMaximo(this.form.observacoes, 2000, 'As observações devem ter no máximo 2000 caracteres.')
          : '',
        validarData(this.form.data_inicio_vigencia, { obrigatorio: true, rotulo: 'Data de início da vigência' }),
      );
    },

    async salvarResolucao() {
      if (!this.podeEditar) {
        this.mensagemErro = 'Seu perfil não tem permissão para salvar resoluções.';
        return;
      }

      const erroValidacao = this.validarFormulario();

      if (erroValidacao) {
        this.erroFormulario = erroValidacao;
        return;
      }

      this.mensagemErro = '';
      this.erroFormulario = '';
      this.salvando = true;

      try {
        const temArquivo = Boolean(this.form.anexoFile);
        let response;

        if (temArquivo) {
          const formData = this.montarFormData();
          if (this.modo === 'editar' && this.resolucaoEmEdicao?.id) {
            formData.append('_method', 'PUT');
            response = await window.axios.post(`${ENDPOINT_API}/${this.resolucaoEmEdicao.id}`, formData);
          } else {
            response = await window.axios.post(ENDPOINT_API, formData);
          }
        } else {
          const payload = this.montarPayloadJson();
          if (this.modo === 'editar' && this.resolucaoEmEdicao?.id) {
            response = await window.axios.put(`${ENDPOINT_API}/${this.resolucaoEmEdicao.id}`, payload);
          } else {
            response = await window.axios.post(ENDPOINT_API, payload);
          }
        }

        this.mensagemSucesso = response.data.message || 'Resolução salva com sucesso.';
        this.voltarLista();
        await this.carregarResolucoes();
        setTimeout(() => {
          this.mensagemSucesso = '';
        }, 5000);
      } catch (error) {
        this.erroFormulario = extrairErroApi(error, 'Não foi possível salvar a resolução.');
      } finally {
        this.salvando = false;
      }
    },
    montarPayloadJson() {
      return {
        numero: this.form.numero.trim(),
        curso_relacionado: this.form.curso_relacionado?.trim() || null,
        categoria: this.form.categoria || null,
        resumo: this.form.resumo.trim(),
        relator: this.form.relator?.trim() || null,
        setor: this.form.setor || null,
        data_inicio_vigencia: this.form.data_inicio_vigencia,
        status: this.form.status || null,
        observacoes: this.form.observacoes?.trim() || null,
      };
    },
    montarFormData() {
      const formData = new FormData();
      const payload = this.montarPayloadJson();
      Object.entries(payload).forEach(([chave, valor]) => {
        if (valor !== null && valor !== undefined && valor !== '') {
          formData.append(chave, valor);
        }
      });
      if (this.form.anexoFile) {
        formData.append('anexo', this.form.anexoFile);
      }
      return formData;
    },
    async excluirResolucao(item) {
      if (!this.podeEditar || !item) {
        return;
      }

      const confirmado = window.confirm(`Deseja excluir a resolução ${item.numero}?`);
      if (!confirmado) {
        return;
      }

      try {
        const { data } = await window.axios.delete(`${ENDPOINT_API}/${item.id}`);
        this.mensagemSucesso = data.message || 'Resolução excluída com sucesso.';
        if (this.resolucaoEmEdicao?.id === item.id) {
          this.fecharDetalhes();
        }
        await this.carregarResolucoes();
        setTimeout(() => {
          this.mensagemSucesso = '';
        }, 5000);
      } catch (error) {
        this.mensagemErro = this.extrairErro(error, 'Não foi possível excluir a resolução.');
      }
    },
    calcularFimVigencia(inicio) {
      if (!inicio) return '';
      const data = new Date(`${String(inicio).slice(0, 10)}T00:00:00`);
      if (Number.isNaN(data.getTime())) return '';
      data.setFullYear(data.getFullYear() + (this.meta.vigencia_anos || 5));
      const ano = data.getFullYear();
      const mes = String(data.getMonth() + 1).padStart(2, '0');
      const dia = String(data.getDate()).padStart(2, '0');
      return `${ano}-${mes}-${dia}`;
    },
    normalizarData(valor) {
      if (!valor) return '';
      return String(valor).slice(0, 10);
    },
    formatarData(data) {
      if (!data) return '—';
      const str = String(data).slice(0, 10);
      const [ano, mes, dia] = str.split('-');
      if (!ano || !mes || !dia) return data;
      return `${dia}/${mes}/${ano}`;
    },
    labelStatus(status) {
      return STATUS_LABELS[status] || status || '—';
    },
    labelSemaforo(statusVigencia) {
      return SEMAFORO_LABELS[statusVigencia] || statusVigencia || '—';
    },
    classeStatus(status) {
      return `status-${status || 'vigente'}`;
    },
    semaforoDe(item) {
      return item.semaforo || 'verde';
    },
    extrairErro(error, fallback) {
      return extrairErroApi(error, fallback);
    },
  },
};
