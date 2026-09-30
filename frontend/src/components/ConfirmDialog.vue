<script setup>
import { ref, watch, nextTick } from 'vue'
import AppButton from './ui/AppButton.vue'
import AppIcon from './ui/AppIcon.vue'
import { useConfirm } from '../composables/useConfirm'

const { state, settle } = useConfirm()

const dialog = ref(null)
const cancelButton = ref(null)
// Elemento que abriu o diálogo, para devolver o foco ao fechar.
let opener = null

watch(
  () => state.open,
  async (open) => {
    const el = dialog.value
    if (!el) return
    if (open) {
      opener = document.activeElement
      if (!el.open) el.showModal() // foco preso e Esc tratados pelo navegador
      await nextTick()
      // Foco inicial na opção segura.
      cancelButton.value?.$el?.focus()
    } else {
      if (el.open) el.close()
      const target = opener
      opener = null
      if (target && target.isConnected && typeof target.focus === 'function') target.focus()
    }
  }
)

// Esc dispara "cancel": trata como "Cancelar".
function onCancel(event) {
  event.preventDefault()
  settle(false)
}

// Clique no backdrop (fora da caixa do diálogo) também cancela.
function onClick(event) {
  if (event.target === dialog.value) settle(false)
}
</script>

<template>
  <dialog
    ref="dialog"
    class="confirm"
    aria-labelledby="confirm-title"
    aria-describedby="confirm-message"
    @cancel="onCancel"
    @click="onClick"
  >
    <div class="box">
      <div class="icon-wrap" :class="state.tone">
        <AppIcon :name="state.tone === 'danger' ? 'alert' : 'info'" :size="22" />
      </div>
      <div class="body">
        <h2 id="confirm-title">{{ state.title }}</h2>
        <p id="confirm-message" class="message">{{ state.message }}</p>
      </div>
      <div class="actions">
        <AppButton ref="cancelButton" variant="secondary" @click="settle(false)">{{ state.cancelLabel }}</AppButton>
        <AppButton :variant="state.tone === 'danger' ? 'danger' : 'primary'" @click="settle(true)">
          {{ state.confirmLabel }}
        </AppButton>
      </div>
    </div>
  </dialog>
</template>

<style scoped>
.confirm {
  width: min(28rem, calc(100vw - 2rem));
  padding: 0;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-xl);
  background: var(--color-surface);
  color: var(--color-text);
  box-shadow: var(--shadow-lg);
}
.confirm::backdrop { background: var(--color-overlay); backdrop-filter: blur(2px); }
.confirm[open] { animation: pop 180ms var(--ease); }
@keyframes pop {
  from { opacity: 0; transform: translateY(8px) scale(0.98); }
  to { opacity: 1; transform: none; }
}
.box { display: grid; grid-template-columns: auto 1fr; gap: var(--space-4); padding: var(--space-6); }
.icon-wrap {
  display: grid;
  place-items: center;
  width: 2.75rem;
  height: 2.75rem;
  border-radius: var(--radius-full);
  background: var(--color-primary-soft);
  color: var(--color-primary);
}
.icon-wrap.danger { background: var(--color-danger-soft); color: var(--color-danger); }
.body h2 { margin: 0.35rem 0 var(--space-2); font-size: var(--text-lg); }
.message { color: var(--color-text-muted); font-size: var(--text-sm); overflow-wrap: anywhere; }
.actions {
  grid-column: 1 / -1;
  display: flex;
  justify-content: flex-end;
  gap: var(--space-2);
  margin-top: var(--space-2);
}
@media (max-width: 480px) {
  .box { grid-template-columns: 1fr; }
  .actions { flex-direction: column-reverse; }
  .actions :deep(.btn) { width: 100%; }
}
</style>
