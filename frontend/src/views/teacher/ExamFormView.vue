<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../services/api'
import { useToast } from '../../composables/useToast'
import PageHeader from '../../components/ui/PageHeader.vue'
import AppButton from '../../components/ui/AppButton.vue'
import AppBadge from '../../components/ui/AppBadge.vue'
import AppIcon from '../../components/ui/AppIcon.vue'
import SkeletonBlock from '../../components/ui/SkeletonBlock.vue'

const props = defineProps({ id: { type: [String, Number], default: null } })
const router = useRouter()
const toast = useToast()

const isEdit = computed(() => !!props.id)
const saving = ref(false)
const loading = ref(false)
const errors = ref({})
const generalError = ref(null)
// Provas já respondidas não podem ser editadas (a API responde 409).
const locked = ref(false)
// Prova de outro professor (a API responde 403): o formulário não é exibido.
const forbidden = ref(false)

function forbid(e) {
  forbidden.value = true
  generalError.value = e.response.data?.message || 'Esta prova pertence a outro professor.'
}

const form = ref({
  title: '',
  description: '',
  questions: [newQuestion()],
})

const LETTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
const letter = (i) => LETTERS[i] ?? String(i + 1)

function newQuestion() {
  return { statement: '', options: [newOption(true), newOption(false)] }
}
function newOption(correct = false) {
  return { text: '', is_correct: correct }
}

function addQuestion() {
  form.value.questions.push(newQuestion())
}
function removeQuestion(i) {
  form.value.questions.splice(i, 1)
}
function addOption(q) {
  q.options.push(newOption(false))
}
function removeOption(q, i) {
  q.options.splice(i, 1)
}
function setCorrect(q, i) {
  q.options.forEach((o, idx) => (o.is_correct = idx === i))
}

const fieldError = (key) => errors.value[key]?.[0] ?? null

async function load() {
  if (!isEdit.value) return
  loading.value = true
  try {
    const { data } = await api.get(`/exams/${props.id}`)
    const exam = data.data
    locked.value = exam.attempts_count > 0
    form.value = {
      title: exam.title,
      description: exam.description || '',
      questions: exam.questions.map((q) => ({
        statement: q.statement,
        options: q.options.map((o) => ({ text: o.text, is_correct: o.is_correct })),
      })),
    }
  } catch (e) {
    if (e.response?.status === 403) forbid(e)
    else generalError.value = 'Erro ao carregar a prova.'
  } finally {
    loading.value = false
  }
}

