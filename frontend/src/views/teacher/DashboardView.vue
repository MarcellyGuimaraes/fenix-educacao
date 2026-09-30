<script setup>
import { ref, onMounted } from 'vue'
import api from '../../services/api'
import { useToast } from '../../composables/useToast'
import PageHeader from '../../components/ui/PageHeader.vue'
import AppIcon from '../../components/ui/AppIcon.vue'
import AppBadge from '../../components/ui/AppBadge.vue'
import AppPagination from '../../components/ui/AppPagination.vue'
import SkeletonBlock from '../../components/ui/SkeletonBlock.vue'
import EmptyState from '../../components/ui/EmptyState.vue'
import ErrorState from '../../components/ui/ErrorState.vue'

const toast = useToast()

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
  try {
    await loadRanking(1)
  } catch {
    toast.error('Erro ao filtrar o ranking.')
  }
}

async function changeStudentsPage(page) {
  try {
    await loadStudents(page)
  } catch {
    toast.error('Erro ao carregar a página de alunos.')
  }
}

async function changeRankingPage(page) {
  try {
    await loadRanking(page)
  } catch {
    toast.error('Erro ao carregar a página do ranking.')
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

function differenceTone(value) {
  return value > 0 ? 'positive' : value < 0 ? 'negative' : 'neutral'
}

const MEDALS = { 1: 'gold', 2: 'silver', 3: 'bronze' }

onMounted(load)
</script>

<template>
  <div>
    <PageHeader title="Dashboard" subtitle="Desempenho dos alunos nas suas provas." />

    <template v-if="loading">
      <div class="metrics" aria-busy="true" aria-label="Carregando dashboard">
        <div v-for="n in 4" :key="n" class="card metric">
          <SkeletonBlock width="2.5rem" height="2.5rem" radius="var(--radius-md)" />
          <SkeletonBlock width="60%" />
          <SkeletonBlock width="45%" height="2rem" />
        </div>
      </div>
      <div v-for="n in 2" :key="n" class="card panel-skeleton">
        <SkeletonBlock width="30%" height="1.25rem" />
        <SkeletonBlock v-for="r in 4" :key="r" height="2.25rem" />
      </div>
    </template>

    <ErrorState v-else-if="error" :message="error" @retry="load" />

    <template v-else-if="summary">
      <section class="metrics" aria-label="Resumo">
        <div class="card metric fade-in">
          <span class="metric-icon primary"><AppIcon name="target" :size="20" /></span>
          <span class="label" title="Média das médias de cada prova: cada prova tem o mesmo peso">Média das provas</span>
          <span class="value">{{ summary.exams_average_percentage }}<small>%</small></span>
          <span class="foot" title="Cada tentativa tem o mesmo peso">
            Por tentativa: <strong>{{ summary.average_percentage }}%</strong>
          </span>
        </div>
        <div class="card metric fade-in">
          <span class="metric-icon success"><AppIcon name="trophy" :size="20" /></span>
          <span class="label">Melhor pontuação (Top 1)</span>
          <span class="value">
            <template v-if="summary.best">{{ summary.best.percentage }}<small>%</small></template>
            <template v-else>—</template>
          </span>
          <span class="foot">{{ summary.best?.student_name || 'sem dados' }}</span>
        </div>
        <div class="card metric fade-in">
          <span class="metric-icon danger"><AppIcon name="trending-down" :size="20" /></span>
          <span class="label">Pior pontuação</span>
          <span class="value">
            <template v-if="summary.worst">{{ summary.worst.percentage }}<small>%</small></template>
            <template v-else>—</template>
          </span>
          <span class="foot">{{ summary.worst?.student_name || 'sem dados' }}</span>
        </div>
        <div class="card metric fade-in">
          <span class="metric-icon info"><AppIcon name="activity" :size="20" /></span>
          <span class="label">Tentativas totais</span>
          <span class="value">{{ summary.total_attempts }}</span>
          <span class="foot">em todas as suas provas</span>
        </div>
      </section>

      <section class="card panel fade-in" aria-labelledby="exams-title">
        <header class="panel-head">
          <div>
            <h2 id="exams-title">Média por prova</h2>
            <p class="hint">Tentativas, média, melhor e pior resultado de cada prova.</p>
          </div>
        </header>
        <div class="table-wrap">
          <table v-if="examMetrics.length">
            <thead>
              <tr>
                <th>Prova</th>
                <th class="num">Tentativas</th>
                <th class="num">Média</th>
                <th class="num">Melhor</th>
                <th class="num">Pior</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="exam in examMetrics" :key="exam.exam_id">
                <td class="strong wrap">{{ exam.exam_title }}</td>
                <td class="num">{{ exam.attempts_count }}</td>
                <td class="num"><strong>{{ formatPercentage(exam.average_percentage) }}</strong></td>
                <td class="num">{{ formatPercentage(exam.best_percentage) }}</td>
                <td class="num">{{ formatPercentage(exam.worst_percentage) }}</td>
              </tr>
            </tbody>
          </table>
          <EmptyState v-else compact icon="file" title="Nenhuma prova cadastrada." />
        </div>
      </section>

      <section class="card panel fade-in" aria-labelledby="students-title">
        <header class="panel-head">
          <div>
            <h2 id="students-title">Aluno × média</h2>
            <p class="hint">
              Cada tentativa é comparada com a média da própria prova, para que a dificuldade de cada prova não distorça a comparação.
            </p>
          </div>
        </header>
        <div class="table-wrap">
          <table v-if="students.length">
            <thead>
              <tr>
                <th>Aluno</th>
                <th class="num">Tentativas</th>
                <th class="num">Média do aluno</th>
                <th class="num">Desvio em relação à média de cada prova</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="student in students" :key="student.student_id">
                <td class="strong">{{ student.student_name }}</td>
                <td class="num">{{ student.attempts_count }}</td>
                <td class="num"><strong>{{ student.average_percentage }}%</strong></td>
                <td class="num">
                  <span class="delta" :class="differenceTone(student.difference_from_exam_average)">
                    <AppIcon
                      v-if="student.difference_from_exam_average !== 0"
                      :name="student.difference_from_exam_average > 0 ? 'arrow-up' : 'arrow-down'"
                      :size="14"
                      :stroke-width="2.5"
                    />
                    {{ formatDifference(student.difference_from_exam_average) }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
          <EmptyState v-else compact icon="users" title="Nenhum aluno respondeu suas provas ainda." />
        </div>
        <AppPagination
          :page="studentsPagination.current_page"
          :last-page="studentsPagination.last_page"
          label="Paginação de alunos"
          @change="changeStudentsPage"
        />
      </section>

      <section class="card panel fade-in" aria-labelledby="ranking-title">
        <header class="panel-head">
          <div>
            <h2 id="ranking-title">Ranking</h2>
            <p class="hint">Tentativas ordenadas pelo maior percentual.</p>
          </div>
          <div class="filter">
            <label for="ranking-exam" class="filter-label">Prova</label>
            <select id="ranking-exam" v-model="rankingExamId" class="select" @change="changeRankingFilter">
              <option value="">Todas as provas</option>
              <option v-for="exam in examMetrics" :key="exam.exam_id" :value="exam.exam_id">{{ exam.exam_title }}</option>
            </select>
          </div>
        </header>
        <div class="table-wrap">
          <table v-if="ranking.length">
            <thead>
              <tr>
                <th class="pos">#</th>
                <th>Aluno</th>
                <th>Prova</th>
                <th class="num">Acertos</th>
                <th class="num">Percentual</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in ranking" :key="row.attempt_id">
                <td class="pos">
                  <span v-if="MEDALS[row.position]" class="medal" :class="MEDALS[row.position]">
                    <span class="sr-only">{{ row.position }}º lugar</span>
                    <span aria-hidden="true">{{ row.position }}</span>
                  </span>
                  <span v-else class="position">{{ row.position }}</span>
                </td>
                <td class="strong">{{ row.student_name }}</td>
                <td class="muted wrap">{{ row.exam_title }}</td>
                <td class="num">{{ row.score }}/{{ row.total_questions }}</td>
                <td class="num">
                  <AppBadge :tone="row.percentage >= 70 ? 'success' : row.percentage >= 40 ? 'warning' : 'danger'">
                    {{ row.percentage }}%
                  </AppBadge>
                </td>
              </tr>
            </tbody>
          </table>
          <EmptyState v-else compact icon="trophy" title="Nenhuma tentativa registrada." />
        </div>
        <AppPagination
          :page="pagination.current_page"
          :last-page="pagination.last_page"
          label="Paginação do ranking"
          @change="changeRankingPage"
        />
      </section>
    </template>
  </div>
</template>

<style scoped>
.metrics { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: var(--space-4); margin-bottom: var(--space-6); }
.metric { display: flex; flex-direction: column; gap: var(--space-1); min-width: 0; }
.metric-icon {
  display: grid;
  place-items: center;
  width: 2.5rem;
  height: 2.5rem;
  margin-bottom: var(--space-2);
  border-radius: var(--radius-md);
}
.metric-icon.primary { background: var(--color-primary-soft); color: var(--color-primary); }
.metric-icon.success { background: var(--color-success-soft); color: var(--color-success); }
.metric-icon.danger { background: var(--color-danger-soft); color: var(--color-danger); }
.metric-icon.info { background: var(--color-info-soft); color: var(--color-info); }
.metric .label { font-size: var(--text-sm); font-weight: 600; color: var(--color-text-muted); }
.metric .value { font-size: var(--text-2xl); font-weight: 800; letter-spacing: -0.02em; font-variant-numeric: tabular-nums; }
.metric .value small { margin-left: 1px; font-size: var(--text-md); font-weight: 700; color: var(--color-text-muted); }
.metric .foot { font-size: var(--text-xs); color: var(--color-text-subtle); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.metric .foot strong { color: var(--color-text-muted); }

.panel { margin-bottom: var(--space-6); padding: 0; overflow: hidden; }
.panel-skeleton { display: grid; gap: var(--space-3); margin-bottom: var(--space-6); }
.panel-head {
  display: flex;
  align-items: flex-end;
  flex-wrap: wrap;
  gap: var(--space-4);
  padding: var(--space-5);
  border-bottom: 1px solid var(--color-border);
}
.panel-head > div:first-child { flex: 1; min-width: 14rem; }
.panel-head h2 { margin: 0; }
.hint { margin-top: var(--space-1); font-size: var(--text-sm); color: var(--color-text-muted); }

.filter { display: flex; align-items: center; gap: var(--space-2); }
.filter-label { font-size: var(--text-sm); font-weight: 600; color: var(--color-text-muted); }
.filter .select { width: auto; min-width: 14rem; }

td.strong { font-weight: 600; }
td.wrap { white-space: normal; min-width: 10rem; }
th.pos,
td.pos { width: 3.5rem; text-align: center; }
.position { color: var(--color-text-subtle); font-variant-numeric: tabular-nums; }
.medal {
  display: inline-grid;
  place-items: center;
  width: 1.75rem;
  height: 1.75rem;
  border-radius: var(--radius-full);
  font-size: var(--text-xs);
  font-weight: 800;
}
.medal.gold { background: var(--color-medal-gold); color: var(--color-medal-gold-text); }
.medal.silver { background: var(--color-medal-silver); color: var(--color-medal-silver-text); }
.medal.bronze { background: var(--color-medal-bronze); color: var(--color-medal-bronze-text); }

.delta { display: inline-flex; align-items: center; gap: 2px; font-weight: 600; }
.delta.positive { color: var(--color-success); }
.delta.negative { color: var(--color-danger); }
.delta.neutral { color: var(--color-text-muted); }

@media (max-width: 900px) {
  .metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 560px) {
  .metrics { gap: var(--space-3); }
  .metric { padding: var(--space-4); }
  .metric .value { font-size: var(--text-xl); }
  .panel-head { padding: var(--space-4); }
  .filter { width: 100%; }
  .filter .select { min-width: 0; flex: 1; }
}
</style>
