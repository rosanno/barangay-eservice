<template>
  <div>
    <!-- ─── Header + filters ─────────────────────────────────────── -->
    <div class="d-flex flex-wrap align-center justify-space-between mb-4 ga-3">
      <div>
        <div class="d-flex align-center">
          <v-icon icon="mdi-file-document-outline" size="16" style="color: #f5a623; margin-right: 7px" />
          <span style="font-size: 14px; font-weight: 600; color: #1a1a1a">Requests</span>
        </div>
        <p style="font-size: 11px; color: #aaa; margin: 2px 0 0 23px">
          {{ meta.total ?? 0 }} total
        </p>
      </div>

      <div class="d-flex flex-wrap ga-2">
        <v-text-field
          v-model="filters.search"
          density="compact"
          variant="outlined"
          placeholder="Search resident or tracking #"
          prepend-inner-icon="mdi-magnify"
          hide-details
          class="filter-input"
          style="width: 240px"
          @update:model-value="onSearchInput"
        />
        <v-select
          v-model="filters.status"
          :items="statusOptions"
          item-title="label"
          item-value="value"
          density="compact"
          variant="outlined"
          hide-details
          class="filter-input"
          style="width: 170px"
          :menu-props="{ contentClass: 'filter-select-menu' }"
          @update:model-value="() => loadRequests(1)"
        />
        <v-select
          v-model="filters.documentTypeId"
          :items="documentTypeOptions"
          item-title="name"
          item-value="id"
          density="compact"
          variant="outlined"
          hide-details
          class="filter-input"
          style="width: 200px"
          :menu-props="{ contentClass: 'filter-select-menu' }"
          @update:model-value="() => loadRequests(1)"
        />
      </div>
    </div>

    <!-- ─── Table ────────────────────────────────────────────────── -->
    <v-card variant="flat" style="background: #ffffff; border-radius: 10px; box-shadow: none">
      <v-card-text class="px-5 pt-5 pb-5">
        <div v-if="loading" class="d-flex justify-center py-10">
          <v-progress-circular indeterminate size="24" color="#0f1e3d" />
        </div>

        <p v-else-if="!requests.length" style="font-size: 13px; color: #999; padding: 32px 20px">
          No requests match these filters.
        </p>

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
            <tr v-for="req in requests" :key="req.id" class="table-row">
              <td style="padding: 12px 5px">
                <div class="d-flex align-center ga-2">
                  <v-avatar size="26" :style="{ background: req.avatarBg }">
                    <span style="font-size: 10px; font-weight: 600" :style="{ color: req.avatarText }">
                      {{ initials(req.resident) }}
                    </span>
                  </v-avatar>
                  <span style="font-size: 13px; font-weight: 500; color: #1a1a1a">
                    {{ req.resident }}
                  </span>
                </div>
              </td>
              <td style="font-size: 12px; color: #666; padding: 12px 8px">{{ req.type }}</td>
              <td style="font-size: 11px; color: #999; padding: 12px 8px">{{ req.trackingNumber }}</td>
              <td style="padding: 12px 8px">
                <span class="status-chip" :class="req.statusClass">{{ req.statusLabel }}</span>
              </td>
              <td style="font-size: 11px; color: #aaa; padding: 12px 8px; white-space: nowrap">
                {{ req.date }}
              </td>

              <!-- Actions -->
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
                        @click="openView(req)"
                      />
                    </template>
                  </v-tooltip>

                  <v-menu v-if="req.transitions.length">
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
                      <v-list-item
                        v-for="target in req.transitions"
                        :key="target"
                        @click="handleTransition(req, target)"
                      >
                        <v-list-item-title style="font-size: 13px">
                          {{ TRANSITION_LABELS[target] }}
                        </v-list-item-title>
                      </v-list-item>
                    </v-list>
                  </v-menu>
                  <!-- keeps the eye icon aligned across rows when there's no menu -->
                  <span v-else class="action-btn action-btn--spacer" />
                </div>
              </td>
            </tr>
          </tbody>
        </v-table>

        <div v-if="meta.last_page > 1" class="d-flex justify-center py-4">
          <v-pagination
            v-model="filters.page"
            :length="meta.last_page"
            density="compact"
            :total-visible="6"
            @update:model-value="loadRequests"
          />
        </div>
      </v-card-text>
    </v-card>

    <!-- ─── View details — shared component, also used by Residents.vue ─ -->
    <DocumentRequestDetailDialog
      v-model="viewDialogOpen"
      :request-id="viewingId"
      @updated="loadRequests(filters.page)"
    />

    <!-- ─── Rejection reason dialog (list-level ⋮ menu actions) ──── -->
    <v-dialog v-model="rejectDialog.open" max-width="420">
      <v-card>
        <v-card-title style="font-size: 15px">Reject request</v-card-title>
        <v-card-text>
          <p style="font-size: 12px; color: #888; margin-bottom: 10px">
            This is shown to the resident, so be specific about what needs fixing.
          </p>
          <v-textarea
            v-model="rejectDialog.reason"
            variant="outlined"
            density="compact"
            rows="3"
            placeholder="e.g. Attached ID is expired. Please resubmit with a valid ID."
            hide-details
          />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="rejectDialog.open = false">Cancel</v-btn>
          <v-btn
            color="error"
            variant="flat"
            :disabled="!rejectDialog.reason.trim()"
            :loading="rejectDialog.submitting"
            @click="submitRejection"
          >
            Reject
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar.open" :color="snackbar.color" timeout="3000">
      {{ snackbar.text }}
    </v-snackbar>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import {
  fetchAdminDocumentRequests,
  updateAdminDocumentRequestStatus,
} from '@/api/adminDocumentRequests'
import { fetchDocumentTypes } from '@/api/documentRequests'
import DocumentRequestDetailDialog from '@/components/DocumentRequestDetailDialog.vue'

