<script setup>
import { RouterLink } from 'vue-router'
import AppIcon from './AppIcon.vue'

defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: null },
  // Rota de "voltar" opcional, exibida acima do título.
  back: { type: [Object, String], default: null },
  backLabel: { type: String, default: 'Voltar' },
})
</script>

<template>
  <header class="page-header">
    <RouterLink v-if="back" :to="back" class="back">
      <AppIcon name="arrow-left" :size="16" />
      {{ backLabel }}
    </RouterLink>
    <div class="head-row">
      <div class="titles">
        <h1>{{ title }}</h1>
        <p v-if="subtitle || $slots.subtitle" class="subtitle">
          <slot name="subtitle">{{ subtitle }}</slot>
        </p>
      </div>
      <div v-if="$slots.actions" class="actions">
        <slot name="actions" />
      </div>
    </div>
  </header>
</template>

<style scoped>
.page-header { margin-bottom: var(--space-6); }
.back {
  display: inline-flex;
  align-items: center;
  gap: var(--space-1);
  margin-bottom: var(--space-3);
  color: var(--color-text-muted);
  font-size: var(--text-sm);
  font-weight: 500;
  text-decoration: none;
  border-radius: var(--radius-sm);
}
.back:hover { color: var(--color-text); }
.head-row { display: flex; align-items: flex-end; flex-wrap: wrap; gap: var(--space-4); }
.titles { flex: 1; min-width: 0; }
.titles h1 { margin: 0; overflow-wrap: anywhere; }
.subtitle { margin-top: var(--space-1); color: var(--color-text-muted); }
.actions { display: flex; flex-wrap: wrap; gap: var(--space-2); }
</style>
