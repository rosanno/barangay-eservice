<template>
  <div>
    <!-- ─── Header + search ──────────────────────────────────────── -->
    <div class="d-flex flex-wrap align-center justify-space-between mb-4 ga-3">
      <div>
        <div class="d-flex align-center">
          <v-icon icon="mdi-account-group-outline" size="16" style="color: #f5a623; margin-right: 7px" />
          <span style="font-size: 14px; font-weight: 600; color: #1a1a1a">Residents</span>
        </div>
        <p style="font-size: 11px; color: #aaa; margin: 2px 0 0 23px">
          {{ meta.total ?? 0 }} registered
        </p>
      </div>

      <div class="d-flex align-center ga-2">
        <v-text-field
          v-model="search"
          density="compact"
          variant="outlined"
          placeholder="Search by name, email, or purok"
          prepend-inner-icon="mdi-magnify"
          hide-details
          class="filter-input search-field"
          style="width: 300px"
          @update:model-value="onSearchInput"
        />
        <v-btn
          style="background: #0f1e3d; color: #fff; text-transform: none"
          prepend-icon="mdi-plus"
          to="/admin/residents/new"
        >
          Add resident
        </v-btn>
      </div>
    </div>

    <!-- ─── Table ────────────────────────────────────────────────── -->
    <v-card variant="flat" style="background: #ffffff; border-radius: 10px; box-shadow: none">
      <v-card-text class="px-5 pt-5 pb-5">
        <div v-if="loading" class="d-flex justify-center py-10">
          <v-progress-circular indeterminate size="24" color="#0f1e3d" />
        </div>

        <p v-else-if="!residents.length" style="font-size: 13px; color: #999; padding: 32px 20px">
          No residents match that search.
        </p>

        <v-table v-else density="comfortable">
          <thead>
            <tr>
              <th v-for="col in tableColumns" :key="col" class="table-head">{{ col }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="resident in residents" :key="resident.id" class="table-row">
              <td style="padding: 12px 8px">
                <div class="d-flex align-center ga-2">
                  <v-avatar size="26" :style="{ background: resident.avatarBg }">
                    <span style="font-size: 10px; font-weight: 600" :style="{ color: resident.avatarText }">
                      {{ initials(resident.name) }}
                    </span>
                  </v-avatar>
                  <span style="font-size: 13px; font-weight: 500; color: #1a1a1a">
                    {{ resident.name }}
                  </span>
                </div>
              </td>
              <td style="font-size: 12px; color: #666; padding: 12px 8px">{{ resident.email }}</td>
              <td style="font-size: 12px; color: #666; padding: 12px 8px">{{ resident.purok || '—' }}</td>
              <td style="font-size: 12px; color: #666; padding: 12px 8px">
                {{ resident.sex || '—' }}<span v-if="resident.age"> · {{ resident.age }} yrs</span>
              </td>
              <td style="font-size: 12px; color: #1a1a1a; padding: 12px 8px; text-align: center">
                {{ resident.requestCount }}
              </td>
              <td style="padding: 12px 8px; text-align: right">
                <button type="button" class="view-link" @click="openRequestsList(resident)">
                  View requests
                </button>
              </td>
            </tr>
          </tbody>
        </v-table>

        <div v-if="meta.last_page > 1" class="d-flex justify-center py-4">
          <v-pagination
            v-model="page"
            :length="meta.last_page"
            density="compact"
            :total-visible="6"
            @update:model-value="loadResidents"
          />
        </div>
      </v-card-text>
    </v-card>

    <!-- ─── Per-resident requests list dialog ───────────────────── -->
    <v-dialog v-model="requestsDialog.open" max-width="520">
      <v-card>
        <v-card-title class="d-flex align-center justify-space-between" style="font-size: 14px; font-weight: 600">
          {{ requestsDialog.resident?.name }}'s Requests
          <v-btn icon variant="text" size="small" @click="requestsDialog.open = false">
            <v-icon icon="mdi-close" size="18" />
          </v-btn>
        </v-card-title>

        <v-card-text class="pa-0">
          <div v-if="requestsDialog.loading" class="d-flex justify-center py-8">
            <v-progress-circular indeterminate size="22" color="#0f1e3d" />
          </div>

          <p v-else-if="!requestsDialog.requests.length" style="font-size: 12.5px; color: #999; padding: 24px 20px">
            This resident hasn't made any document requests yet.
          </p>

          <button
            v-for="item in requestsDialog.requests"
            :key="item.id"
            type="button"
            class="resident-request-row"
            @click="openDetail(item)"
          >
            <span class="resident-request-row__main">
              <span class="resident-request-row__type">{{ item.type }}</span>
              <span class="resident-request-row__tracking">{{ item.trackingNumber }}</span>
            </span>
            <span class="status-chip" :class="item.statusClass">{{ item.statusLabel }}</span>
          </button>
        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- ─── Full request detail — same shared component Clearances.vue
         uses, so the view here is identical in capability (status
         actions, QR code, timeline) rather than a stripped-down copy ── -->
    <DocumentRequestDetailDialog
      v-model="detailDialogOpen"
      :request-id="viewingId"
      @updated="reloadRequestsDialog"
    />
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { fetchAdminResidents } from '@/api/adminResidents'
import { fetchAdminDocumentRequests } from '@/api/adminDocumentRequests'
import DocumentRequestDetailDialog from '@/components/DocumentRequestDetailDialog.vue'

const tableColumns = ['Name', 'Email', 'Purok', 'Sex / Age', 'Requests', '']

const AVATAR_PALETTE = [
  { bg: '#e8f0fe', text: '#1a5fd8' },
  { bg: '#e8f5e9', text: '#27ae60' },
  { bg: '#fff3e0', text: '#e67e22' },
  { bg: '#fff9ed', text: '#a07020' },
]

const STATUS_META = {
  pending: { label: 'Pending', class: 'status-pending' },
  processing: { label: 'Processing', class: 'status-processing' },
  ready_for_pickup: { label: 'Ready', class: 'status-ready' },
  released: { label: 'Released', class: 'status-approved' },
  rejected: { label: 'Rejected', class: 'status-rejected' },
  cancelled: { label: 'Cancelled', class: 'status-rejected' },
}

const search = ref('')
const page = ref(1)
const residents = ref([])
const meta = ref({ total: 0, last_page: 1 })
const loading = ref(true)

let searchDebounce = null
function onSearchInput() {
  clearTimeout(searchDebounce)
  searchDebounce = setTimeout(() => loadResidents(1), 350)
}

async function loadResidents(targetPage = page.value) {
  page.value = targetPage
  loading.value = true
  try {
    const { data, meta: pageMeta } = await fetchAdminResidents({
      page: targetPage,
      per_page: 20,
      search: search.value || undefined,
    })

    residents.value = data.map((resident, index) => {
      const palette = AVATAR_PALETTE[index % AVATAR_PALETTE.length]
      return {
        id: resident.id,
        name: resident.name,
        email: resident.email,
        purok: resident.purok,
        sex: resident.sex,
        age: resident.age,
        requestCount: resident.request_count,
        avatarBg: palette.bg,
        avatarText: palette.text,
      }
    })
    meta.value = pageMeta
  } catch (error) {
    residents.value = []
    console.error('Failed to load residents', error)
  } finally {
    loading.value = false
  }
}

// ── Per-resident requests dialog ───────────────────────────────────
const requestsDialog = reactive({
  open: false,
  loading: false,
  resident: null,
  requests: [],
})

async function openRequestsList(resident) {
  requestsDialog.resident = resident
  requestsDialog.open = true
  await loadResidentRequests(resident.id)
}

async function loadResidentRequests(residentId) {
  requestsDialog.loading = true
  try {
    // user_id is an exact match — unlike a name search, this can never
    // pull in a different resident who happens to share a name.
    const { data } = await fetchAdminDocumentRequests({ user_id: residentId, per_page: 50 })

    requestsDialog.requests = data.map((item) => {
      const meta = STATUS_META[item.status] || { label: item.status, class: 'status-pending' }
      return {
        id: item.id,
        type: item.document_type.name,
        trackingNumber: item.tracking_number,
        statusLabel: meta.label,
        statusClass: meta.class,
      }
    })
  } catch {
    requestsDialog.requests = []
  } finally {
    requestsDialog.loading = false
  }
}

function reloadRequestsDialog() {
  if (requestsDialog.resident) loadResidentRequests(requestsDialog.resident.id)
  loadResidents(page.value) // request counts on the main table may have shifted
}

// ── Full detail dialog (shared component) ──────────────────────────
const detailDialogOpen = ref(false)
const viewingId = ref(null)

function openDetail(item) {
  viewingId.value = item.id
  detailDialogOpen.value = true
}

function initials(name) {
  return name
    .split(' ')
    .map((p) => p[0])
    .slice(0, 2)
    .join('')
    .toUpperCase()
}

onMounted(() => loadResidents(1))
</script>

<style scoped src="./DashboardCss.css"></style>

<style scoped>
.table-head {
  font-size: 11px;
  font-weight: 500;
  color: #999;
  border-bottom: 1px solid #f0ede3;
  padding: 0 8px 8px;
  text-transform: none;
  letter-spacing: 0;
  text-align: left;
}

.table-row {
  border-bottom: 1px solid #f7f5f0;
}

.filter-input :deep(.v-field__input),
.filter-input :deep(input),
.filter-input :deep(.v-select__selection-text),
.filter-input :deep(.v-field__prepend-inner .v-icon) {
  font-size: 12.5px;
}

.filter-input :deep(.v-field__input) {
  min-height: 36px;
}

.view-link {
  background: none;
  border: none;
  font-size: 12px;
  color: #0f1e3d;
  font-weight: 600;
  cursor: pointer;
  padding: 0;
}

.resident-request-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  width: 100%;
  padding: 12px 20px;
  background: none;
  border: none;
  border-bottom: 1px solid #f7f5f0;
  text-align: left;
  cursor: pointer;
}

.resident-request-row:last-child {
  border-bottom: none;
}

.resident-request-row:hover {
  background: #faf9f6;
}

.resident-request-row__main {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.resident-request-row__type {
  font-size: 12.5px;
  font-weight: 500;
  color: #1a1a1a;
}

.resident-request-row__tracking {
  font-size: 11px;
  color: #999;
}
</style>