<script setup>
import AppIcon from './AppIcon.vue'

defineProps({
  icon: { type: String, default: 'inbox' },
  title: { type: String, required: true },
  description: { type: String, default: null },
  // Versão compacta para dentro de tabelas/cartões.
  compact: { type: Boolean, default: false },
})
</script>

<template>
  <div class="empty" :class="{ compact }">
    <div class="icon-wrap">
      <AppIcon :name="icon" :size="compact ? 20 : 26" />
    </div>
    <p class="title">{{ title }}</p>
    <p v-if="description" class="desc">{{ description }}</p>
    <div v-if="$slots.default" class="actions"><slot /></div>
  </div>
</template>

<style scoped>
.empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: var(--space-2);
  padding: var(--space-12) var(--space-6);
  border: 1px dashed var(--color-border-strong);
  border-radius: var(--radius-lg);
  background: var(--color-surface);
}
.empty.compact { padding: var(--space-8) var(--space-4); border: none; background: transparent; }
.icon-wrap {
  display: grid;
  place-items: center;
  width: 3.5rem;
  height: 3.5rem;
  margin-bottom: var(--space-2);
  border-radius: var(--radius-full);
  background: var(--color-primary-soft);
  color: var(--color-primary);
}
.compact .icon-wrap { width: 2.75rem; height: 2.75rem; }
.title { font-weight: 650; color: var(--color-text); }
.desc { max-width: 28rem; color: var(--color-text-muted); font-size: var(--text-sm); }
.actions { margin-top: var(--space-3); }
</style>
