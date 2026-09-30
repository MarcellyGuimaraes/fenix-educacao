<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../services/api'
import { useToast } from '../../composables/useToast'
import { useConfirm } from '../../composables/useConfirm'
import PageHeader from '../../components/ui/PageHeader.vue'
import AppButton from '../../components/ui/AppButton.vue'
import AppBadge from '../../components/ui/AppBadge.vue'
import AppIcon from '../../components/ui/AppIcon.vue'
import ProgressBar from '../../components/ui/ProgressBar.vue'
import SkeletonBlock from '../../components/ui/SkeletonBlock.vue'
import ErrorState from '../../components/ui/ErrorState.vue'

const props = defineProps({ id: { type: [String, Number], required: true } })
const router = useRouter()
const toast = useToast()
const { confirm } = useConfirm()

const exam = ref(null)
const answers = ref({}) // question_id -> option_id
const loading = ref(true)
const submitting = ref(false)
const error = ref(null)

const total = computed(() => exam.value?.questions.length ?? 0)
const answeredCount = computed(() =>
  exam.value ? exam.value.questions.filter((q) => answers.value[q.id]).length : 0
)
const remaining = computed(() => total.value - answeredCount.value)
const allAnswered = computed(() => !!exam.value && remaining.value === 0)

const LETTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
const letter = (i) => LETTERS[i] ?? String(i + 1)

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
  if (!allAnswered.value || submitting.value) return
  const ok = await confirm({
    title: 'Enviar respostas?',
    message: 'Depois de enviadas, as respostas não poderão ser alteradas e a prova não poderá ser refeita.',
    confirmLabel: 'Enviar respostas',
  })
  if (!ok) return

  submitting.value = true
  try {
    const payload = {
      answers: exam.value.questions.map((q) => ({
        question_id: q.id,
        option_id: answers.value[q.id],
      })),
    }
    const { data } = await api.post(`/student/exams/${props.id}/attempts`, payload)
    toast.success('Respostas enviadas! Confira seu resultado.')
    router.push({ name: 'student.attempt.result', params: { id: data.data.id } })
  } catch (e) {
    if (e.response?.status === 409) {
      toast.error('Você já realizou esta prova.')
    } else if (e.response?.status === 422) {
      toast.error('Responda todas as questões antes de enviar.')
    } else {
      toast.error('Erro ao enviar a prova.')
    }
  } finally {
    submitting.value = false
  }
}

onMounted(load)
</script>

<template>
  <div>
    <template v-if="loading">
      <div aria-busy="true" aria-label="Carregando prova" class="stack">
        <SkeletonBlock width="8rem" />
        <SkeletonBlock width="55%" height="2rem" />
        <div v-for="n in 2" :key="n" class="card stack">
          <SkeletonBlock width="30%" />
          <SkeletonBlock width="80%" height="1.25rem" />
          <SkeletonBlock v-for="o in 4" :key="o" height="3rem" radius="var(--radius-md)" />
        </div>
      </div>
    </template>

    <template v-else-if="error && !exam">
      <PageHeader title="Prova" :back="{ name: 'student.exams' }" back-label="Provas disponíveis" />
      <ErrorState :message="error" @retry="load" />
    </template>

    <template v-else-if="exam">
      <PageHeader :title="exam.title" :back="{ name: 'student.exams' }" back-label="Provas disponíveis">
        <template #subtitle>
          <span v-if="exam.description">{{ exam.description }}</span>
          <span v-else>{{ total }} {{ total === 1 ? 'questão' : 'questões' }} · escolha uma alternativa em cada questão.</span>
        </template>
      </PageHeader>

      <form class="questions" @submit.prevent="submit">
        <fieldset
          v-for="(q, qi) in exam.questions"
          :key="q.id"
          class="card question fade-in"
          :class="{ answered: answers[q.id] }"
        >
          <legend class="sr-only">Questão {{ qi + 1 }}: {{ q.statement }}</legend>
          <div class="q-head" aria-hidden="true">
            <span class="q-number">Questão {{ qi + 1 }} de {{ total }}</span>
            <AppBadge v-if="answers[q.id]" tone="success" icon="check">Respondida</AppBadge>
          </div>
          <p class="statement" aria-hidden="true">{{ q.statement }}</p>

          <div class="options">
            <label
              v-for="(o, oi) in q.options"
              :key="o.id"
              class="option"
              :class="{ selected: answers[q.id] === o.id }"
            >
              <input v-model="answers[q.id]" class="radio" type="radio" :name="`q-${q.id}`" :value="o.id" />
              <span class="letter" aria-hidden="true">{{ letter(oi) }}</span>
              <span class="text">{{ o.text }}</span>
              <AppIcon v-if="answers[q.id] === o.id" class="check" name="check-circle" :size="20" />
            </label>
          </div>
        </fieldset>

        <div class="submit-bar">
          <div class="progress-info">
            <div class="progress-label">
              <strong>{{ answeredCount }} de {{ total }}</strong> respondidas
              <span v-if="remaining > 0" class="remaining">
                · falta{{ remaining === 1 ? '' : 'm' }} {{ remaining }}
                {{ remaining === 1 ? 'questão' : 'questões' }}
              </span>
              <span v-else class="ready"><AppIcon name="check" :size="14" /> tudo pronto!</span>
            </div>
            <ProgressBar
              :value="answeredCount"
              :max="total"
              label="Progresso da prova"
              :value-text="`${answeredCount} de ${total} respondidas`"
              :tone="allAnswered ? 'success' : 'primary'"
            />
          </div>
          <AppButton type="submit" icon="send" :loading="submitting" :disabled="!allAnswered">
            {{ submitting ? 'Enviando…' : 'Enviar respostas' }}
          </AppButton>
        </div>
      </form>
    </template>
  </div>
