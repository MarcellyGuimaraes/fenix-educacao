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
    const { data } = await api.get('/exams')
    exams.value = data.data
  } catch {
    error.value = 'Erro ao carregar provas.'
  } finally {
    loading.value = false
  }
}

async function remove(exam) {
  if (!confirm(`Excluir a prova "${exam.title}"? Esta ação não pode ser desfeita.`)) return
  try {
    await api.delete(`/exams/${exam.id}`)
    await load()
  } catch {
    alert('Não foi possível excluir a prova.')
  }
}

onMounted(load)
</script>

<template>
  <div>
    <div class="row" style="margin-bottom: 1.25rem">
      <h1>Provas</h1>
      <div class="spacer"></div>
      <button class="btn" @click="router.push({ name: 'teacher.exams.create' })">+ Nova prova</button>
    </div>

    <div v-if="error" class="alert error">{{ error }}</div>
    <div v-if="loading" class="muted">Carregando…</div>

    <div v-else-if="!exams.length" class="card muted">Nenhuma prova cadastrada ainda.</div>

    <div v-else class="grid">
      <div v-for="exam in exams" :key="exam.id" class="card row">
        <div>
          <h3>{{ exam.title }}</h3>
          <p class="muted">
            {{ exam.questions_count }} questões · {{ exam.attempts_count }} tentativas
            <template v-if="exam.attempts_count > 0"> · edição bloqueada (prova já respondida)</template>
          </p>
        </div>
        <div class="spacer"></div>
        <button
          class="btn secondary"
          :disabled="exam.attempts_count > 0"
          :title="exam.attempts_count > 0 ? 'Provas já respondidas não podem ser editadas.' : null"
          @click="router.push({ name: 'teacher.exams.edit', params: { id: exam.id } })"
        >
          Editar
        </button>
        <button class="btn danger" @click="remove(exam)">Excluir</button>
      </div>
    </div>
  </div>
</template>
