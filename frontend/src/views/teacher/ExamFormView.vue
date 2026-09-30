<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../services/api'

const props = defineProps({ id: { type: [String, Number], default: null } })
const router = useRouter()

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
    router.push({ name: 'teacher.exams' })
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors || {}
      generalError.value = e.response.data.message || 'Corrija os campos destacados.'
    } else if (e.response?.status === 403) {
      forbid(e)
    } else if (e.response?.status === 409) {
      locked.value = true
      generalError.value = e.response.data.message || 'Esta prova já foi respondida e não pode ser editada.'
    } else {
      generalError.value = 'Erro ao salvar a prova.'
    }
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <div>
    <h1>{{ isEdit ? 'Editar prova' : 'Nova prova' }}</h1>
    <div v-if="generalError" class="alert error">{{ generalError }}</div>
    <div v-else-if="locked" class="alert info">
      Esta prova já foi respondida por alunos e não pode mais ser editada.
    </div>
    <div v-if="loading" class="muted">Carregando…</div>

    <form v-else-if="!forbidden" class="grid" @submit.prevent="submit">
      <div class="card grid">
        <div>
          <label>Título</label>
          <input class="input" v-model="form.title" placeholder="Ex.: Prova de Matemática – 1º bimestre" />
          <small v-if="errors.title" class="err">{{ errors.title[0] }}</small>
        </div>
        <div>
          <label>Descrição (opcional)</label>
          <textarea class="textarea" rows="2" v-model="form.description"></textarea>
        </div>
      </div>

      <div v-for="(q, qi) in form.questions" :key="qi" class="card grid">
        <div class="row">
          <h3>Questão {{ qi + 1 }}</h3>
          <div class="spacer"></div>
          <button type="button" class="btn danger" v-if="form.questions.length > 1" @click="removeQuestion(qi)">
            Remover questão
          </button>
        </div>
        <div>
          <label>Enunciado</label>
          <textarea class="textarea" rows="2" v-model="q.statement"></textarea>
          <small v-if="errors[`questions.${qi}.statement`]" class="err">
            {{ errors[`questions.${qi}.statement`][0] }}
          </small>
        </div>

        <div class="grid options">
          <label>Alternativas (marque a correta)</label>
          <div v-for="(o, oi) in q.options" :key="oi" class="row option">
            <input type="radio" :name="`correct-${qi}`" :checked="o.is_correct" @change="setCorrect(q, oi)" />
            <input class="input" v-model="o.text" :placeholder="`Alternativa ${oi + 1}`" />
            <button type="button" class="btn secondary" v-if="q.options.length > 2" @click="removeOption(q, oi)">✕</button>
          </div>
          <small v-if="errors[`questions.${qi}.options`]" class="err">
            {{ errors[`questions.${qi}.options`][0] }}
          </small>
          <button type="button" class="btn ghost" @click="addOption(q)">+ alternativa</button>
        </div>
      </div>

      <div class="row">
        <button type="button" class="btn secondary" @click="addQuestion">+ Adicionar questão</button>
        <div class="spacer"></div>
        <button type="button" class="btn secondary" @click="router.push({ name: 'teacher.exams' })">Cancelar</button>
        <button type="submit" class="btn" :disabled="saving || locked">{{ saving ? 'Salvando…' : 'Salvar prova' }}</button>
      </div>
    </form>
  </div>
</template>

<style scoped>
.options { gap: 0.5rem; }
.option { gap: 0.5rem; }
.option .input { flex: 1; }
</style>
