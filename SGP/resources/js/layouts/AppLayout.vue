<template>
  <div class="app-layout">
    <Sidebar />
    <main class="app-main">
      <router-view />
    </main>
  </div>
</template>

<script>
import Sidebar from '../components/Sidebar.vue';

export default {
  name: 'AppLayout',
  components: { Sidebar },
};
</script>

<style scoped>
.app-layout {
  display: flex;
  min-height: 100vh;
}

.app-main {
  flex: 1;
  padding: 1.5rem;
  background: #f8f9fa;
  color: #212529;
  overflow-y: auto;
}
</style>