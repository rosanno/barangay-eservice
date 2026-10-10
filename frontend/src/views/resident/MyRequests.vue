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
const confirmCancel = ref({ open: false, item: null })
const cancelling = ref(false)

// The API sends document_type as an object ({ id, code, name, ... }); fall
// back to the DOCUMENT_TYPES lookup only if it's ever a plain string.
const typeTitle = (v) =>
  v && typeof v === 'object' ? v.name : DOCUMENT_TYPES.find((t) => t.value === v)?.title ?? v

const filtered = computed(() => {
  const q = search.value.trim().toLowerCase()
  return requests.value.filter((r) => {
    const matchStatus = statusFilter.value === 'all' || r.status === statusFilter.value
    const matchText =
      !q ||
      r.reference_no?.toLowerCase().includes(q) ||
      typeTitle(r.document_type).toLowerCase().includes(q)
    return matchStatus && matchText
  })
})

const statusOptions = [
  { title: 'All statuses', value: 'all' },
  ...Object.entries(STATUS).map(([value, s]) => ({ title: s.label, value })),
]

const tableColumns = ['Reference', 'Document', 'Purpose', 'Requested', 'Status', '']

// Maps the Laravel DocumentRequestResource shape onto the field names this
// page already reads, so the template and filtering stay untouched.
const normalize = (r) => ({
  ...r,
  reference_no: r.reference_no ?? r.tracking_number,
  created_at: r.created_at ?? r.timeline?.requested_at,
})

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await getMyRequests()
    requests.value = data.map(normalize)
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

onMounted(load)
</script>

