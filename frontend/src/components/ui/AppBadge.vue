<script setup>
import AppIcon from './AppIcon.vue'

defineProps({
  tone: {
    type: String,
    default: 'neutral',
    validator: (v) => ['neutral', 'primary', 'success', 'danger', 'info', 'warning'].includes(v),
  },
  icon: { type: String, default: null },
})
</script>

<template>
  <span class="badge" :class="tone">
    <AppIcon v-if="icon" :name="icon" :size="13" :stroke-width="2.5" />
    <slot />
  </span>
</template>

<style scoped>
.badge {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.2rem 0.6rem;
  border-radius: var(--radius-full);
  font-size: var(--text-xs);
  font-weight: 600;
  line-height: 1.4;
  white-space: nowrap;
}
.neutral { background: var(--color-surface-2); color: var(--color-text-muted); }
.primary { background: var(--color-primary-soft); color: var(--color-primary-soft-text); }
.success { background: var(--color-success-soft); color: var(--color-success-soft-text); }
.danger { background: var(--color-danger-soft); color: var(--color-danger-soft-text); }
.info { background: var(--color-info-soft); color: var(--color-info-soft-text); }
.warning { background: var(--color-warning-soft); color: var(--color-warning-soft-text); }
</style>
