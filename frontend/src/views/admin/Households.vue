<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import {
  getHouseholds, getHousehold, getHouseholdStats,
  updateHouseholdStatus, deleteHousehold, exportHouseholds,
} from '@/api/household'

const STATUS = {
  pending:      { label: 'Pending',      color: 'warning' },
  verified:     { label: 'Verified',     color: 'success' },
  needs_update: { label: 'Needs Update', color: 'info' },
  rejected:     { label: 'Rejected',     color: 'error' },
  inactive:     { label: 'Inactive',     color: 'grey' },
}
const statusOptions = [{ title: 'All statuses', value: '' }, ...Object.entries(STATUS).map(([value, s]) => ({ title: s.label, value }))]

const loading = ref(false)
const items = ref([])
const total = ref(0)
const lastPage = ref(1)
const stats = ref(null)
const filters = reactive({ search: '', status: '', purok: '', page: 1, per_page: 10 })
const snackbar = reactive({ show: false, text: '', color: 'success' })

const notify = (text, color = 'success') => Object.assign(snackbar, { show: true, text, color })

async function load() {
  loading.value = true
  try {
    const data = await getHouseholds({ ...filters })
    items.value = data.items
    total.value = data.total
    lastPage.value = data.last_page
  } catch (e) {
    notify('Failed to load households', 'error')
  } finally {
    loading.value = false
  }
}

async function loadStats() {
  try { stats.value = await getHouseholdStats() } catch { /* non-critical */ }
}

let timer
watch(() => filters.search, () => { clearTimeout(timer); timer = setTimeout(() => { filters.page = 1; load() }, 350) })
watch(() => [filters.status, filters.purok], () => { filters.page = 1; load() })
watch(() => filters.page, load)

const puroks = computed(() => [{ title: 'All puroks', value: '' }, ...(stats.value?.by_purok ?? []).map((p) => ({ title: p.purok, value: p.purok }))])

const statCards = computed(() => !stats.value ? [] : [
  { label: 'Households',  value: stats.value.verified_households, hint: `${stats.value.pending_households} pending` },
  { label: 'Population',  value: stats.value.population, hint: `${stats.value.male} M · ${stats.value.female} F` },
  { label: 'Senior Citizens', value: stats.value.seniors, hint: '60 years and above' },
  { label: 'PWD',         value: stats.value.pwd, hint: `${stats.value.four_ps_households} 4Ps households` },
])

// ── View dialog ──
const viewDialog = ref(false)
const selected = ref(null)
async function openView(row) {
  viewDialog.value = true
  selected.value = null
  try { selected.value = await getHousehold(row.id) } catch { viewDialog.value = false; notify('Failed to load household', 'error') }
}

// ── Status actions ──
const remarksDialog = reactive({ show: false, row: null, status: '', text: '', saving: false })

async function setStatus(row, status, remarks = null) {
  try {
    await updateHouseholdStatus(row.id, status, remarks)
    notify(`Household marked ${STATUS[status].label.toLowerCase()}`)
    await Promise.all([load(), loadStats()])
    if (viewDialog.value && selected.value?.id === row.id) selected.value = await getHousehold(row.id)
  } catch (e) {
    notify(e.response?.data?.message ?? 'Update failed', 'error')
  }
}

function askRemarks(row, status) {
  Object.assign(remarksDialog, { show: true, row, status, text: '' })
}
async function submitRemarks() {
  if (!remarksDialog.text.trim()) return
  remarksDialog.saving = true
  await setStatus(remarksDialog.row, remarksDialog.status, remarksDialog.text.trim())
  remarksDialog.saving = false
  remarksDialog.show = false
}

const deleteDialog = reactive({ show: false, row: null })
async function confirmDelete() {
  try {
    await deleteHousehold(deleteDialog.row.id)
    notify('Household removed')
    deleteDialog.show = false
    await Promise.all([load(), loadStats()])
  } catch { notify('Delete failed', 'error') }
}

async function doExport() {
  try {
    const blob = await exportHouseholds()
    const url = URL.createObjectURL(blob)
    const a = Object.assign(document.createElement('a'), { href: url, download: `household-census-${new Date().toISOString().slice(0, 10)}.csv` })
    a.click()
    URL.revokeObjectURL(url)
  } catch { notify('Export failed', 'error') }
}