<template>
  <div>
    <!-- ─── Header + filters ─────────────────────────────────────── -->
    <div class="d-flex flex-wrap align-center justify-space-between mb-4 ga-3">
      <div>
        <div class="d-flex align-center">
          <v-icon icon="mdi-file-document-outline" size="16" style="color: #f5a623; margin-right: 7px" />
          <span style="font-size: 14px; font-weight: 600; color: #1a1a1a">My Requests</span>
        </div>
        <p style="font-size: 11px; color: #aaa; margin: 2px 0 0 23px">
          {{ requests.length }} total
        </p>
      </div>

      <div class="d-flex flex-wrap ga-2">
        <v-text-field
          v-model="search"
          density="compact"
          variant="outlined"
          placeholder="Search reference no. or document"
          prepend-inner-icon="mdi-magnify"
          hide-details
          clearable
          class="filter-input"
          style="width: 250px"
        />
        <v-select
          v-model="statusFilter"
          :items="statusOptions"
          density="compact"
          variant="outlined"
          hide-details
          class="filter-input"
          style="width: 170px"
          :menu-props="{ contentClass: 'filter-select-menu' }"
        />
        <v-btn
          style="background: #0f1e3d; color: #fff; text-transform: none"
          prepend-icon="mdi-plus"
          @click="router.push('/documents/request')"
        >
          New request
        </v-btn>
      </div>
    </div>

    <v-alert v-if="error" type="error" variant="tonal" class="mb-4" closable @click:close="error = ''">
      {{ error }}
    </v-alert>

    <!-- ─── Table ────────────────────────────────────────────────── -->
    <v-card variant="flat" style="background: #ffffff; border-radius: 10px; box-shadow: none">
      <v-card-text class="px-5 pt-5 pb-5">
        <div v-if="loading" class="d-flex justify-center py-10">
          <v-progress-circular indeterminate size="24" color="#0f1e3d" />
        </div>

        <v-table v-else-if="filtered.length" density="comfortable">
          <thead>
            <tr>
              <th
                v-for="col in tableColumns"
                :key="col || 'actions'"
                class="table-head"
                :class="{
                  'table-head--actions': !col,
                  'hide-sm': col === 'Purpose' || col === 'Requested',
                }"
              >
                {{ col }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="r in filtered"
              :key="r.id"
              class="table-row table-row--clickable"
              @click="router.push(`/resident/requests/${r.id}`)"
            >
              <td style="font-size: 12px; font-weight: 600; color: #0f1e3d; padding: 12px 8px; white-space: nowrap">
                {{ r.reference_no }}
              </td>
              <td style="font-size: 13px; font-weight: 500; color: #1a1a1a; padding: 12px 8px">
                {{ typeTitle(r.document_type) }}
              </td>
              <td class="hide-sm" style="font-size: 12px; color: #666; padding: 12px 8px">{{ r.purpose }}</td>
              <td class="hide-sm" style="font-size: 11px; color: #aaa; padding: 12px 8px; white-space: nowrap">
                {{ formatDate(r.created_at) }}
              </td>
              <td style="padding: 12px 8px">
                <span
                  class="status-chip"
                  :style="{ color: STATUS[r.status]?.color, background: STATUS[r.status]?.bg }"
                >
                  {{ STATUS[r.status]?.label ?? r.status }}
                </span>
              </td>

              <td class="actions-cell" @click.stop>
                <div class="row-actions">
                  <v-tooltip text="View details" location="top">
                    <template #activator="{ props }">
                      <v-btn
                        v-bind="props"
                        icon="mdi-eye-outline"
                        variant="text"
                        density="comfortable"
                        class="action-btn"
                        aria-label="View request"
                        @click="router.push(`/resident/requests/${r.id}`)"
                      />
                    </template>
                  </v-tooltip>

                  <v-menu v-if="r.status === 'pending'">
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
                        prepend-icon="mdi-close-circle-outline"
                        title="Cancel request"
                        base-color="error"
                        @click="confirmCancel = { open: true, item: r }"
                      />
                    </v-list>
                  </v-menu>
                  <!-- keeps the eye icon aligned across rows when there's no menu -->
                  <span v-else class="action-btn action-btn--spacer" />
                </div>
              </td>
            </tr>
          </tbody>
        </v-table>

        <div v-else class="empty">
          <v-icon size="36" color="#f5a623">mdi-file-document-outline</v-icon>
          <p class="empty__title">
            {{ requests.length ? 'No requests match your filters' : 'You have no requests yet' }}
          </p>
          <p class="empty__text">
            {{ requests.length ? 'Clear the search or choose another status.' : 'Request a clearance or certificate and track it here.' }}
          </p>
          <v-btn
            v-if="!requests.length"
            style="background: #0f1e3d; color: #fff; text-transform: none"
            @click="router.push('/resident/requests/new')"
          >
            New request
          </v-btn>
        </div>
      </v-card-text>
    </v-card>

    <!-- ─── Cancel confirm ───────────────────────────────────────── -->
    <v-dialog v-model="confirmCancel.open" max-width="420">
      <v-card>
        <v-card-title style="font-size: 15px">Cancel this request?</v-card-title>
        <v-card-text style="font-size: 13px; color: #555">
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

.table-head--actions {
  width: 84px;
}

.table-row td {
  vertical-align: middle;
  border-bottom: 1px solid #f7f5f0;
}

.table-row:last-child td {
  border-bottom: none;
}

.table-row:hover {
  background: #fafbfc;
}

.table-row--clickable {
  cursor: pointer;
}

/* Same pill shape as the admin pages; colors still come from STATUS */
.status-chip {
  font-size: 10px;
  font-weight: 600;
  border-radius: 20px;
  padding: 2px 10px;
  display: inline-block;
  white-space: nowrap;
}

.filter-input :deep(.v-field__input),
.filter-input :deep(input),
.filter-input :deep(.v-select__selection-text) {
  font-size: 12.5px;
}

.filter-input :deep(.v-field__input) {
  min-height: 36px;
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

.empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 40px 16px;
}

.empty__title {
  font-size: 14px;
  font-weight: 600;
  color: #1a1a1a;
  margin: 12px 0 0;
}

.empty__text {
  font-size: 12px;
  color: #999;
  margin: 4px 0 16px;
}

@media (max-width: 640px) {
  .hide-sm {
    display: none;
  }
}
</style>

<style>
.filter-select-menu .v-list-item-title {
  font-size: 12.5px;
}
.filter-select-menu .v-list-item {
  min-height: 34px;
}
</style>