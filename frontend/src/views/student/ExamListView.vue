<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../services/api'
import { useSessionStore } from '../../stores/session'
import PageHeader from '../../components/ui/PageHeader.vue'
import AppButton from '../../components/ui/AppButton.vue'
import AppBadge from '../../components/ui/AppBadge.vue'
import AppIcon from '../../components/ui/AppIcon.vue'
import SkeletonBlock from '../../components/ui/SkeletonBlock.vue'
import EmptyState from '../../components/ui/EmptyState.vue'
import ErrorState from '../../components/ui/ErrorState.vue'

const router = useRouter()
const session = useSessionStore()
const exams = ref([])
const loading = ref(true)
const error = ref(null)

const firstName = computed(() => (session.userName || '').split(' ')[0])
const pendingCount = computed(() => exams.value.filter((e) => !e.attempted).length)

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

function scoreTone(pct) {
  return pct >= 70 ? 'success' : pct >= 40 ? 'warning' : 'danger'
}

onMounted(load)
</script>

<template>
  <div>
    <PageHeader :title="`Olá, ${firstName}!`">
      <template #subtitle>
        <template v-if="loading">Carregando suas provas…</template>
        <template v-else-if="pendingCount === 1">Você tem <strong>1 prova</strong> para responder.</template>
        <template v-else-if="pendingCount > 1">Você tem <strong>{{ pendingCount }} provas</strong> para responder.</template>
        <template v-else>Nenhuma prova pendente. Bom trabalho!</template>
      </template>
    </PageHeader>

    <div v-if="loading" class="exam-grid" aria-busy="true" aria-label="Carregando provas">
      <div v-for="n in 4" :key="n" class="card exam">
        <SkeletonBlock width="2.75rem" height="2.75rem" radius="var(--radius-md)" />
        <SkeletonBlock width="70%" height="1.2rem" />
        <SkeletonBlock width="40%" />
        <SkeletonBlock height="2.5rem" radius="var(--radius-md)" />
      </div>
    </div>

    <ErrorState v-else-if="error" :message="error" @retry="load" />

    <EmptyState
      v-else-if="!exams.length"
      icon="book"
      title="Nenhuma prova disponível no momento"
      description="Quando um professor publicar uma prova, ela aparecerá aqui."
    />

    <div v-else class="exam-grid">
      <article v-for="exam in exams" :key="exam.id" class="card exam fade-in" :class="{ done: exam.attempted }">
        <div class="exam-top">
          <span class="exam-icon" aria-hidden="true">
            <AppIcon :name="exam.attempted ? 'check-circle' : 'file'" :size="20" />
          </span>
          <AppBadge v-if="exam.attempted" :tone="scoreTone(exam.percentage)" icon="check">
            Concluída · {{ exam.percentage }}%
          </AppBadge>
          <AppBadge v-else tone="primary" icon="sparkles">Pendente</AppBadge>
        </div>
        <h2 class="exam-title">{{ exam.title }}</h2>
        <p class="meta">
          <AppIcon name="list-checks" :size="15" />
          {{ exam.questions_count }} {{ exam.questions_count === 1 ? 'questão' : 'questões' }}
        </p>
        <AppButton
          :variant="exam.attempted ? 'secondary' : 'primary'"
          block
          :icon-right="exam.attempted ? null : 'arrow-right'"
          :icon="exam.attempted ? 'chart' : null"
          @click="open(exam)"
        >
          {{ exam.attempted ? 'Ver resultado' : 'Responder' }}
        </AppButton>
      </article>
    </div>
  </div>
</template>

<style scoped>
.exam-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: var(--space-4); }
.exam { display: flex; flex-direction: column; gap: var(--space-3); transition: border-color var(--duration) var(--ease), box-shadow var(--duration) var(--ease); }
.exam:hover { border-color: var(--color-border-strong); box-shadow: var(--shadow-md); }
.exam-top { display: flex; align-items: center; justify-content: space-between; gap: var(--space-2); }
.exam-icon {
  display: grid;
  place-items: center;
  width: 2.75rem;
  height: 2.75rem;
  border-radius: var(--radius-md);
  background: var(--color-primary-soft);
  color: var(--color-primary);
}
.exam.done .exam-icon { background: var(--color-success-soft); color: var(--color-success); }
.exam-title { margin: var(--space-1) 0 0; font-size: var(--text-lg); overflow-wrap: anywhere; }
.meta { display: flex; align-items: center; gap: var(--space-1); margin-bottom: var(--space-2); color: var(--color-text-muted); font-size: var(--text-sm); }
.exam .btn { margin-top: auto; }
</style>
