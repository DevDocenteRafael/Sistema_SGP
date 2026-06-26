<template>
  <div class="login-page">
    <h1>Login</h1>
    <p>Página em construção.</p>
    <router-link class="btn btn-primary" to="/app/inicio">Entrar</router-link>
  </div>
</template>

<script>
export default {
  name: 'Login',
};
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  padding: 2rem;
}
</style>
