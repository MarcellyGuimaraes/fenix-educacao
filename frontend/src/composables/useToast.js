import { reactive } from 'vue'

// Notificações temporárias. Estado singleton fora dos componentes: um toast
// disparado antes de um router.push continua visível na tela seguinte.
const SUCCESS_TIMEOUT = 4000
const INFO_TIMEOUT = 5000
const MAX_TOASTS = 4

const state = reactive({ items: [] })
let nextId = 1

function dismiss(id) {
  const index = state.items.findIndex((t) => t.id === id)
  if (index === -1) return
  clearTimeout(state.items[index].timer)
  state.items.splice(index, 1)
}

function push(type, message, timeout) {
  const toast = { id: nextId++, type, message, timer: null }
  if (timeout) toast.timer = setTimeout(() => dismiss(toast.id), timeout)
  state.items.push(toast)
  // Descarta os mais antigos para não empilhar indefinidamente.
  while (state.items.length > MAX_TOASTS) dismiss(state.items[0].id)
  return toast.id
}

export function useToast() {
  return {
    toasts: state.items,
    success: (message) => push('success', message, SUCCESS_TIMEOUT),
    info: (message) => push('info', message, INFO_TIMEOUT),
    // Erros permanecem até o usuário fechar.
    error: (message) => push('error', message, 0),
    dismiss,
  }
}
