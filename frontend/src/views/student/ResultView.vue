<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../../services/api'
import PageHeader from '../../components/ui/PageHeader.vue'
import AppBadge from '../../components/ui/AppBadge.vue'
import AppIcon from '../../components/ui/AppIcon.vue'
import ScoreRing from '../../components/ui/ScoreRing.vue'
import SkeletonBlock from '../../components/ui/SkeletonBlock.vue'
import ErrorState from '../../components/ui/ErrorState.vue'

const props = defineProps({ id: { type: [String, Number], required: true } })

const result = ref(null)
const loading = ref(true)
const error = ref(null)

const wrongCount = computed(() => (result.value ? result.value.total_questions - result.value.score : 0))

const feedback = computed(() => {
  const pct = result.value?.percentage ?? 0
  if (pct >= 90) return { title: 'Excelente!', text: 'Você dominou o conteúdo desta prova.' }
  if (pct >= 70) return { title: 'Mandou bem!', text: 'Ótimo desempenho, poucos detalhes a revisar.' }
  if (pct >= 50) return { title: 'Bom esforço!', text: 'Revise as questões que errou para fixar o conteúdo.' }
  return { title: 'Continue praticando', text: 'Veja abaixo as respostas corretas e revise o conteúdo.' }
})

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
    <template v-if="loading">
      <div aria-busy="true" aria-label="Carregando resultado" class="stack">
        <SkeletonBlock width="8rem" />
        <SkeletonBlock width="50%" height="2rem" />
        <div class="card score-card">
          <SkeletonBlock width="168px" height="168px" radius="50%" />
          <div class="stack" style="flex: 1">
            <SkeletonBlock width="40%" height="1.5rem" />
            <SkeletonBlock width="70%" />
          </div>
        </div>
      </div>
    </template>

    <template v-else-if="error">
      <PageHeader title="Resultado" :back="{ name: 'student.exams' }" back-label="Provas disponíveis" />
      <ErrorState :message="error" @retry="load" />
    </template>

    <template v-else-if="result">
      <PageHeader :title="result.exam_title" subtitle="Resultado da prova" :back="{ name: 'student.exams' }" back-label="Provas disponíveis" />

      <section class="card score-card fade-in" aria-labelledby="score-title">
        <ScoreRing :percentage="result.percentage" />
        <div class="score-info">
          <h2 id="score-title">{{ feedback.title }}</h2>
          <p class="muted">{{ feedback.text }}</p>
          <div class="stats">
            <div class="stat ok">
              <AppIcon name="check-circle" :size="18" />
              <span><strong>{{ result.score }}</strong> {{ result.score === 1 ? 'acerto' : 'acertos' }}</span>
            </div>
            <div class="stat wrong">
              <AppIcon name="x-circle" :size="18" />
              <span><strong>{{ wrongCount }}</strong> {{ wrongCount === 1 ? 'erro' : 'erros' }}</span>
            </div>
            <div class="stat">
              <AppIcon name="list-checks" :size="18" />
              <span><strong>{{ result.total_questions }}</strong> {{ result.total_questions === 1 ? 'questão' : 'questões' }}</span>
            </div>
          </div>
        </div>
      </section>

      <h2 class="section-title">Detalhamento</h2>
      <ol class="answers">
        <li
          v-for="(a, i) in result.answers"
          :key="a.question_id"
          class="card answer fade-in"
          :class="a.is_correct ? 'ok' : 'wrong'"
        >
          <div class="a-head">
            <span class="q-number">Questão {{ i + 1 }}</span>
            <AppBadge v-if="a.is_correct" tone="success" icon="check">Correta</AppBadge>
            <AppBadge v-else tone="danger" icon="x">Incorreta</AppBadge>
          </div>
          <p class="statement">{{ a.statement }}</p>

          <div class="responses">
            <div class="response" :class="a.is_correct ? 'is-correct' : 'is-wrong'">
              <AppIcon :name="a.is_correct ? 'check-circle' : 'x-circle'" :size="18" />
              <div>
                <span class="r-label">Sua resposta</span>
                <span class="r-text">{{ a.chosen_option_text }}</span>
              </div>
            </div>
            <div v-if="!a.is_correct" class="response is-correct">
              <AppIcon name="check-circle" :size="18" />
              <div>
                <span class="r-label">Resposta correta</span>
                <span class="r-text">{{ a.correct_option_text }}</span>
              </div>
            </div>
          </div>
        </li>
      </ol>
    </template>
  </div>
</template>

<style scoped>
.score-card { display: flex; align-items: center; gap: var(--space-8); padding: var(--space-8); }
.score-info { flex: 1; }
.score-info h2 { font-size: var(--text-xl); }
.stats { display: flex; flex-wrap: wrap; gap: var(--space-2); margin-top: var(--space-5); }
.stat {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
  padding: var(--space-2) var(--space-3);
  border-radius: var(--radius-md);
  background: var(--color-surface-2);
  font-size: var(--text-sm);
  color: var(--color-text-muted);
}
.stat strong { color: var(--color-text); }
.stat.ok { background: var(--color-success-soft); color: var(--color-success-soft-text); }
.stat.wrong { background: var(--color-danger-soft); color: var(--color-danger-soft-text); }
.stat.ok strong,
.stat.wrong strong { color: inherit; }

.section-title { margin: var(--space-8) 0 var(--space-4); }
.answers { display: grid; gap: var(--space-3); margin: 0; padding: 0; list-style: none; }
.answer { border-left: 4px solid var(--color-border); }
.answer.ok { border-left-color: var(--color-success); }
.answer.wrong { border-left-color: var(--color-danger); }
.a-head { display: flex; align-items: center; justify-content: space-between; gap: var(--space-2); margin-bottom: var(--space-2); }
.q-number { font-size: var(--text-xs); font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: var(--color-text-subtle); }
.statement { margin-bottom: var(--space-4); font-weight: 600; overflow-wrap: anywhere; }
.responses { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: var(--space-2); }
.response {
  display: flex;
  align-items: flex-start;
  gap: var(--space-2);
  padding: var(--space-3);
  border-radius: var(--radius-md);
  background: var(--color-surface-2);
}
.response > div { display: flex; flex-direction: column; min-width: 0; }
.response.is-correct { background: var(--color-success-soft); color: var(--color-success-soft-text); }
.response.is-wrong { background: var(--color-danger-soft); color: var(--color-danger-soft-text); }
.r-label { font-size: var(--text-xs); font-weight: 600; opacity: 0.85; }
.r-text { font-weight: 600; overflow-wrap: anywhere; }

@media (max-width: 640px) {
  .score-card { flex-direction: column; text-align: center; gap: var(--space-5); padding: var(--space-6) var(--space-4); }
  .stats { justify-content: center; }
}
</style>
