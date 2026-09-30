<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../services/api'
import { useToast } from '../../composables/useToast'
import { useConfirm } from '../../composables/useConfirm'
import PageHeader from '../../components/ui/PageHeader.vue'
import AppButton from '../../components/ui/AppButton.vue'
import AppBadge from '../../components/ui/AppBadge.vue'
import AppIcon from '../../components/ui/AppIcon.vue'
import SkeletonBlock from '../../components/ui/SkeletonBlock.vue'
import EmptyState from '../../components/ui/EmptyState.vue'
import ErrorState from '../../components/ui/ErrorState.vue'

const router = useRouter()
const toast = useToast()
const { confirm } = useConfirm()
const exams = ref([])
const loading = ref(true)
const error = ref(null)

// `silent` recarrega sem trocar a lista pelo skeleton (ex.: após excluir).
async function load({ silent = false } = {}) {
  if (!silent) loading.value = true
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

function createExam() {
  router.push({ name: 'teacher.exams.create' })
}

async function remove(exam) {
  const ok = await confirm({
    title: 'Excluir prova?',
    message: `A prova "${exam.title}" e suas questões serão excluídas. Esta ação não pode ser desfeita.`,
    confirmLabel: 'Excluir prova',
    tone: 'danger',
  })
  if (!ok) return
  try {
    await api.delete(`/exams/${exam.id}`)
    toast.success(`Prova "${exam.title}" excluída.`)
    await load({ silent: true })
  } catch (e) {
    // 403 (prova de outro professor) ou 409 (prova já respondida): mostra o
    // motivo retornado pela API.
    const status = e.response?.status
    toast.error(status === 403 || status === 409 ? e.response.data.message : 'Não foi possível excluir a prova.')
    if (status === 409) await load({ silent: true })
  }
}

onMounted(load)
</script>

<template>
  <div>
    <PageHeader title="Minhas provas" subtitle="Crie, edite e acompanhe as provas que você publicou.">
      <template #actions>
        <AppButton icon="plus" @click="createExam">Nova prova</AppButton>
      </template>
    </PageHeader>

    <div v-if="loading" class="list" aria-busy="true" aria-label="Carregando provas">
      <div v-for="n in 3" :key="n" class="card exam">
        <SkeletonBlock width="2.75rem" height="2.75rem" radius="var(--radius-md)" />
        <div class="stack" style="flex: 1; gap: var(--space-2)">
          <SkeletonBlock width="45%" height="1.2rem" />
          <SkeletonBlock width="25%" />
        </div>
      </div>
    </div>

    <ErrorState v-else-if="error" :message="error" @retry="load" />

    <EmptyState
      v-else-if="!exams.length"
      icon="file"
      title="Nenhuma prova cadastrada ainda"
      description="Crie sua primeira prova de múltipla escolha e ela ficará disponível para os alunos."
    >
      <AppButton icon="plus" @click="createExam">Criar primeira prova</AppButton>
    </EmptyState>

    <ul v-else class="list">
      <li v-for="exam in exams" :key="exam.id" class="card exam fade-in">
        <span class="exam-icon" :class="{ locked: exam.attempts_count > 0 }" aria-hidden="true">
          <AppIcon :name="exam.attempts_count > 0 ? 'lock' : 'file'" :size="20" />
        </span>

        <div class="exam-body">
          <h2 class="exam-title">{{ exam.title }}</h2>
          <p v-if="exam.description" class="desc">{{ exam.description }}</p>
          <div class="badges">
            <AppBadge icon="list-checks">
              {{ exam.questions_count }} {{ exam.questions_count === 1 ? 'questão' : 'questões' }}
            </AppBadge>
            <AppBadge :tone="exam.attempts_count > 0 ? 'info' : 'neutral'" icon="users">
              {{ exam.attempts_count }} {{ exam.attempts_count === 1 ? 'tentativa' : 'tentativas' }}
            </AppBadge>
          </div>
          <p v-if="exam.attempts_count > 0" class="lock-hint">
            <AppIcon name="lock" :size="13" />
            Já respondida: edição e exclusão bloqueadas para preservar o histórico.
          </p>
        </div>

        <div class="actions">
          <AppButton
            variant="secondary"
            size="sm"
            icon="pencil"
            :disabled="exam.attempts_count > 0"
            :title="exam.attempts_count > 0 ? 'Provas já respondidas não podem ser editadas.' : null"
            @click="router.push({ name: 'teacher.exams.edit', params: { id: exam.id } })"
          >
            Editar
          </AppButton>
          <AppButton
            variant="danger-ghost"
            size="sm"
            icon="trash"
            :disabled="exam.attempts_count > 0"
            :title="exam.attempts_count > 0 ? 'Provas já respondidas não podem ser excluídas: o histórico de tentativas é preservado.' : null"
            @click="remove(exam)"
          >
            Excluir
          </AppButton>
        </div>
      </li>
    </ul>
  </div>
</template>

<style scoped>
.list { display: grid; gap: var(--space-3); margin: 0; padding: 0; list-style: none; }
.exam { display: flex; align-items: flex-start; gap: var(--space-4); transition: border-color var(--duration) var(--ease), box-shadow var(--duration) var(--ease); }
.exam:hover { border-color: var(--color-border-strong); box-shadow: var(--shadow-md); }
.exam-icon {
  display: grid;
  flex-shrink: 0;
  place-items: center;
  width: 2.75rem;
  height: 2.75rem;
  border-radius: var(--radius-md);
  background: var(--color-primary-soft);
  color: var(--color-primary);
}
.exam-icon.locked { background: var(--color-surface-2); color: var(--color-text-subtle); }
.exam-body { flex: 1; min-width: 0; }
.exam-title { margin: 0 0 var(--space-1); font-size: var(--text-lg); overflow-wrap: anywhere; }
.desc {
  margin-bottom: var(--space-2);
  color: var(--color-text-muted);
  font-size: var(--text-sm);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.badges { display: flex; flex-wrap: wrap; gap: var(--space-2); }
.lock-hint { display: flex; align-items: center; gap: var(--space-1); margin-top: var(--space-2); color: var(--color-text-subtle); font-size: var(--text-xs); }
.actions { display: flex; gap: var(--space-2); flex-shrink: 0; }

@media (max-width: 640px) {
  .exam { flex-wrap: wrap; }
  .exam-body { flex-basis: calc(100% - 4rem); }
  .actions { width: 100%; }
  .actions :deep(.btn) { flex: 1; }
}
</style>
