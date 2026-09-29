<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../services/api'

const props = defineProps({ id: { type: [String, Number], required: true } })
const router = useRouter()

const exam = ref(null)
const answers = ref({}) // question_id -> option_id
const loading = ref(true)
const submitting = ref(false)
const error = ref(null)

const allAnswered = computed(
  () => exam.value && exam.value.questions.every((q) => answers.value[q.id])
)

async function load() {
  loading.value = true
  error.value = null
  try {
    const { data } = await api.get(`/student/exams/${props.id}`)
    exam.value = data.data
  } catch {
    error.value = 'Erro ao carregar a prova.'
  } finally {
    loading.value = false
  }
}

async function submit() {
  submitting.value = true
  error.value = null
  try {
    const payload = {
      answers: exam.value.questions.map((q) => ({
        question_id: q.id,
        option_id: answers.value[q.id],
      })),
    }
    const { data } = await api.post(`/student/exams/${props.id}/attempts`, payload)
    router.push({ name: 'student.attempt.result', params: { id: data.data.id } })
  } catch (e) {
    if (e.response?.status === 409) {
      error.value = 'Você já realizou esta prova.'
    } else if (e.response?.status === 422) {
      error.value = 'Responda todas as questões antes de enviar.'
    } else {
      error.value = 'Erro ao enviar a prova.'
    }
  } finally {
    submitting.value = false
  }
}

onMounted(load)
</script>

<template>
  <div>
    <div v-if="loading" class="muted">Carregando…</div>
    <div v-else-if="error && !exam" class="alert error">{{ error }}</div>

    <template v-else-if="exam">
      <h1>{{ exam.title }}</h1>
      <p v-if="exam.description" class="muted">{{ exam.description }}</p>

      <div v-if="error" class="alert error" style="margin-top: 1rem">{{ error }}</div>

      <form class="grid" style="margin-top: 1rem" @submit.prevent="submit">
        <div v-for="(q, qi) in exam.questions" :key="q.id" class="card grid">
          <h3>{{ qi + 1 }}. {{ q.statement }}</h3>
          <label v-for="o in q.options" :key="o.id" class="option" :class="{ selected: answers[q.id] === o.id }">
            <input type="radio" :name="`q-${q.id}`" :value="o.id" v-model="answers[q.id]" />
            <span>{{ o.text }}</span>
          </label>
        </div>

        <div class="row">
          <button type="button" class="btn secondary" @click="router.push({ name: 'student.exams' })">Voltar</button>
          <div class="spacer"></div>
          <span v-if="!allAnswered" class="muted">Responda todas as questões</span>
          <button type="submit" class="btn" :disabled="!allAnswered || submitting">
            {{ submitting ? 'Enviando…' : 'Enviar respostas' }}
          </button>
        </div>
      </form>
    </template>
  </div>
</template>

<style scoped>
.option {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.6rem 0.8rem;
  border: 1px solid var(--border);
  border-radius: 8px;
  cursor: pointer;
  font-weight: 400;
  color: var(--text);
  margin-bottom: 0;
}
.option.selected { border-color: var(--primary); background: #fff7ed; }
.option input { margin: 0; }
</style>
