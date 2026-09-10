<template>
  <div>
    <!-- ─── Stat cards ────────────────────────────────────────────── -->
    <v-row class="mb-2">
      <v-col v-for="stat in stats" :key="stat.label" cols="12" sm="6" lg="3">
        <StatCard
          :label="stat.label"
          :value="stat.value"
          :accent-color="stat.accentColor"
          :delta="stat.delta"
          :delta-icon="stat.deltaIcon"
          :delta-color="stat.deltaColor"
          :loading="stat.loading"
        />
      </v-col>
    </v-row>

    <!-- ─── Recent requests + Today's appointments ───────────────── -->
    <v-row>
      <!-- Recent requests table -->
      <v-col cols="12" lg="7">
        <v-card
          variant="flat"
          style="background: #ffffff; border-radius: 10px; box-shadow: none"
        >
          <div class="d-flex align-center px-5 pt-5 pb-0">
            <v-icon
              icon="mdi-inbox-outline"
              size="16"
              style="color: #f5a623; margin-right: 7px"
            />
            <span style="font-size: 14px; font-weight: 600; color: #1a1a1a">
              Recent requests
            </span>
          </div>

          <v-card-text class="px-5 pt-4 pb-5">
            <div v-if="loadingRequests" class="d-flex justify-center py-8">
              <v-progress-circular indeterminate size="22" color="#0f1e3d" />
            </div>

            <p
              v-else-if="!recentRequests.length"
              style="font-size: 13px; color: #999; padding: 12px 0"
            >
              No document requests yet.
            </p>

            <v-table v-else density="compact">
              <thead>
                <tr>
                  <th
                    v-for="col in tableColumns"
                    :key="col"
                    style="
                      font-size: 11px;
                      font-weight: 500;
                      color: #999;
                      border-bottom: 1px solid #f0ede3;
                      padding: 0 0 8px;
                      text-transform: none;
                      letter-spacing: 0;
                    "
                  >
                    {{ col }}
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="req in recentRequests"
                  :key="req.id"
                  style="border-bottom: 1px solid #f7f5f0"
                >
                  <!-- Resident -->
                  <td style="padding: 10px 0">
                    <div class="d-flex align-center ga-2">
                      <v-avatar size="26" :style="{ background: req.avatarBg }">
                        <span
                          style="font-size: 10px; font-weight: 600"
                          :style="{ color: req.avatarText }"
                        >
                          {{ initials(req.resident) }}
                        </span>
                      </v-avatar>
                      <span style="font-size: 13px; font-weight: 500; color: #1a1a1a">
                        {{ req.resident }}
                      </span>
                    </div>
                  </td>
                  <!-- Type -->
                  <td
                    style="
                      font-size: 12px;
                      color: #666;
                      padding: 10px 8px;
                      max-width: 180px;
                    "
                  >
                    {{ req.type }}
                  </td>
                  <!-- Status -->
                  <td style="padding: 10px 8px">
                    <span class="status-chip" :class="req.statusClass">
                      {{ req.statusLabel }}
                    </span>
                  </td>
                  <!-- Date -->
                  <td style="font-size: 11px; color: #aaa; padding: 10px 0; text-align: right">
                    {{ req.date }}
                  </td>
                </tr>
              </tbody>
            </v-table>

            <div class="mt-4">
              <v-btn
                variant="text"
                size="small"
                to="/admin/clearances"
                style="
                  color: #0f1e3d;
                  font-size: 12px;
                  text-transform: none;
                  letter-spacing: 0;
                  padding: 0;
                "
                append-icon="mdi-arrow-right"
              >
                View all requests
              </v-btn>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Today's appointments -->
      <v-col cols="12" lg="5">
        <v-card
          variant="flat"
          style="background: #ffffff; border-radius: 10px; box-shadow: none; height: 100%"
        >
          <div class="d-flex align-center justify-space-between px-5 pt-5 pb-0">
            <div class="d-flex align-center">
              <v-icon
                icon="mdi-calendar-check-outline"
                size="16"
                style="color: #f5a623; margin-right: 7px"
              />
              <span style="font-size: 14px; font-weight: 600; color: #1a1a1a">
                Today's appointments
              </span>
            </div>
            <span style="font-size: 11px; color: #aaa">{{ todayLabel }}</span>
          </div>

          <v-card-text class="px-5 pt-4 pb-2">
            <div v-if="loadingAppointments" class="d-flex justify-center py-8">
              <v-progress-circular indeterminate size="20" color="#0f1e3d" />
            </div>

            <p
              v-else-if="!appointments.length"
              style="font-size: 12px; color: #999; padding: 8px 0 16px"
            >
              No appointments scheduled for today.
            </p>

            <div
              v-else
              v-for="(appt, i) in appointments"
              :key="appt.id"
              class="d-flex align-center ga-3 py-3"
              :style="{
                borderBottom:
                  i < appointments.length - 1 ? '1px solid #f0ede3' : 'none',
              }"
            >
              <!-- Date badge -->
              <div class="appt-date-badge">
                <div class="appt-day">{{ appt.day }}</div>
                <div class="appt-mon">{{ appt.mon }}</div>
              </div>
              <!-- Info -->
              <div style="flex: 1; min-width: 0">
                <div
                  style="
                    font-size: 13px;
                    font-weight: 500;
                    color: #1a1a1a;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                  "
                >
                  {{ appt.resident }}
                </div>
                <div style="font-size: 11px; color: #aaa; margin-top: 1px">
                  {{ appt.purpose }}
                </div>
              </div>
              <!-- Time -->
              <div class="d-flex align-center ga-1" style="font-size: 11px; color: #888">
                <v-icon icon="mdi-clock-outline" size="12" />
                {{ appt.time }}
              </div>
            </div>
          </v-card-text>

          <!-- Walk-in notice -->
          <div class="mx-5 mb-5">
            <div
              class="d-flex align-center ga-2 px-3 py-2"
              style="
                background: #fff9ed;
                border-radius: 8px;
                border: 1px solid #f5a62340;
              "
            >
              <v-icon icon="mdi-information-outline" size="15" style="color: #f5a623" />
              <span style="font-size: 11px; color: #a07020">
                Walk-in slots available: 2 remaining today
              </span>
            </div>
          </div>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { fetchAdminDocumentRequests } from '@/api/adminDocumentRequests'
