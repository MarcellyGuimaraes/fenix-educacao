<script setup>
import { computed } from 'vue'
import { RouterView, RouterLink, useRouter } from 'vue-router'
import { useSessionStore } from './stores/session'
import AppIcon from './components/ui/AppIcon.vue'
import ThemeToggle from './components/ui/ThemeToggle.vue'
import ToastHost from './components/ToastHost.vue'
import ConfirmDialog from './components/ConfirmDialog.vue'

const session = useSessionStore()
const router = useRouter()

const homeRoute = computed(() =>
  session.isTeacher ? { name: 'teacher.exams' } : session.isStudent ? { name: 'student.exams' } : { name: 'home' }
)

const navItems = computed(() =>
  session.isTeacher
    ? [
        { to: { name: 'teacher.exams' }, label: 'Provas', icon: 'file' },
        { to: { name: 'teacher.dashboard' }, label: 'Dashboard', icon: 'chart' },
      ]
    : [{ to: { name: 'student.exams' }, label: 'Provas disponíveis', icon: 'book' }]
)

const initials = computed(() =>
  (session.userName || '?')
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0].toUpperCase())
    .join('')
)

function leave() {
  session.leave()
  router.push({ name: 'home' })
}
</script>

<template>
  <a class="skip-link" href="#conteudo">Pular para o conteúdo</a>

  <div class="app">
    <header v-if="session.isAuthenticated" class="topbar">
      <div class="topbar-inner">
        <RouterLink :to="homeRoute" class="brand" aria-label="Fênix Provas — início">
          <span class="brand-mark"><AppIcon name="flame" :size="18" :stroke-width="2.25" /></span>
          <span class="brand-name">Fênix <span>Provas</span></span>
        </RouterLink>

        <nav class="nav" aria-label="Principal">
          <RouterLink v-for="item in navItems" :key="item.label" :to="item.to" class="nav-link">
            <AppIcon :name="item.icon" :size="16" />
            {{ item.label }}
          </RouterLink>
        </nav>

        <div class="session">
          <ThemeToggle />
          <div class="user">
            <span class="avatar" :class="session.role" aria-hidden="true">{{ initials }}</span>
            <span class="user-text">
              <span class="user-name">{{ session.userName }}</span>
              <span class="user-role">{{ session.isTeacher ? 'Professor' : 'Aluno' }}</span>
            </span>
          </div>
          <button type="button" class="logout" @click="leave">
            <AppIcon name="log-out" :size="16" />
            <span class="logout-text">Sair</span>
          </button>
        </div>
      </div>
    </header>

    <main id="conteudo" class="content" :class="{ bare: !session.isAuthenticated }" tabindex="-1">
      <RouterView />
    </main>
  </div>

  <ToastHost />
  <ConfirmDialog />
</template>

<style scoped>
.skip-link {
  position: absolute;
  left: var(--space-4);
  top: -3rem;
  z-index: 200;
  padding: var(--space-2) var(--space-4);
  border-radius: var(--radius-md);
  background: var(--color-primary);
  color: var(--color-on-primary);
  font-weight: 600;
  text-decoration: none;
}
.skip-link:focus { top: var(--space-2); }

.topbar {
  position: sticky;
  top: 0;
  z-index: 20;
  border-bottom: 1px solid var(--color-border);
  background: color-mix(in srgb, var(--color-surface) 86%, transparent);
  backdrop-filter: saturate(1.6) blur(10px);
}
.topbar-inner {
  display: flex;
  align-items: center;
  gap: var(--space-6);
  max-width: var(--content-width);
  height: 4rem;
  margin: 0 auto;
  padding: 0 var(--space-6);
}

.brand {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
  color: var(--color-text);
  font-weight: 750;
  font-size: var(--text-lg);
  letter-spacing: -0.02em;
  text-decoration: none;
  border-radius: var(--radius-md);
}
.brand-mark {
  display: grid;
  place-items: center;
  width: 2rem;
  height: 2rem;
  border-radius: var(--radius-md);
  background: var(--color-primary);
  color: var(--color-on-primary);
}
.brand-name span { color: var(--color-primary); }

.nav { display: flex; gap: var(--space-1); }
.nav-link {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
  padding: var(--space-2) var(--space-3);
  border-radius: var(--radius-md);
  color: var(--color-text-muted);
  font-size: var(--text-sm);
  font-weight: 600;
  text-decoration: none;
  white-space: nowrap;
  transition: background var(--duration) var(--ease), color var(--duration) var(--ease);
}
.nav-link:hover { background: var(--color-surface-2); color: var(--color-text); }
.nav-link.router-link-active { background: var(--color-primary-soft); color: var(--color-primary-soft-text); }

.session { display: flex; align-items: center; gap: var(--space-3); margin-left: auto; }
.user { display: flex; align-items: center; gap: var(--space-2); }
.avatar {
  display: grid;
  place-items: center;
  width: 2.25rem;
  height: 2.25rem;
  border-radius: var(--radius-full);
  font-size: var(--text-xs);
  font-weight: 700;
}
.avatar.teacher { background: var(--color-info-soft); color: var(--color-info-soft-text); }
.avatar.student { background: var(--color-success-soft); color: var(--color-success-soft-text); }
.user-text { display: flex; flex-direction: column; line-height: 1.2; }
.user-name { font-size: var(--text-sm); font-weight: 600; max-width: 12rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.user-role { font-size: var(--text-xs); color: var(--color-text-subtle); }

.logout {
  display: inline-flex;
  align-items: center;
  gap: var(--space-1);
  padding: var(--space-2) var(--space-3);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-surface);
  color: var(--color-text-muted);
  font-size: var(--text-sm);
  font-weight: 600;
}
.logout:hover { color: var(--color-danger); border-color: var(--color-danger); }

.content {
  max-width: var(--content-width);
  margin: 0 auto;
  padding: var(--space-8) var(--space-6) var(--space-12);
  outline: none;
}
.content.bare { max-width: none; padding: 0; }

@media (max-width: 860px) {
  .user-text { display: none; }
}

@media (max-width: 640px) {
  .topbar-inner {
    flex-wrap: wrap;
    height: auto;
    row-gap: var(--space-2);
    padding: var(--space-3) var(--space-4) var(--space-2);
  }
  .brand-name { font-size: var(--text-md); }
  .nav {
    order: 3;
    width: 100%;
    overflow-x: auto;
    scrollbar-width: none;
  }
  .session { gap: var(--space-2); }
  .logout { padding: var(--space-2); }
  /* Texto só visualmente oculto: o botão mantém o nome acessível "Sair". */
  .logout-text {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
  }
  .content { padding: var(--space-6) var(--space-4) var(--space-12); }
}
</style>
