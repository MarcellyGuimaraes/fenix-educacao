<script setup>
defineProps({
  tag: { type: String, default: 'div' },
  // Remove o padding interno (útil para tabelas que encostam nas bordas).
  flush: { type: Boolean, default: false },
  interactive: { type: Boolean, default: false },
})
</script>

<template>
  <component :is="tag" class="card" :class="{ flush, interactive }">
    <header v-if="$slots.header" class="card-header">
      <slot name="header" />
    </header>
    <slot />
  </component>
</template>

<style scoped>
.card.flush { padding: 0; overflow: hidden; }
.card.interactive { transition: border-color var(--duration) var(--ease), box-shadow var(--duration) var(--ease), transform var(--duration) var(--ease); }
.card.interactive:hover { border-color: var(--color-border-strong); box-shadow: var(--shadow-md); transform: translateY(-1px); }
.card-header {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: var(--space-3);
  margin-bottom: var(--space-4);
}
.card.flush .card-header { padding: var(--space-4) var(--space-5); margin-bottom: 0; border-bottom: 1px solid var(--color-border); }
</style>