import { fetchAdminAppointments } from '@/api/adminAppointments'
import StatCard from '@/components/dashboard/StatCard.vue'

// ── Stat cards ──────────────────────────────────────────────────────
const totalRequests = ref(0)
const totalRequestsLoading = ref(true)
const pendingApproval = ref(0)
const pendingApprovalLoading = ref(true)

const stats = computed(() => [
  {
    label: 'Total requests',
    value: totalRequests.value,
    accentColor: '#0f1e3d',
    delta: 'all time',
    deltaIcon: 'mdi-file-multiple-outline',
    deltaColor: '#888',
    loading: totalRequestsLoading.value,
  },
  {
    label: 'Pending approval',
    value: pendingApproval.value,
    accentColor: '#f5a623',
    delta: 'awaiting review',
    deltaIcon: 'mdi-clock-outline',
    deltaColor: '#e67e22',
    loading: pendingApprovalLoading.value,
  },
  {
    label: 'Released today',
    value: '9',
    accentColor: '#27ae60',
    delta: '3 awaiting pickup',
    deltaIcon: 'mdi-check',
    deltaColor: '#27ae60',
    loading: false,
  },
  {
    label: "Today's appointments",
    value: appointments.value.length,
    accentColor: '#2e86de',
    delta: appointments.value.length ? `Next at ${appointments.value[0].time}` : 'None scheduled',
    deltaIcon: 'mdi-calendar-outline',
    deltaColor: '#888',
    loading: loadingAppointments.value,
  },
])

async function loadStats() {
  totalRequestsLoading.value = true
  pendingApprovalLoading.value = true
  try {
    const [totalRes, pendingRes] = await Promise.all([
      fetchAdminDocumentRequests({ per_page: 1 }),
      fetchAdminDocumentRequests({ status: 'pending', per_page: 1 }),
    ])
    totalRequests.value = totalRes.meta?.total ?? 0
    pendingApproval.value = pendingRes.meta?.total ?? 0
  } catch (error) {
    console.error('Failed to load stats', error)
  } finally {
    totalRequestsLoading.value = false
    pendingApprovalLoading.value = false
  }
}

// ── Recent requests ─────────────────────────────────────────────────
const recentRequests = ref([])
const loadingRequests = ref(true)
const tableColumns = ['Resident', 'Document type', 'Status', 'Date']

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

async function loadRecentRequests() {
  loadingRequests.value = true
  try {
    const { data } = await fetchAdminDocumentRequests({ per_page: 5 })

    recentRequests.value = data.map((item, index) => {
      const meta = STATUS_META[item.status] || { label: item.status, class: 'status-pending' }
      const palette = AVATAR_PALETTE[index % AVATAR_PALETTE.length]

      return {
        id: item.id,
        resident: item.requested_by?.name || 'Unknown resident',
        type: item.document_type.name,
        statusLabel: meta.label,
        statusClass: meta.class,
        date: formatDate(item.timeline.requested_at),
        avatarBg: palette.bg,
        avatarText: palette.text,
      }
    })
  } catch (error) {
    recentRequests.value = []
    console.error('Failed to load recent requests', error)
  } finally {
    loadingRequests.value = false
  }
}

// ── Appointments (now real) ──────────────────────────────────────────
const appointments = ref([])
const loadingAppointments = ref(true)

async function loadAppointments() {
  loadingAppointments.value = true
  try {
    const data = await fetchAdminAppointments()

    appointments.value = data.map((item) => {
      const date = new Date(item.scheduled_at)
      return {
        id: item.id,
        resident: item.resident_name,
        purpose: item.purpose,
        time: date.toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' }),
        day: date.toLocaleDateString('en-PH', { day: 'numeric' }),
        mon: date.toLocaleDateString('en-PH', { month: 'short' }),
      }
    })
  } catch (error) {
    appointments.value = []
    console.error('Failed to load appointments', error)
  } finally {
    loadingAppointments.value = false
  }
}

onMounted(() => {
  loadStats()
  loadRecentRequests()
  loadAppointments()
})

// ── Helpers ─────────────────────────────────────────────────────────
const todayLabel = computed(() => {
  return new Date().toLocaleDateString('en-PH', { weekday: 'short', month: 'short', day: 'numeric' })
})

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
</script>

<style scoped src="./DashboardCss.css">
</style>