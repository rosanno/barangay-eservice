<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import {
  getAppointmentReport,
  getAppointmentSummary,
  exportAppointmentReport,
} from '@/api/adminAppointmentReport'
import StatCard from '@/components/dashboard/StatCard.vue'

// `cls` maps onto the same status-chip colors the other admin pages use.
const STATUS = {
  scheduled: { label: 'Scheduled', cls: 'status-processing' },
  pending:   { label: 'Pending',   cls: 'status-pending' },
  confirmed: { label: 'Confirmed', cls: 'status-processing' },
  completed: { label: 'Completed', cls: 'status-approved' },
  cancelled: { label: 'Cancelled', cls: 'status-inactive' },
  no_show:   { label: 'No-show',   cls: 'status-rejected' },
}
// Unknown statuses still render: a gray pill with a readable label.
const titleCase = (v) => String(v ?? '').replace(/_/g, ' ').replace(/^\w/, (c) => c.toUpperCase())
const statusLabel = (v) => STATUS[v]?.label ?? titleCase(v)
const statusClass = (v) => STATUS[v]?.cls ?? 'status-inactive'

const tableColumns = ['Resident', 'Purpose', 'Date', 'Time', 'Status', '']

const AVATAR_PALETTE = [
  { bg: '#e8f0fe', text: '#1a5fd8' },
  { bg: '#e8f5e9', text: '#27ae60' },
  { bg: '#fff3e0', text: '#e67e22' },
  { bg: '#fff9ed', text: '#a07020' },
]
const initials = (name) => (name ?? '').split(' ').filter(Boolean).slice(0, 2).map((p) => p[0]).join('').toUpperCase()

const filters = reactive({ search: '', status: null, purpose: null, from: null, to: null })
const page = ref(1)
const perPage = 10

const items = ref([])
const total = ref(0)
const lastPage = ref(1)
const loading = ref(false)
const error = ref('')
const exporting = ref(false)

const summary = ref({
  total: 0,
  by_status: {},
  completion_rate: 0,
  no_show_rate: 0,
  daily: [],
  by_purpose: [],
  peak_hour: null,
  purposes: [],
  statuses: [],
})

const dialog = ref(false)
const selected = ref(null)
const snackbar = reactive({ show: false, text: '' })

const hasFilters = computed(() => Object.values(filters).some(Boolean))

const buildParams = () =>
  Object.fromEntries(Object.entries(filters).filter(([, v]) => v !== null && v !== ''))

async function fetchTable() {
  loading.value = true
  error.value = ''
  try {
    const data = await getAppointmentReport({ ...buildParams(), page: page.value, per_page: perPage })
    items.value = data.items
    total.value = data.total
    lastPage.value = data.last_page
  } catch (e) {
    error.value = "Couldn't load appointments. Check your connection and try again."
  } finally {
    loading.value = false
  }
}

async function fetchSummary() {
  try {
    summary.value = await getAppointmentSummary(buildParams())
  } catch (e) {
    // The table has its own error state; stat cards just stay at their last values.
  }
}

function refreshAll() {
  fetchSummary()
  fetchTable()
}

let timer
watch(
  filters,
  () => {
    clearTimeout(timer)
    timer = setTimeout(() => {
      fetchSummary()
      if (page.value === 1) fetchTable()
      else page.value = 1 // the page watcher below reloads the table
    }, 300)
  },
  { deep: true },
)
watch(page, fetchTable)

onMounted(refreshAll)

function clearFilters() {
  Object.assign(filters, { search: '', status: null, purpose: null, from: null, to: null })
}

async function exportCsv() {
  exporting.value = true
  try {
    const blob = await exportAppointmentReport(buildParams())
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `appointment-report-${new Date().toISOString().slice(0, 10)}.csv`
    a.click()
    URL.revokeObjectURL(url)
  } catch (e) {
    notify("Couldn't export the report. Try again.")
  } finally {
    exporting.value = false
  }
}

function openDetails(row) {
  selected.value = row
  dialog.value = true
}

async function copyReference(row) {
  try {
    await navigator.clipboard.writeText(`APT-${row.id}`)
    notify('Reference copied')
  } catch (e) {
    notify("Couldn't copy the reference")
  }
}

