<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import {
  getHouseholds, getHousehold, getHouseholdStats,
  updateHouseholdStatus, deleteHousehold, exportHouseholds,
} from '@/api/household'
import StatCard from '@/components/dashboard/StatCard.vue'

// `cls` maps onto the same status-chip colors the other admin pages use.
const STATUS = {
  pending:      { label: 'Pending',      cls: 'status-pending' },
  verified:     { label: 'Verified',     cls: 'status-approved' },
  needs_update: { label: 'Needs Update', cls: 'status-processing' },
  rejected:     { label: 'Rejected',     cls: 'status-rejected' },
  inactive:     { label: 'Inactive',     cls: 'status-inactive' },
}
const statusOptions = [{ title: 'All statuses', value: '' }, ...Object.entries(STATUS).map(([value, s]) => ({ title: s.label, value }))]

const tableColumns = ['Household No.', 'Head of Household', 'Address', 'Members', 'Status', 'Submitted', '']

const AVATAR_PALETTE = [
  { bg: '#e8f0fe', text: '#1a5fd8' },
  { bg: '#e8f5e9', text: '#27ae60' },
  { bg: '#fff3e0', text: '#e67e22' },
  { bg: '#fff9ed', text: '#a07020' },
]

const loading = ref(false)
const items = ref([])
const total = ref(0)
const lastPage = ref(1)
const stats = ref(null)
const statsFailed = ref(false)
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
  try { stats.value = await getHouseholdStats() } catch { statsFailed.value = true /* non-critical: the row just hides */ }
}

let timer
watch(() => filters.search, () => { clearTimeout(timer); timer = setTimeout(() => { filters.page = 1; load() }, 350) })
watch(() => [filters.status, filters.purok], () => { filters.page = 1; load() })
watch(() => filters.page, load)

const puroks = computed(() => [{ title: 'All puroks', value: '' }, ...(stats.value?.by_purok ?? []).map((p) => ({ title: p.purok, value: p.purok }))])

const statCards = computed(() => {
  const s = stats.value
  const num = (v) => (v ?? 0).toLocaleString()
  return [
    { label: 'Households', value: s ? num(s.verified_households) : '', accentColor: '#0f1e3d', delta: s ? `${s.pending_households} pending` : '', deltaIcon: 'mdi-clock-outline', deltaColor: '#e67e22', loading: !s },
    { label: 'Population', value: s ? num(s.population) : '', accentColor: '#f5a623', delta: s ? `${s.male} M · ${s.female} F` : '', deltaIcon: 'mdi-account-multiple-outline', deltaColor: '#888', loading: !s },
    { label: 'Senior Citizens', value: s ? num(s.seniors) : '', accentColor: '#2e86de', delta: '60 years and above', deltaIcon: 'mdi-human-cane', deltaColor: '#888', loading: !s },
    { label: 'PWD', value: s ? num(s.pwd) : '', accentColor: '#27ae60', delta: s ? `${s.four_ps_households} 4Ps households` : '', deltaIcon: 'mdi-wheelchair-accessibility', deltaColor: '#888', loading: !s },
  ]
})

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
const initials = (name) => (name ?? '').split(' ').filter(Boolean).slice(0, 2).map((p) => p[0]).join('').toUpperCase()

onMounted(() => { load(); loadStats() })
</script>

