<script setup>
import { computed } from 'vue'
import AppIcon from './AppIcon.vue'

// Botão da aplicação. Estilos em style.css (.btn + modificadores).
// Para botão só com ícone, informe `icon` e `label` (vira o aria-label).
const props = defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (v) => ['primary', 'secondary', 'ghost', 'soft', 'danger', 'danger-ghost'].includes(v),
  },
  size: { type: String, default: 'md', validator: (v) => ['sm', 'md', 'lg'].includes(v) },
  type: { type: String, default: 'button' },
  icon: { type: String, default: null },
  iconRight: { type: String, default: null },
  label: { type: String, default: null },
  loading: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  block: { type: Boolean, default: false },
})

const iconOnly = computed(() => !!props.label)
const iconSize = computed(() => (props.size === 'sm' ? 15 : props.size === 'lg' ? 20 : 17))
</script>

<template>
  <button
    :type="type"
    class="btn"
    :class="[
      variant !== 'primary' && variant,
      size !== 'md' && size,
      { block, 'icon-only': iconOnly, 'is-loading': loading },
    ]"
    :disabled="disabled || loading"
    :aria-label="label"
    :title="label"
    :aria-busy="loading ? 'true' : null"
  >
    <span v-if="loading" class="spinner" aria-hidden="true"></span>
    <AppIcon v-else-if="icon" :name="icon" :size="iconSize" />
    <slot v-if="!iconOnly" />
    <AppIcon v-if="iconRight && !loading" :name="iconRight" :size="iconSize" />
  </button>
</template>

<style scoped>
.spinner {
  width: 1em;
  height: 1em;
  border: 2px solid currentColor;
  border-right-color: transparent;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
.btn.is-loading:disabled { opacity: 0.8; cursor: progress; }
</style>
