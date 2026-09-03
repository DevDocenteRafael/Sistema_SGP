import logoSenac from '../../images/Logo-Senac-branco.png';
import { marcarSessao } from './auth';
import { SGP_RODAPE } from './versao';

const STORAGE_CREDENCIAIS = 'sgp_login_lembrado';

export default {
  name: 'Login',
  data() {
    return {
      logoSenac,
      rodapeVersao: SGP_RODAPE,
      email: '',
      senha: '',
      lembrar: true,
      showPassword: false,
      loading: false,
      errorMessage: '',
    };
  },
  mounted() {
    this.carregarCredenciaisSalvas();
  },
  methods: {
    carregarCredenciaisSalvas() {
      try {
        const raw = localStorage.getItem(STORAGE_CREDENCIAIS);
        if (!raw) return;

        const salvo = JSON.parse(raw);
        this.email = salvo.email || '';
        this.senha = salvo.senha || '';
        this.lembrar = true;
      } catch {
        localStorage.removeItem(STORAGE_CREDENCIAIS);
      }
    },

    salvarCredenciais() {
      if (!this.lembrar) {
        localStorage.removeItem(STORAGE_CREDENCIAIS);
        return;
      }

      localStorage.setItem(STORAGE_CREDENCIAIS, JSON.stringify({
        email: this.email,
        senha: this.senha,
      }));
    },

    async handleLogin() {
      this.loading = true;
      this.errorMessage = '';

      try {
        const { data } = await window.axios.post('/api/login', {
          email: this.email,
          senha: this.senha,
        });

        this.salvarCredenciais();
        marcarSessao(data.token, data.usuario);

        const redirect = this.$route.query.redirect || '/app/inicio';
        this.$router.replace(redirect);
      } catch (error) {
        const raw = typeof error.response?.data === 'string' ? error.response.data : '';
        if (error.response?.status === 429) {
          this.errorMessage = 'Muitas tentativas de login. Aguarde cerca de 1 minuto e tente novamente.';
        } else if (error.response?.status === 422) {
          const errors = error.response.data.errors;
          this.errorMessage = errors?.email?.[0] || errors?.senha?.[0] || error.response.data.message || 'Dados inválidos.';
        } else if (
          error.response?.status === 500
          && /No space left on device|errno=28|SQLITE_FULL/i.test(raw)
        ) {
          this.errorMessage = 'O disco do computador está cheio. Libere espaço em C: e tente entrar novamente.';
        } else if (error.response?.status === 500) {
          this.errorMessage = error.response?.data?.message
            || 'Erro interno no servidor (HTTP 500). Verifique o back-end e o espaço em disco.';
        } else {
          this.errorMessage = error.response?.data?.message
            || `Não foi possível entrar (HTTP ${error.response?.status || 'sem resposta'}). Confira se o back está na pasta SGP_Back e rode php artisan optimize:clear.`;
        }
      } finally {
        this.loading = false;
      }
    },
  },
};
