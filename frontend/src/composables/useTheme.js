import { ref, computed } from 'vue'

// Mesma chave lida pelo script inline do index.html.
const STORAGE_KEY = 'fenix_theme'
const MODES = ['light', 'dark', 'system']

function readSaved() {
  try {
    const saved = localStorage.getItem(STORAGE_KEY)
    return MODES.includes(saved) ? saved : 'system'
  } catch {
    return 'system'
  }
}

const media = typeof window !== 'undefined' && window.matchMedia
  ? window.matchMedia('(prefers-color-scheme: dark)')
  : null

// Estado compartilhado por todos os consumidores (singleton do módulo).
const mode = ref(readSaved())
const systemDark = ref(media?.matches ?? false)

// No modo "Sistema" o CSS já acompanha o SO sozinho; aqui só mantemos o
// valor resolvido atualizado para quem precisar dele (ex.: ícone do toggle).
media?.addEventListener?.('change', (e) => {
  systemDark.value = e.matches
})

function apply(value) {
  const root = document.documentElement
  if (value === 'system') root.removeAttribute('data-theme')
  else root.setAttribute('data-theme', value)
}

export function useTheme() {
  const resolved = computed(() =>
    mode.value === 'system' ? (systemDark.value ? 'dark' : 'light') : mode.value
  )

  function setMode(value) {
    if (!MODES.includes(value)) return
    mode.value = value
    apply(value)
    try {
      if (value === 'system') localStorage.removeItem(STORAGE_KEY)
      else localStorage.setItem(STORAGE_KEY, value)
    } catch {
      /* ignore */
    }
  }

  return { mode, resolved, setMode }
}
