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
      {{ rodapeVersao }}
    </footer>
  </div>
</template>

<script src="../scripts/Login.js"></script>
<style scoped src="../../css/Login.css"></style>
