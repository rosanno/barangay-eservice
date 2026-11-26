<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth' // ASSUMPTION: adjust to your auth store
import { getSurvey, saveSurvey, submitSurvey } from '@/services/householdSurveyService'
import { useSurveyOptions, surveyStatusMeta } from '@/composables/useSurveyOptions'
import SurveyMemberDialog from '@/components/staff/SurveyMemberDialog.vue'
import SurveySummary from '@/components/staff/SurveySummary.vue'
import SurveyVerification from '@/components/staff/SurveyVerification.vue'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const { options, load: loadOptions, label } = useSurveyOptions()

const id = route.params.id
const backTo = route.meta.backTo ?? '/staff/household-surveys' // admin route sets its own
const survey = ref(null) // server copy (status, notes, etc.)
const form = ref(null)   // editable copy
const step = ref(1)
const loading = ref(true)
const saving = ref(false)
const savedAt = ref(null)
const submitting = ref(false)
const submitErrors = ref([])
const toast = ref({ show: false, text: '', color: 'success' })

const memberDialog = ref(false)
const editingKey = ref(null)
const isVerifier = computed(() => auth.user?.role === 'admin')
const editable = computed(() =>
  survey.value && ['draft', 'needs_revision'].includes(survey.value.status) && survey.value.enumerator_id === auth.user?.id)

const steps = [
  { title: 'Household', value: 1 }, { title: 'Family members', value: 2 },
  { title: 'Demographics', value: 3 }, { title: 'Additional info', value: 4 }, { title: 'Review', value: 5 },
]

const SECTION_FIELDS = [
  'household_number', 'head_name', 'address', 'purok', 'housing_type', 'ownership_status',
  'housing_condition', 'water_source', 'has_electricity', 'sanitation', 'extra_answers',
]

function hydrate(s) {
  survey.value = s
  form.value = {
    ...Object.fromEntries(SECTION_FIELDS.map((k) => [k, s[k] ?? (k === 'extra_answers' ? {} : null)])),
    members: s.members.map((m) => ({ ...m })),
  }
  step.value = s.current_step || 1
}

onMounted(async () => {
  try {
    await loadOptions()
    hydrate(await getSurvey(id))
  } catch (e) {
    toast.value = { show: true, text: e.response?.data?.message || 'Could not load survey.', color: 'error' }
  } finally {
    loading.value = false
  }
})

/* ---------- autosave ---------- */
let timer = null
let dirty = false
let ready = false

watch(form, () => {
  if (!ready || !editable.value) return
  dirty = true
  clearTimeout(timer)
  timer = setTimeout(flush, 1500)
}, { deep: true })
watch(loading, (v) => { if (!v) setTimeout(() => (ready = true), 0) })
watch(step, () => { if (ready && editable.value) { dirty = true; flush() } })

async function flush() {
  clearTimeout(timer)
  if (!dirty || !editable.value) return
  if (saving.value) { timer = setTimeout(flush, 500); return } // retry once the in-flight save ends
  dirty = false
  saving.value = true
  try {
    await saveSurvey(id, { ...form.value, current_step: step.value })
    savedAt.value = new Date()
  } catch (e) {
    dirty = true
    toast.value = { show: true, text: e.response?.data?.message || 'Autosave failed. Your changes are kept on this page.', color: 'error' }
  } finally {
    saving.value = false
  }
}
onBeforeUnmount(() => { if (dirty) flush() })

/* ---------- members ---------- */
const hasHead = computed(() => form.value?.members.some((m) => m.relationship === 'head' && m.client_key !== editingKey.value))
const editingMember = computed(() => form.value?.members.find((m) => m.client_key === editingKey.value) ?? null)
const memberName = (m) => `${m.first_name} ${m.last_name}`.trim() || 'New member'

function addMember() { editingKey.value = null; memberDialog.value = true }
function editMember(m) { editingKey.value = m.client_key; memberDialog.value = true }
function removeMember(m) {
  if (confirm(`Remove ${memberName(m)} from this household?`)) {
    form.value.members = form.value.members.filter((x) => x.client_key !== m.client_key)
  }
}
function onMemberSave(m) {
  const i = form.value.members.findIndex((x) => x.client_key === m.client_key)
  i >= 0 ? (form.value.members[i] = m) : form.value.members.push(m)
}

/* ---------- submit ---------- */
const stepFor = (key) => {
  if (/^members\.\d+\.(civil_status|education|employment_status|occupation)/.test(key)) return 3
  if (key.startsWith('members')) return 2
  if (['housing_condition', 'water_source', 'has_electricity', 'sanitation'].includes(key)) return 4
  return 1
}

