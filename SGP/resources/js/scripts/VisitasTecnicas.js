import { UNIDADES } from './unidades';
import { getPerfil, podeEditarDados, podeConsultarDados } from './auth';

export default {
  name: 'VisitasTecnicas',

  data() {
    return {
      modo: 'lista',
      visitasBase: [
        {
          id: 1,
          identificador: 'VT-2026-001',
          unidade: 'SENAC DF',
          responsavel: 'Ana Souza',
          local: 'CPED',
          data: '2026-07-24',
          status: 'Realizada',
          objetivo: 'Acompanhamento de ações de formação e atualização do portfólio pedagógico.',
          observacoes: 'Visita concluída com relatório encaminhado para coordenação.',
        },
        {
          id: 2,
          identificador: 'VT-2026-002',
          unidade: 'SENAC DF',
          responsavel: 'Bruno Lima',
          local: 'Centro de Formação',
          data: '2026-07-26',
          status: 'Agendada',
          objetivo: 'Verificação de estrutura e logística para a próxima ação programada.',
          observacoes: 'A visita está aguardando confirmação final de horário e equipe.',
        },
        {
          id: 3,
          identificador: 'VT-2026-003',
          unidade: 'SENAC DF',
          responsavel: 'Carla Mendes',
          local: 'Escola Técnica',
          data: '2026-07-28',
          status: 'Planejada',
          objetivo: 'Mapeamento inicial das necessidades e oportunidades de melhoria.',
          observacoes: 'Registro em preparação com foco em cronograma e participantes.',
        },
        {
          id: 4,
          identificador: 'VT-2026-004',
          unidade: 'SENAC DF',
          responsavel: 'Daniel Rego',
          local: 'Unidade de Educação',
          data: '2026-07-30',
          status: 'Cancelada',
          objetivo: 'Análise de processo em andamento.',
          observacoes: 'Visita cancelada por ajuste de agenda institucional.',
        },
      ],
      visitas: [],
      filtroBusca: '',
      filtroStatus: '',
      carregando: true,
      erro: '',
      mensagemSucesso: '',
      visitaDetalhe: null,
      erroFormulario: '',
      salvando: false,
      editandoId: null,
      form: this.formVazio(),
      unidades: UNIDADES,
    };
  },

  computed: {
    perfilUsuario() {
      return getPerfil();
    },

    acessoBloqueado() {
      return !this.podeConsultarVisita;
    },

    podeEditarVisita() {
      return podeEditarDados();
    },

    podeConsultarVisita() {
      return podeConsultarDados();
    },

    resumoCards() {
      const total = this.visitas.length;
      const realizadas = this.visitas.filter((visita) => visita.status === 'Realizada').length;
      const agendadas = this.visitas.filter((visita) => visita.status === 'Agendada').length;
      const pendentes = this.visitas.filter((visita) => ['Planejada', 'Agendada'].includes(visita.status)).length;

      return [
        { label: 'Total', value: String(total).padStart(2, '0'), help: 'Visitas registradas no período' },
        { label: 'Realizadas', value: String(realizadas).padStart(2, '0'), help: 'Concluídas com registro' },
        { label: 'Agendadas', value: String(agendadas).padStart(2, '0'), help: 'Próximas visitas' },
        { label: 'Pendentes', value: String(pendentes).padStart(2, '0'), help: 'Aguardando confirmação' },
      ];
    },

    visitasFiltradas() {
      const busca = this.filtroBusca.toLowerCase();

      return this.visitas.filter((visita) => {
        const atendeBusca = !busca || [
          visita.identificador,
          visita.unidade,
          visita.responsavel,
          visita.local,
          visita.status,
        ]
          .join(' ')
          .toLowerCase()
          .includes(busca);

        const atendeStatus = !this.filtroStatus || visita.status === this.filtroStatus;

        return atendeBusca && atendeStatus;
      });
    },

    temFiltro() {
      return Boolean(this.filtroBusca || this.filtroStatus);
    },
  },

  mounted() {
    this.carregarVisitas();
  },

  methods: {
    formVazio() {
      return {
        identificador: '',
        unidade: '',
        responsavel: '',
        local: '',
        data: '',
        status: '',
        objetivo: '',
        observacoes: '',
      };
    },

    carregarVisitas() {
      this.carregando = true;
      this.erro = '';

      if (!this.podeConsultarVisita) {
        this.visitas = [];
        this.carregando = false;
        this.erro = 'Seu perfil não possui acesso para consultar visitas técnicas.';
        return;
      }

      setTimeout(() => {
        try {
          this.visitas = [...this.visitasBase];
          this.carregando = false;
        } catch (error) {
          this.carregando = false;
          this.erro = 'Não foi possível carregar as visitas técnicas. Tente novamente.';
        }
      }, 450);
    },

    limparFiltros() {
      this.filtroBusca = '';
      this.filtroStatus = '';
    },

    bloquearSemPermissao(mensagem = 'Seu perfil só permite consultar visitas técnicas.') {
      this.erro = mensagem;
      this.mensagemSucesso = '';
      this.erroFormulario = '';
      this.visitaDetalhe = null;
      this.modo = 'lista';
    },

    abrirNovo() {
      if (!this.podeEditarVisita) {
        this.bloquearSemPermissao();
        return;
      }

      this.modo = 'novo';
      this.editandoId = null;
      this.form = this.formVazio();
      this.erroFormulario = '';
      this.mensagemSucesso = '';
    },

    abrirEdicao(visita) {
      if (!this.podeEditarVisita) {
        this.bloquearSemPermissao();
        return;
      }

      this.modo = 'editar';
      this.editandoId = visita.id ?? null;
      this.form = {
        identificador: visita.identificador ?? '',
        unidade: visita.unidade ?? '',
        responsavel: visita.responsavel ?? '',
        local: visita.local ?? '',
        data: visita.data ?? '',
        status: visita.status ?? '',
        objetivo: visita.objetivo ?? '',
        observacoes: visita.observacoes ?? '',
      };
      this.erroFormulario = '';
      this.mensagemSucesso = '';
      this.visitaDetalhe = null;
    },

    voltarLista() {
      this.modo = 'lista';
      this.editandoId = null;
      this.erroFormulario = '';
      this.form = this.formVazio();
    },

    async salvarVisita() {
      if (!this.podeEditarVisita) {
        this.erroFormulario = 'Seu perfil não tem permissão para alterar visitas técnicas.';
        this.modo = 'lista';
        return;
      }

      if (
        !this.form.identificador ||
        !this.form.unidade ||
        !this.form.responsavel ||
        !this.form.local ||
        !this.form.data ||
        !this.form.status ||
        !this.form.objetivo
      ) {
        this.erroFormulario = 'Preencha todos os campos obrigatórios antes de salvar.';
        return;
      }

      this.salvando = true;
      this.erroFormulario = '';
      this.mensagemSucesso = '';

      setTimeout(() => {
        const payload = {
          ...this.form,
          id: this.modo === 'editar' && this.editandoId ? this.editandoId : Date.now(),
        };

        if (this.modo === 'editar') {
          const index = this.visitas.findIndex((item) => item.id === this.editandoId);

          if (index >= 0) {
            this.visitas.splice(index, 1, payload);
          }
        } else {
          this.visitas.unshift(payload);
        }

        this.mensagemSucesso = this.modo === 'editar'
          ? 'Visita técnica atualizada com sucesso.'
          : 'Visita técnica cadastrada com sucesso.';

        this.salvando = false;
        this.voltarLista();
      }, 250);
    },

    cancelarVisita(visita) {
      if (!this.podeEditarVisita) {
        this.bloquearSemPermissao();
        return;
      }

      if (!visita || visita.status === 'Cancelada') {
        return;
      }

      const confirmar = window.confirm(`Deseja cancelar a visita ${visita.identificador}?`);

      if (!confirmar) {
        return;
      }

      const index = this.visitas.findIndex((item) => item.id === visita.id);

      if (index >= 0) {
        this.visitas[index] = {
          ...this.visitas[index],
          status: 'Cancelada',
          observacoes: `${this.visitas[index].observacoes || ''} Visita cancelada pelo usuário.`.trim(),
        };
      }

      this.mensagemSucesso = 'Visita técnica cancelada com sucesso.';
      this.erro = '';
      this.erroFormulario = '';
      this.visitaDetalhe = null;
    },

    abrirDetalhes(visita) {
      this.visitaDetalhe = { ...visita };
    },

    fecharDetalhes() {
      this.visitaDetalhe = null;
    },

    formatarData(data) {
      if (!data) {
        return '—';
      }

      const [ano, mes, dia] = String(data).split('-');

      if (!ano || !mes || !dia) {
        return data;
      }

      return `${dia}/${mes}/${ano}`;
    },

    statusClass(status) {
      if (status === 'Realizada') {
        return 'badge-realizada';
      }

      if (status === 'Agendada') {
        return 'badge-agendada';
      }

      if (status === 'Planejada') {
        return 'badge-planejada';
      }

      return 'badge-cancelada';
    },
  },
};
