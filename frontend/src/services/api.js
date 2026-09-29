import axios from 'axios'
import { useSessionStore } from '../stores/session'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8080/api',
  headers: { Accept: 'application/json' },
})

// Anexa o perfil "logado" (professor/aluno) em toda requisição.
// O backend não tem autenticação real (conforme o enunciado): confia nesses
// headers apenas para saber qual dos dois perfis está agindo.
api.interceptors.request.use((config) => {
  const session = useSessionStore()
  if (session.role) config.headers['X-User-Role'] = session.role
  if (session.userId) config.headers['X-User-Id'] = session.userId
  return config
})

export default api
