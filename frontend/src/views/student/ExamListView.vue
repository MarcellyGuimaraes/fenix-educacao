<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../services/api'

const router = useRouter()
const exams = ref([])
const loading = ref(true)
const error = ref(null)

async function load() {
  loading.value = true
  error.value = null
  try {
    const { data } = await api.get('/student/exams')
    exams.value = data.data
  } catch {
    error.value = 'Erro ao carregar provas.'
  } finally {
    loading.value = false
  }
}

function open(exam) {
  if (exam.attempted) {
    router.push({ name: 'student.attempt.result', params: { id: exam.attempt_id } })
  } else {
    router.push({ name: 'student.exam.take', params: { id: exam.id } })
  }
}

onMounted(load)
</script>

<template>
  <div>
    <h1>Provas disponíveis</h1>
    <div v-if="error" class="alert error">{{ error }}</div>
    <div v-if="loading" class="muted">Carregando…</div>

    <div v-else-if="!exams.length" class="card muted">Nenhuma prova disponível no momento.</div>

    <div v-else class="grid">
      <div v-for="exam in exams" :key="exam.id" class="card row">
        <div>
          <h3>{{ exam.title }}</h3>
          <p class="muted">{{ exam.questions_count }} questões</p>
        </div>
        <div class="spacer"></div>
        <span v-if="exam.attempted" class="badge done">Concluída · {{ exam.percentage }}%</span>
        <button class="btn" @click="open(exam)">
          {{ exam.attempted ? 'Ver resultado' : 'Responder' }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.badge.done { background: #dcfce7; color: #15803d; padding: 0.25rem 0.6rem; border-radius: 999px; font-size: 0.8rem; font-weight: 600; }
</style>
