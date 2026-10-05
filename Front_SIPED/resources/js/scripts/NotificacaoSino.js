import { CICLO_CONTEXTO_EVENTO } from './cicloContexto';
import { destinoDaNotificacao } from './filtroNotificacao';

const ROTULOS = {
  vencido: 'Vencido',
  atencao: 'Atenção',
};

const LIMITE_PAGINA = 40;
const POLLING_MS = 2 * 60 * 1000;
const BUSCA_MS = 280;

const OPCOES_MODULO = [
  { valor: '', rotulo: 'Todas' },
  { valor: 'resolucoes', rotulo: 'Resolução' },
  { valor: 'termos-referencia', rotulo: 'TR' },
  { valor: 'visitas-tecnicas', rotulo: 'Visita' },
];

const OPCOES_NIVEL = [
  { valor: '', rotulo: 'Todas' },
  { valor: 'vencido', rotulo: 'Vencido' },
  { valor: 'atencao', rotulo: 'Atenção' },
];

export default {
  name: 'NotificacaoSino',
  data() {
    return {
      aberto: false,
      carregando: false,
      carregandoMais: false,
      salvando: false,
      erro: '',
      itens: [],
      busca: '',
      moduloFiltro: '',
      nivelFiltro: '',
      buscaTimer: null,
      pollingId: null,
      opcoesModulo: OPCOES_MODULO,
      opcoesNivel: OPCOES_NIVEL,
      meta: {
        total: 0,
        total_geral: 0,
        nao_lidas: 0,
        has_more: false,
        filtrado: false,
        por_nivel: { vencido: 0, atencao: 0 },
        escopo: { todos: true, eixos: [] },
        por_modulo: {
          resolucoes: 0,
          'termos-referencia': 0,
          'visitas-tecnicas': 0,
        },
      },
    };
  },
  computed: {
    textoEscopo() {
      const escopo = this.meta.escopo;
      if (!escopo || escopo.todos || !escopo.eixos?.length) return '';
      return `Mostrando alertas dos seus eixos (${escopo.eixos.join(', ')}) e itens gerais.`;
    },
    naoLidas() {
      return this.meta.nao_lidas || 0;
    },
    badgeTexto() {
      return this.naoLidas > 99 ? '99+' : String(this.naoLidas);
    },
    temFiltro() {
      return Boolean(this.busca.trim() || this.moduloFiltro || this.nivelFiltro);
    },
    rotuloBotao() {
      if (this.naoLidas === 0) {
        return 'Notificações de prazo';
      }
      return this.naoLidas === 1
        ? '1 notificação de prazo não lida'
        : `${this.naoLidas} notificações de prazo não lidas`;
    },
    subtitulo() {
      const total = this.meta.total || 0;
      const geral = this.meta.total_geral || total;
      const visiveis = this.itens.length;
      if (this.temFiltro) {
        return `${this.naoLidas} não lida(s) · ${total} de ${geral} com este filtro.`;
      }
      if (this.naoLidas === 0) {
        return 'Prazos de resoluções, termos e visitas.';
      }
      if (geral > visiveis) {
        return `${this.naoLidas} não lida(s) · mostrando ${visiveis} de ${geral}.`;
      }
      return `${this.naoLidas} não lida(s) · ${geral} alerta(s) no momento.`;
    },
    mensagemVazio() {
      if (this.temFiltro) {
        return 'Nenhum alerta com esse nome, SEI ou filtro.';
      }
      return 'Nenhum prazo em atenção, crítico ou vencido.';
    },
    temMais() {
      return Boolean(this.meta.has_more);
    },
    restantes() {
      return Math.max(0, (this.meta.total || 0) - this.itens.length);
    },
  },
  mounted() {
    this.carregar();
    this.iniciarPolling();
    window.addEventListener(CICLO_CONTEXTO_EVENTO, this.aoAtualizarContexto);
    document.addEventListener('visibilitychange', this.aoMudarVisibilidade);
    document.addEventListener('click', this.fecharFora);
    document.addEventListener('keydown', this.fecharEsc);
  },
  beforeUnmount() {
    this.pararPolling();
    this.limparTimerBusca();
    window.removeEventListener(CICLO_CONTEXTO_EVENTO, this.aoAtualizarContexto);
    document.removeEventListener('visibilitychange', this.aoMudarVisibilidade);
    document.removeEventListener('click', this.fecharFora);
    document.removeEventListener('keydown', this.fecharEsc);
  },
  methods: {
    rotuloNivel(nivel) {
      return ROTULOS[nivel] || nivel;
    },
    contagemModulo(valor) {
      if (!valor) {
        return 0;
      }
      return this.meta.por_modulo?.[valor] || 0;
    },
    contagemNivel(valor) {
      if (!valor) {
        return 0;
      }
      return this.meta.por_nivel?.[valor] || 0;
    },
    paramsListagem(offset, limit) {
      const params = { offset, limit };
      const q = this.busca.trim();
      if (q) {
        params.q = q;
      }
      if (this.moduloFiltro) {
        params.modulo = this.moduloFiltro;
      }
      if (this.nivelFiltro) {
        params.nivel = this.nivelFiltro;
      }
      return params;
    },
    iniciarPolling() {
      this.pararPolling();
      this.pollingId = window.setInterval(() => {
        if (document.visibilityState === 'visible') {
          this.carregar({ silencioso: true });
        }
      }, POLLING_MS);
    },
    pararPolling() {
      if (this.pollingId) {
        window.clearInterval(this.pollingId);
        this.pollingId = null;
      }
    },
    limparTimerBusca() {
      if (this.buscaTimer) {
        window.clearTimeout(this.buscaTimer);
        this.buscaTimer = null;
      }
    },
    agendarBusca() {
      this.limparTimerBusca();
      this.buscaTimer = window.setTimeout(() => {
        this.carregar();
      }, BUSCA_MS);
    },
    escolherModulo(valor) {
      this.moduloFiltro = this.moduloFiltro === valor ? '' : valor;
      this.carregar();
    },
    escolherNivel(valor) {
      this.nivelFiltro = this.nivelFiltro === valor ? '' : valor;
      this.carregar();
    },
    limparFiltros() {
      this.limparTimerBusca();
      this.busca = '';
      this.moduloFiltro = '';
      this.nivelFiltro = '';
      this.carregar();
      this.$nextTick(() => this.$refs.busca?.focus());
    },
    aoAtualizarContexto() {
      this.carregar({ silencioso: true });
    },
    aoMudarVisibilidade() {
      if (document.visibilityState === 'visible') {
        this.carregar({ silencioso: true });
      }
    },
    async carregar({ silencioso = false, append = false } = {}) {
      const jaTemLista = this.itens.length > 0;
      if (!silencioso && !append && !jaTemLista) {
        this.carregando = true;
      }
      if (append) {
        this.carregandoMais = true;
      }
      this.erro = '';
      try {
        const offset = append ? this.itens.length : 0;
        const limit = append
          ? LIMITE_PAGINA
          : Math.min(100, Math.max(LIMITE_PAGINA, this.aberto ? this.itens.length : LIMITE_PAGINA));
        const { data } = await window.axios.get('/api/notificacoes', {
          params: this.paramsListagem(offset, limit),
        });
        const novos = data.itens || [];
        this.itens = append ? [...this.itens, ...novos] : novos;
        this.meta = {
          ...this.meta,
          ...(data.meta || {}),
        };
      } catch (error) {
        if (!silencioso) {
          this.erro = error.response?.data?.message || 'Não foi possível carregar as notificações.';
        }
        if (!append && !silencioso) {
          this.itens = [];
        }
      } finally {
        this.carregando = false;
        this.carregandoMais = false;
      }
    },
    carregarMais() {
      if (this.carregandoMais || !this.temMais) {
        return;
      }
      this.carregar({ append: true });
    },
    alternar() {
      this.aberto = !this.aberto;
      if (this.aberto) {
        this.carregar();
        this.$nextTick(() => this.$refs.busca?.focus());
      }
    },
    fechar() {
      this.aberto = false;
    },
    fecharFora(evento) {
      if (!this.aberto) {
        return;
      }
      const raiz = this.$refs.raiz;
      if (raiz && !raiz.contains(evento.target)) {
        this.fechar();
      }
    },
    fecharEsc(evento) {
      if (evento.key === 'Escape') {
        this.fechar();
      }
    },
    async marcarTodas() {
      const chaves = this.itens.filter((item) => !item.lida).map((item) => item.chave);
      if (!chaves.length) {
        return;
      }
      await this.enviarLidas(chaves);
    },
    async enviarLidas(chaves, { recarregar = true } = {}) {
      this.salvando = true;
      this.erro = '';
      try {
        await window.axios.post('/api/notificacoes/marcar-lidas', { chaves });
        if (recarregar) {
          await this.carregar({ silencioso: true });
        }
      } catch (error) {
        this.erro = error.response?.data?.message || 'Não foi possível marcar como lida.';
      } finally {
        this.salvando = false;
      }
    },
    abrirItem(item) {
      if (!item.lida) {
        item.lida = true;
        this.meta.nao_lidas = Math.max(0, (this.meta.nao_lidas || 1) - 1);
        this.enviarLidas([item.chave], { recarregar: false });
      }
      this.fechar();
      const destino = destinoDaNotificacao(item);
      if (!destino) {
        return;
      }

      const alertaAtual = String(this.$route.query.alerta || this.$route.query.id || '');
      if (this.$route.name === destino.name && alertaAtual === destino.query.alerta) {
        return;
      }

      this.$router.push(destino);
    },
  },
};
