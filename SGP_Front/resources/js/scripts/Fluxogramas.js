import { podeConsultarDados, podeEditarDados } from './auth';

export default {
  name: 'Fluxogramas',

  data() {
    return {
      fluxogramas: [],
      carregando: true,
      salvando: false,
      erro: '',
      mensagemSucesso: '',
      erroFormulario: '',
      podeEditarApi: false,
      modalAberto: false,
      itemEmEdicao: null,
      form: {
        titulo: '',
        descricao: '',
        tipo: 'linear',
      },
    };
  },

  computed: {
    acessoBloqueado() {
      return !podeConsultarDados();
    },

    podeEditar() {
      return podeEditarDados() && this.podeEditarApi;
    },

    modalTitulo() {
      return this.itemEmEdicao ? 'Editar fluxograma' : 'Novo fluxograma';
    },
  },

  mounted() {
    this.carregar();
  },

  methods: {
    rotuloTipo(tipo) {
      return tipo === 'funcional' ? 'Funcional' : 'Linear';
    },

    async carregar() {
      this.carregando = true;
      this.erro = '';

      if (!podeConsultarDados()) {
        this.fluxogramas = [];
        this.carregando = false;
        return;
      }

      try {
        const { data } = await window.axios.get('/api/fluxogramas');
        this.fluxogramas = Array.isArray(data.data) ? data.data : [];
        this.podeEditarApi = Boolean(data.meta?.pode_editar);
      } catch (error) {
        this.erro = error.response?.data?.message
          || 'Não foi possível carregar os fluxogramas.';
        this.fluxogramas = [];
      } finally {
        this.carregando = false;
      }
    },

    abrirFluxograma(item) {
      this.$router.push(`/app/ferramentas/fluxograma/${item.slug}`);
    },

    abrirNovo() {
      if (!this.podeEditar) {
        return;
      }

      this.itemEmEdicao = null;
      this.form = { titulo: '', descricao: '', tipo: 'linear' };
      this.erroFormulario = '';
      this.modalAberto = true;
    },

    abrirEdicao(item) {
      if (!this.podeEditar) {
        return;
      }

      this.itemEmEdicao = item;
      this.form = {
        titulo: item.titulo || '',
        descricao: item.descricao || '',
        tipo: item.tipo || 'linear',
      };
      this.erroFormulario = '';
      this.modalAberto = true;
    },

    fecharModal() {
      this.modalAberto = false;
      this.itemEmEdicao = null;
      this.form = { titulo: '', descricao: '', tipo: 'linear' };
      this.erroFormulario = '';
    },

    async salvar() {
      if (!this.podeEditar || this.salvando) {
        return;
      }

      const titulo = this.form.titulo.trim();

      if (!titulo) {
        this.erroFormulario = 'Informe o título do fluxograma.';
        return;
      }

      this.salvando = true;
      this.erroFormulario = '';
      this.erro = '';

      const payload = {
        titulo,
        descricao: this.form.descricao.trim() || null,
        tipo: this.form.tipo || 'linear',
      };

      try {
        if (this.itemEmEdicao) {
          const { data } = await window.axios.put(
            `/api/fluxogramas/${this.itemEmEdicao.slug}`,
            payload
          );

          const index = this.fluxogramas.findIndex((item) => item.id === this.itemEmEdicao.id);

          if (index !== -1) {
            this.fluxogramas.splice(index, 1, {
              id: data.fluxograma.id,
              titulo: data.fluxograma.titulo,
              slug: data.fluxograma.slug,
              descricao: data.fluxograma.descricao,
              tipo: data.fluxograma.tipo,
              total_nos: Array.isArray(data.fluxograma.diagrama?.nodes)
                ? data.fluxograma.diagrama.nodes.length
                : 0,
              updated_at: data.fluxograma.updated_at,
            });
          }

          this.mensagemSucesso = data.message || 'Fluxograma atualizado com sucesso.';
          this.fecharModal();
        } else {
          const { data } = await window.axios.post('/api/fluxogramas', payload);
          this.mensagemSucesso = data.message || 'Fluxograma criado com sucesso.';
          this.fecharModal();
          this.$router.push(`/app/ferramentas/fluxograma/${data.fluxograma.slug}`);
          return;
        }

        this.limparMensagemDepois();
      } catch (error) {
        this.erroFormulario = error.response?.data?.message
          || error.response?.data?.errors?.titulo?.[0]
          || 'Não foi possível salvar o fluxograma.';
      } finally {
        this.salvando = false;
      }
    },

    async excluir(item) {
      if (!this.podeEditar || this.salvando) {
        return;
      }

      if (!window.confirm(`Excluir o fluxograma "${item.titulo}"?`)) {
        return;
      }

      this.salvando = true;
      this.erro = '';

      try {
        const { data } = await window.axios.delete(`/api/fluxogramas/${item.slug}`);
        this.fluxogramas = this.fluxogramas.filter((f) => f.id !== item.id);
        this.mensagemSucesso = data.message || 'Fluxograma excluído com sucesso.';
        this.limparMensagemDepois();
      } catch (error) {
        this.erro = error.response?.data?.message
          || 'Não foi possível excluir o fluxograma.';
      } finally {
        this.salvando = false;
      }
    },

    limparMensagemDepois() {
      window.setTimeout(() => {
        this.mensagemSucesso = '';
      }, 2500);
    },
  },
};