const route = useRoute()

const tableColumns = ['Resident', 'Document type', 'Tracking #', 'Status', 'Date', '']

const TRANSITIONS = {
  pending: ['processing', 'rejected', 'cancelled'],
  processing: ['ready_for_pickup', 'rejected'],
  ready_for_pickup: ['released'],
  released: [],
  rejected: [],
  cancelled: [],
}

const TRANSITION_LABELS = {
  processing: 'Mark as Processing',
  ready_for_pickup: 'Mark Ready for Pickup',
  released: 'Mark as Released',
  rejected: 'Reject request',
  cancelled: 'Cancel request',
}

const STATUS_META = {
  pending: { label: 'Pending', class: 'status-pending' },
  processing: { label: 'Processing', class: 'status-processing' },
  ready_for_pickup: { label: 'Ready', class: 'status-ready' },
  released: { label: 'Released', class: 'status-approved' },
  rejected: { label: 'Rejected', class: 'status-rejected' },
  cancelled: { label: 'Cancelled', class: 'status-rejected' },
}

const AVATAR_PALETTE = [
  { bg: '#e8f0fe', text: '#1a5fd8' },
  { bg: '#e8f5e9', text: '#27ae60' },
  { bg: '#fff3e0', text: '#e67e22' },
  { bg: '#fff9ed', text: '#a07020' },
]

const statusOptions = [
  { label: 'All statuses', value: null },
  ...Object.entries(STATUS_META).map(([value, meta]) => ({ label: meta.label, value })),
]

const filters = reactive({
  search: typeof route.query.search === 'string' ? route.query.search : '',
  status: null,
  documentTypeId: null,
  page: 1,
})

const documentTypeOptions = ref([{ id: null, name: 'All document types' }])
const requests = ref([])
const meta = ref({ total: 0, last_page: 1 })
const loading = ref(true)

const rejectDialog = reactive({
  open: false,
  reason: '',
  target: null,
  submitting: false,
})

// Everything the view dialog needs to fetch/render/act on a request now
// lives inside DocumentRequestDetailDialog — this page just owns which
// one is open.
const viewDialogOpen = ref(false)
const viewingId = ref(null)

const snackbar = reactive({ open: false, text: '', color: 'success' })

