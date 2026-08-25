import logoSenac from '../../images/Logo-Senac-branco.png';
import { marcarSessao } from './auth';
import { SGP_RODAPE } from './versao';

export default {
  name: 'Login',
  data() {
    return {
      logoSenac,
      rodapeVersao: SGP_RODAPE,
      email: '',
      senha: '',
      showPassword: false,
      loading: false,
      errorMessage: '',
    };
  },
  methods: {
    async handleLogin() {
      this.loading = true;
      this.errorMessage = '';

      try {
        const { data } = await window.axios.post('/api/login', {
          email: this.email,
          senha: this.senha,
        });

        marcarSessao(data.token, data.usuario);

        const redirect = this.$route.query.redirect || '/app/inicio';
        this.$router.replace(redirect);
      } catch (error) {
        if (error.response?.status === 429) {
          this.errorMessage = 'Muitas tentativas de login. Aguarde cerca de 1 minuto e tente novamente.';
        } else if (error.response?.status === 422) {
          const errors = error.response.data.errors;
          this.errorMessage = errors?.email?.[0] || errors?.senha?.[0] || error.response.data.message || 'Dados inválidos.';
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
