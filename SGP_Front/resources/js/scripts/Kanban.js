import draggable from 'vuedraggable';
import { podeConsultarDados, podeEditarDados } from './auth';

function formVazio(colunaTitulo = '') {
  return {
    titulo: '',
    descricao: '',
    coluna_titulo: colunaTitulo,
  };
}

export default {
  name: 'Kanban',

  components: {
    draggable,
  },

  data() {
    return {
      quadro: null,
      colunas: [],
      carregando: true,
      salvando: false,
      erro: '',
      mensagemSucesso: '',
      erroFormulario: '',
      erroFormularioColuna: '',
      podeEditarApi: false,
      modalAberto: false,
      modalColunaAberto: false,
      cartaoEmEdicao: null,
      form: formVazio(),
      formColuna: { titulo: '' },
      colunaEditandoId: null,
      tituloColunaEditando: '',
      snapshotColunas: null,
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
      return this.cartaoEmEdicao ? 'Editar cartão' : 'Novo cartão';
    },
  },

  mounted() {
    this.carregarQuadro();
  },

  watch: {
    '$route.params.slug'() {
      this.carregarQuadro();
    },
  },

  methods: {
    slugAtual() {
      return this.$route.params.slug;
    },

    async carregarQuadro() {
      this.carregando = true;
      this.erro = '';

      if (!podeConsultarDados()) {
        this.colunas = [];
        this.carregando = false;
        return;
      }

      const slug = this.slugAtual();

      if (!slug) {
        this.$router.replace('/app/ferramentas/kanban');
        return;
      }

      try {
        const { data } = await window.axios.get(`/api/kanban/quadros/${slug}`);
        this.quadro = data.data?.quadro ?? null;
        this.colunas = Array.isArray(data.data?.colunas)
          ? data.data.colunas.map((coluna) => ({
              ...coluna,
              cartoes: Array.isArray(coluna.cartoes) ? [...coluna.cartoes] : [],
            }))
          : [];
        this.podeEditarApi = Boolean(data.meta?.pode_editar);
      } catch (error) {
        this.erro = error.response?.data?.message
          || 'Não foi possível carregar o quadro Kanban.';
        this.colunas = [];
        this.quadro = null;
      } finally {
        this.carregando = false;
      }
    },

    abrirNovo(coluna = null) {
      if (!this.podeEditar) {
        return;
      }

      const colunaTitulo = typeof coluna === 'object' && coluna?.titulo
        ? coluna.titulo
        : (this.colunas[0]?.titulo || '');

      this.cartaoEmEdicao = null;
      this.form = formVazio(colunaTitulo);
      this.erroFormulario = '';
      this.modalAberto = true;
    },

    abrirEdicao(cartao) {
      if (!this.podeEditar) {
        return;
      }

      this.cartaoEmEdicao = cartao;
      this.form = {
        titulo: cartao.titulo || '',
        descricao: cartao.descricao || '',
        coluna_titulo: '',
      };
      this.erroFormulario = '';
      this.modalAberto = true;
    },

    abrirDetalhe(cartao) {
      if (!this.podeEditar) {
        return;
      }

      this.abrirEdicao(cartao);
    },

    fecharModal() {
      this.modalAberto = false;
      this.cartaoEmEdicao = null;
      this.form = formVazio();
      this.erroFormulario = '';
    },

    abrirNovaColuna() {
      if (!this.podeEditar) {
        return;
      }

      this.formColuna = { titulo: '' };
      this.erroFormularioColuna = '';
      this.modalColunaAberto = true;
    },

    fecharModalColuna() {
      this.modalColunaAberto = false;
      this.formColuna = { titulo: '' };
      this.erroFormularioColuna = '';
    },

    async salvarNovaColuna() {
      if (!this.podeEditar || this.salvando) {
        return;
      }

      const titulo = this.formColuna.titulo.trim();

      if (!titulo) {
        this.erroFormularioColuna = 'Informe o nome da coluna.';
        return;
      }

      this.salvando = true;
      this.erroFormularioColuna = '';
      this.erro = '';

      try {
        const { data } = await window.axios.post(`/api/kanban/quadros/${this.slugAtual()}/colunas`, { titulo });
        const coluna = data.kanban_coluna;

        this.colunas.push({
          id: coluna.id,
          titulo: coluna.titulo,
          position: coluna.position,
          cor: coluna.cor,
          cartoes: Array.isArray(coluna.cartoes) ? [...coluna.cartoes] : [],
        });

        this.mensagemSucesso = data.message || 'Coluna criada com sucesso.';
        this.fecharModalColuna();
        this.limparMensagemDepois();
      } catch (error) {
        this.erroFormularioColuna = error.response?.data?.message
          || error.response?.data?.errors?.titulo?.[0]
          || 'Não foi possível criar a coluna.';
      } finally {
        this.salvando = false;
      }
    },

    iniciarEdicaoColuna(coluna) {
      if (!this.podeEditar) {
        return;
      }

      this.colunaEditandoId = coluna.id;
      this.tituloColunaEditando = coluna.titulo;
      this.$nextTick(() => {
        const input = this.$el.querySelector('.kanban-coluna-input');
        input?.focus();
        input?.select();
      });
    },

    cancelarEdicaoColuna() {
      this.colunaEditandoId = null;
      this.tituloColunaEditando = '';
    },

    async salvarEdicaoColuna(coluna) {
      if (!this.podeEditar || this.salvando) {
        return;
      }

      const titulo = this.tituloColunaEditando.trim();

      if (!titulo) {
        this.erro = 'Informe o nome da coluna.';
        return;
      }

      if (titulo === coluna.titulo) {
        this.cancelarEdicaoColuna();
        return;
      }

      this.salvando = true;
      this.erro = '';

      try {
        const { data } = await window.axios.put(`/api/kanban/colunas/${coluna.id}`, { titulo });
        coluna.titulo = data.kanban_coluna.titulo;
        this.mensagemSucesso = data.message || 'Coluna atualizada com sucesso.';
        this.cancelarEdicaoColuna();
        this.limparMensagemDepois();
      } catch (error) {
        this.erro = error.response?.data?.message
          || error.response?.data?.errors?.titulo?.[0]
          || 'Não foi possível renomear a coluna.';
      } finally {
        this.salvando = false;
      }
    },

    async excluirColuna(coluna) {
      if (!this.podeEditar || this.salvando) {
        return;
      }

      const total = coluna.cartoes.length;
      const aviso = total > 0
        ? `Excluir a coluna "${coluna.titulo}" e seus ${total} cartão(ões)?`
        : `Excluir a coluna "${coluna.titulo}"?`;

      if (!window.confirm(aviso)) {
        return;
      }

      this.salvando = true;
      this.erro = '';

      try {
        const { data } = await window.axios.delete(`/api/kanban/colunas/${coluna.id}`);
        this.colunas = this.colunas.filter((item) => item.id !== coluna.id);
        this.mensagemSucesso = data.message || 'Coluna excluída com sucesso.';
        this.limparMensagemDepois();
      } catch (error) {
        this.erro = error.response?.data?.message
          || 'Não foi possível excluir a coluna.';
      } finally {
        this.salvando = false;
      }
    },

    async salvar() {
      if (!this.podeEditar || this.salvando) {
        return;
      }

      this.salvando = true;
      this.erroFormulario = '';
      this.erro = '';

      try {
        if (this.cartaoEmEdicao) {
          const { data } = await window.axios.put(
            `/api/kanban/cartoes/${this.cartaoEmEdicao.id}`,
            {
              titulo: this.form.titulo,
              descricao: this.form.descricao || null,
            }
          );

          this.atualizarCartaoLocal(data.kanban_cartao);
          this.mensagemSucesso = data.message || 'Cartão atualizado com sucesso.';
        } else {
          const { data } = await window.axios.post(`/api/kanban/quadros/${this.slugAtual()}/cartoes`, {
            coluna_titulo: this.form.coluna_titulo.trim(),
            titulo: this.form.titulo,
            descricao: this.form.descricao || null,
          });

          this.adicionarCartaoLocal(data.kanban_cartao, data.kanban_coluna, data.coluna_criada);
          this.mensagemSucesso = data.message || 'Cartão criado com sucesso.';
        }

        this.fecharModal();
        this.limparMensagemDepois();
      } catch (error) {
        this.erroFormulario = error.response?.data?.message
          || error.response?.data?.errors?.coluna_titulo?.[0]
          || 'Não foi possível salvar o cartão.';
      } finally {
        this.salvando = false;
      }
    },

    async excluir(cartao) {
      if (!this.podeEditar || this.salvando) {
        return;
      }

      if (!window.confirm(`Excluir o cartão "${cartao.titulo}"?`)) {
        return;
      }

      this.salvando = true;
      this.erro = '';

      try {
        const { data } = await window.axios.delete(`/api/kanban/cartoes/${cartao.id}`);
        this.removerCartaoLocal(cartao.id);
        this.mensagemSucesso = data.message || 'Cartão excluído com sucesso.';
        this.limparMensagemDepois();
      } catch (error) {
        this.erro = error.response?.data?.message
          || 'Não foi possível excluir o cartão.';
      } finally {
        this.salvando = false;
      }
    },

    guardarSnapshot() {
      this.snapshotColunas = JSON.parse(JSON.stringify(this.colunas));
    },

    async onCartaoMovido(event, colunaDestino) {
      if (!this.podeEditar) {
        return;
      }

      if (!event.moved && !event.added) {
        return;
      }

      const cartao = event.moved?.element ?? event.added?.element;
      const novaPosicao = event.moved?.newIndex ?? event.added?.newIndex;

      if (!cartao || novaPosicao == null) {
        return;
      }

      const snapshot = this.snapshotColunas
        ?? JSON.parse(JSON.stringify(this.colunas));
      this.snapshotColunas = null;

      this.salvando = true;
      this.erro = '';

      try {
        await window.axios.put(`/api/kanban/cartoes/${cartao.id}/mover`, {
          kanban_coluna_id: colunaDestino.id,
          position: novaPosicao,
        });

        cartao.kanban_coluna_id = colunaDestino.id;
        cartao.position = novaPosicao;
        this.reindexarColuna(colunaDestino);
      } catch (error) {
        this.colunas = snapshot;
        this.erro = error.response?.data?.message
          || 'Não foi possível salvar a movimentação.';
      } finally {
        this.salvando = false;
      }
    },

    reindexarColuna(coluna) {
      coluna.cartoes.forEach((cartao, index) => {
        cartao.position = index;
        cartao.kanban_coluna_id = coluna.id;
      });
    },

    adicionarCartaoLocal(cartao, colunaInfo = null, colunaCriada = false) {
      let coluna = this.colunas.find((item) => item.id === cartao.kanban_coluna_id);

      if (!coluna && colunaInfo) {
        coluna = {
          id: colunaInfo.id,
          titulo: colunaInfo.titulo,
          position: colunaInfo.position,
          cor: colunaInfo.cor,
          cartoes: [],
        };
        this.colunas.push(coluna);
      }

      if (!coluna) {
        this.carregarQuadro();
        return;
      }

      if (colunaCriada && colunaInfo) {
        coluna.titulo = colunaInfo.titulo;
        coluna.position = colunaInfo.position;
        coluna.cor = colunaInfo.cor;
      }

      coluna.cartoes.push(cartao);
    },

    atualizarCartaoLocal(cartao) {
      for (const coluna of this.colunas) {
        const index = coluna.cartoes.findIndex((item) => item.id === cartao.id);

        if (index !== -1) {
          coluna.cartoes.splice(index, 1, {
            ...coluna.cartoes[index],
            ...cartao,
          });
          return;
        }
      }
    },

    removerCartaoLocal(cartaoId) {
      for (const coluna of this.colunas) {
        const index = coluna.cartoes.findIndex((item) => item.id === cartaoId);

        if (index !== -1) {
          coluna.cartoes.splice(index, 1);
          this.reindexarColuna(coluna);
          return;
        }
      }
    },

    limparMensagemDepois() {
      window.setTimeout(() => {
        this.mensagemSucesso = '';
      }, 2500);
    },
  },
};
