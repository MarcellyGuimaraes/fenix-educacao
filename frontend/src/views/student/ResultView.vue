<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../services/api'

const props = defineProps({ id: { type: [String, Number], required: true } })
const router = useRouter()

const result = ref(null)
const loading = ref(true)
const error = ref(null)

async function load() {
  loading.value = true
  error.value = null
  try {
    const { data } = await api.get(`/student/attempts/${props.id}`)
    result.value = data.data
  } catch {
    error.value = 'Erro ao carregar o resultado.'
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <div>
    <div v-if="loading" class="muted">Carregando…</div>
    <div v-else-if="error" class="alert error">{{ error }}</div>

    <template v-else-if="result">
      <h1>Resultado — {{ result.exam_title }}</h1>

      <div class="score card">
        <div class="big">{{ result.percentage }}%</div>
        <div class="muted">{{ result.score }} de {{ result.total_questions }} acertos</div>
      </div>

      <h2 style="margin-top: 1.5rem">Detalhamento</h2>
      <div class="grid">
        <div v-for="(a, i) in result.answers" :key="a.question_id" class="card grid answer" :class="a.is_correct ? 'ok' : 'wrong'">
          <h3>{{ i + 1 }}. {{ a.statement }}</h3>
          <p>
            Sua resposta: <strong>{{ a.chosen_option_text }}</strong>
            <span v-if="a.is_correct" class="tag ok">✓ correta</span>
            <span v-else class="tag wrong">✕ incorreta</span>
          </p>
          <p v-if="!a.is_correct" class="muted">
            Resposta correta: <strong>{{ a.correct_option_text }}</strong>
          </p>
        </div>
      </div>

      <div class="row" style="margin-top: 1.5rem">
        <button class="btn secondary" @click="router.push({ name: 'student.exams' })">Voltar às provas</button>
      </div>
    </template>
  </div>
</template>

<style scoped>
.score { text-align: center; padding: 2rem; }
.score .big { font-size: 3rem; font-weight: 700; color: var(--primary); }
.answer.ok { border-left: 4px solid var(--success); }
.answer.wrong { border-left: 4px solid var(--danger); }
.tag { font-size: 0.75rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 999px; margin-left: 0.5rem; }
.tag.ok { background: #dcfce7; color: #15803d; }
.tag.wrong { background: #fee2e2; color: #991b1b; }
</style>
