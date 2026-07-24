import { UNIDADES } from './unidades';
import { getPerfil, podeEditarDados, podeConsultarDados } from './auth';

export default {
  name: 'HorasPedagogicas',

  data() {
    return {
      modo: 'lista',
      horasBase: [
        {
          id: 1,
          identificador: 'HP-2026-001',
          unidade: 'SENAC DF',
          responsavel: 'Ana Souza',
          atividade: 'Acompanhamento pedagógico',
          data: '2026-07-24',
          status: 'Concluída',
          objetivo: 'Acompanhamento e validação do planejamento pedagógico do módulo.',
          observacoes: 'Registro concluído com análise final encaminhada ao gestor.',
        },
        {
          id: 2,
          identificador: 'HP-2026-002',
          unidade: 'SENAC DF',
          responsavel: 'Bruno Lima',
          atividade: 'Preparação de materiais',
          data: '2026-07-26',
          status: 'Em Andamento',
          objetivo: 'Organização e revisão de materiais para atividades de formação.',
          observacoes: 'Pendência na revisão final de versão impressa.',
        },
        {
          id: 3,
          identificador: 'HP-2026-003',
          unidade: 'SENAC DF',
          responsavel: 'Carla Mendes',
          atividade: 'Reunião de coordenação',
          data: '2026-07-28',
          status: 'Planejada',
          objetivo: 'Definição de cronograma e alinhamento operacional da próxima etapa.',
          observacoes: 'Registro em análise para confirmação da agenda.',
        },
        {
          id: 4,
          identificador: 'HP-2026-004',
          unidade: 'SENAC DF',
          responsavel: 'Daniel Rego',
          atividade: 'Monitoria e apoio ao processo',
          data: '2026-07-30',
          status: 'Cancelada',
          objetivo: 'Apoio ao acompanhamento do processo em revisão.',
          observacoes: 'Solicitação cancelada por ajuste de agenda institucional.',
        },
      ],
      horas: [],
      filtroBusca: '',
      filtroStatus: '',
      carregando: true,
      erro: '',
      mensagemSucesso: '',
      horaDetalhe: null,
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
      return !this.podeConsultarHoras;
    },

    podeEditarHoras() {
      return podeEditarDados();
    },

    podeConsultarHoras() {
      return podeConsultarDados();
    },

    resumoCards() {
      const total = this.horas.length;
      const concluidas = this.horas.filter((hora) => hora.status === 'Concluída').length;
      const emAndamento = this.horas.filter((hora) => hora.status === 'Em Andamento').length;
      const pendentes = this.horas.filter((hora) => ['Planejada', 'Em Andamento'].includes(hora.status)).length;

      return [
        { label: 'Total', value: String(total).padStart(2, '0'), help: 'Registros no período' },
        { label: 'Concluídas', value: String(concluidas).padStart(2, '0'), help: 'Atividades encerradas' },
        { label: 'Em Andamento', value: String(emAndamento).padStart(2, '0'), help: 'Processos ativos' },
        { label: 'Pendentes', value: String(pendentes).padStart(2, '0'), help: 'Aguardando conclusão' },
      ];
    },

    horasFiltradas() {
      const busca = this.filtroBusca.toLowerCase();

      return this.horas.filter((hora) => {
        const atendeBusca = !busca || [
          hora.identificador,
          hora.unidade,
          hora.responsavel,
          hora.atividade,
          hora.status,
        ]
          .join(' ')
          .toLowerCase()
          .includes(busca);

        const atendeStatus = !this.filtroStatus || hora.status === this.filtroStatus;

        return atendeBusca && atendeStatus;
      });
    },

    temFiltro() {
      return Boolean(this.filtroBusca || this.filtroStatus);
    },
  },

  mounted() {
    this.carregarHoras();
  },

  methods: {
    formVazio() {
      return {
        identificador: '',
        unidade: '',
        responsavel: '',
        atividade: '',
        data: '',
        status: '',
        objetivo: '',
        observacoes: '',
      };
    },

    carregarHoras() {
      this.carregando = true;
      this.erro = '';

      if (!this.podeConsultarHoras) {
        this.horas = [];
        this.carregando = false;
        this.erro = 'Seu perfil não possui acesso para consultar horas pedagógicas.';
        return;
      }

      setTimeout(() => {
        try {
          this.horas = [...this.horasBase];
          this.carregando = false;
        } catch (error) {
          this.carregando = false;
          this.erro = 'Não foi possível carregar as horas pedagógicas. Tente novamente.';
        }
      }, 450);
    },

    limparFiltros() {
      this.filtroBusca = '';
      this.filtroStatus = '';
    },

    bloquearSemPermissao(mensagem = 'Seu perfil só permite consultar horas pedagógicas.') {
      this.erro = mensagem;
      this.mensagemSucesso = '';
      this.erroFormulario = '';
      this.horaDetalhe = null;
      this.modo = 'lista';
    },

    abrirNovo() {
      if (!this.podeEditarHoras) {
        this.bloquearSemPermissao();
        return;
      }

      this.modo = 'novo';
      this.editandoId = null;
      this.form = this.formVazio();
      this.erroFormulario = '';
      this.mensagemSucesso = '';
    },

    abrirEdicao(hora) {
      if (!this.podeEditarHoras) {
        this.bloquearSemPermissao();
        return;
      }

      this.modo = 'editar';
      this.editandoId = hora.id ?? null;
      this.form = {
        identificador: hora.identificador ?? '',
        unidade: hora.unidade ?? '',
        responsavel: hora.responsavel ?? '',
        atividade: hora.atividade ?? '',
        data: hora.data ?? '',
        status: hora.status ?? '',
        objetivo: hora.objetivo ?? '',
        observacoes: hora.observacoes ?? '',
      };
      this.erroFormulario = '';
      this.mensagemSucesso = '';
      this.horaDetalhe = null;
    },

    voltarLista() {
      this.modo = 'lista';
      this.editandoId = null;
      this.erroFormulario = '';
      this.form = this.formVazio();
    },

    async salvarHora() {
      if (!this.podeEditarHoras) {
        this.erroFormulario = 'Seu perfil não tem permissão para alterar horas pedagógicas.';
        this.modo = 'lista';
        return;
      }

      if (
        !this.form.identificador ||
        !this.form.unidade ||
        !this.form.responsavel ||
        !this.form.atividade ||
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
          const index = this.horas.findIndex((item) => item.id === this.editandoId);

          if (index >= 0) {
            this.horas.splice(index, 1, payload);
          }
        } else {
          this.horas.unshift(payload);
        }

        this.mensagemSucesso = this.modo === 'editar'
          ? 'Hora pedagógica atualizada com sucesso.'
          : 'Hora pedagógica cadastrada com sucesso.';

        this.salvando = false;
        this.voltarLista();
      }, 250);
    },

    cancelarHora(hora) {
      if (!this.podeEditarHoras) {
        this.bloquearSemPermissao();
        return;
      }

      if (!hora || hora.status === 'Cancelada') {
        return;
      }

      const confirmar = window.confirm(`Deseja cancelar a hora ${hora.identificador}?`);

      if (!confirmar) {
        return;
      }

      const index = this.horas.findIndex((item) => item.id === hora.id);

      if (index >= 0) {
        this.horas[index] = {
          ...this.horas[index],
          status: 'Cancelada',
          observacoes: `${this.horas[index].observacoes || ''} Registro cancelado pelo usuário.`.trim(),
        };
      }

      this.mensagemSucesso = 'Hora pedagógica cancelada com sucesso.';
      this.erro = '';
      this.erroFormulario = '';
      this.horaDetalhe = null;
    },

    abrirDetalhes(hora) {
      this.horaDetalhe = { ...hora };
    },

    fecharDetalhes() {
      this.horaDetalhe = null;
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
      if (status === 'Concluída') {
        return 'badge-concluida';
      }

      if (status === 'Em Andamento') {
        return 'badge-andamento';
      }

      if (status === 'Planejada') {
        return 'badge-planejada';
      }

      return 'badge-cancelada';
    },
  },
};