async function submit() {
  submitErrors.value = []
  submitting.value = true
  try {
    clearTimeout(timer)
    while (saving.value) await new Promise((r) => setTimeout(r, 100)) // let any in-flight autosave finish
    dirty = true
    await flush() // then save the latest state before validating on the server
    if (dirty) throw { response: { data: { message: 'Could not save your latest changes. Please try again.' } } }
    await submitSurvey(id)
    toast.value = { show: true, text: 'Survey submitted for verification.', color: 'success' }
    setTimeout(() => router.push(backTo), 800)
  } catch (e) {
    const errs = e.response?.data?.errors
    submitErrors.value = errs
      ? Object.entries(errs).map(([key, msgs]) => ({ key, text: msgs[0], step: stepFor(key) }))
      : [{ key: 'x', text: e.response?.data?.message || 'Submit failed.', step: 5 }]
  } finally {
    submitting.value = false
  }
}

const savedLabel = computed(() =>
  saving.value ? 'Saving…' : savedAt.value ? `Saved ${savedAt.value.toLocaleTimeString()}` : 'Changes save automatically')
</script>

<template>
  <div class="pa-6" style="max-width:1000px;margin:0 auto">
    <v-progress-linear v-if="loading" indeterminate />

    <template v-else-if="survey && form && options">
      <div class="d-flex align-center mb-4">
        <v-btn icon="mdi-arrow-left" variant="text" @click="router.push(backTo)" />
        <div class="ml-2">
          <h1 class="text-h5 font-weight-bold" style="color:#0B1F4B">
            Household {{ survey.household_number || '' }} — {{ survey.campaign?.name }}
          </h1>
          <span class="px-3 py-1 rounded-pill text-caption font-weight-medium"
            :style="{ background: surveyStatusMeta[survey.status].bg, color: surveyStatusMeta[survey.status].fg }">
            {{ surveyStatusMeta[survey.status].label }}
          </span>
        </div>
        <v-spacer />
        <span v-if="editable" class="text-caption text-medium-emphasis">
          <v-icon size="14" :class="{ 'mdi-spin': saving }">{{ saving ? 'mdi-loading' : 'mdi-cloud-check-outline' }}</v-icon> {{ savedLabel }}
        </span>
      </div>

      <v-alert v-if="survey.status === 'needs_revision' && survey.verification_notes" type="warning" variant="tonal" class="mb-4"
        title="Returned for correction">{{ survey.verification_notes }}</v-alert>
      <v-alert v-if="survey.status === 'approved'" type="success" variant="tonal" class="mb-4">
        Approved by {{ survey.verifier?.name }}.<span v-if="survey.verification_notes"> Notes: {{ survey.verification_notes }}</span>
      </v-alert>

      <!-- Read-only mode -->
      <template v-if="!editable">
        <v-card rounded="xl" border elevation="0" class="pa-5"><SurveySummary :survey="survey" /></v-card>
        <SurveyVerification v-if="isVerifier && survey.status === 'for_verification'" :survey="survey"
          @done="router.push(backTo)" />
      </template>

      <!-- Edit mode -->
      <v-stepper v-else v-model="step" :items="steps" hide-actions flat class="rounded-xl border">
        <!-- A -->
        <template #item.1>
          <h3 class="text-subtitle-1 font-weight-bold mb-4">Section A – Household information</h3>
          <v-row dense>
            <v-col cols="12" sm="4"><v-text-field v-model="form.household_number" label="Household number" variant="outlined" density="comfortable" /></v-col>
            <v-col cols="12" sm="8"><v-text-field v-model="form.head_name" label="Household head (full name)" variant="outlined" density="comfortable" /></v-col>
            <v-col cols="12" sm="8"><v-text-field v-model="form.address" label="Complete address" variant="outlined" density="comfortable" /></v-col>
            <v-col cols="12" sm="4"><v-select v-model="form.purok" :items="options.puroks" label="Purok / Sitio" variant="outlined" density="comfortable" /></v-col>
            <v-col cols="12" sm="6"><v-select v-model="form.housing_type" :items="options.housing_type" label="Housing type" variant="outlined" density="comfortable" /></v-col>
            <v-col cols="12" sm="6"><v-select v-model="form.ownership_status" :items="options.ownership_status" label="Ownership status" variant="outlined" density="comfortable" /></v-col>
          </v-row>
        </template>

        <!-- B -->
        <template #item.2>
          <div class="d-flex align-center mb-4">
            <h3 class="text-subtitle-1 font-weight-bold">Section B – Family members</h3>
            <v-spacer />
            <v-btn color="#0B1F4B" size="small" prepend-icon="mdi-plus" @click="addMember">Add member</v-btn>
          </div>
          <div v-if="!form.members.length" class="text-center text-medium-emphasis pa-6">
            No members yet. Start by adding the household head.
          </div>
          <v-list v-else lines="two">
            <v-list-item v-for="m in form.members" :key="m.client_key" class="border rounded-lg mb-2">
              <v-list-item-title class="font-weight-medium">
                {{ memberName(m) }}
                <v-chip v-if="m.is_new_resident" size="x-small" color="info" class="ml-1">New resident</v-chip>
                <v-chip v-else-if="m.resident_id" size="x-small" class="ml-1">Linked</v-chip>
              </v-list-item-title>
              <v-list-item-subtitle>{{ label('relationship', m.relationship) }} · {{ label('sex', m.sex) }} · {{ m.birthdate || '—' }}</v-list-item-subtitle>
              <template #append>
                <v-btn icon="mdi-pencil-outline" size="small" variant="text" @click="editMember(m)" />
                <v-btn icon="mdi-delete-outline" size="small" variant="text" color="error" @click="removeMember(m)" />
              </template>
            </v-list-item>
          </v-list>
        </template>

        <!-- C -->
        <template #item.3>
          <h3 class="text-subtitle-1 font-weight-bold mb-4">Section C – Demographic information</h3>
          <div v-if="!form.members.length" class="text-medium-emphasis">Add family members in Section B first.</div>
          <v-expansion-panels v-else variant="accordion" multiple>
            <v-expansion-panel v-for="m in form.members" :key="m.client_key">
              <v-expansion-panel-title>{{ memberName(m) }} <span class="text-medium-emphasis ml-2">· {{ label('relationship', m.relationship) }}</span></v-expansion-panel-title>
              <v-expansion-panel-text>
                <v-row dense class="pt-2">
                  <v-col cols="12" sm="4"><v-select v-model="m.civil_status" :items="options.civil_status" label="Civil status" variant="outlined" density="comfortable" /></v-col>
                  <v-col cols="12" sm="4"><v-select v-model="m.education" :items="options.education" label="Educational attainment" variant="outlined" density="comfortable" /></v-col>
                  <v-col cols="12" sm="4"><v-select v-model="m.employment_status" :items="options.employment_status" label="Employment status" variant="outlined" density="comfortable" /></v-col>
                  <v-col cols="12" sm="6"><v-text-field v-model="m.occupation" label="Occupation" :disabled="['unemployed','student','retired','homemaker'].includes(m.employment_status)" variant="outlined" density="comfortable" /></v-col>
                  <v-col cols="12" sm="6"><v-text-field v-model="m.contact_number" label="Contact number (optional)" variant="outlined" density="comfortable" /></v-col>
                  <v-col cols="6"><v-checkbox v-model="m.is_pwd" label="Person with disability" hide-details density="compact" /></v-col>
                  <v-col cols="6"><v-checkbox v-model="m.is_registered_voter" label="Registered voter" hide-details density="compact" /></v-col>
                </v-row>
              </v-expansion-panel-text>
            </v-expansion-panel>
          </v-expansion-panels>
        </template>

        <!-- D -->
        <template #item.4>
          <h3 class="text-subtitle-1 font-weight-bold mb-4">Section D – Additional information</h3>
          <v-row dense>
            <v-col cols="12" sm="6"><v-select v-model="form.housing_condition" :items="options.housing_condition" label="Housing condition" variant="outlined" density="comfortable" /></v-col>
            <v-col cols="12" sm="6"><v-select v-model="form.water_source" :items="options.water_source" label="Water source" variant="outlined" density="comfortable" /></v-col>
            <v-col cols="12" sm="6"><v-select v-model="form.sanitation" :items="options.sanitation" label="Sanitation facility" variant="outlined" density="comfortable" /></v-col>
            <v-col cols="12" sm="6">
              <v-select v-model="form.has_electricity" :items="[{ title: 'Yes', value: true }, { title: 'No', value: false }]" label="Electricity available?" variant="outlined" density="comfortable" />
            </v-col>
            <v-col v-for="q in options.extra_questions" :key="q.key" cols="12" :sm="q.type === 'text' ? 12 : 6">
              <v-select v-if="q.type === 'yesno'" v-model="form.extra_answers[q.key]" :items="[{ title: 'Yes', value: true }, { title: 'No', value: false }]" :label="q.label" variant="outlined" density="comfortable" />
              <v-textarea v-else v-model="form.extra_answers[q.key]" :label="q.label" rows="2" auto-grow variant="outlined" density="comfortable" />
            </v-col>
          </v-row>
        </template>

        <!-- Review -->
        <template #item.5>
          <h3 class="text-subtitle-1 font-weight-bold mb-4">Review answers</h3>
          <v-alert v-if="submitErrors.length" type="error" variant="tonal" class="mb-4" title="Please fix the following before submitting">
            <div v-for="e in submitErrors" :key="e.key + e.text">
              <a href="#" @click.prevent="step = e.step">{{ e.text }}</a>
            </div>
          </v-alert>
          <SurveySummary :survey="{ ...survey, ...form }" />
        </template>

        <div class="d-flex pa-5 border-t">
          <v-btn v-if="step > 1" variant="text" @click="step--">Back</v-btn>
          <v-spacer />
          <v-btn v-if="step < 5" color="#0B1F4B" variant="flat" @click="step++">Next</v-btn>
          <v-btn v-else color="#C9A227" variant="flat" :loading="submitting" @click="submit">Submit for verification</v-btn>
        </div>
      </v-stepper>

      <SurveyMemberDialog v-model="memberDialog" :member="editingMember" :options="options"
        :has-head="hasHead" :default-relationship="form.members.length ? null : 'head'" @save="onMemberSave" />
    </template>

    <v-snackbar v-model="toast.show" :color="toast.color" timeout="3000">{{ toast.text }}</v-snackbar>
  </div>
</template>