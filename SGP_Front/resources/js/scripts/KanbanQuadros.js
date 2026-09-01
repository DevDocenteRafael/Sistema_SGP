import { podeConsultarDados, podeEditarDados } from './auth';
import {
  combinarValidacoes,
  extrairErroApi,
  tamanhoMaximo,
  textoObrigatorio,
} from '../utils/validacao';

export default {
  name: 'KanbanQuadros',

  data() {
    return {
      quadros: [],
      carregando: true,
      salvando: false,
      erro: '',
      mensagemSucesso: '',
      erroFormulario: '',
      podeEditarApi: false,
      modalAberto: false,
      quadroEmEdicao: null,
      form: { nome: '' },
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
      return this.quadroEmEdicao ? 'Renomear quadro' : 'Novo quadro';
    },
  },

  mounted() {
    this.carregarQuadros();
  },

  methods: {
    validarFormulario() {
      return combinarValidacoes(
        textoObrigatorio(this.form.nome, 'Informe o nome do quadro.'),
        tamanhoMaximo(this.form.nome, 100, 'O nome do quadro pode ter no máximo 100 caracteres.'),
      );
    },

    extrairErro(error, fallback) {
      return extrairErroApi(error, fallback);
    },

    async carregarQuadros() {
      this.carregando = true;
      this.erro = '';

      if (!podeConsultarDados()) {
        this.quadros = [];
        this.carregando = false;
        return;
      }

      try {
        const { data } = await window.axios.get('/api/kanban/quadros');
        this.quadros = Array.isArray(data.data) ? data.data : [];
        this.podeEditarApi = Boolean(data.meta?.pode_editar);
      } catch (error) {
        this.erro = this.extrairErro(error, 'Não foi possível carregar os quadros.');
        this.quadros = [];
      } finally {
        this.carregando = false;
      }
    },

    abrirQuadro(quadro) {
      this.$router.push(`/app/ferramentas/kanban/${quadro.slug}`);
    },

    abrirNovo() {
      if (!this.podeEditar) {
        return;
      }

      this.quadroEmEdicao = null;
      this.form = { nome: '' };
      this.erroFormulario = '';
      this.modalAberto = true;
    },

    abrirEdicao(quadro) {
      if (!this.podeEditar) {
        return;
      }

      this.quadroEmEdicao = quadro;
      this.form = { nome: quadro.nome || '' };
      this.erroFormulario = '';
      this.modalAberto = true;
    },

    fecharModal() {
      this.modalAberto = false;
      this.quadroEmEdicao = null;
      this.form = { nome: '' };
      this.erroFormulario = '';
    },

    async salvar() {
      if (!this.podeEditar || this.salvando) {
        return;
      }

      const erroValidacao = this.validarFormulario();

      if (erroValidacao) {
        this.erroFormulario = erroValidacao;
        return;
      }

      const nome = this.form.nome.trim();

      this.salvando = true;
      this.erroFormulario = '';
      this.erro = '';

      try {
        if (this.quadroEmEdicao) {
          const { data } = await window.axios.put(
            `/api/kanban/quadros/${this.quadroEmEdicao.slug}`,
            { nome }
          );

          const index = this.quadros.findIndex((item) => item.id === this.quadroEmEdicao.id);

          if (index !== -1) {
            this.quadros.splice(index, 1, data.kanban_quadro);
          }

          this.mensagemSucesso = data.message || 'Quadro atualizado com sucesso.';
          this.fecharModal();
        } else {
          const { data } = await window.axios.post('/api/kanban/quadros', { nome });
          this.mensagemSucesso = data.message || 'Quadro criado com sucesso.';
          this.fecharModal();
          this.$router.push(`/app/ferramentas/kanban/${data.kanban_quadro.slug}`);
          return;
        }

        this.limparMensagemDepois();
      } catch (error) {
        this.erroFormulario = this.extrairErro(error, 'Não foi possível salvar o quadro.');
      } finally {
        this.salvando = false;
      }
    },

    async excluir(quadro) {
      if (!this.podeEditar || this.salvando) {
        return;
      }

      if (!window.confirm(`Excluir o quadro "${quadro.nome}" e todo o seu conteúdo?`)) {
        return;
      }

      this.salvando = true;
      this.erro = '';

      try {
        const { data } = await window.axios.delete(`/api/kanban/quadros/${quadro.slug}`);
        this.quadros = this.quadros.filter((item) => item.id !== quadro.id);
        this.mensagemSucesso = data.message || 'Quadro excluído com sucesso.';
        this.limparMensagemDepois();
      } catch (error) {
        this.erro = this.extrairErro(error, 'Não foi possível excluir o quadro.');
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
