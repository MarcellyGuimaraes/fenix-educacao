<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'
import { useSessionStore } from '../stores/session'
import AppIcon from '../components/ui/AppIcon.vue'
import AppButton from '../components/ui/AppButton.vue'
import ThemeToggle from '../components/ui/ThemeToggle.vue'
import SkeletonBlock from '../components/ui/SkeletonBlock.vue'
import ErrorState from '../components/ui/ErrorState.vue'

const router = useRouter()
const session = useSessionStore()

const teachers = ref([])
const students = ref([])
const selectedTeacher = ref(null)
const selectedStudent = ref(null)
const loading = ref(true)
const error = ref(null)

async function load() {
  loading.value = true
  error.value = null
  try {
    const [t, s] = await Promise.all([api.get('/teachers'), api.get('/students')])
    teachers.value = t.data.data
    students.value = s.data.data
    selectedTeacher.value = teachers.value[0]?.id ?? null
    selectedStudent.value = students.value[0]?.id ?? null
  } catch {
    error.value = 'Não foi possível carregar os perfis. A API está no ar (http://localhost:8080)?'
  } finally {
    loading.value = false
  }
}

onMounted(load)

function enterAsTeacher() {
  const teacher = teachers.value.find((t) => t.id === selectedTeacher.value)
  if (!teacher) return
  session.enter('teacher', teacher.id, teacher.name)
  router.push({ name: 'teacher.exams' })
}

function enterAsStudent() {
  const student = students.value.find((s) => s.id === selectedStudent.value)
  if (!student) return
  session.enter('student', student.id, student.name)
  router.push({ name: 'student.exams' })
}
</script>

<template>
  <div class="home">
    <div class="glow" aria-hidden="true"></div>

    <div class="top">
      <ThemeToggle />
    </div>

    <div class="inner">
      <div class="hero fade-in">
        <span class="logo" aria-hidden="true"><AppIcon name="flame" :size="30" :stroke-width="2" /></span>
        <h1>Fênix <span>Provas</span></h1>
        <p class="lead">Crie provas, responda online e acompanhe o desempenho da turma em um só lugar.</p>
      </div>

      <div v-if="loading" class="cards" aria-busy="true" aria-label="Carregando perfis">
        <div v-for="n in 2" :key="n" class="card profile">
          <SkeletonBlock width="3.5rem" height="3.5rem" radius="var(--radius-lg)" />
          <SkeletonBlock width="45%" height="1.4rem" />
          <SkeletonBlock width="90%" />
          <SkeletonBlock height="2.5rem" radius="var(--radius-md)" />
          <SkeletonBlock height="3rem" radius="var(--radius-md)" />
        </div>
      </div>

      <ErrorState v-else-if="error" class="fade-in" title="Sem conexão com a API" :message="error" @retry="load" />

      <div v-else class="cards">
        <section class="card profile teacher fade-in" aria-labelledby="teacher-title">
          <span class="profile-icon"><AppIcon name="presentation" :size="26" /></span>
          <div>
            <h2 id="teacher-title">Sou professor</h2>
            <p class="muted">Crie e gerencie provas e acompanhe o desempenho da turma no dashboard.</p>
          </div>
          <ul class="perks">
            <li><AppIcon name="check" :size="15" /> Provas de múltipla escolha</li>
            <li><AppIcon name="check" :size="15" /> Médias, ranking e comparação por aluno</li>
          </ul>
          <div class="field">
            <label for="teacher" class="field-label">Professor</label>
            <select id="teacher" v-model="selectedTeacher" class="select">
              <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option>
            </select>
          </div>
          <AppButton size="lg" block icon-right="arrow-right" :disabled="!teachers.length" @click="enterAsTeacher">
            Entrar como professor
          </AppButton>
        </section>

        <section class="card profile student fade-in" aria-labelledby="student-title">
          <span class="profile-icon"><AppIcon name="graduation-cap" :size="26" /></span>
          <div>
            <h2 id="student-title">Sou aluno</h2>
            <p class="muted">Responda às provas disponíveis e veja sua pontuação na hora.</p>
          </div>
          <ul class="perks">
            <li><AppIcon name="check" :size="15" /> Correção automática</li>
            <li><AppIcon name="check" :size="15" /> Resultado detalhado por questão</li>
          </ul>
          <div class="field">
            <label for="student" class="field-label">Aluno</label>
            <select id="student" v-model="selectedStudent" class="select">
              <option v-for="s in students" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </div>
          <AppButton size="lg" block icon-right="arrow-right" :disabled="!students.length" @click="enterAsStudent">
            Entrar como aluno
          </AppButton>
        </section>
      </div>

      <p class="footnote subtle small">
        <AppIcon name="info" :size="14" /> Ambiente de demonstração: não há senha, basta escolher um perfil.
      </p>
    </div>
  </div>
