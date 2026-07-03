<template>
  <div class="login-page">
    <div class="login-bg-circle login-bg-circle--1"></div>
    <div class="login-bg-circle login-bg-circle--2"></div>

    <header class="login-header">
      <div class="login-logo-wrap">
        <img :src="logoSenac" alt="Senac" class="login-logo" />
      </div>
    </header>

    <div class="login-body">
      <div class="login-content">
        <h1 class="login-title">SGP</h1>
        <p class="login-subtitle">SISTEMA DE GERENCIAMENTO DE PORTFÓLIO</p>

        <div class="login-card">
        <p class="login-card-intro">Entre para iniciar uma nova sessão</p>

        <form class="login-form" @submit.prevent="handleLogin">
          <div v-if="errorMessage" class="login-error" role="alert">
            {{ errorMessage }}
          </div>

          <div class="form-group">
            <label for="email">E-mail</label>
            <div class="input-wrapper">
              <span class="input-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
              </span>
              <input
                id="email"
                v-model="email"
                type="email"
                autocomplete="email"
                placeholder="seu@email.senac.br"
                required
              />
            </div>
          </div>

          <div class="form-group">
            <label for="senha">Senha</label>
            <div class="input-wrapper">
              <span class="input-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              </span>
              <input
                id="senha"
                v-model="senha"
                :type="showPassword ? 'text' : 'password'"
                autocomplete="current-password"
                placeholder="••••••••"
                required
              />
              <button
                type="button"
                class="toggle-password"
                :aria-label="showPassword ? 'Ocultar senha' : 'Mostrar senha'"
                @click="showPassword = !showPassword"
              >
                <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
              </button>
            </div>
          </div>

          <button type="submit" class="btn-entrar" :disabled="loading">
            {{ loading ? 'Entrando...' : 'Entrar' }}
          </button>
        </form>

        <p class="login-card-footer">
          Acesso restrito a colaboradores autorizados do SENAC DF.
        </p>
        </div>
      </div>
    </div>

    <footer class="login-page-footer">
      © 2026 SENAC DF · SGP v1.0-beta · Uso interno
    </footer>
  </div>
</template>

<script>
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
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  background: #004587;
  position: relative;
  overflow: hidden;
  font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
}

.login-bg-circle {
  position: absolute;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.04);
  pointer-events: none;
}

.login-bg-circle--1 {
  width: 480px;
  height: 480px;
  top: -120px;
  right: -80px;
}

.login-bg-circle--2 {
  width: 360px;
  height: 360px;
  bottom: -100px;
  left: -60px;
}

.login-header {
  position: absolute;
  top: 1.25rem;
  left: 0;
  right: 0;
  z-index: 2;
  display: flex;
  justify-content: center;
  width: 100%;
}

.login-body {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 5rem 1rem 4rem;
  position: relative;
  z-index: 1;
}

.login-content {
  width: 100%;
  max-width: 420px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.login-content .login-title {
  margin-bottom: 0.35rem;
}

.login-content .login-subtitle {
  margin-bottom: 1.5rem;
}

.login-content .login-card {
  width: 100%;
}

.login-logo-wrap {
  display: flex;
  justify-content: center;
  align-items: center;
}

.login-logo {
  display: block;
  height: 30px;
  width: auto;
  max-width: 120px;
  object-fit: contain;
  object-position: center center;
}

.login-title {
  color: #fff;
  font-size: 2.5rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  margin: 0 0 0.35rem;
}

.login-subtitle {
  color: rgba(255, 255, 255, 0.85);
  font-size: 0.7rem;
  letter-spacing: 0.12em;
  margin: 0;
  font-weight: 500;
}

.login-card {
  background: #fff;
  border-radius: 12px;
  border-top: 4px solid #f3800d;
  padding: 2rem 1.75rem 1.5rem;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
  text-align: left;
}

.login-card-intro {
  text-align: center;
  color: #6b7280;
  font-size: 0.9rem;
  margin: 0 0 1.5rem;
}

.login-error {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #b91c1c;
  border-radius: 8px;
  padding: 0.75rem 1rem;
  font-size: 0.875rem;
  margin-bottom: 1rem;
}

.form-group {
  margin-bottom: 1.25rem;
}

.form-group label {
  display: block;
  color: #4b5563;
  font-size: 0.875rem;
  margin-bottom: 0.4rem;
  font-weight: 500;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 0.85rem;
  color: #9ca3af;
  display: flex;
  pointer-events: none;
}

.input-wrapper input {
  width: 100%;
  padding: 0.7rem 2.75rem 0.7rem 2.5rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.95rem;
  color: #111827;
  outline: none;
  transition: border-color 0.15s;
}

.input-wrapper input:focus {
  border-color: #004587;
  box-shadow: 0 0 0 3px rgba(0, 69, 135, 0.12);
}

.toggle-password {
  position: absolute;
  right: 0.75rem;
  background: none;
  border: none;
  color: #9ca3af;
  cursor: pointer;
  display: flex;
  padding: 0.25rem;
}

.toggle-password:hover {
  color: #4b5563;
}

.btn-entrar {
  width: 100%;
  margin-top: 0.5rem;
  padding: 0.85rem;
  background: #f3800d;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.15s;
}

.btn-entrar:hover:not(:disabled) {
  background: #d96f0a;
}

.btn-entrar:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.login-card-footer {
  text-align: center;
  color: #9ca3af;
  font-size: 0.75rem;
  margin: 1.5rem 0 0;
  line-height: 1.4;
}

.login-page-footer {
  position: absolute;
  bottom: 1.25rem;
  left: 0;
  right: 0;
  z-index: 2;
  text-align: center;
  color: rgba(255, 255, 255, 0.55);
  font-size: 0.75rem;
}
</style>
