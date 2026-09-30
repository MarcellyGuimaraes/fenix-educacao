<script setup>
import AppButton from './AppButton.vue'

defineProps({
  page: { type: Number, required: true },
  lastPage: { type: Number, required: true },
  label: { type: String, default: 'Paginação' },
})
defineEmits(['change'])
</script>

<template>
  <nav v-if="lastPage > 1" class="pagination" :aria-label="label">
    <AppButton variant="secondary" size="sm" icon="chevron-left" :disabled="page <= 1" @click="$emit('change', page - 1)">
      Anterior
    </AppButton>
    <span class="status" aria-live="polite">Página <strong>{{ page }}</strong> de {{ lastPage }}</span>
    <AppButton
      variant="secondary"
      size="sm"
      icon-right="chevron-right"
      :disabled="page >= lastPage"
      @click="$emit('change', page + 1)"
    >
      Próxima
    </AppButton>
  </nav>
</template>

<style scoped>
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-4);
  padding: var(--space-3) var(--space-4);
  border-top: 1px solid var(--color-border);
}
.status { font-size: var(--text-sm); color: var(--color-text-muted); }
.status strong { color: var(--color-text); }
</style>
