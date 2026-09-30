<script setup>
import { ref, onMounted } from 'vue'
import api from '../../services/api'

const summary = ref(null)
const examMetrics = ref([])
const students = ref([])
const studentsPagination = ref({ current_page: 1, last_page: 1 })
const ranking = ref([])
const pagination = ref({ current_page: 1, last_page: 1 })
// Filtro do ranking: '' = todas as provas; senão, o id da prova.
const rankingExamId = ref('')
const loading = ref(true)
const error = ref(null)

async function loadSummary() {
  const { data } = await api.get('/dashboard/summary')
  summary.value = data.data
}

// Também alimenta o seletor de prova do ranking.
async function loadExamMetrics() {
  const { data } = await api.get('/dashboard/exams')
  examMetrics.value = data.data
}

async function loadStudents(page = 1) {
  const { data } = await api.get('/dashboard/students', { params: { page, per_page: 10 } })
  students.value = data.data
  studentsPagination.value = { current_page: data.meta.current_page, last_page: data.meta.last_page }
}

async function loadRanking(page = 1) {
  const params = { page, per_page: 10 }
  if (rankingExamId.value) params.exam_id = rankingExamId.value
  const { data } = await api.get('/dashboard/ranking', { params })
  ranking.value = data.data
  pagination.value = { current_page: data.meta.current_page, last_page: data.meta.last_page }
}

async function changeRankingFilter() {
  error.value = null
  try {
    await loadRanking(1)
  } catch {
    error.value = 'Erro ao filtrar o ranking.'
  }
}

async function load() {
  loading.value = true
  error.value = null
  try {
    await Promise.all([loadSummary(), loadExamMetrics(), loadStudents(1), loadRanking(1)])
  } catch {
    error.value = 'Erro ao carregar o dashboard.'
  } finally {
    loading.value = false
  }
}

function formatPercentage(value) {
  return value === null || value === undefined ? '—' : `${value}%`
}

function formatDifference(value) {
  return `${value > 0 ? '+' : ''}${value} p.p.`
}

onMounted(load)
</script>

