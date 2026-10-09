<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { STATUS, DOCUMENT_TYPES, formatDate } from '@/utils/requestStatus'
import { getMyRequests, cancelRequest } from '@/api/residentRequest'

const router = useRouter()
const requests = ref([])
const loading = ref(true)
const error = ref('')
const search = ref('')
const statusFilter = ref('all')
const typeFilter = ref('all')
const confirmCancel = ref({ open: false, item: null })
const cancelling = ref(false)

const typeTitle = (v) => DOCUMENT_TYPES.find((t) => t.value === v)?.title ?? v

const filtered = computed(() => {
  const q = search.value.trim().toLowerCase()
  return requests.value.filter((r) => {
    const matchStatus = statusFilter.value === 'all' || r.status === statusFilter.value
    const matchType = typeFilter.value === 'all' || r.document_type === typeFilter.value
    const matchText =
      !q ||
      r.reference_no?.toLowerCase().includes(q) ||
      typeTitle(r.document_type).toLowerCase().includes(q)
    return matchStatus && matchType && matchText
  })
})

const statusOptions = [
  { title: 'All statuses', value: 'all' },
  ...Object.entries(STATUS).map(([value, s]) => ({ title: s.label, value })),
]

const typeOptions = [
  { title: 'All document types', value: 'all' },
  ...DOCUMENT_TYPES.map((t) => ({ title: t.title, value: t.value })),
]

async function load() {
  loading.value = true
  error.value = ''
  try {
    requests.value = await getMyRequests()
  } catch (e) {
    error.value = e.response?.data?.message ?? 'We could not load your requests. Check your connection and try again.'
  } finally {
    loading.value = false
  }
}

async function doCancel() {
  cancelling.value = true
  try {
    await cancelRequest(confirmCancel.value.item.id)
    confirmCancel.value = { open: false, item: null }
    await load()
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Could not cancel this request.'
    confirmCancel.value.open = false
  } finally {
    cancelling.value = false
  }
}

const open = (r) => router.push(`/resident/requests/${r.id}`)

onMounted(load)
</script>

<template>
  <div class="page">
    <v-alert v-if="error" type="error" variant="tonal" class="mb-4" closable @click:close="error = ''">
      {{ error }}
    </v-alert>

    <!-- Toolbar: same structure as admin Document Requests -->
    <div class="toolbar">
      <div class="toolbar-title">
        <v-icon size="20" color="#E0A33A">mdi-file-document-outline</v-icon>
        <div>
          <div class="t">Requests</div>
          <div class="c">{{ requests.length }} total</div>
        </div>
      </div>

      <div class="toolbar-filters">
        <v-text-field
          v-model="search"
          class="f-search"
          placeholder="Search document or tracking #"
          prepend-inner-icon="mdi-magnify"
          density="compact"
          variant="outlined"
          hide-details
          clearable
        />
        <v-select
          v-model="statusFilter"
          class="f-select"
          :items="statusOptions"
          density="compact"
          variant="outlined"
          style="width: 170px"
          :menu-props="{ contentClass: 'filter-select-menu' }"
          hide-details
        />
        <v-select
          v-model="typeFilter"
          class="f-select f-type"
          :items="typeOptions"
          density="compact"
          variant="outlined"
          style="width: 200px"
          :menu-props="{ contentClass: 'filter-select-menu' }"
          hide-details
        />
      </div>
    </div>

    <div class="table-card">
      <v-progress-linear v-if="loading" indeterminate color="#C9A227" />

      <table v-if="!loading && filtered.length">
        <thead>
          <tr>
            <th>Document type</th>
            <th class="hide-sm">Tracking #</th>
            <th>Status</th>
            <th class="hide-sm">Date</th>
            <th class="actions-col"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in filtered" :key="r.id" @click="open(r)">
            <td>
              <div class="cell-main">
                <span class="avatar"><v-icon size="16">mdi-file-document-outline</v-icon></span>
                <span class="name">{{ typeTitle(r.document_type) }}</span>
              </div>
            </td>
            <td class="muted hide-sm">{{ r.reference_no }}</td>
            <td>
              <span class="pill" :style="{ color: STATUS[r.status]?.color, background: STATUS[r.status]?.bg }">
                {{ STATUS[r.status]?.label ?? r.status }}
              </span>
            </td>
            <td class="muted hide-sm">{{ formatDate(r.created_at) }}</td>
            <td class="actions-col" @click.stop>
              <v-btn icon="mdi-eye-outline" size="small" variant="text" aria-label="View request" @click="open(r)" />
              <v-menu v-if="r.status === 'pending'">
                <template #activator="{ props }">
                  <v-btn v-bind="props" icon="mdi-dots-vertical" size="small" variant="text" aria-label="More actions" />
                </template>
                <v-list density="compact">
                  <v-list-item prepend-icon="mdi-eye-outline" title="View details" @click="open(r)" />
                  <v-list-item
                    prepend-icon="mdi-close-circle-outline"
                    title="Cancel request"
                    base-color="error"
                    @click="confirmCancel = { open: true, item: r }"
                  />
                </v-list>
              </v-menu>
              <span v-else class="kebab-spacer" />
            </td>
          </tr>
        </tbody>
      </table>

      <div v-else-if="!loading" class="empty">
        <v-icon size="44" color="#C9A227">mdi-file-document-outline</v-icon>
        <h3>{{ requests.length ? 'No requests match your filters' : 'You have no requests yet' }}</h3>
        <p>
          {{ requests.length ? 'Clear the search or choose another status.' : 'Request a clearance or certificate and track it here.' }}
        </p>
        <v-btn v-if="!requests.length" class="cta" @click="router.push('/resident/requests/new')">New request</v-btn>
      </div>
    </div>

    <v-dialog v-model="confirmCancel.open" max-width="420">
      <v-card rounded="lg">
        <v-card-title>Cancel this request?</v-card-title>
        <v-card-text>
          {{ confirmCancel.item?.reference_no }} will be cancelled. You can submit a new request anytime.
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="confirmCancel.open = false">Keep request</v-btn>
          <v-btn color="error" variant="flat" :loading="cancelling" @click="doCancel">Cancel request</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<style>
