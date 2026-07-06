import logoSenac from '../../images/Logo-Senac-branco.png';

export default {
  name: 'Login',
  data() {
    return {
      logoSenac,
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

        localStorage.setItem('sgp_token', data.token);
        localStorage.setItem('sgp_usuario', JSON.stringify(data.usuario));
        window.axios.defaults.headers.common.Authorization = `Bearer ${data.token}`;

        const redirect = this.$route.query.redirect || '/app/inicio';
        this.$router.push(redirect);
      } catch (error) {
        if (error.response?.status === 422) {
          const errors = error.response.data.errors;
          this.errorMessage = errors?.email?.[0] || errors?.senha?.[0] || 'Dados inválidos.';
        } else {
          this.errorMessage = 'Não foi possível entrar. Tente novamente.';
        }
      } finally {
        this.loading = false;
      }
    },
  },
};