<template>
  <div>
    <h1>Dashboard</h1>
    <div v-if="error" class="alert error">{{ error }}</div>
    <div v-if="loading" class="muted">Carregando…</div>

    <template v-else-if="summary">
      <div class="metrics">
        <div class="card metric">
          <span class="label" title="Média das médias de cada prova: cada prova tem o mesmo peso">Média das provas</span>
          <span class="value">{{ summary.exams_average_percentage }}%</span>
          <span class="muted small" title="Cada tentativa tem o mesmo peso">
            Por tentativa: {{ summary.average_percentage }}%
          </span>
        </div>
        <div class="card metric">
          <span class="label">🏆 Melhor pontuação (Top 1)</span>
          <span class="value">{{ summary.best ? summary.best.percentage + '%' : '—' }}</span>
          <span class="muted small">{{ summary.best?.student_name || 'sem dados' }}</span>
        </div>
        <div class="card metric">
          <span class="label">Pior pontuação</span>
          <span class="value">{{ summary.worst ? summary.worst.percentage + '%' : '—' }}</span>
          <span class="muted small">{{ summary.worst?.student_name || 'sem dados' }}</span>
        </div>
        <div class="card metric">
          <span class="label">Tentativas totais</span>
          <span class="value">{{ summary.total_attempts }}</span>
        </div>
      </div>

      <h2 class="section-title">Média por prova</h2>
      <div class="card table-wrap">
        <table>
          <thead>
            <tr>
              <th>Prova</th>
              <th>Tentativas</th>
              <th>Média</th>
              <th>Melhor</th>
              <th>Pior</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="exam in examMetrics" :key="exam.exam_id">
              <td>{{ exam.exam_title }}</td>
              <td>{{ exam.attempts_count }}</td>
              <td><strong>{{ formatPercentage(exam.average_percentage) }}</strong></td>
              <td>{{ formatPercentage(exam.best_percentage) }}</td>
              <td>{{ formatPercentage(exam.worst_percentage) }}</td>
            </tr>
            <tr v-if="!examMetrics.length">
              <td colspan="5" class="muted">Nenhuma prova cadastrada.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <h2 class="section-title">Aluno × média</h2>
      <p class="muted small hint">
        Cada tentativa é comparada com a média da própria prova, para que a dificuldade de cada prova não distorça a comparação.
      </p>
      <div class="card table-wrap">
        <table>
          <thead>
            <tr>
              <th>Aluno</th>
              <th>Tentativas</th>
              <th>Média do aluno</th>
              <th>Desvio em relação à média de cada prova</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="student in students" :key="student.student_id">
              <td>{{ student.student_name }}</td>
              <td>{{ student.attempts_count }}</td>
              <td><strong>{{ student.average_percentage }}%</strong></td>
              <td
                :class="{
                  positive: student.difference_from_exam_average > 0,
                  negative: student.difference_from_exam_average < 0,
                }"
              >
                {{ formatDifference(student.difference_from_exam_average) }}
              </td>
            </tr>
            <tr v-if="!students.length">
              <td colspan="4" class="muted">Nenhum aluno respondeu suas provas ainda.</td>
            </tr>
          </tbody>
        </table>

        <div v-if="studentsPagination.last_page > 1" class="row pager">
          <button class="btn secondary" :disabled="studentsPagination.current_page <= 1" @click="loadStudents(studentsPagination.current_page - 1)">
            ‹ Anterior
          </button>
          <span class="muted">Página {{ studentsPagination.current_page }} de {{ studentsPagination.last_page }}</span>
          <button class="btn secondary" :disabled="studentsPagination.current_page >= studentsPagination.last_page" @click="loadStudents(studentsPagination.current_page + 1)">
            Próxima ›
          </button>
        </div>
      </div>

      <div class="row section-title">
        <h2>Ranking</h2>
        <div class="spacer"></div>
        <label for="ranking-exam" class="filter-label">Prova</label>
        <select id="ranking-exam" v-model="rankingExamId" class="filter" @change="changeRankingFilter">
          <option value="">Todas as provas</option>
          <option v-for="exam in examMetrics" :key="exam.exam_id" :value="exam.exam_id">{{ exam.exam_title }}</option>
        </select>
      </div>
      <div class="card table-wrap">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Aluno</th>
              <th>Prova</th>
              <th>Acertos</th>
              <th>Percentual</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in ranking" :key="row.attempt_id">
              <td>{{ row.position }}</td>
              <td>{{ row.student_name }}</td>
              <td>{{ row.exam_title }}</td>
              <td>{{ row.score }}/{{ row.total_questions }}</td>
              <td><strong>{{ row.percentage }}%</strong></td>
            </tr>
            <tr v-if="!ranking.length">
              <td colspan="5" class="muted">Nenhuma tentativa registrada.</td>
            </tr>
          </tbody>
        </table>

        <div v-if="pagination.last_page > 1" class="row pager">
          <button class="btn secondary" :disabled="pagination.current_page <= 1" @click="loadRanking(pagination.current_page - 1)">
            ‹ Anterior
          </button>
          <span class="muted">Página {{ pagination.current_page }} de {{ pagination.last_page }}</span>
          <button class="btn secondary" :disabled="pagination.current_page >= pagination.last_page" @click="loadRanking(pagination.current_page + 1)">
            Próxima ›
          </button>
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
.metrics { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; }
.metric { display: flex; flex-direction: column; gap: 0.3rem; }
.metric .label { font-size: 0.8rem; color: var(--text-muted); font-weight: 600; }
.metric .value { font-size: 1.8rem; font-weight: 700; color: var(--primary); }
.metric .small { font-size: 0.8rem; }
.section-title { margin-top: 2rem; margin-bottom: 0.5rem; }
.section-title h2 { margin: 0; }
.hint { margin: -0.25rem 0 0.5rem; font-size: 0.85rem; }
.filter-label { margin: 0; }
.filter { width: auto; min-width: 14rem; }
.table-wrap { overflow-x: auto; }
.positive { color: var(--success); font-weight: 600; }
.negative { color: var(--danger); font-weight: 600; }
.pager { justify-content: center; margin-top: 1rem; gap: 1rem; }
@media (max-width: 720px) {
  .metrics { grid-template-columns: 1fr 1fr; }
  .filter { min-width: 0; flex: 1; }
}
</style>
