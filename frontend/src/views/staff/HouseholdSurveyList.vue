<script setup>
import { ref, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/store/auth'
import { getSurveyTargets, startSurvey } from '@/api/householdSurveyApi'
import { useSurveyOptions, surveyStatusMeta } from '@/composables/useSurveyOptions'

const router = useRouter()
const auth = useAuthStore()
const { options, load: loadOptions } = useSurveyOptions()

const rows = ref([])
const total = ref(0)
const loading = ref(false)
const search = ref('')
const campaignId = ref(null)
const purok = ref(null)
const status = ref(null)
const starting = ref(null)
const toast = ref({ show: false, text: '', color: 'error' })

const statusItems = Object.entries(surveyStatusMeta).map(([value, m]) => ({ value, title: m.label }))
const isVerifier = () => auth.user?.role === 'admin'

async function load() {
  loading.value = true
  try {
    const res = await getSurveyTargets({
      search: search.value || undefined, campaign_id: campaignId.value || undefined,
      purok: purok.value || undefined, status: status.value || undefined, per_page: 50,
    })
    rows.value = res.data
    total.value = res.total
  } catch {
    toast.value = { show: true, text: 'Failed to load households.', color: 'error' }
  } finally {
    loading.value = false
  }
}

let t
watch(search, () => { clearTimeout(t); t = setTimeout(load, 300) })
watch([campaignId, purok, status], load)
onMounted(() => { loadOptions(); load() })

// What button does each row get?
function action(r) {
  const s = r.survey
  const mine = s && s.enumerator_id === auth.user?.id
  if (!s) return r.campaign.status === 'active' ? { label: 'Start Survey', color: '#0B1F4B', variant: 'flat' } : null
  if (mine && s.status === 'draft') return { label: 'Continue', color: '#0B1F4B', variant: 'flat' }
  if (mine && s.status === 'needs_revision') return { label: 'Revise', color: '#B91C1C', variant: 'flat' }
  if (isVerifier() && s.status === 'for_verification') return { label: 'Review', color: '#0B1F4B', variant: 'flat' }
  if (mine || isVerifier()) return { label: 'View', color: '#0B1F4B', variant: 'outlined' }
  return null
}

async function open(r) {
  try {
    if (!r.survey) {
      starting.value = r.id
      const s = await startSurvey(r.id)
      return router.push(`/staff/census-surveys/${s.id}`)
    }
    router.push(`/staff/census-surveys/${r.survey.id}`)
  } catch (e) {
    toast.value = { show: true, text: e.response?.data?.message || 'Could not open survey.', color: 'error' }
  } finally {
    starting.value = null
  }
}
</script>

<template>
  <div class="pa-6">
    <div class="mb-5">
      <h1 class="text-h5 font-weight-bold" style="color:#0B1F4B">Household Survey</h1>
      <div class="text-body-2 text-medium-emphasis">{{ total }} households</div>
    </div>

    <div class="d-flex ga-3 mb-4 flex-wrap">
      <v-text-field v-model="search" placeholder="Search household no., head or address..."
        prepend-inner-icon="mdi-magnify" variant="outlined" density="compact" hide-details
        style="max-width:340px" bg-color="white" />
      <v-select v-model="campaignId" :items="options?.campaigns" item-title="name" item-value="id"
        label="Campaign" clearable variant="outlined" density="compact" hide-details style="max-width:240px" bg-color="white" />
      <v-select v-model="purok" :items="options?.puroks" label="Purok" clearable variant="outlined"
        density="compact" hide-details style="max-width:160px" bg-color="white" />
      <v-select v-model="status" :items="statusItems" label="Survey status" clearable variant="outlined"
        density="compact" hide-details style="max-width:200px" bg-color="white" />
    </div>

    <v-card rounded="xl" elevation="0" border>
      <v-table>
        <thead>
          <tr><th>Household</th><th>Purok</th><th>Campaign</th><th>Enumerator</th><th>Status</th><th class="text-right">Action</th></tr>
        </thead>
        <tbody>
          <tr v-if="loading"><td colspan="6" class="text-center pa-8"><v-progress-circular indeterminate size="28" /></td></tr>
          <tr v-else-if="!rows.length"><td colspan="6" class="text-center pa-8 text-medium-emphasis">No households found.</td></tr>
          <tr v-for="r in rows" v-else :key="r.id">
            <td>
              <div class="font-weight-medium">{{ r.survey?.head_name || `Household ${r.household.household_number ?? ''}` }}</div>
              <div class="text-caption text-medium-emphasis">#{{ r.household.household_number }} · {{ r.household.address }}</div>
            </td>
            <td>{{ r.household.purok }}</td>
            <td>{{ r.campaign.name }}</td>
            <td>{{ r.survey?.enumerator?.name || '—' }}</td>
            <td>
              <span class="px-3 py-1 rounded-pill text-caption font-weight-medium"
                :style="{ background: surveyStatusMeta[r.status].bg, color: surveyStatusMeta[r.status].fg }">
                {{ surveyStatusMeta[r.status].label }}
              </span>
              <v-icon v-if="r.survey?.revision_count" size="14" class="ml-1" title="Returned before">mdi-history</v-icon>
            </td>
            <td class="text-right">
              <v-btn v-if="action(r)" size="small" :color="action(r).color" :variant="action(r).variant"
                :loading="starting === r.id" @click="open(r)">{{ action(r).label }}</v-btn>
            </td>
          </tr>
        </tbody>
      </v-table>
    </v-card>

    <v-snackbar v-model="toast.show" :color="toast.color" timeout="3000">{{ toast.text }}</v-snackbar>
  </div>
</template>