function notify(text) {
  snackbar.text = text
  snackbar.show = true
}

/* ---------- display helpers ---------- */
const statusItems = computed(() =>
  summary.value.statuses.map((value) => ({ title: statusLabel(value), value })),
)

const by = (key) => summary.value.by_status?.[key] ?? 0

// Shaped for the shared StatCard component.
const stats = computed(() => [
  { label: 'Total appointments', value: summary.value.total, accentColor: '#0f1e3d' },
  {
    label: 'Completed', value: by('completed'), accentColor: '#27ae60',
    delta: `${summary.value.completion_rate}% of resolved`, deltaIcon: 'mdi-check', deltaColor: '#27ae60',
  },
  {
    label: 'Upcoming', value: by('scheduled') + by('pending') + by('confirmed'), accentColor: '#2e86de',
    delta: 'Still to come', deltaIcon: 'mdi-calendar-outline', deltaColor: '#888',
  },
  { label: 'Cancelled', value: by('cancelled'), accentColor: '#8a8f98' },
  {
    label: 'No-shows', value: by('no_show'), accentColor: '#c0392b',
    delta: `${summary.value.no_show_rate}% of resolved`, deltaIcon: 'mdi-alert-circle-outline', deltaColor: '#c0392b',
  },
])

const trend = computed(() => summary.value.daily.slice(-30))
const trendMax = computed(() => Math.max(1, ...trend.value.map((d) => d.total)))
const purposeMax = computed(() => Math.max(1, ...summary.value.by_purpose.map((p) => p.total)))

// Accepts ISO strings or MySQL "YYYY-MM-DD HH:MM:SS" (Safari can't parse the latter directly).
const toDate = (d) => new Date(/^\d{4}-\d{2}-\d{2} \d/.test(d) ? d.replace(' ', 'T') : d)

const fmtDate = (d) =>
  d ? toDate(d).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'

const fmtClock = (d) =>
  d ? toDate(d).toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' }) : '—'

// "YYYY-MM-DD" from the summary: parse as a local date so the day never shifts.
const fmtShortDay = (d) =>
  new Date(`${d}T00:00:00`).toLocaleDateString('en-PH', { month: 'short', day: 'numeric' })

function fmtTime(t) {
  if (!t) return '—'
  const [h, m] = t.split(':').map(Number)
  const suffix = h >= 12 ? 'PM' : 'AM'
  return `${h % 12 || 12}:${String(m).padStart(2, '0')} ${suffix}`
}

const fmtHour = (h) => (h === null ? '—' : fmtTime(`${h}:00`).replace(':00', ''))
</script>

