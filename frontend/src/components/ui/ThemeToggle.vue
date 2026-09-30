<script setup>
import AppIcon from './AppIcon.vue'
import { useTheme } from '../../composables/useTheme'

const { mode, setMode } = useTheme()

const options = [
  { value: 'light', label: 'Tema claro', icon: 'sun' },
  { value: 'dark', label: 'Tema escuro', icon: 'moon' },
  { value: 'system', label: 'Seguir o sistema', icon: 'monitor' },
]
</script>

<template>
  <div class="theme-toggle" role="group" aria-label="Tema">
    <button
      v-for="opt in options"
      :key="opt.value"
      type="button"
      class="opt"
      :class="{ active: mode === opt.value }"
      :aria-pressed="mode === opt.value ? 'true' : 'false'"
      :aria-label="opt.label"
      :title="opt.label"
      @click="setMode(opt.value)"
    >
      <AppIcon :name="opt.icon" :size="15" />
    </button>
  </div>
</template>

<style scoped>
.theme-toggle {
  display: inline-flex;
  gap: 2px;
  padding: 3px;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-full);
  background: var(--color-surface-2);
}
.opt {
  display: grid;
  place-items: center;
  width: 1.9rem;
  height: 1.9rem;
  border: none;
  border-radius: var(--radius-full);
  background: transparent;
  color: var(--color-text-subtle);
  transition: background var(--duration) var(--ease), color var(--duration) var(--ease);
}
.opt:hover { color: var(--color-text); }
.opt.active { background: var(--color-surface); color: var(--color-primary); box-shadow: var(--shadow-sm); }
</style>
