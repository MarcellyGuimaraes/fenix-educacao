<script setup>
import { RouterView, RouterLink, useRouter } from 'vue-router'
import { useSessionStore } from './stores/session'

const session = useSessionStore()
const router = useRouter()

function leave() {
  session.leave()
  router.push({ name: 'home' })
}
</script>

<template>
  <div class="app">
    <header v-if="session.isAuthenticated" class="topbar">
      <div class="brand" @click="router.push('/')">🔥 Fênix Provas</div>

      <nav v-if="session.isTeacher" class="nav">
        <RouterLink :to="{ name: 'teacher.exams' }">Provas</RouterLink>
        <RouterLink :to="{ name: 'teacher.dashboard' }">Dashboard</RouterLink>
      </nav>
      <nav v-else class="nav">
        <RouterLink :to="{ name: 'student.exams' }">Provas disponíveis</RouterLink>
      </nav>

      <div class="session">
        <span class="badge" :class="session.role">
          {{ session.isTeacher ? 'Professor' : 'Aluno' }}: {{ session.userName }}
        </span>
        <button class="link" @click="leave">Sair</button>
      </div>
    </header>

    <main class="content">
      <RouterView />
    </main>
  </div>
</template>

<style scoped>
.topbar {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding: 0.9rem 1.5rem;
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  position: sticky;
  top: 0;
  z-index: 10;
}
.brand { font-weight: 700; cursor: pointer; font-size: 1.1rem; }
.nav { display: flex; gap: 1rem; }
.nav a { color: var(--text-muted); text-decoration: none; font-weight: 500; }
.nav a.router-link-active { color: var(--primary); }
.session { margin-left: auto; display: flex; align-items: center; gap: 0.75rem; }
.badge { padding: 0.25rem 0.6rem; border-radius: 999px; font-size: 0.8rem; font-weight: 600; }
.badge.teacher { background: #dbeafe; color: #1d4ed8; }
.badge.student { background: #dcfce7; color: #15803d; }
.content { max-width: 1040px; margin: 0 auto; padding: 1.5rem; }
.link { background: none; border: none; color: var(--text-muted); cursor: pointer; text-decoration: underline; font-size: 0.85rem; }
</style>
