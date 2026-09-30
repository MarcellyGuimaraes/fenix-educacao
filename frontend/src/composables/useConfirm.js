import { reactive } from 'vue'

// Diálogo de confirmação global, substitui window.confirm.
// Uso: if (!(await confirm({ title, message, confirmLabel, tone: 'danger' }))) return
const state = reactive({
  open: false,
  title: '',
  message: '',
  confirmLabel: 'Confirmar',
  cancelLabel: 'Cancelar',
  tone: 'primary', // 'primary' | 'danger'
})

let resolver = null

function settle(result) {
  state.open = false
  const resolve = resolver
  resolver = null
  resolve?.(result)
}

function confirm(options = {}) {
  // Um novo pedido cancela o anterior ainda pendente.
  if (resolver) settle(false)
  Object.assign(state, {
    title: options.title ?? 'Tem certeza?',
    message: options.message ?? '',
    confirmLabel: options.confirmLabel ?? 'Confirmar',
    cancelLabel: options.cancelLabel ?? 'Cancelar',
    tone: options.tone ?? 'primary',
    open: true,
  })
  return new Promise((resolve) => {
    resolver = resolve
  })
}

export function useConfirm() {
  return { confirm, state, settle }
}
