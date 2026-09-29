import { createRouter, createWebHistory } from 'vue-router'
import { useSessionStore } from '../stores/session'

const routes = [
  { path: '/', name: 'home', component: () => import('../views/HomeView.vue') },

  // Professor
  {
    path: '/professor',
    name: 'teacher.exams',
    component: () => import('../views/teacher/ExamListView.vue'),
    meta: { role: 'teacher' },
  },
  {
    path: '/professor/provas/nova',
    name: 'teacher.exams.create',
    component: () => import('../views/teacher/ExamFormView.vue'),
    meta: { role: 'teacher' },
  },
  {
    path: '/professor/provas/:id/editar',
    name: 'teacher.exams.edit',
    component: () => import('../views/teacher/ExamFormView.vue'),
    props: true,
    meta: { role: 'teacher' },
  },
  {
    path: '/professor/dashboard',
    name: 'teacher.dashboard',
    component: () => import('../views/teacher/DashboardView.vue'),
    meta: { role: 'teacher' },
  },

  // Aluno
  {
    path: '/aluno',
    name: 'student.exams',
    component: () => import('../views/student/ExamListView.vue'),
    meta: { role: 'student' },
  },
  {
    path: '/aluno/provas/:id',
    name: 'student.exam.take',
    component: () => import('../views/student/ExamTakeView.vue'),
    props: true,
    meta: { role: 'student' },
  },
  {
    path: '/aluno/tentativas/:id',
    name: 'student.attempt.result',
    component: () => import('../views/student/ResultView.vue'),
    props: true,
    meta: { role: 'student' },
  },

  { path: '/:pathMatch(.*)*', redirect: '/' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// Guarda simples de perfil: rotas de professor/aluno exigem o perfil correspondente.
router.beforeEach((to) => {
  const session = useSessionStore()
  if (to.meta.role && session.role !== to.meta.role) {
    return { name: 'home' }
  }
})

export default router