.filter-select-menu .v-list-item-title {
  font-size: 12.5px;
}
.filter-select-menu .v-list-item {
  min-height: 34px;
}
</style>

<style scoped>
.f-select :deep(.v-field__input),
.f-select :deep(input),
.f-select :deep(.v-select__selection-text),
.f-select :deep(.v-field__prepend-inner .v-icon) {
  font-size: 12.5px;
}

.f-select :deep(.v-field__input) {
  min-height: 36px;
}

.toolbar { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 20px; flex-wrap: wrap; }
.toolbar-title { display: flex; align-items: center; gap: 10px; }
.toolbar-title .t { font-weight: 700; font-size: 1.05rem; color: #0B1F44; line-height: 1.2; }
.toolbar-title .c { font-size: .8rem; color: #9CA3AF; }
.toolbar-filters { display: flex; gap: 12px; flex: 1 1 auto; justify-content: flex-end; flex-wrap: wrap; }
.f-search { flex: 0 1 380px; min-width: 220px; }
.f-select { flex: 0 0 200px; }
.f-type { flex-basis: 230px; }

.table-card { background: #fff; border-radius: 16px; padding: 12px 20px 8px; overflow: hidden; }
table { width: 100%; border-collapse: collapse; }
th { text-align: left; font-size: .8rem; font-weight: 500; color: #9CA3AF; padding: 18px 12px; border-bottom: 1px solid #ECEAE4; }
td { padding: 14px 12px; border-bottom: 1px solid #F1EFEA; font-size: .92rem; color: #1F2937; }
tbody tr { cursor: pointer; }
tbody tr:hover { background: #FAF8F3; }
tbody tr:last-child td { border-bottom: 0; }

.cell-main { display: flex; align-items: center; gap: 12px; }
.avatar { width: 36px; height: 36px; border-radius: 50%; background: #FCEFD2; color: #B7791F; display: inline-flex; align-items: center; justify-content: center; flex: none; }
.name { font-weight: 500; color: #111827; }
.muted { color: #6B7280; }
.pill { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: .78rem; font-weight: 600; white-space: nowrap; }
.actions-col { width: 96px; text-align: right; white-space: nowrap; }
.kebab-spacer { display: inline-block; width: 28px; }

.cta { background: #0B1F44 !important; color: #E8C45A !important; text-transform: none; letter-spacing: 0; font-weight: 600; }
.empty { text-align: center; padding: 56px 16px; }
.empty h3 { margin-top: 12px; color: #0B1F44; }
.empty p { color: #6B7280; margin: 4px 0 16px; }

@media (max-width: 640px) {
  .page { padding: 20px 16px 40px; }
  .hide-sm { display: none; }
  .toolbar-filters { justify-content: stretch; }
  .f-search, .f-select { flex: 1 1 100%; }
}
</style>