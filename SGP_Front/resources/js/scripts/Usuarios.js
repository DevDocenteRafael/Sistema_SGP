import { UNIDADES } from './unidades';
import PageTableCard from '../components/crud/PageTableCard.vue';
import { mixinHistoricoFormulario } from './formularioHistorico';

export default {
  name: 'Usuarios',
  mixins: [mixinHistoricoFormulario],
  components: { PageTableCard },
  data() {
    return {
      modo: 'lista',
      usuarios: [],
      carregando: false,
      salvando: false,
      editandoId: null,
      usuarioDetalhe: null,
      mensagemSucesso: '',
      mensagemErro: '',
      erroFormulario: '',
      filtros: {
        busca: '',
        perfil: '',
        status: '',
      },
      form: this.formVazio(),
      buscaTimeout: null,
      unidades: UNIDADES,
    };
  },
  mounted() {
    this.carregarUsuarios();
  },
  methods: {
    formVazio() {
      return {
        nome: '',
        email: '',
        senha: '',
        confirmarSenha: '',
        perfil: '',
        status: true,
        unidade: '',
        area: '',
        telefone: '',
        cpf: '',
      };
    },

    iniciais(nome) {
      if (!nome) {
        return '?';
      }

      const partes = nome.trim().split(/\s+/);

      if (partes.length === 1) {
        return partes[0].slice(0, 2).toUpperCase();
      }

      return `${partes[0][0]}${partes[partes.length - 1][0]}`.toUpperCase();
    },

    avatarClass(perfil) {
      return {
        'avatar-admin': perfil === 'Administrador',
        'avatar-editor': perfil === 'Editor',
        'avatar-consultor': perfil === 'Consultor',
      };
    },

    badgePerfil(perfil) {
      return {
        'badge-admin': perfil === 'Administrador',
        'badge-editor': perfil === 'Editor',
        'badge-consultor': perfil === 'Consultor',
      };
    },

    async carregarUsuarios() {
      clearTimeout(this.buscaTimeout);

      this.buscaTimeout = setTimeout(async () => {
        this.carregando = true;
        this.mensagemErro = '';

        try {
          const params = {};

          if (this.filtros.busca) {
            params.busca = this.filtros.busca;
          }

          if (this.filtros.perfil) {
            params.perfil = this.filtros.perfil;
          }

          if (this.filtros.status !== '') {
            params.status = this.filtros.status;
          }

          const { data } = await window.axios.get('/api/usuarios', { params });
          this.usuarios = data.data ?? [];
        } catch (error) {
          this.mensagemErro = this.extrairErro(error, 'Não foi possível carregar os usuários.');
        } finally {
          this.carregando = false;
        }
      }, 200);
    },

    abrirDetalhes(usuario) {
      this.usuarioDetalhe = { ...usuario };
    },

    fecharDetalhes() {
      this.usuarioDetalhe = null;
    },

    editarDoDetalhe() {
      const usuario = this.usuarioDetalhe;
      this.fecharDetalhes();
      this.abrirEdicao(usuario);
    },

    abrirNovo() {
      this.aplicarEstadoNovoLocal();
      this.empilharHistoricoFormulario('novo');
    },

    aplicarEstadoNovoLocal() {
      this.modo = 'novo';
      this.editandoId = null;
      this.usuarioDetalhe = null;
      this.form = this.formVazio();
      this.erroFormulario = '';
      this.mensagemErro = '';
    },

    abrirEdicao(usuario) {
      this.aplicarEstadoEdicaoLocal(usuario);
      this.empilharHistoricoFormulario('editar', usuario.id);
    },

    aplicarEstadoEdicaoLocal(usuario) {
      this.modo = 'editar';
      this.editandoId = usuario.id;
      this.form = {
        nome: usuario.nome ?? '',
        email: usuario.email ?? '',
        senha: '',
        confirmarSenha: '',
        perfil: usuario.perfil ?? '',
        status: Boolean(usuario.status),
        unidade: usuario.unidade ?? '',
        area: usuario.area ?? '',
        telefone: usuario.telefone ?? '',
        cpf: usuario.cpf ?? '',
      };
      this.erroFormulario = '';
      this.mensagemErro = '';
    },

    async aplicarEstadoEdicaoPorId(id) {
      let usuario = this.usuarios.find((item) => String(item.id) === String(id));

      if (!usuario) {
        try {
          const { data } = await window.axios.get(`/api/usuarios/${id}`);
          usuario = data.usuario || data.data || null;
        } catch {
          usuario = null;
        }
      }

      if (!usuario) {
        this.aplicarEstadoListaLocal();
        this.limparHistoricoFormulario();
        return;
      }

      this.aplicarEstadoEdicaoLocal(usuario);
    },

    voltarLista() {
      this.aplicarEstadoListaLocal();
      this.limparHistoricoFormulario();
    },

    aplicarEstadoListaLocal() {
      this.modo = 'lista';
      this.editandoId = null;
      this.erroFormulario = '';
      this.form = this.formVazio();
    },

    async salvarUsuario() {
      if (this.form.senha || this.form.confirmarSenha) {
        if (this.form.senha !== this.form.confirmarSenha) {
          this.erroFormulario = 'As senhas não coincidem.';
          return;
        }
      }

      this.salvando = true;
      this.erroFormulario = '';
      this.mensagemSucesso = '';

      const payload = {
        nome: this.form.nome,
        email: this.form.email,
        perfil: this.form.perfil,
        status: this.form.status,
        unidade: this.form.unidade || null,
        area: this.form.area || null,
        telefone: this.form.telefone || null,
        cpf: this.form.cpf || null,
      };

      if (this.form.senha) {
        payload.senha = this.form.senha;
      }

      try {
        if (this.editandoId) {
          const { data } = await window.axios.put(`/api/usuarios/${this.editandoId}`, payload);
          this.mensagemSucesso = data.message;
        } else {
          const { data } = await window.axios.post('/api/usuarios', payload);
          this.mensagemSucesso = data.message;
        }

        this.voltarLista();
        await this.carregarUsuarios();
      } catch (error) {
        this.erroFormulario = this.extrairErro(error, 'Não foi possível salvar o usuário.');
      } finally {
        this.salvando = false;
      }
    },

    async excluirUsuario(usuario) {
      const confirmar = window.confirm(
        `Excluir o usuário "${usuario.nome}"? Esta ação não pode ser desfeita.`
      );

      if (!confirmar) {
        return;
      }

      this.mensagemErro = '';
      this.mensagemSucesso = '';

      try {
        const { data } = await window.axios.delete(`/api/usuarios/${usuario.id}`);
        this.mensagemSucesso = data.message;
        await this.carregarUsuarios();
      } catch (error) {
        this.mensagemErro = this.extrairErro(error, 'Não foi possível excluir o usuário.');
      }
    },

    extrairErro(error, fallback) {
      if (error.response?.data?.message) {
        return error.response.data.message;
      }

      const errors = error.response?.data?.errors;

      if (errors) {
        const primeiro = Object.values(errors)[0];

        return Array.isArray(primeiro) ? primeiro[0] : fallback;
      }

      return fallback;
    },
  },
};