</template>

<style scoped>
.questions { display: grid; gap: var(--space-4); }
.question { margin: 0; min-width: 0; border-left: 4px solid var(--color-border); transition: border-color var(--duration) var(--ease); }
.question.answered { border-left-color: var(--color-success); }
.q-head { display: flex; align-items: center; justify-content: space-between; gap: var(--space-2); margin-bottom: var(--space-2); }
.q-number { font-size: var(--text-xs); font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: var(--color-primary); }
.statement { margin-bottom: var(--space-4); font-size: var(--text-lg); font-weight: 600; line-height: 1.45; overflow-wrap: anywhere; }

.options { display: grid; gap: var(--space-2); }
.option {
  position: relative;
  display: flex;
  align-items: center;
  gap: var(--space-3);
  min-height: 3.25rem;
  padding: var(--space-3) var(--space-4);
  border: 1.5px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-surface);
  cursor: pointer;
  transition: border-color var(--duration) var(--ease), background var(--duration) var(--ease);
}
.option:hover { border-color: var(--color-border-strong); background: var(--color-surface-hover); }
.option.selected { border-color: var(--color-primary); background: var(--color-primary-soft); }
.option:has(.radio:focus-visible) { box-shadow: var(--focus-ring); }

/* Radio nativo oculto visualmente, mas focável e operável pelo teclado (setas). */
.radio { position: absolute; opacity: 0; width: 1px; height: 1px; margin: 0; pointer-events: none; }

.letter {
  display: grid;
  flex-shrink: 0;
  place-items: center;
  width: 2rem;
  height: 2rem;
  border: 1.5px solid var(--color-border-strong);
  border-radius: var(--radius-sm);
  font-size: var(--text-sm);
  font-weight: 700;
  color: var(--color-text-muted);
  transition: all var(--duration) var(--ease);
}
.option.selected .letter { border-color: var(--color-primary); background: var(--color-primary); color: var(--color-on-primary); }
.text { flex: 1; overflow-wrap: anywhere; }
.check { color: var(--color-primary); }

.submit-bar {
  position: sticky;
  bottom: var(--space-4);
  z-index: 5;
  display: flex;
  align-items: center;
  gap: var(--space-6);
  margin-top: var(--space-2);
  padding: var(--space-4) var(--space-5);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  background: color-mix(in srgb, var(--color-surface) 92%, transparent);
  backdrop-filter: blur(10px);
  box-shadow: var(--shadow-lg);
}
.progress-info { flex: 1; display: grid; gap: var(--space-2); min-width: 0; }
.progress-label { font-size: var(--text-sm); color: var(--color-text-muted); }
.progress-label strong { color: var(--color-text); }
.ready { display: inline-flex; align-items: center; gap: 2px; color: var(--color-success); font-weight: 600; }

@media (max-width: 560px) {
  .submit-bar { flex-direction: column; align-items: stretch; gap: var(--space-3); bottom: var(--space-2); padding: var(--space-3) var(--space-4); }
  .statement { font-size: var(--text-md); }
}
</style>
