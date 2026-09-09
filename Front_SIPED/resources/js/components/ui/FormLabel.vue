<template>
  <component
    :is="elemento"
    class="sgp-form-label"
    :for="htmlFor || undefined"
  >
    <span class="sgp-form-label__text">{{ label }}</span>
    <em v-if="required" class="required-mark" aria-hidden="true">*</em>
  </component>
</template>

<script>
export default {
  name: 'FormLabel',
  props: {
    label: { type: String, required: true },
    required: { type: Boolean, default: false },
    htmlFor: { type: String, default: '' },
    tag: { type: String, default: '' },
  },
  computed: {
    elemento() {
      if (this.tag) {
        return this.tag;
      }
      return this.htmlFor ? 'label' : 'span';
    },
  },
};
</script>