const fmtDate = (d) => (d ? new Date(d).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' }) : '—')
const label = (v) => (v ? v.replaceAll('_', ' ').replace(/^\w/, (c) => c.toUpperCase()) : '—')

onMounted(() => { load(); loadStats() })
</script>

<template>
  <div class="pa-6">
    <!-- Heading -->
    <div class="d-flex align-center justify-space-between flex-wrap ga-3 mb-5">
      <div>
        <h1 class="text-h5 font-weight-bold">Household Census</h1>
        <div class="text-body-2 text-medium-emphasis">{{ total }} total households</div>
      </div>
      <v-btn color="primary" variant="flat" prepend-icon="mdi-download" @click="doExport">Export CSV</v-btn>
    </div>

    <!-- Census summary -->
    <v-row class="mb-2">
      <v-col v-for="c in statCards" :key="c.label" cols="6" md="3">
        <v-card rounded="xl" elevation="0" border class="pa-4">
          <div class="text-caption text-medium-emphasis">{{ c.label }}</div>
          <div class="text-h5 font-weight-bold">{{ c.value.toLocaleString() }}</div>
          <div class="text-caption text-medium-emphasis">{{ c.hint }}</div>
        </v-card>
      </v-col>
    </v-row>

    <!-- Search + filters -->
    <v-row class="mb-1" dense>
      <v-col cols="12" md="6">
        <v-text-field v-model="filters.search" placeholder="Search by household no., name or address…" prepend-inner-icon="mdi-magnify" variant="outlined" density="comfortable" hide-details clearable />
      </v-col>
      <v-col cols="6" md="3">
        <v-select v-model="filters.status" :items="statusOptions" variant="outlined" density="comfortable" hide-details />
      </v-col>
      <v-col cols="6" md="3">
        <v-select v-model="filters.purok" :items="puroks" variant="outlined" density="comfortable" hide-details />
      </v-col>
    </v-row>

    <!-- Table -->
    <v-card rounded="xl" elevation="0" border class="mt-3">
      <v-table>
        <thead>
          <tr>
            <th>Household No.</th><th>Head of Household</th><th>Address</th>
            <th class="text-center">Members</th><th>Status</th><th>Submitted</th><th class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading"><td colspan="7" class="text-center pa-8"><v-progress-circular indeterminate size="28" /></td></tr>
          <tr v-else-if="!items.length"><td colspan="7" class="text-center pa-8 text-medium-emphasis">No households found.</td></tr>
          <tr v-for="row in items" v-else :key="row.id">
            <td class="font-weight-medium">{{ row.household_number }}</td>
            <td>{{ row.head_name ?? '—' }}</td>
            <td>{{ row.purok }}<div class="text-caption text-medium-emphasis">{{ row.street_address }}</div></td>
            <td class="text-center">{{ row.member_count }}</td>
            <td><v-chip :color="STATUS[row.status].color" size="small" variant="tonal" label>{{ STATUS[row.status].label }}</v-chip></td>
            <td>{{ fmtDate(row.created_at) }}</td>
            <td class="text-right text-no-wrap">
              <v-btn icon="mdi-eye-outline" size="small" variant="text" @click="openView(row)" />
              <v-menu>
                <template #activator="{ props }"><v-btn v-bind="props" icon="mdi-dots-vertical" size="small" variant="text" /></template>
                <v-list density="compact">
                  <v-list-item v-if="row.status !== 'verified'" prepend-icon="mdi-check-circle-outline" title="Verify" @click="setStatus(row, 'verified')" />
                  <v-list-item prepend-icon="mdi-pencil-outline" title="Request update" @click="askRemarks(row, 'needs_update')" />
                  <v-list-item prepend-icon="mdi-close-circle-outline" title="Reject" @click="askRemarks(row, 'rejected')" />
                  <v-list-item v-if="row.status !== 'inactive'" prepend-icon="mdi-home-off-outline" title="Mark inactive" @click="setStatus(row, 'inactive')" />
                  <v-divider />
                  <v-list-item prepend-icon="mdi-delete-outline" title="Delete" base-color="error" @click="Object.assign(deleteDialog, { show: true, row })" />
                </v-list>
              </v-menu>
            </td>
          </tr>
        </tbody>
      </v-table>
      <v-divider />
      <div class="d-flex justify-center pa-3"><v-pagination v-model="filters.page" :length="lastPage" :total-visible="5" density="comfortable" /></div>
    </v-card>

    <!-- View dialog -->
    <v-dialog v-model="viewDialog" max-width="900" scrollable>
      <v-card rounded="xl">
        <v-card-title class="d-flex align-center justify-space-between pa-5">
          <span>{{ selected?.household_number ?? 'Loading…' }}</span>
          <v-btn icon="mdi-close" variant="text" @click="viewDialog = false" />
        </v-card-title>
        <v-divider />
        <v-card-text v-if="selected" class="pa-5">
          <v-chip :color="STATUS[selected.status].color" variant="tonal" label class="mb-3">{{ STATUS[selected.status].label }}</v-chip>
          <v-alert v-if="selected.remarks" type="info" variant="tonal" density="compact" class="mb-4">{{ selected.remarks }}</v-alert>

          <v-row dense class="mb-4">
            <v-col cols="12" sm="6"><div class="text-caption text-medium-emphasis">Address</div>{{ selected.purok }}, {{ selected.street_address }}</v-col>
            <v-col cols="6" sm="3"><div class="text-caption text-medium-emphasis">Tenure</div>{{ label(selected.housing_tenure) }}</v-col>
            <v-col cols="6" sm="3"><div class="text-caption text-medium-emphasis">House material</div>{{ label(selected.house_material) }}</v-col>
            <v-col cols="6" sm="3"><div class="text-caption text-medium-emphasis">Water source</div>{{ label(selected.water_source) }}</v-col>
            <v-col cols="6" sm="3"><div class="text-caption text-medium-emphasis">Toilet</div>{{ label(selected.toilet_type) }}</v-col>
            <v-col cols="6" sm="3"><div class="text-caption text-medium-emphasis">Electricity</div>{{ selected.has_electricity ? 'Yes' : 'No' }}</v-col>
            <v-col cols="6" sm="3"><div class="text-caption text-medium-emphasis">Income bracket</div>{{ label(selected.income_bracket) }}</v-col>
            <v-col cols="6" sm="3"><div class="text-caption text-medium-emphasis">4Ps</div>{{ selected.is_4ps ? 'Yes' : 'No' }}</v-col>
            <v-col cols="6" sm="3"><div class="text-caption text-medium-emphasis">Verified</div>{{ selected.verified_by ? `${fmtDate(selected.verified_at)} · ${selected.verified_by}` : '—' }}</v-col>
          </v-row>

          <div class="text-subtitle-2 mb-2">Household Members ({{ selected.members.length }})</div>
          <v-table density="compact" class="border rounded-lg">
            <thead><tr><th>Name</th><th>Relationship</th><th>Age</th><th>Sex</th><th>Civil Status</th><th>Occupation</th><th>Tags</th></tr></thead>
            <tbody>
              <tr v-for="m in selected.members" :key="m.id">
                <td class="font-weight-medium">{{ m.full_name }}<v-chip v-if="m.is_head" size="x-small" color="primary" class="ml-2" label>Head</v-chip></td>
                <td>{{ m.relationship_to_head }}</td><td>{{ m.age }}</td><td>{{ label(m.sex) }}</td>
                <td>{{ label(m.civil_status) }}</td><td>{{ m.occupation || '—' }}</td>
                <td class="text-no-wrap">
                  <v-chip v-if="m.age >= 60" size="x-small" label class="mr-1">Senior</v-chip>
                  <v-chip v-if="m.is_pwd" size="x-small" label class="mr-1">PWD</v-chip>
                  <v-chip v-if="m.is_solo_parent" size="x-small" label class="mr-1">Solo parent</v-chip>
                  <v-chip v-if="m.is_registered_voter" size="x-small" label>Voter</v-chip>
                </td>
              </tr>
            </tbody>
          </v-table>
        </v-card-text>
        <v-card-text v-else class="text-center pa-10"><v-progress-circular indeterminate /></v-card-text>
        <v-divider />
        <v-card-actions v-if="selected" class="pa-4">
          <v-spacer />
          <v-btn variant="text" @click="askRemarks(selected, 'needs_update')">Request update</v-btn>
          <v-btn variant="text" color="error" @click="askRemarks(selected, 'rejected')">Reject</v-btn>
          <v-btn v-if="selected.status !== 'verified'" color="success" variant="flat" @click="setStatus(selected, 'verified')">Verify</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Remarks dialog -->
    <v-dialog v-model="remarksDialog.show" max-width="460">
      <v-card rounded="xl" class="pa-2">
        <v-card-title>{{ remarksDialog.status === 'rejected' ? 'Reject household' : 'Request an update' }}</v-card-title>
        <v-card-text>
          <v-textarea v-model="remarksDialog.text" label="Remarks (shown to the resident)" rows="3" variant="outlined" autofocus />
        </v-card-text>
        <v-card-actions><v-spacer />
          <v-btn variant="text" @click="remarksDialog.show = false">Cancel</v-btn>
          <v-btn color="primary" variant="flat" :loading="remarksDialog.saving" :disabled="!remarksDialog.text.trim()" @click="submitRemarks">Submit</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Delete dialog -->
    <v-dialog v-model="deleteDialog.show" max-width="420">
      <v-card rounded="xl" class="pa-2">
        <v-card-title>Delete household?</v-card-title>
        <v-card-text>{{ deleteDialog.row?.household_number }} and its members will be removed from the census.</v-card-text>
        <v-card-actions><v-spacer />
          <v-btn variant="text" @click="deleteDialog.show = false">Cancel</v-btn>
          <v-btn color="error" variant="flat" @click="confirmDelete">Delete</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000">{{ snackbar.text }}</v-snackbar>
  </div>
</template>
