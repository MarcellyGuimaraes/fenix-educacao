<script setup>
import AppIcon from './AppIcon.vue'
import AppButton from './AppButton.vue'

defineProps({
  message: { type: String, required: true },
  title: { type: String, default: 'Algo deu errado' },
})
defineEmits(['retry'])
</script>

<template>
  <div class="error-state" role="alert">
    <div class="icon-wrap"><AppIcon name="alert" :size="24" /></div>
    <p class="title">{{ title }}</p>
    <p class="msg">{{ message }}</p>
    <AppButton variant="secondary" icon="refresh" @click="$emit('retry')">Tentar novamente</AppButton>
  </div>
</template>

<style scoped>
.error-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: var(--space-2);
  padding: var(--space-10) var(--space-6);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  background: var(--color-surface);
}
.icon-wrap {
  display: grid;
  place-items: center;
  width: 3.25rem;
  height: 3.25rem;
  margin-bottom: var(--space-1);
  border-radius: var(--radius-full);
  background: var(--color-danger-soft);
  color: var(--color-danger);
}
.title { font-weight: 650; }
.msg { max-width: 30rem; margin-bottom: var(--space-3); color: var(--color-text-muted); font-size: var(--text-sm); }
</style>
