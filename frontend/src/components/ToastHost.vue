<script setup>
import { computed } from 'vue'
import AppIcon from './ui/AppIcon.vue'
import { useToast } from '../composables/useToast'

const { toasts, dismiss } = useToast()

const ICONS = { success: 'check-circle', error: 'x-circle', info: 'info' }

// Duas regiões vivas fixas: erros são anunciados de forma assertiva,
// o restante de forma educada.
const errors = computed(() => toasts.filter((t) => t.type === 'error'))
const others = computed(() => toasts.filter((t) => t.type !== 'error'))
</script>

<template>
  <div class="toast-host">
    <TransitionGroup tag="div" name="toast" class="region" role="alert" aria-live="assertive" aria-relevant="additions">
      <div v-for="t in errors" :key="t.id" class="toast" :class="t.type">
        <AppIcon :name="ICONS[t.type]" :size="20" />
        <p class="msg">{{ t.message }}</p>
        <button type="button" class="close" aria-label="Fechar notificação" @click="dismiss(t.id)">
          <AppIcon name="x" :size="16" />
        </button>
      </div>
    </TransitionGroup>
    <TransitionGroup tag="div" name="toast" class="region" role="status" aria-live="polite" aria-relevant="additions">
      <div v-for="t in others" :key="t.id" class="toast" :class="t.type">
        <AppIcon :name="ICONS[t.type]" :size="20" />
        <p class="msg">{{ t.message }}</p>
        <button type="button" class="close" aria-label="Fechar notificação" @click="dismiss(t.id)">
          <AppIcon name="x" :size="16" />
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.toast-host {
  position: fixed;
  z-index: 100;
  right: var(--space-4);
  bottom: var(--space-4);
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
  width: min(24rem, calc(100vw - 2 * var(--space-4)));
  pointer-events: none;
}
.region { display: flex; flex-direction: column; gap: var(--space-2); }
.toast {
  display: flex;
  align-items: flex-start;
  gap: var(--space-3);
  padding: var(--space-3) var(--space-3) var(--space-3) var(--space-4);
  border: 1px solid var(--color-border);
  border-left-width: 4px;
  border-radius: var(--radius-md);
  background: var(--color-surface);
  box-shadow: var(--shadow-lg);
  pointer-events: auto;
}
.toast.success { border-left-color: var(--color-success); }
.toast.success > .icon { color: var(--color-success); }
.toast.error { border-left-color: var(--color-danger); }
.toast.error > .icon { color: var(--color-danger); }
.toast.info { border-left-color: var(--color-info); }
.toast.info > .icon { color: var(--color-info); }
.msg { flex: 1; padding-top: 1px; font-size: var(--text-sm); font-weight: 500; overflow-wrap: anywhere; }
.close {
  display: grid;
  place-items: center;
  width: 1.75rem;
  height: 1.75rem;
  margin: -2px 0;
  border: none;
  border-radius: var(--radius-sm);
  background: transparent;
  color: var(--color-text-subtle);
}
.close:hover { background: var(--color-surface-2); color: var(--color-text); }

.toast-enter-active,
.toast-leave-active { transition: opacity 200ms var(--ease), transform 200ms var(--ease); }
.toast-enter-from { opacity: 0; transform: translateY(8px) scale(0.98); }
.toast-leave-to { opacity: 0; transform: translateX(16px); }

@media (max-width: 640px) {
  .toast-host { left: var(--space-4); right: var(--space-4); width: auto; }
}
</style>
