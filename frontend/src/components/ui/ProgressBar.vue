<script setup>
import { computed } from 'vue'

const props = defineProps({
  value: { type: Number, required: true },
  max: { type: Number, default: 100 },
  label: { type: String, required: true },
  // Texto lido por leitores de tela (ex.: "3 de 5 respondidas").
  valueText: { type: String, default: null },
  tone: { type: String, default: 'primary' },
})

const percent = computed(() =>
  props.max > 0 ? Math.min(100, Math.max(0, (props.value / props.max) * 100)) : 0
)
</script>

<template>
  <div
    class="progress"
    :class="tone"
    role="progressbar"
    :aria-label="label"
    aria-valuemin="0"
    :aria-valuemax="max"
    :aria-valuenow="value"
    :aria-valuetext="valueText"
  >
    <div class="fill" :style="{ width: `${percent}%` }"></div>
  </div>
</template>

<style scoped>
.progress {
  height: 0.5rem;
  width: 100%;
  overflow: hidden;
  border-radius: var(--radius-full);
  background: var(--color-surface-2);
}
.fill {
  height: 100%;
  border-radius: inherit;
  background: var(--color-primary);
  transition: width 300ms var(--ease);
}
.success .fill { background: var(--color-success); }
</style>
