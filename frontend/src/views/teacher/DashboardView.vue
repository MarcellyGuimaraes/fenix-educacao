<script setup>
import { ref, onMounted } from 'vue'
import api from '../../services/api'

const summary = ref(null)
const ranking = ref([])
const pagination = ref({ current_page: 1, last_page: 1 })
const loading = ref(true)
const error = ref(null)

async function loadSummary() {
  const { data } = await api.get('/dashboard/summary')
  summary.value = data.data
}

async function loadRanking(page = 1) {
  const { data } = await api.get('/dashboard/ranking', { params: { page, per_page: 10 } })
  ranking.value = data.data
  pagination.value = { current_page: data.meta.current_page, last_page: data.meta.last_page }
}

async function load() {
  loading.value = true
  error.value = null
  try {
    await Promise.all([loadSummary(), loadRanking(1)])
  } catch {
    error.value = 'Erro ao carregar o dashboard.'
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <div>
    <h1>Dashboard</h1>
    <div v-if="error" class="alert error">{{ error }}</div>
    <div v-if="loading" class="muted">Carregando…</div>

    <template v-else>
      <div class="metrics">
        <div class="card metric">
          <span class="label">Média geral</span>
          <span class="value">{{ summary.average_percentage }}%</span>
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

      <h2 style="margin-top: 2rem">Ranking</h2>
      <div class="card">
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
.pager { justify-content: center; margin-top: 1rem; gap: 1rem; }
@media (max-width: 720px) { .metrics { grid-template-columns: 1fr 1fr; } }
</style>