</template>

<style scoped>
.home {
  position: relative;
  min-height: 100vh;
  overflow: hidden;
  background: radial-gradient(ellipse 80% 60% at 50% -10%, var(--color-bg-accent), transparent 70%), var(--color-bg);
}
.glow {
  position: absolute;
  top: -12rem;
  left: 50%;
  width: 36rem;
  height: 24rem;
  transform: translateX(-50%);
  border-radius: 50%;
  background: var(--color-primary);
  opacity: 0.08;
  filter: blur(80px);
  pointer-events: none;
}
.top { position: relative; display: flex; justify-content: flex-end; padding: var(--space-4) var(--space-6); }
.inner { position: relative; max-width: 820px; margin: 0 auto; padding: var(--space-6) var(--space-6) var(--space-12); }

.hero { text-align: center; margin-bottom: var(--space-10); }
.logo {
  display: inline-grid;
  place-items: center;
  width: 4rem;
  height: 4rem;
  margin-bottom: var(--space-4);
  border-radius: var(--radius-xl);
  background: var(--color-primary);
  color: var(--color-on-primary);
  box-shadow: 0 12px 30px -10px var(--color-primary);
}
.hero h1 { font-size: var(--text-3xl); font-weight: 800; letter-spacing: -0.03em; }
.hero h1 span { color: var(--color-primary); }
.lead { max-width: 32rem; margin: var(--space-2) auto 0; font-size: var(--text-lg); color: var(--color-text-muted); }

.cards { display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); }
.profile {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
  padding: var(--space-6);
  border-radius: var(--radius-xl);
  box-shadow: var(--shadow-md);
}
.profile h2 { font-size: var(--text-xl); margin-bottom: var(--space-1); }
.profile .muted { font-size: var(--text-sm); }
.profile-icon {
  display: grid;
  place-items: center;
  width: 3.5rem;
  height: 3.5rem;
  border-radius: var(--radius-lg);
}
.teacher .profile-icon { background: var(--color-info-soft); color: var(--color-info); }
.student .profile-icon { background: var(--color-success-soft); color: var(--color-success); }
.perks { display: grid; gap: var(--space-2); margin: 0; padding: 0; list-style: none; font-size: var(--text-sm); color: var(--color-text-muted); }
.perks li { display: flex; align-items: center; gap: var(--space-2); }
.perks .icon { color: var(--color-success); }
.field { margin-top: auto; }

.footnote { display: flex; align-items: center; justify-content: center; gap: var(--space-1); margin-top: var(--space-8); text-align: center; }

@media (max-width: 680px) {
  .cards { grid-template-columns: 1fr; }
  .top { padding: var(--space-3) var(--space-4); }
  .inner { padding: 0 var(--space-4) var(--space-10); }
  .hero { margin-bottom: var(--space-8); }
  .hero h1 { font-size: var(--text-2xl); }
  .lead { font-size: var(--text-md); }
}
</style>