<template>
  <div>
    <!-- ─── Header + filters ─────────────────────────────────────── -->
    <div class="d-flex flex-wrap align-center justify-space-between mb-4 ga-3">
      <div>
        <div class="d-flex align-center">
          <v-icon icon="mdi-home-group" size="16" style="color: #f5a623; margin-right: 7px" />
          <span style="font-size: 14px; font-weight: 600; color: #1a1a1a">Household Census</span>
        </div>
        <p style="font-size: 11px; color: #aaa; margin: 2px 0 0 23px">{{ total }} total households</p>
      </div>

      <div class="d-flex flex-wrap ga-2">
        <v-text-field
          v-model="filters.search"
          density="compact"
          variant="outlined"
          placeholder="Search household no., name or address"
          prepend-inner-icon="mdi-magnify"
          hide-details
          clearable
          class="filter-input"
          style="width: 270px"
        />
        <v-select
          v-model="filters.status"
          :items="statusOptions"
          density="compact"
          variant="outlined"
          hide-details
          class="filter-input"
          style="width: 160px"
          :menu-props="{ contentClass: 'filter-select-menu' }"
        />
        <v-select
          v-model="filters.purok"
          :items="puroks"
          density="compact"
          variant="outlined"
          hide-details
          class="filter-input"
          style="width: 150px"
          :menu-props="{ contentClass: 'filter-select-menu' }"
        />
        <v-btn
          style="background: #0f1e3d; color: #fff; text-transform: none"
          prepend-icon="mdi-download"
          @click="doExport"
        >
          Export CSV
        </v-btn>
      </div>
    </div>

    <!-- ─── Census summary ───────────────────────────────────────── -->
    <v-row v-if="!statsFailed" class="mb-2">
      <v-col v-for="c in statCards" :key="c.label" cols="6" md="3">
        <StatCard v-bind="c" />
      </v-col>
    </v-row>

    <!-- ─── Table ────────────────────────────────────────────────── -->
    <v-card variant="flat" class="mt-2" style="background: #ffffff; border-radius: 10px; box-shadow: none; margin-top: 30px;">
      <v-card-text class="px-5 pt-5 pb-5">
        <div v-if="loading" class="d-flex justify-center py-10">
          <v-progress-circular indeterminate size="24" color="#0f1e3d" />
        </div>

        <p v-else-if="!items.length" style="font-size: 13px; color: #999; padding: 32px 20px">
          No households found.
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
            <tr v-for="(row, idx) in items" :key="row.id" class="table-row">
              <td style="font-size: 12px; font-weight: 600; color: #0f1e3d; padding: 12px 8px; white-space: nowrap">
                {{ row.household_number }}
              </td>
              <td style="padding: 12px 8px">
                <div v-if="row.head_name" class="d-flex align-center ga-2">
                  <v-avatar
                    size="26"
                    :style="{ background: AVATAR_PALETTE[idx % AVATAR_PALETTE.length].bg }"
                  >
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
              <td style="padding: 12px 8px">
                <div style="font-size: 12px; color: #666">{{ row.purok }}</div>
                <div style="font-size: 11px; color: #aaa">{{ row.street_address }}</div>
              </td>
              <td style="font-size: 12px; color: #1a1a1a; padding: 12px 8px; text-align: center">
                {{ row.member_count }}
              </td>
              <td style="padding: 12px 8px">
                <span class="status-chip" :class="STATUS[row.status]?.cls">
                  {{ STATUS[row.status]?.label ?? row.status }}
                </span>
              </td>
              <td style="font-size: 11px; color: #aaa; padding: 12px 8px; white-space: nowrap">
                {{ fmtDate(row.created_at) }}
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
                        aria-label="View details"
                        @click="openView(row)"
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
                      <v-list-item v-if="row.status !== 'verified'" prepend-icon="mdi-check-circle-outline" title="Verify" @click="setStatus(row, 'verified')" />
                      <v-list-item prepend-icon="mdi-pencil-outline" title="Request update" @click="askRemarks(row, 'needs_update')" />
                      <v-list-item prepend-icon="mdi-close-circle-outline" title="Reject" @click="askRemarks(row, 'rejected')" />
                      <v-list-item v-if="row.status !== 'inactive'" prepend-icon="mdi-home-off-outline" title="Mark inactive" @click="setStatus(row, 'inactive')" />
                      <v-divider />
                      <v-list-item prepend-icon="mdi-delete-outline" title="Delete" base-color="error" @click="Object.assign(deleteDialog, { show: true, row })" />
                    </v-list>
                  </v-menu>
                </div>
              </td>
            </tr>
          </tbody>
        </v-table>

        <div v-if="lastPage > 1" class="d-flex justify-center py-4">
          <v-pagination v-model="filters.page" :length="lastPage" density="compact" :total-visible="6" />
        </div>
      </v-card-text>
    </v-card>

    <!-- ─── View dialog ──────────────────────────────────────────── -->
    <v-dialog v-model="viewDialog" max-width="860" scrollable>
      <v-card>
        <div v-if="!selected" class="d-flex justify-center py-10">
          <v-progress-circular indeterminate size="24" color="#0f1e3d" />
        </div>

        <template v-else>
          <div class="dialog-head">
            <div>
              <span class="dialog-title">{{ selected.household_number }}</span>
              <span class="status-chip ml-2" :class="STATUS[selected.status]?.cls">
                {{ STATUS[selected.status]?.label ?? selected.status }}
              </span>
            </div>
            <v-btn icon="mdi-close" variant="text" density="comfortable" class="action-btn" aria-label="Close" @click="viewDialog = false" />
          </div>

          <v-card-text class="px-5 pb-2">
            <div v-if="selected.remarks" class="info-note">
              <v-icon icon="mdi-information-outline" size="15" />
              <span>{{ selected.remarks }}</span>
            </div>

            <div class="detail-grid">
              <div class="detail detail--wide2">
                <span class="detail__label">Address</span>
                <span class="detail__value">{{ selected.purok }}, {{ selected.street_address }}</span>
              </div>
              <div class="detail"><span class="detail__label">Tenure</span><span class="detail__value">{{ label(selected.housing_tenure) }}</span></div>
              <div class="detail"><span class="detail__label">House material</span><span class="detail__value">{{ label(selected.house_material) }}</span></div>
              <div class="detail"><span class="detail__label">Water source</span><span class="detail__value">{{ label(selected.water_source) }}</span></div>
              <div class="detail"><span class="detail__label">Toilet</span><span class="detail__value">{{ label(selected.toilet_type) }}</span></div>
              <div class="detail"><span class="detail__label">Electricity</span><span class="detail__value">{{ selected.has_electricity ? 'Yes' : 'No' }}</span></div>
              <div class="detail"><span class="detail__label">Income bracket</span><span class="detail__value">{{ label(selected.income_bracket) }}</span></div>
              <div class="detail"><span class="detail__label">4Ps</span><span class="detail__value">{{ selected.is_4ps ? 'Yes' : 'No' }}</span></div>
              <div class="detail detail--wide3">
                <span class="detail__label">Verified</span>
                <span class="detail__value">{{ selected.verified_by ? `${fmtDate(selected.verified_at)} · ${selected.verified_by}` : '—' }}</span>
              </div>
            </div>

            <p class="section-label">Household members ({{ selected.members.length }})</p>
            <div class="members-wrap">
              <v-table density="compact">
                <thead>
                  <tr>
                    <th v-for="h in ['Name', 'Relationship', 'Age', 'Sex', 'Civil status', 'Occupation', 'Tags']" :key="h" class="table-head">{{ h }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="m in selected.members" :key="m.id" class="table-row">
                    <td style="font-size: 12.5px; font-weight: 500; color: #1a1a1a; padding: 8px; white-space: nowrap">
                      {{ m.full_name }}
                      <span v-if="m.is_head" class="status-chip chip--head ml-2">Head</span>
                    </td>
                    <td style="font-size: 12px; color: #666; padding: 8px">{{ m.relationship_to_head }}</td>
                    <td style="font-size: 12px; color: #666; padding: 8px">{{ m.age }}</td>
                    <td style="font-size: 12px; color: #666; padding: 8px">{{ label(m.sex) }}</td>
                    <td style="font-size: 12px; color: #666; padding: 8px">{{ label(m.civil_status) }}</td>
                    <td style="font-size: 12px; color: #666; padding: 8px">{{ m.occupation || '—' }}</td>
                    <td style="padding: 8px; white-space: nowrap">
                      <span v-if="m.age >= 60" class="tag">Senior</span>
                      <span v-if="m.is_pwd" class="tag">PWD</span>
                      <span v-if="m.is_solo_parent" class="tag">Solo parent</span>
                      <span v-if="m.is_registered_voter" class="tag">Voter</span>
                    </td>
                  </tr>
                </tbody>
              </v-table>
            </div>
          </v-card-text>

          <v-card-actions class="px-5 pb-5">
            <v-spacer />
            <v-btn variant="text" style="text-transform: none" @click="askRemarks(selected, 'needs_update')">Request update</v-btn>
            <v-btn variant="text" color="error" style="text-transform: none" @click="askRemarks(selected, 'rejected')">Reject</v-btn>
            <v-btn
              v-if="selected.status !== 'verified'"
              style="background: #0f1e3d; color: #fff; text-transform: none"
              @click="setStatus(selected, 'verified')"
            >
              Verify
            </v-btn>
          </v-card-actions>
        </template>
      </v-card>
    </v-dialog>

    <!-- ─── Remarks dialog ───────────────────────────────────────── -->
    <v-dialog v-model="remarksDialog.show" max-width="420">
      <v-card>
        <v-card-title style="font-size: 15px">
          {{ remarksDialog.status === 'rejected' ? 'Reject household' : 'Request an update' }}
        </v-card-title>
        <v-card-text>
          <p style="font-size: 12px; color: #888; margin-bottom: 10px">
            This is shown to the resident, so be specific about what needs fixing.
          </p>
          <v-textarea
            v-model="remarksDialog.text"
            variant="outlined"
            density="compact"
            rows="3"
            placeholder="Remarks for the resident"
            hide-details
            autofocus
          />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="remarksDialog.show = false">Cancel</v-btn>
          <v-btn
            :color="remarksDialog.status === 'rejected' ? 'error' : undefined"
            :variant="remarksDialog.status === 'rejected' ? 'flat' : undefined"
            :style="remarksDialog.status === 'rejected' ? '' : 'background: #0f1e3d; color: #fff; text-transform: none'"
            :loading="remarksDialog.saving"
            :disabled="!remarksDialog.text.trim()"
            @click="submitRemarks"
          >
            {{ remarksDialog.status === 'rejected' ? 'Reject' : 'Submit' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ─── Delete dialog ────────────────────────────────────────── -->
    <v-dialog v-model="deleteDialog.show" max-width="420">
      <v-card>
        <v-card-title style="font-size: 15px">Delete household?</v-card-title>
        <v-card-text style="font-size: 13px; color: #555">
          {{ deleteDialog.row?.household_number }} and its members will be removed from the census.
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="deleteDialog.show = false">Cancel</v-btn>
          <v-btn color="error" variant="flat" @click="confirmDelete">Delete</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000">{{ snackbar.text }}</v-snackbar>
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
.chip--head { background: #e8f0fe; color: #1a5fd8; }

/* Table */
.table-head { font-size: 11px; font-weight: 500; color: #999; border-bottom: 1px solid #f0ede3; padding: 0 8px 8px; text-transform: none; letter-spacing: 0; text-align: left; }
.table-head--center { text-align: center; }
.table-head--actions { width: 84px; }
.table-row td { vertical-align: middle; border-bottom: 1px solid #f7f5f0; }
.table-row:last-child td { border-bottom: none; }
.table-row:hover { background: #fafbfc; }

.filter-input :deep(.v-field__input),
.filter-input :deep(input),
.filter-input :deep(.v-select__selection-text) { font-size: 12.5px; }
.filter-input :deep(.v-field__input) { min-height: 36px; }

/* Row actions */
.actions-cell { padding: 8px 5px; width: 84px; }
.row-actions { display: flex; align-items: center; justify-content: flex-end; gap: 2px; }
.action-btn { width: 32px; height: 32px; border-radius: 8px; color: #8a8f98; transition: background .15s, color .15s; }
.action-btn .v-icon { font-size: 18px; }
.action-btn:hover { background: #f1f3f7; color: #0f1e3d; }

/* View dialog */
.dialog-head { display: flex; align-items: center; justify-content: space-between; padding: 18px 20px 10px 24px; }
.dialog-title { font-size: 15px; font-weight: 600; color: #1a1a1a; }

.info-note { display: flex; align-items: flex-start; gap: 8px; background: #e8f0fe; color: #1a5fd8; font-size: 12px; padding: 10px 12px; border-radius: 8px; margin-bottom: 16px; }

.detail-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px 20px; padding: 14px 0; border-top: 1px solid #f0ede3; border-bottom: 1px solid #f0ede3; margin-bottom: 18px; }
.detail { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.detail--wide2 { grid-column: span 2; }
.detail--wide3 { grid-column: span 3; }
.detail__label { font-size: 11px; color: #999; }
.detail__value { font-size: 12.5px; font-weight: 500; color: #1a1a1a; }

.section-label { font-size: 11px; font-weight: 600; color: #999; text-transform: uppercase; letter-spacing: .04em; margin: 0 0 10px; }

.members-wrap { overflow-x: auto; margin-bottom: 8px; }

.tag { display: inline-block; font-size: 10px; font-weight: 600; background: #f3f4f6; color: #555; border-radius: 6px; padding: 2px 7px; margin-right: 4px; }

@media (max-width: 600px) {
  .detail-grid { grid-template-columns: 1fr 1fr; }
  .detail--wide3 { grid-column: span 2; }
}
</style>

<style>
.filter-select-menu .v-list-item-title { font-size: 12.5px; }
.filter-select-menu .v-list-item { min-height: 34px; }
</style>