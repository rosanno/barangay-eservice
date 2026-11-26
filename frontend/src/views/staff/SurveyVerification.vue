<script setup>
import { ref, onMounted } from 'vue'
import { getSurveyComparison, verifySurvey } from '@/services/householdSurveyService'

const props = defineProps({ survey: { type: Object, required: true } })
const emit = defineEmits(['done'])

const rows = ref([])
const loading = ref(true)
const notes = ref('')
const busy = ref(null)
const error = ref('')

const meta = {
  match:          { text: 'Matches record',   color: 'success' },
  differences:    { text: 'Differs from record', color: 'warning' },
  possible_match: { text: 'Possible match',   color: 'info' },
  new:            { text: 'New resident',     color: 'default' },
}
const fieldLabel = (f) => f.replace('_', ' ')

onMounted(async () => {
  rows.value = await getSurveyComparison(props.survey.id).catch(() => [])
  loading.value = false
})

async function decide(action) {
  error.value = ''
  if (action === 'return' && !notes.value.trim()) return (error.value = 'Add a note explaining what needs correction.')
  busy.value = action
  try {
    emit('done', await verifySurvey(props.survey.id, { action, notes: notes.value || null }))
  } catch (e) {
    error.value = e.response?.data?.errors?.notes?.[0] || e.response?.data?.message || 'Action failed.'
  } finally {
    busy.value = null
  }
}
</script>

<template>
  <v-card rounded="xl" border elevation="0" class="pa-5 mt-6">
    <h3 class="text-subtitle-1 font-weight-bold mb-3">Verification</h3>

    <v-progress-linear v-if="loading" indeterminate />
    <v-list v-else lines="two" density="compact" class="mb-4">
      <v-list-item v-for="r in rows" :key="r.client_key">
        <v-list-item-title>{{ r.name }}</v-list-item-title>
        <v-list-item-subtitle>
          <template v-if="r.result === 'differences'">
            <span v-for="d in r.differences" :key="d.field" class="mr-3">
              {{ fieldLabel(d.field) }}: survey “{{ d.survey || '—' }}” vs record “{{ d.record || '—' }}”
            </span>
          </template>
          <template v-else-if="r.result === 'possible_match'">
            Possible existing record: {{ r.possible.map((p) => `#${p.id} ${p.last_name}, ${p.first_name}`).join('; ') }}
          </template>
        </v-list-item-subtitle>
        <template #append><v-chip size="small" :color="meta[r.result].color">{{ meta[r.result].text }}</v-chip></template>
      </v-list-item>
    </v-list>

    <v-textarea v-model="notes" label="Verification notes" rows="2" auto-grow variant="outlined"
      hint="Required when returning the survey for correction." persistent-hint />
    <v-alert v-if="error" type="error" variant="tonal" density="compact" class="mt-3">{{ error }}</v-alert>

    <div class="d-flex ga-3 mt-4 justify-end">
      <v-btn color="error" variant="outlined" :loading="busy === 'return'" :disabled="!!busy" @click="decide('return')">Return for correction</v-btn>
      <v-btn color="success" variant="flat" :loading="busy === 'approve'" :disabled="!!busy" @click="decide('approve')">Approve survey</v-btn>
    </div>
  </v-card>
</template>