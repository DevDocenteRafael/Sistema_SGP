import { atualizarUsuarioSessao, getUsuario, podeGerenciarUsuarios } from './auth';
import { UNIDADES } from './unidades';
import { carregarUnidadesNomes } from './unidadesApi';
import PageTableCard from '../components/crud/PageTableCard.vue';
import CrudPageHeader from '../components/crud/CrudPageHeader.vue';
import { mixinHistoricoFormulario } from './formularioHistorico';
import {
  combinarValidacoes,
  extrairErroApi,
  mascaraCpf,
  mascaraTelefone,
  tamanhoMaximo,
  textoObrigatorio,
  validarCpf,
  validarEmail,
  validarSenha,
  somenteNumeros,
} from '../utils/validacao';

export default {
  name: 'Usuarios',
  mixins: [mixinHistoricoFormulario],
  components: { PageTableCard, CrudPageHeader },
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
  computed: {
    podeEditar() {
      return podeGerenciarUsuarios();
    },
    temFiltro() {
      return Object.values(this.filtros).some((valor) => valor !== '' && valor != null);
    },
  },
  mounted() {
    this.carregarUsuarios();
    carregarUnidadesNomes().then((nomes) => {
      this.unidades = nomes;
    });
  },
  beforeUnmount() {
    this.revogarPreviewFoto();
  },
  methods: {
    limparFiltros() {
      this.filtros = { busca: '', perfil: '', status: '' };
      this.carregarUsuarios();
    },

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
        foto: '',
        fotoArquivo: null,
        removerFoto: false,
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
      if (!this.podeEditar) {
        this.mensagemErro = 'Você não tem permissão para cadastrar usuários.';
        return;
      }

      this.aplicarEstadoNovoLocal();
      this.empilharHistoricoFormulario('novo');
    },

    aplicarEstadoNovoLocal() {
      this.revogarPreviewFoto();
      this.modo = 'novo';
      this.editandoId = null;
      this.usuarioDetalhe = null;
      this.form = this.formVazio();
      this.erroFormulario = '';
      this.mensagemErro = '';
    },

    abrirEdicao(usuario) {
      if (!this.podeEditar) {
        this.mensagemErro = 'Você não tem permissão para editar usuários.';
        return;
      }

      this.aplicarEstadoEdicaoLocal(usuario);
      this.empilharHistoricoFormulario('editar', usuario.id);
    },

    aplicarEstadoEdicaoLocal(usuario) {
      this.revogarPreviewFoto();
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
        foto: usuario.foto || '',
        fotoArquivo: null,
        removerFoto: false,
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
      this.revogarPreviewFoto();
      this.modo = 'lista';
      this.editandoId = null;
      this.erroFormulario = '';
      this.form = this.formVazio();
    },

    revogarPreviewFoto() {
      if (this.form?.foto && String(this.form.foto).startsWith('blob:')) {
        URL.revokeObjectURL(this.form.foto);
      }
    },

    onFotoSelecionada(evento) {
      const arquivo = evento.target.files?.[0];
      evento.target.value = '';

      if (!arquivo) {
        return;
      }

      if (!String(arquivo.type || '').startsWith('image/')) {
        this.erroFormulario = 'A foto deve ser uma imagem válida.';
        return;
      }

      if (arquivo.size > 2 * 1024 * 1024) {
        this.erroFormulario = 'A foto deve ter no máximo 2 MB.';
        return;
      }

      this.revogarPreviewFoto();
      this.form.fotoArquivo = arquivo;
      this.form.removerFoto = false;
      this.form.foto = URL.createObjectURL(arquivo);
      this.erroFormulario = '';
    },

    limparFoto() {
      this.revogarPreviewFoto();
      this.form.foto = '';
      this.form.fotoArquivo = null;
      this.form.removerFoto = true;
    },

    formatarCpf(evento) {
      this.form.cpf = mascaraCpf(evento.target.value);
    },

    formatarTelefone(evento) {
      this.form.telefone = mascaraTelefone(evento.target.value);
    },

    validarFormulario() {
      return combinarValidacoes(
        textoObrigatorio(this.form.nome, 'O nome é obrigatório.'),
        tamanhoMaximo(this.form.nome, 100, 'O nome deve ter no máximo 100 caracteres.'),
        validarEmail(this.form.email, { obrigatorio: true }),
        tamanhoMaximo(this.form.email, 100, 'O e-mail deve ter no máximo 100 caracteres.'),
        textoObrigatorio(this.form.perfil, 'O perfil é obrigatório.'),
        validarCpf(this.form.cpf),
        tamanhoMaximo(this.form.area, 100, 'A área deve ter no máximo 100 caracteres.'),
        this.modo === 'novo'
          ? validarSenha(this.form.senha, { obrigatorio: true })
          : validarSenha(this.form.senha),
        this.form.senha !== this.form.confirmarSenha ? 'As senhas não coincidem.' : '',
      );
    },

    montarFormData() {
      const formData = new FormData();
      formData.append('nome', this.form.nome.trim());
      formData.append('email', this.form.email.trim());
      formData.append('perfil', this.form.perfil);
      formData.append('status', this.form.status ? '1' : '0');
      formData.append('unidade', this.form.unidade || '');
      formData.append('area', this.form.area?.trim() || '');
      formData.append('cpf', this.form.cpf ? somenteNumeros(this.form.cpf) : '');
      formData.append('telefone', this.form.telefone ? somenteNumeros(this.form.telefone) : '');

      if (this.form.senha) {
        formData.append('senha', this.form.senha);
      }

      if (this.form.fotoArquivo) {
        formData.append('foto', this.form.fotoArquivo);
      }

      if (this.form.removerFoto && !this.form.fotoArquivo) {
        formData.append('remover_foto', '1');
      }

      return formData;
    },

    async salvarUsuario() {
      const erroValidacao = this.validarFormulario();

      if (erroValidacao) {
        this.erroFormulario = erroValidacao;
        return;
      }

      if (!this.podeEditar) {
        this.erroFormulario = 'Você não tem permissão para salvar usuários.';
        return;
      }

      this.salvando = true;
      this.erroFormulario = '';
      this.mensagemSucesso = '';

      try {
        const formData = this.montarFormData();
        let usuarioSalvo = null;

        if (this.editandoId) {
          formData.append('_method', 'PUT');
          const { data } = await window.axios.post(`/api/usuarios/${this.editandoId}`, formData);
          this.mensagemSucesso = data.message;
          usuarioSalvo = data.usuario;
        } else {
          const { data } = await window.axios.post('/api/usuarios', formData);
          this.mensagemSucesso = data.message;
          usuarioSalvo = data.usuario;
        }

        const logado = getUsuario();
        if (usuarioSalvo && logado && String(logado.id) === String(usuarioSalvo.id)) {
          atualizarUsuarioSessao({
            nome: usuarioSalvo.nome,
            email: usuarioSalvo.email,
            perfil: usuarioSalvo.perfil,
            unidade: usuarioSalvo.unidade ?? null,
            foto: usuarioSalvo.foto ?? null,
          });
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
      if (!this.podeEditar) {
        this.mensagemErro = 'Você não tem permissão para excluir usuários.';
        return;
      }

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
      return extrairErroApi(error, fallback);
    },
  },
};