<template>
  <div>
    <!-- ─── Header ───────────────────────────────────────────────── -->
    <div class="d-flex flex-wrap align-center justify-space-between mb-4 ga-3">
      <div>
        <div class="d-flex align-center">
          <v-icon icon="mdi-calendar-check-outline" size="16" style="color: #f5a623; margin-right: 7px" />
          <span style="font-size: 14px; font-weight: 600; color: #1a1a1a">Appointment Report</span>
        </div>
        <p style="font-size: 11px; color: #aaa; margin: 2px 0 0 23px">
          {{ total }} {{ total === 1 ? 'appointment' : 'appointments' }}
        </p>
      </div>

      <v-btn
        style="background: #0f1e3d; color: #fff; text-transform: none"
        prepend-icon="mdi-download"
        :loading="exporting"
        :disabled="!total"
        @click="exportCsv"
      >
        Export CSV
      </v-btn>
    </div>

    <!-- ─── Filters ──────────────────────────────────────────────── -->
    <div class="d-flex flex-wrap ga-2 mb-4">
      <v-text-field
        v-model="filters.search"
        placeholder="Search resident or purpose"
        prepend-inner-icon="mdi-magnify"
        variant="outlined"
        density="compact"
        hide-details
        clearable
        class="filter-input"
        style="width: 250px"
      />
      <v-select
        v-model="filters.status"
        label="Status"
        :items="statusItems"
        variant="outlined"
        density="compact"
        hide-details
        clearable
        class="filter-input"
        style="width: 160px"
        :menu-props="{ contentClass: 'filter-select-menu' }"
      />
      <v-select
        v-model="filters.purpose"
        label="Purpose"
        :items="summary.purposes"
        variant="outlined"
        density="compact"
        hide-details
        clearable
        class="filter-input"
        style="width: 190px"
        :menu-props="{ contentClass: 'filter-select-menu' }"
      />
      <v-text-field
        v-model="filters.from"
        type="date"
        label="From"
        variant="outlined"
        density="compact"
        hide-details
        class="filter-input"
        style="width: 150px"
      />
      <v-text-field
        v-model="filters.to"
        type="date"
        label="To"
        variant="outlined"
        density="compact"
        hide-details
        class="filter-input"
        style="width: 150px"
      />
    </div>

    <!-- ─── Summary ──────────────────────────────────────────────── -->
    <v-row class="mb-2" aria-label="Summary">
      <v-col v-for="s in stats" :key="s.label" cols="6" sm="4" lg>
        <StatCard v-bind="s" />
      </v-col>
    </v-row>

    <!-- ─── Insights ─────────────────────────────────────────────── -->
    <v-row>
      <v-col cols="12" lg="7">
        <v-card variant="flat" class="chart-card">
          <div class="d-flex align-baseline justify-space-between">
            <p class="chart-title">Appointments per day</p>
            <span v-if="summary.peak_hour !== null" class="chart-meta">
              Busiest hour: {{ fmtHour(summary.peak_hour) }}
            </span>
          </div>

          <div v-if="trend.length" class="bars" role="img" aria-label="Appointments per day">
            <div
              v-for="d in trend"
              :key="d.day"
              class="bars__col"
              :title="`${fmtShortDay(d.day)}: ${d.total}`"
            >
              <span class="bars__bar" :style="{ height: (d.total / trendMax) * 100 + '%' }" />
            </div>
          </div>
          <p v-else class="empty-text">No appointments in this range.</p>

          <div v-if="trend.length" class="bars__axis">
            <span>{{ fmtShortDay(trend[0].day) }}</span>
            <span>{{ fmtShortDay(trend[trend.length - 1].day) }}</span>
          </div>
        </v-card>
      </v-col>

      <v-col cols="12" lg="5">
        <v-card variant="flat" class="chart-card">
          <p class="chart-title">By purpose</p>
          <p v-if="!summary.by_purpose.length" class="empty-text">No appointments in this range.</p>
          <div v-for="p in summary.by_purpose" :key="p.purpose" class="bar-row">
            <span class="bar-label">{{ p.purpose }}</span>
            <div class="bar-track">
              <div class="bar-fill" :style="{ width: (p.total / purposeMax) * 100 + '%' }" />
            </div>
            <span class="bar-count">{{ p.total }}</span>
          </div>
        </v-card>
      </v-col>
    </v-row>

    <!-- ─── Table ────────────────────────────────────────────────── -->
    <v-card variant="flat" class="mt-1" style="background: #ffffff; border-radius: 10px; box-shadow: none">
      <v-card-text class="px-5 pt-5 pb-5">
        <div v-if="error" class="state">
          <p>{{ error }}</p>
          <v-btn variant="outlined" style="text-transform: none" @click="fetchTable">Try again</v-btn>
        </div>

        <div v-else-if="loading" class="d-flex justify-center py-10">
          <v-progress-circular indeterminate size="24" color="#0f1e3d" />
        </div>

        <div v-else-if="!items.length" class="state">
          <p>No appointments match these filters.</p>
          <v-btn v-if="hasFilters" variant="text" size="small" style="text-transform: none" @click="clearFilters">
            Clear filters
          </v-btn>
        </div>

        <v-table v-else density="comfortable">
          <thead>
            <tr>
              <th
                v-for="col in tableColumns"
                :key="col || 'actions'"
                class="table-head"
                :class="{ 'table-head--actions': !col }"
              >
                {{ col }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, idx) in items" :key="row.id" class="table-row">
              <td style="padding: 12px 8px">
                <div v-if="row.user?.name" class="d-flex align-center ga-2">
                  <v-avatar size="26" :style="{ background: AVATAR_PALETTE[idx % AVATAR_PALETTE.length].bg }">
                    <span
                      style="font-size: 10px; font-weight: 600"
                      :style="{ color: AVATAR_PALETTE[idx % AVATAR_PALETTE.length].text }"
                    >
                      {{ initials(row.user.name) }}
                    </span>
                  </v-avatar>
                  <div>
                    <div style="font-size: 13px; font-weight: 500; color: #1a1a1a">{{ row.user.name }}</div>
                    <div style="font-size: 11px; color: #aaa">{{ row.user.email }}</div>
                  </div>
                </div>
                <span v-else style="font-size: 12px; color: #999">—</span>
              </td>
              <td style="font-size: 12px; color: #666; padding: 12px 8px">{{ row.purpose }}</td>
              <td style="font-size: 12px; color: #666; padding: 12px 8px; white-space: nowrap">{{ fmtDate(row.scheduled_at) }}</td>
              <td style="font-size: 12px; color: #666; padding: 12px 8px; white-space: nowrap">{{ fmtClock(row.scheduled_at) }}</td>
              <td style="padding: 12px 8px">
                <span class="status-chip" :class="statusClass(row.status)">{{ statusLabel(row.status) }}</span>
              </td>

              <td class="actions-cell">
                <div class="row-actions">
                  <v-tooltip text="View details" location="top">
                    <template #activator="{ props }">
                      <v-btn
                        v-bind="props"
                        icon="mdi-eye-outline"
                        variant="text"
                        density="comfortable"
                        class="action-btn"
                        aria-label="View appointment"
                        @click="openDetails(row)"
                      />
                    </template>
                  </v-tooltip>

                  <v-menu>
                    <template #activator="{ props }">
                      <v-btn
                        v-bind="props"
                        icon="mdi-dots-vertical"
                        variant="text"
                        density="comfortable"
                        class="action-btn"
                        aria-label="More actions"
                      />
                    </template>
                    <v-list density="compact">
                      <v-list-item prepend-icon="mdi-content-copy" title="Copy reference" @click="copyReference(row)" />
                    </v-list>
                  </v-menu>
                </div>
              </td>
            </tr>
          </tbody>
        </v-table>

        <div v-if="lastPage > 1 && !error" class="d-flex justify-center py-4">
          <v-pagination v-model="page" :length="lastPage" :total-visible="6" density="compact" />
        </div>
      </v-card-text>
    </v-card>

    <!-- ─── Details ──────────────────────────────────────────────── -->
    <v-dialog v-model="dialog" max-width="480">
      <v-card v-if="selected">
        <div class="dialog-head">
          <div>
            <span class="dialog-title">Appointment APT-{{ selected.id }}</span>
            <span class="status-chip ml-2" :class="statusClass(selected.status)">
              {{ statusLabel(selected.status) }}
            </span>
          </div>
          <v-btn icon="mdi-close" variant="text" density="comfortable" class="action-btn" aria-label="Close" @click="dialog = false" />
        </div>

        <v-card-text class="px-5 pb-2">
          <div class="fact-grid">
            <div class="fact"><span class="fact__label">Resident</span><span class="fact__value">{{ selected.user?.name ?? '—' }}</span></div>
            <div class="fact"><span class="fact__label">Email</span><span class="fact__value">{{ selected.user?.email ?? '—' }}</span></div>
            <div class="fact"><span class="fact__label">Purpose</span><span class="fact__value">{{ selected.purpose }}</span></div>
            <div class="fact"><span class="fact__label">Date</span><span class="fact__value">{{ fmtDate(selected.scheduled_at) }}</span></div>
            <div class="fact"><span class="fact__label">Time</span><span class="fact__value">{{ fmtClock(selected.scheduled_at) }}</span></div>
            <div class="fact"><span class="fact__label">Notes</span><span class="fact__value">{{ selected.notes || 'None' }}</span></div>
          </div>
        </v-card-text>

        <v-card-actions class="px-5 pb-5">
          <v-spacer />
          <v-btn variant="text" @click="dialog = false">Close</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar.show" :timeout="2500">{{ snackbar.text }}</v-snackbar>
  </div>
