<template>
  <div>
    <div v-if="sucesso" class="alert alert-success">{{ sucesso }}</div>
    <div v-if="erro" class="alert alert-error">{{ erro }}</div>
    <div v-if="bloqueado" class="alert alert-error">
      Você não possui autorização para consultar esta funcionalidade. Verifique seu perfil de acesso.
    </div>
  </div>
</template>

<script>
export default {
  name: 'CrudAlerts',
  props: {
    sucesso: { type: String, default: '' },
    erro: { type: String, default: '' },
    bloqueado: { type: Boolean, default: false },
  },
};
</script>
