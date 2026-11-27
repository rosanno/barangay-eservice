<script setup>
import { ref, computed, watch, onMounted, unref } from 'vue'
import { useRouter } from 'vue-router'
import { getVerificationQueue } from '@/api/householdSurveyApi'
import { useSurveyOptions, surveyStatusMeta } from '@/composables/useSurveyOptions'
import StatCard from '@/components/dashboard/StatCard.vue'

const router = useRouter()
const { options, load: loadOptions } = useSurveyOptions()

// `cls` maps onto the same status-chip colors the other admin pages use.
const STATUS_CLS = {
  for_verification: 'status-pending',
  needs_revision: 'status-processing',
  approved: 'status-approved',
}

const tableColumns = ['Household No.', 'Head of Household', 'Purok', 'Campaign', 'Enumerator', 'Members', 'Status', 'Submitted', '']

const AVATAR_PALETTE = [
  { bg: '#e8f0fe', text: '#1a5fd8' },
  { bg: '#e8f5e9', text: '#27ae60' },
  { bg: '#fff3e0', text: '#e67e22' },
  { bg: '#fff9ed', text: '#a07020' },
]

const PER_PAGE = 10

const rows = ref([])
const total = ref(0)
const lastPage = ref(1)
const page = ref(1)
const counts = ref({ for_verification: 0, needs_revision: 0, approved: 0 })
const countsLoaded = ref(false)
const loading = ref(false)
const status = ref('for_verification')
const search = ref('')
const campaignId = ref('')
const purok = ref('')
const toast = ref({ show: false, text: '' })

const tabs = computed(() => [
  { value: 'for_verification', label: 'For verification', count: counts.value.for_verification },
  { value: 'needs_revision', label: 'Returned', count: counts.value.needs_revision },
  { value: 'approved', label: 'Approved', count: counts.value.approved },
  { value: 'all', label: 'All' },
])

const campaignOptions = computed(() => [
  { id: '', name: 'All campaigns' },
  ...(unref(options)?.campaigns ?? []),
])

const purokOptions = computed(() => [
  { title: 'All puroks', value: '' },
  ...(unref(options)?.puroks ?? []).map((p) => (typeof p === 'object' ? p : { title: p, value: p })),
])

const statCards = computed(() => {
  const ready = countsLoaded.value
  const num = (v) => (ready ? (v ?? 0).toLocaleString() : '')
  const c = counts.value
  return [
    { label: 'For Verification', value: num(c.for_verification), accentColor: '#f5a623', delta: 'Waiting for review', deltaIcon: 'mdi-clock-outline', deltaColor: '#e67e22', loading: !ready },
    { label: 'Returned', value: num(c.needs_revision), accentColor: '#2e86de', delta: 'Needs revision', deltaIcon: 'mdi-pencil-outline', deltaColor: '#888', loading: !ready },
    { label: 'Approved', value: num(c.approved), accentColor: '#27ae60', delta: 'Review complete', deltaIcon: 'mdi-check-circle-outline', deltaColor: '#888', loading: !ready },
  ]
})

const fmtDate = (d) => (d ? new Date(d).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }) : '—')
const initials = (name) => (name ?? '').split(' ').filter(Boolean).slice(0, 2).map((p) => p[0]).join('').toUpperCase()

const goSurvey = (row) => router.push(`/admin/census/verification/${row.id}`)

async function load() {
  loading.value = true
  try {
    const res = await getVerificationQueue({
      status: status.value, search: search.value || undefined,
      campaign_id: campaignId.value || undefined, purok: purok.value || undefined,
      page: page.value, per_page: PER_PAGE,
    })
    rows.value = res.surveys.data
    total.value = res.surveys.total
    lastPage.value = res.surveys.last_page ?? Math.max(1, Math.ceil(res.surveys.total / PER_PAGE))
    counts.value = res.counts
    countsLoaded.value = true
    // The last row on this page may have just left the queue.
    if (page.value > lastPage.value) page.value = lastPage.value
  } catch {
    rows.value = []
    total.value = 0
    lastPage.value = 1
    toast.value = { show: true, text: 'Failed to load surveys.' }
  } finally {
    loading.value = false
  }
}

// Go back to page 1 on filter changes. The page watcher loads if the page changes.
function reload() {
  if (page.value === 1) load()
  else page.value = 1
}