</template>

<style scoped>
/* Status chips: same colors as the other admin pages */
.status-chip { font-size: 10px; font-weight: 600; border-radius: 20px; padding: 2px 10px; display: inline-block; white-space: nowrap; }
.status-pending { background: #fff3e0; color: #a05e00; }
.status-approved { background: #e8f5e9; color: #1b6e34; }
.status-processing { background: #e8f0fe; color: #1a5fd8; }
.status-rejected { background: #fdecea; color: #c0392b; }
.status-inactive { background: #f3f4f6; color: #6b7280; }

/* Filters */
.filter-input :deep(.v-field__input),
.filter-input :deep(input),
.filter-input :deep(.v-label),
.filter-input :deep(.v-select__selection-text) { font-size: 12.5px; }
.filter-input :deep(.v-field__input) { min-height: 36px; }

/* Chart cards: same as the Reports page */
.chart-card { background: #ffffff; border-radius: 10px; box-shadow: none; padding: 20px 22px; height: 100%; }
.chart-title { font-size: 13px; font-weight: 600; color: #1a1a1a; margin: 0 0 16px; }
.chart-meta { font-size: 11px; color: #999; }
.empty-text { font-size: 12px; color: #999; text-align: center; padding: 24px 0; margin: 0; }

.bars { display: flex; align-items: flex-end; gap: 3px; height: 110px; }
.bars__col { flex: 1; height: 100%; display: flex; align-items: flex-end; }
.bars__bar { display: block; width: 100%; min-height: 3px; background: #0f1e3d; border-radius: 3px 3px 0 0; }
.bars__col:hover .bars__bar { background: #f5a623; }
.bars__axis { display: flex; justify-content: space-between; margin-top: 6px; font-size: 10.5px; color: #999; }

.bar-row { display: grid; grid-template-columns: 120px 1fr 32px; align-items: center; gap: 10px; margin-bottom: 10px; }
.bar-label { font-size: 12px; color: #666; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.bar-track { height: 8px; background: #f0ede3; border-radius: 4px; overflow: hidden; }
.bar-fill { height: 100%; background: #f5a623; border-radius: 4px; transition: width .3s ease; }
.bar-count { font-size: 12px; color: #1a1a1a; font-weight: 500; text-align: right; }

/* Table */
.table-head { font-size: 11px; font-weight: 500; color: #999; border-bottom: 1px solid #f0ede3; padding: 0 8px 8px; text-transform: none; letter-spacing: 0; text-align: left; }
.table-head--actions { width: 84px; }
.table-row td { vertical-align: middle; border-bottom: 1px solid #f7f5f0; }
.table-row:last-child td { border-bottom: none; }
.table-row:hover { background: #fafbfc; }

.state { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 32px 16px; font-size: 13px; color: #999; text-align: center; }
.state p { margin: 0; }

/* Row actions */
.actions-cell { padding: 8px 5px; width: 84px; }
.row-actions { display: flex; align-items: center; justify-content: flex-end; gap: 2px; }
.action-btn { width: 32px; height: 32px; border-radius: 8px; color: #8a8f98; transition: background .15s, color .15s; }
.action-btn .v-icon { font-size: 18px; }
.action-btn:hover { background: #f1f3f7; color: #0f1e3d; }

/* Details dialog */
.dialog-head { display: flex; align-items: center; justify-content: space-between; padding: 18px 20px 10px 24px; }
.dialog-title { font-size: 15px; font-weight: 600; color: #1a1a1a; }
.fact-grid { display: flex; flex-direction: column; gap: 10px; padding: 14px 0; border-top: 1px solid #f0ede3; }
.fact { display: flex; justify-content: space-between; gap: 12px; font-size: 12.5px; }
.fact__label { color: #999; flex-shrink: 0; }
.fact__value { color: #1a1a1a; font-weight: 500; text-align: right; }
</style>

<style>
.filter-select-menu .v-list-item-title { font-size: 12.5px; }
.filter-select-menu .v-list-item { min-height: 34px; }
</style>