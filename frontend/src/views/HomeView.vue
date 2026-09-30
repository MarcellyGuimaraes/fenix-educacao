<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'
import { useSessionStore } from '../stores/session'

const router = useRouter()
const session = useSessionStore()

const teachers = ref([])
const students = ref([])
const selectedTeacher = ref(null)
const selectedStudent = ref(null)
const loading = ref(true)
const error = ref(null)

onMounted(async () => {
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
})

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
    <div class="hero">
      <h1>🔥 Fênix Provas Online</h1>
      <p class="muted">Escolha um perfil para acessar o sistema.</p>
    </div>

    <div v-if="error" class="alert error">{{ error }}</div>
    <div v-if="loading" class="muted">Carregando perfis…</div>

    <div v-else class="cards">
      <div class="card profile">
        <div class="icon">👩‍🏫</div>
        <h2>Professor</h2>
        <p class="muted">Crie e gerencie provas e acompanhe o desempenho da turma.</p>
        <label for="teacher">Selecione o professor</label>
        <select id="teacher" v-model="selectedTeacher">
          <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option>
        </select>
        <button class="btn" :disabled="!teachers.length" @click="enterAsTeacher">
          Entrar como Professor
        </button>
      </div>

      <div class="card profile">
        <div class="icon">🧑‍🎓</div>
        <h2>Aluno</h2>
        <p class="muted">Responda às provas disponíveis e veja sua pontuação.</p>
        <label for="student">Selecione o aluno</label>
        <select id="student" v-model="selectedStudent">
          <option v-for="s in students" :key="s.id" :value="s.id">{{ s.name }}</option>
        </select>
        <button class="btn" :disabled="!students.length" @click="enterAsStudent">
          Entrar como Aluno
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.home { max-width: 760px; margin: 3rem auto 0; }
.hero { text-align: center; margin-bottom: 2.5rem; }
.hero h1 { font-size: 2rem; }
.cards { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
.profile { display: flex; flex-direction: column; gap: 0.6rem; text-align: center; align-items: center; }
.profile .icon { font-size: 2.5rem; }
.profile select { margin-bottom: 0.3rem; }
.profile .btn { margin-top: 0.4rem; width: 100%; justify-content: center; }
@media (max-width: 640px) {
  .cards { grid-template-columns: 1fr; }
  .home { margin-top: 1.5rem; }
}
</style>