async function submit() {
  saving.value = true
  errors.value = {}
  generalError.value = null
  try {
    if (isEdit.value) {
      await api.put(`/exams/${props.id}`, form.value)
    } else {
      await api.post('/exams', form.value)
    }
    toast.success(isEdit.value ? 'Prova atualizada.' : 'Prova criada com sucesso.')
    router.push({ name: 'teacher.exams' })
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors || {}
      toast.error(e.response.data.message || 'Corrija os campos destacados.')
    } else if (e.response?.status === 403) {
      forbid(e)
      toast.error(generalError.value)
    } else if (e.response?.status === 409) {
      locked.value = true
      generalError.value = e.response.data.message || 'Esta prova já foi respondida e não pode ser editada.'
      toast.error(generalError.value)
    } else {
      toast.error('Erro ao salvar a prova.')
    }
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <div>
    <PageHeader
      :title="isEdit ? 'Editar prova' : 'Nova prova'"
      :subtitle="isEdit ? 'Altere as questões e salve.' : 'Monte uma prova de múltipla escolha: cada questão tem uma alternativa correta.'"
      :back="{ name: 'teacher.exams' }"
      back-label="Minhas provas"
    />

    <div v-if="generalError" class="alert error" role="alert">
      <AppIcon name="alert" :size="18" />
      <span>{{ generalError }}</span>
    </div>
    <div v-else-if="locked" class="alert info">
      <AppIcon name="lock" :size="18" />
      <span>Esta prova já foi respondida por alunos e não pode mais ser editada.</span>
    </div>

    <div v-if="loading" class="stack" aria-busy="true" aria-label="Carregando prova">
      <div v-for="n in 2" :key="n" class="card stack">
        <SkeletonBlock width="20%" />
        <SkeletonBlock height="2.5rem" radius="var(--radius-md)" />
        <SkeletonBlock height="4rem" radius="var(--radius-md)" />
      </div>
    </div>

    <form v-else-if="!forbidden" class="form" novalidate @submit.prevent="submit">
      <fieldset class="card section" :disabled="locked">
        <legend class="section-title"><AppIcon name="file" :size="18" /> Informações</legend>
        <div>
          <label for="exam-title" class="field-label">Título</label>
          <input
            id="exam-title"
            v-model="form.title"
            class="input"
            placeholder="Ex.: Prova de Matemática – 1º bimestre"
            :aria-invalid="fieldError('title') ? 'true' : null"
            :aria-describedby="fieldError('title') ? 'exam-title-err' : null"
          />
          <small v-if="fieldError('title')" id="exam-title-err" class="err">
            <AppIcon name="alert" :size="13" /> {{ fieldError('title') }}
          </small>
        </div>
        <div>
          <label for="exam-description" class="field-label">
            Descrição <span class="field-hint">(opcional)</span>
          </label>
          <textarea
            id="exam-description"
            v-model="form.description"
            class="textarea"
            rows="2"
            placeholder="Instruções ou conteúdo avaliado"
          ></textarea>
        </div>
      </fieldset>

      <small v-if="fieldError('questions')" class="err"><AppIcon name="alert" :size="13" /> {{ fieldError('questions') }}</small>

      <fieldset v-for="(q, qi) in form.questions" :key="qi" class="card section question" :disabled="locked">
        <legend class="sr-only">Questão {{ qi + 1 }}</legend>
        <div class="q-head">
          <span class="q-number" aria-hidden="true">{{ qi + 1 }}</span>
          <h2 class="q-title" aria-hidden="true">Questão {{ qi + 1 }}</h2>
          <div class="spacer"></div>
          <AppButton
            v-if="form.questions.length > 1"
            variant="danger-ghost"
            size="sm"
            icon="trash"
            :label="`Remover questão ${qi + 1}`"
            @click="removeQuestion(qi)"
          />
        </div>

        <div>
          <label :for="`q-${qi}-statement`" class="field-label">Enunciado</label>
          <textarea
            :id="`q-${qi}-statement`"
            v-model="q.statement"
            class="textarea"
            rows="2"
            placeholder="Digite a pergunta"
            :aria-invalid="fieldError(`questions.${qi}.statement`) ? 'true' : null"
          ></textarea>
          <small v-if="fieldError(`questions.${qi}.statement`)" class="err">
            <AppIcon name="alert" :size="13" /> {{ fieldError(`questions.${qi}.statement`) }}
          </small>
        </div>

        <div class="options">
          <p class="field-label">
            Alternativas <span class="field-hint">— marque a correta</span>
          </p>
          <div v-for="(o, oi) in q.options" :key="oi" class="option" :class="{ correct: o.is_correct }">
            <label class="correct-toggle" :title="o.is_correct ? 'Alternativa correta' : 'Marcar como correta'">
              <input
                class="radio"
                type="radio"
                :name="`correct-${qi}`"
                :checked="o.is_correct"
                @change="setCorrect(q, oi)"
              />
              <span class="letter" aria-hidden="true">{{ letter(oi) }}</span>
              <span class="sr-only">Marcar alternativa {{ letter(oi) }} como correta</span>
            </label>
            <input
              v-model="o.text"
              class="input"
              :placeholder="`Alternativa ${letter(oi)}`"
              :aria-label="`Texto da alternativa ${letter(oi)}`"
              :aria-invalid="fieldError(`questions.${qi}.options.${oi}.text`) ? 'true' : null"
            />
            <AppBadge v-if="o.is_correct" tone="success" icon="check" class="correct-badge">Correta</AppBadge>
            <AppButton
              v-if="q.options.length > 2"
              variant="ghost"
              size="sm"
              icon="x"
              :label="`Remover alternativa ${letter(oi)}`"
              @click="removeOption(q, oi)"
            />
          </div>
          <small v-if="fieldError(`questions.${qi}.options`)" class="err">
            <AppIcon name="alert" :size="13" /> {{ fieldError(`questions.${qi}.options`) }}
          </small>
          <div>
            <AppButton variant="soft" size="sm" icon="plus" @click="addOption(q)">Adicionar alternativa</AppButton>
          </div>
        </div>
      </fieldset>

      <button type="button" class="add-question" :disabled="locked" @click="addQuestion">
        <AppIcon name="plus" :size="18" /> Adicionar questão
      </button>

      <div class="action-bar">
        <span class="summary muted small">
          {{ form.questions.length }} {{ form.questions.length === 1 ? 'questão' : 'questões' }}
        </span>
        <div class="spacer"></div>
        <AppButton variant="secondary" @click="router.push({ name: 'teacher.exams' })">Cancelar</AppButton>
        <AppButton type="submit" icon="save" :loading="saving" :disabled="locked">
          {{ saving ? 'Salvando…' : 'Salvar prova' }}
        </AppButton>
      </div>
    </form>
  </div>
</template>

<style scoped>
.form { display: grid; gap: var(--space-4); }
.section { display: grid; gap: var(--space-4); margin: 0; min-width: 0; }
.section:disabled { opacity: 0.7; }
.section-title {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  float: left;
  width: 100%;
  padding: 0;
  font-weight: 700;
  font-size: var(--text-md);
}
.section-title .icon { color: var(--color-primary); }

.q-head { display: flex; align-items: center; gap: var(--space-3); }
.q-number {
  display: grid;
  place-items: center;
  width: 2rem;
  height: 2rem;
  border-radius: var(--radius-full);
  background: var(--color-primary);
  color: var(--color-on-primary);
  font-size: var(--text-sm);
  font-weight: 700;
}
.q-title { margin: 0; font-size: var(--text-md); }

.options { display: grid; gap: var(--space-2); }
.options > .field-label { margin-bottom: 0; }
.option {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  padding: var(--space-1);
  border: 1.5px solid transparent;
  border-radius: var(--radius-md);
  transition: background var(--duration) var(--ease), border-color var(--duration) var(--ease);
}
.option.correct { border-color: var(--color-success); background: var(--color-success-soft); }
.option .input { flex: 1; min-width: 0; }

.correct-toggle { position: relative; display: block; margin: 0; cursor: pointer; }
.radio { position: absolute; opacity: 0; width: 1px; height: 1px; margin: 0; }
.letter {
  display: grid;
  place-items: center;
  width: 2.25rem;
  height: 2.25rem;
  border: 1.5px solid var(--color-border-strong);
  border-radius: var(--radius-full);
  background: var(--color-surface);
  color: var(--color-text-muted);
  font-size: var(--text-sm);
  font-weight: 700;
  transition: all var(--duration) var(--ease);
}
.correct-toggle:hover .letter { border-color: var(--color-success); color: var(--color-success); }
.radio:focus-visible + .letter { box-shadow: var(--focus-ring); }
.option.correct .letter { border-color: var(--color-success); background: var(--color-success); color: var(--color-surface); }

.add-question {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-2);
  min-height: 3.5rem;
  border: 1.5px dashed var(--color-border-strong);
  border-radius: var(--radius-lg);
  background: transparent;
  color: var(--color-text-muted);
  font-weight: 600;
  transition: all var(--duration) var(--ease);
}
.add-question:hover:not(:disabled) { border-color: var(--color-primary); color: var(--color-primary); background: var(--color-primary-soft); }
.add-question:disabled { opacity: 0.5; cursor: not-allowed; }

.action-bar {
  position: sticky;
  bottom: var(--space-4);
  z-index: 5;
  display: flex;
  align-items: center;
  gap: var(--space-2);
  padding: var(--space-3) var(--space-4);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  background: color-mix(in srgb, var(--color-surface) 92%, transparent);
  backdrop-filter: blur(10px);
  box-shadow: var(--shadow-lg);
}

@media (max-width: 560px) {
  .correct-badge { display: none; }
  .summary { display: none; }
  .action-bar { bottom: var(--space-2); }
  .action-bar :deep(.btn) { flex: 1; }
}
</style>
