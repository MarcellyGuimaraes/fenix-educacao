import { defineStore } from 'pinia'

const STORAGE_KEY = 'fenix_session'

function load() {
  try {
    return JSON.parse(localStorage.getItem(STORAGE_KEY)) || {}
  } catch {
    return {}
  }
}

export const useSessionStore = defineStore('session', {
  state: () => ({
    role: load().role || null, // 'teacher' | 'student'
    userId: load().userId || null,
    userName: load().userName || null,
  }),
  getters: {
    isTeacher: (s) => s.role === 'teacher',
    isStudent: (s) => s.role === 'student',
    isAuthenticated: (s) => !!s.role,
  },
  actions: {
    enter(role, userId, userName) {
      this.role = role
      this.userId = userId
      this.userName = userName
      this.persist()
    },
    leave() {
      this.role = null
      this.userId = null
      this.userName = null
      try {
        localStorage.removeItem(STORAGE_KEY)
      } catch {
        /* ignore */
      }
    },
    persist() {
      try {
        localStorage.setItem(
          STORAGE_KEY,
          JSON.stringify({ role: this.role, userId: this.userId, userName: this.userName })
        )
      } catch {
        /* ignore */
      }
    },
  },
})
