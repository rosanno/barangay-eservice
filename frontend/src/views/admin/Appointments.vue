<template>
  <div>
    <!-- ─── Header + filters ─────────────────────────────────────── -->
    <div class="d-flex flex-wrap align-center justify-space-between mb-4 ga-3">
      <div>
        <div class="d-flex align-center">
          <v-icon icon="mdi-calendar-check-outline" size="16" style="color: #f5a623; margin-right: 7px" />
          <span style="font-size: 14px; font-weight: 600; color: #1a1a1a">Appointments</span>
        </div>
        <p style="font-size: 11px; color: #aaa; margin: 2px 0 0 23px">
          {{ appointments.length }} on {{ friendlyDate }}
        </p>
      </div>

      <div class="d-flex flex-wrap ga-2">
        <v-text-field
          v-model="filters.search"
          density="compact"
          variant="outlined"
          placeholder="Search resident"
          prepend-inner-icon="mdi-magnify"
          hide-details
          class="filter-input"
          style="width: 200px"
        />
        <v-text-field
          v-model="filters.date"
          type="date"
          density="compact"
          variant="outlined"
          hide-details
          class="filter-input"
          style="width: 170px"
          @update:model-value="loadAppointments"
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
          @update:model-value="loadAppointments"
        />
      </div>
    </div>

    <!-- ─── Table ────────────────────────────────────────────────── -->
    <v-card variant="flat" style="background: #ffffff; border-radius: 10px; box-shadow: none">
      <v-card-text class="px-5 pt-5 pb-5">
        <div v-if="loading" class="d-flex justify-center py-10">
          <v-progress-circular indeterminate size="24" color="#0f1e3d" />
        </div>

        <p
          v-else-if="!filteredAppointments.length"
          style="font-size: 13px; color: #999; padding: 32px 20px"
        >
          No appointments match these filters.
        </p>

        <v-table v-else density="comfortable">
          <thead>
            <tr>
              <th v-for="col in tableColumns" :key="col" class="table-head">{{ col }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="appt in filteredAppointments" :key="appt.id" class="table-row">
              <td style="font-size: 12px; color: #666; padding: 12px 8px; white-space: nowrap">
                {{ appt.time }}
              </td>
              <td style="padding: 12px 8px">
                <div class="d-flex align-center ga-2">
                  <v-avatar size="26" :style="{ background: appt.avatarBg }">
                    <span style="font-size: 10px; font-weight: 600" :style="{ color: appt.avatarText }">
                      {{ initials(appt.resident) }}
                    </span>
                  </v-avatar>
                  <span style="font-size: 13px; font-weight: 500; color: #1a1a1a">
                    {{ appt.resident }}
                  </span>
                </div>
              </td>
              <td style="font-size: 12px; color: #666; padding: 12px 8px; max-width: 220px">
                {{ appt.purpose }}
              </td>
              <td style="padding: 12px 8px">
                <span class="status-chip" :class="appt.statusClass">{{ appt.statusLabel }}</span>
              </td>
              <td style="padding: 12px 0; text-align: right">
                <v-menu v-if="appt.transitions.length">
                  <template #activator="{ props }">
                    <v-btn icon variant="text" size="small" v-bind="props">
                      <v-icon icon="mdi-dots-vertical" size="18" style="color: #888" />
                    </v-btn>
                  </template>
                  <v-list density="compact">
                    <v-list-item
                      v-for="target in appt.transitions"
                      :key="target"
                      @click="applyTransition(appt, target)"
                    >
                      <v-list-item-title style="font-size: 13px">
                        {{ TRANSITION_LABELS[target] }}
                      </v-list-item-title>
                    </v-list-item>
                  </v-list>
                </v-menu>
                <span v-else style="font-size: 11px; color: #ccc">—</span>
              </td>
            </tr>
          </tbody>
        </v-table>
      </v-card-text>
    </v-card>

    <v-snackbar v-model="snackbar.open" :color="snackbar.color" timeout="3000">
      {{ snackbar.text }}
    </v-snackbar>
  </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { fetchAdminAppointments, updateAdminAppointmentStatus } from '@/api/adminAppointments'

const tableColumns = ['Time', 'Resident', 'Purpose', 'Status', '']

// Mirrors AppointmentStatus::allowedTransitions() on the backend — the
// backend still enforces this independently (422 on an invalid jump);
// this list only decides which menu items to show.
const TRANSITIONS = {
  scheduled: ['completed', 'cancelled', 'no_show'],
  completed: [],
  cancelled: [],
  no_show: [],
}

const TRANSITION_LABELS = {
  completed: 'Mark as Completed',
  cancelled: 'Cancel appointment',
  no_show: 'Mark as No Show',
}

const STATUS_META = {
  scheduled: { label: 'Scheduled', class: 'status-processing' },
  completed: { label: 'Completed', class: 'status-approved' },
  cancelled: { label: 'Cancelled', class: 'status-rejected' },
  no_show: { label: 'No Show', class: 'status-rejected' },
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

function todayIso() {
  return new Date().toISOString().slice(0, 10)
}

const filters = reactive({
  search: '',
  date: todayIso(),
  status: null,
})

const appointments = ref([])
const loading = ref(true)
const snackbar = reactive({ open: false, text: '', color: 'success' })

// Client-side only — the backend doesn't support a resident-name search
// param yet, but a single day's appointment list is small enough that
// filtering what's already loaded is reasonable rather than adding a
// dedicated ?search= endpoint for this.
const filteredAppointments = computed(() => {
  if (!filters.search.trim()) return appointments.value
  const term = filters.search.trim().toLowerCase()
  return appointments.value.filter((a) => a.resident.toLowerCase().includes(term))
})

const friendlyDate = computed(() => {
  return new Date(`${filters.date}T00:00:00`).toLocaleDateString('en-PH', {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
  })
})

async function loadAppointments() {
  loading.value = true
  try {
    const data = await fetchAdminAppointments({
      date: filters.date,
      status: filters.status || undefined,
    })

    appointments.value = data.map((item, index) => {
      const meta = STATUS_META[item.status] || { label: item.status, class: 'status-pending' }
      const palette = AVATAR_PALETTE[index % AVATAR_PALETTE.length]
      const time = new Date(item.scheduled_at).toLocaleTimeString('en-PH', {
        hour: 'numeric',
        minute: '2-digit',
      })

      return {
        id: item.id,
        resident: item.resident_name,
        purpose: item.purpose,
        time,
        status: item.status,
        statusLabel: meta.label,
        statusClass: meta.class,
        transitions: TRANSITIONS[item.status] || [],
        avatarBg: palette.bg,
        avatarText: palette.text,
      }
    })
  } catch (error) {
    appointments.value = []
    console.error('Failed to load appointments', error)
  } finally {
    loading.value = false
  }
}

async function applyTransition(appt, target) {
  try {
    await updateAdminAppointmentStatus(appt.id, { status: target })
    showSnackbar(`${appt.resident}'s appointment marked "${TRANSITION_LABELS[target]}".`)
    loadAppointments()
  } catch (error) {
    showSnackbar(
      error?.response?.data?.message || 'Could not update this appointment. Please try again.',
      'error'
    )
  }
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

watch(() => filters.status, loadAppointments)
loadAppointments()
</script>

<style scoped src="./DashboardCss.css"></style>

<style scoped>
.table-head {
  font-size: 11px;
  font-weight: 500;
  color: #999;
  border-bottom: 1px solid #f0ede3;
  padding: 0 8px 8px 0;
  text-transform: none;
  letter-spacing: 0;
  text-align: left;
}

.table-row {
  border-bottom: 1px solid #f7f5f0;
}

.filter-input :deep(.v-field__input),
.filter-input :deep(input),
.filter-input :deep(.v-select__selection-text) {
  font-size: 12.5px;
}

.filter-input :deep(.v-field__input) {
  min-height: 36px;
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