let timer
watch(search, () => { clearTimeout(timer); timer = setTimeout(reload, 350) })
watch([status, campaignId, purok], reload)
watch(page, load)
onMounted(() => { loadOptions(); load() })
</script>

<template>
  <div>
    <!-- ─── Header + filters ─────────────────────────────────────── -->
    <div class="d-flex flex-wrap align-center justify-space-between mb-4 ga-3">
      <div>
        <div class="d-flex align-center">
          <v-icon icon="mdi-clipboard-check-outline" size="16" style="color: #f5a623; margin-right: 7px" />
          <span style="font-size: 14px; font-weight: 600; color: #1a1a1a">Census Verification</span>
        </div>
        <p style="font-size: 11px; color: #aaa; margin: 2px 0 0 23px">{{ counts.for_verification }} surveys waiting for review</p>
      </div>

      <div class="d-flex flex-wrap ga-2">
        <v-text-field
          v-model="search"
          density="compact"
          variant="outlined"
          placeholder="Search household no. or head"
          prepend-inner-icon="mdi-magnify"
          hide-details
          clearable
          class="filter-input"
          style="width: 270px"
        />
        <v-select
          v-model="campaignId"
          :items="campaignOptions"
          item-title="name"
          item-value="id"
          density="compact"
          variant="outlined"
          hide-details
          class="filter-input"
          style="width: 200px"
          aria-label="Campaign"
          :menu-props="{ contentClass: 'filter-select-menu' }"
        />
        <v-select
          v-model="purok"
          :items="purokOptions"
          density="compact"
          variant="outlined"
          hide-details
          class="filter-input"
          style="width: 150px"
          aria-label="Purok"
          :menu-props="{ contentClass: 'filter-select-menu' }"
        />
      </div>
    </div>

    <!-- ─── Review summary ───────────────────────────────────────── -->
    <v-row v-if="countsLoaded || loading" class="mb-2">
      <v-col v-for="c in statCards" :key="c.label" cols="12" sm="4">
        <StatCard v-bind="c" />
      </v-col>
    </v-row>

    <!-- ─── Table ────────────────────────────────────────────────── -->
    <v-card variant="flat" style="background: #ffffff; border-radius: 10px; box-shadow: none; margin-top: 30px">
      <v-card-text class="px-5 pt-3 pb-5">
        <v-tabs v-model="status" density="compact" color="#0f1e3d" slider-color="#f5a623" class="status-tabs mb-4">
          <v-tab v-for="tab in tabs" :key="tab.value" :value="tab.value" class="status-tab">
            {{ tab.label }}
            <span
              v-if="tab.count !== undefined"
              class="status-chip ml-2"
              :class="tab.value === 'for_verification' && tab.count ? 'status-pending' : 'status-inactive'"
            >
              {{ tab.count }}
            </span>
          </v-tab>
        </v-tabs>

        <div v-if="loading" class="d-flex justify-center py-10">
          <v-progress-circular indeterminate size="24" color="#0f1e3d" />
        </div>

        <p v-else-if="!rows.length" style="font-size: 13px; color: #999; padding: 32px 20px">
          {{ status === 'for_verification' && !search && !campaignId && !purok ? 'Nothing waiting for review. 🎉' : 'No surveys found.' }}
        </p>

        <v-table v-else density="comfortable">
          <thead>
            <tr>
              <th
                v-for="col in tableColumns"
                :key="col || 'actions'"
                class="table-head"
                :class="{ 'table-head--actions': !col, 'table-head--center': col === 'Members' }"
              >
                {{ col }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, idx) in rows" :key="row.id" class="table-row">
              <td style="font-size: 12px; font-weight: 600; color: #0f1e3d; padding: 12px 8px; white-space: nowrap">
                {{ row.household_number }}
              </td>
              <td style="padding: 12px 8px">
                <div v-if="row.head_name" class="d-flex align-center ga-2">
                  <v-avatar size="26" :style="{ background: AVATAR_PALETTE[idx % AVATAR_PALETTE.length].bg }">
                    <span
                      style="font-size: 10px; font-weight: 600"
                      :style="{ color: AVATAR_PALETTE[idx % AVATAR_PALETTE.length].text }"
                    >
                      {{ initials(row.head_name) }}
                    </span>
                  </v-avatar>
                  <span style="font-size: 13px; font-weight: 500; color: #1a1a1a">{{ row.head_name }}</span>
                </div>
                <span v-else style="font-size: 12px; color: #999">—</span>
              </td>
              <td style="font-size: 12px; color: #666; padding: 12px 8px">{{ row.purok }}</td>
              <td style="font-size: 12px; color: #666; padding: 12px 8px">{{ row.campaign }}</td>
              <td style="font-size: 12px; color: #666; padding: 12px 8px">{{ row.enumerator }}</td>
              <td style="font-size: 12px; color: #1a1a1a; padding: 12px 8px; text-align: center">
                {{ row.members_count }}
              </td>
              <td style="padding: 12px 8px; white-space: nowrap">
                <span class="status-chip" :class="STATUS_CLS[row.status] ?? 'status-inactive'">
                  {{ surveyStatusMeta[row.status]?.label ?? row.status }}
                </span>
                <v-tooltip v-if="row.revision_count" text="Returned before" location="top">
                  <template #activator="{ props }">
                    <v-icon v-bind="props" icon="mdi-history" size="14" class="ml-1" style="color: #8a8f98" aria-label="Returned before" />
                  </template>
                </v-tooltip>
              </td>
              <td style="font-size: 11px; color: #aaa; padding: 12px 8px; white-space: nowrap">
                {{ fmtDate(row.submitted_at) }}
              </td>
              <td class="actions-cell">
                <div class="row-actions">
                  <v-tooltip :text="row.status === 'for_verification' ? 'Review survey' : 'View survey'" location="top">
                    <template #activator="{ props }">
                      <v-btn
                        v-bind="props"
                        :icon="row.status === 'for_verification' ? 'mdi-clipboard-check-outline' : 'mdi-eye-outline'"
                        variant="text"
                        density="comfortable"
                        class="action-btn"
                        :aria-label="row.status === 'for_verification' ? 'Review survey' : 'View survey'"
                        @click="goSurvey(row)"
                      />
                    </template>
                  </v-tooltip>
                </div>
              </td>
            </tr>
          </tbody>
        </v-table>

        <div v-if="lastPage > 1" class="d-flex justify-center py-4">
          <v-pagination v-model="page" :length="lastPage" density="compact" :total-visible="6" />
        </div>
      </v-card-text>
    </v-card>

    <v-snackbar v-model="toast.show" color="error" timeout="3000">{{ toast.text }}</v-snackbar>
  </div>