let searchDebounce = null
function onSearchInput() {
  clearTimeout(searchDebounce)
  searchDebounce = setTimeout(() => loadRequests(1), 350)
}

async function loadRequests(page = filters.page) {
  filters.page = page
  loading.value = true
  try {
    const { data, meta: pageMeta } = await fetchAdminDocumentRequests({
      per_page: 15,
      page,
      search: filters.search || undefined,
      status: filters.status || undefined,
      document_type_id: filters.documentTypeId || undefined,
    })

    requests.value = data.map((item, index) => {
      const statusMeta = STATUS_META[item.status] || { label: item.status, class: 'status-pending' }
      const palette = AVATAR_PALETTE[index % AVATAR_PALETTE.length]

      return {
        id: item.id,
        resident: item.requested_by?.name || 'Unknown resident',
        type: item.document_type.name,
        trackingNumber: item.tracking_number,
        status: item.status,
        statusLabel: statusMeta.label,
        statusClass: statusMeta.class,
        transitions: TRANSITIONS[item.status] || [],
        date: formatDate(item.timeline.requested_at),
        avatarBg: palette.bg,
        avatarText: palette.text,
      }
    })
    meta.value = pageMeta
  } catch (error) {
    requests.value = []
    console.error('Failed to load requests', error)
  } finally {
    loading.value = false
  }
}

async function loadDocumentTypeOptions() {
  try {
    const types = await fetchDocumentTypes()
    documentTypeOptions.value = [{ id: null, name: 'All document types' }, ...types]
  } catch {
    // Filter still works without this; just falls back to "All document types".
  }
}

function handleTransition(req, target) {
  if (target === 'rejected') {
    rejectDialog.target = req
    rejectDialog.reason = ''
    rejectDialog.open = true
    return
  }
  applyTransition(req, target)
}

async function applyTransition(req, target, extra = {}) {
  try {
    await updateAdminDocumentRequestStatus(req.id, { status: target, ...extra })
    showSnackbar(`${req.resident}'s request updated to "${TRANSITION_LABELS[target] || target}".`)
    loadRequests(filters.page)
  } catch (error) {
    showSnackbar(
      error?.response?.data?.message || 'Could not update this request. Please try again.',
      'error'
    )
  }
}

async function submitRejection() {
  rejectDialog.submitting = true
  try {
    await applyTransition(rejectDialog.target, 'rejected', {
      rejection_reason: rejectDialog.reason.trim(),
    })
    rejectDialog.open = false
  } finally {
    rejectDialog.submitting = false
  }
}

function openView(req) {
  viewingId.value = req.id
  viewDialogOpen.value = true
}

function showSnackbar(text, color = 'success') {
  snackbar.text = text
  snackbar.color = color
  snackbar.open = true
}

function initials(name) {
  return name
    .split(' ')
    .map((p) => p[0])
    .slice(0, 2)
    .join('')
    .toUpperCase()
}

function formatDate(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('en-PH', { month: 'short', day: 'numeric' })
}

onMounted(() => {
  loadRequests(1)
  loadDocumentTypeOptions()
})
</script>

<style scoped src="./DashboardCss.css"></style>

<style scoped src="./RequestsCss.css"></style>

<style>
.filter-select-menu .v-list-item-title {
  font-size: 12.5px;
}
.filter-select-menu .v-list-item {
  min-height: 34px;
}

/* ── Row layout ── */
.table-row td {
  vertical-align: middle;
  border-bottom: 1px solid #f2f2f2;
}
.table-row:last-child td {
  border-bottom: none;
}
.table-row:hover {
  background: #fafbfc;
}

/* ── Action buttons ── */
.table-head--actions {
  width: 84px;
}
.actions-cell {
  padding: 8px 5px;
  width: 84px;
}
.row-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 2px;
}
.action-btn {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  color: #8a8f98;
  transition: background 0.15s, color 0.15s;
}
.action-btn .v-icon {
  font-size: 18px;
}
.action-btn:hover {
  background: #f1f3f7;
  color: #0f1e3d;
}
.action-btn--spacer {
  display: inline-block;
  pointer-events: none;
}
</style>