</template>

<style scoped>
/* Status chips: same colors as the other admin pages */
.status-chip { font-size: 10px; font-weight: 600; border-radius: 20px; padding: 2px 10px; display: inline-block; white-space: nowrap; }
.status-pending { background: #fff3e0; color: #a05e00; }
.status-approved { background: #e8f5e9; color: #1b6e34; }
.status-processing { background: #e8f0fe; color: #1a5fd8; }
.status-inactive { background: #f3f4f6; color: #6b7280; }

/* Tabs */
.status-tabs { border-bottom: 1px solid #f0ede3; }
.status-tab { font-size: 12.5px; text-transform: none; letter-spacing: 0; }

/* Table */
.table-head { font-size: 11px; font-weight: 500; color: #999; border-bottom: 1px solid #f0ede3; padding: 0 8px 8px; text-transform: none; letter-spacing: 0; text-align: left; }
.table-head--center { text-align: center; }
.table-head--actions { width: 56px; }
.table-row td { vertical-align: middle; border-bottom: 1px solid #f7f5f0; }
.table-row:last-child td { border-bottom: none; }
.table-row:hover { background: #fafbfc; }

.filter-input :deep(.v-field__input),
.filter-input :deep(input),
.filter-input :deep(.v-select__selection-text) { font-size: 12.5px; }
.filter-input :deep(.v-field__input) { min-height: 36px; }

/* Row actions */
.actions-cell { padding: 8px 5px; width: 56px; }
.row-actions { display: flex; align-items: center; justify-content: flex-end; gap: 2px; }
.action-btn { width: 32px; height: 32px; border-radius: 8px; color: #8a8f98; transition: background .15s, color .15s; }
.action-btn .v-icon { font-size: 18px; }
.action-btn:hover { background: #f1f3f7; color: #0f1e3d; }
</style>

<style>
.filter-select-menu .v-list-item-title { font-size: 12.5px; }
.filter-select-menu .v-list-item { min-height: 34px; }
</style>