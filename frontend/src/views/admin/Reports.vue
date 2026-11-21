<template>
  <div>
    <div v-if="loading" class="d-flex justify-center py-12">
      <v-progress-circular indeterminate size="26" color="#0f1e3d" />
    </div>

    <template v-else>
      <!-- ─── KPI cards ─────────────────────────────────────────── -->
      <v-row class="mb-2">
        <v-col cols="12" sm="6" lg="3">
          <div class="kpi-card" style="border-left-color: #0f1e3d">
            <div class="kpi-top">
              <p class="kpi-label">Total Residents</p>
              <p class="kpi-value">{{ summary.total_residents }}</p>
            </div>
            <p class="kpi-footer" style="visibility: hidden">placeholder</p>
          </div>
        </v-col>
        <v-col cols="12" sm="6" lg="3">
          <div class="kpi-card" style="border-left-color: #f5a623">
            <div class="kpi-top">
              <p class="kpi-label">Total Requests</p>
              <p class="kpi-value">{{ summary.total_requests }}</p>
            </div>
            <p class="kpi-footer kpi-delta" :class="monthDelta >= 0 ? 'kpi-delta--up' : 'kpi-delta--down'">
              <v-icon :icon="monthDelta >= 0 ? 'mdi-trending-up' : 'mdi-trending-down'" size="12" />
              {{ Math.abs(monthDelta) }} vs last month
            </p>
          </div>
        </v-col>
        <v-col cols="12" sm="6" lg="3">
          <div class="kpi-card" style="border-left-color: #2e86de">
            <div class="kpi-top">
              <p class="kpi-label">Total Appointments</p>
              <p class="kpi-value">{{ summary.total_appointments }}</p>
            </div>
            <p class="kpi-footer" style="visibility: hidden">placeholder</p>
          </div>
        </v-col>
        <v-col cols="12" sm="6" lg="3">
          <div class="kpi-card" style="border-left-color: #27ae60">
            <div class="kpi-top">
              <p class="kpi-label">Fees Collected</p>
              <p class="kpi-value">₱{{ summary.fees_collected.toFixed(2) }}</p>
            </div>
            <p class="kpi-footer kpi-hint">from paid requests only</p>
          </div>
        </v-col>
      </v-row>

      <v-row>
        <!-- ─── Requests over time ──────────────────────────────── -->
        <v-col cols="12" lg="7">
          <v-card variant="flat" class="chart-card">
            <p class="chart-title">Requests — last 14 days</p>
            <svg
              class="trend-chart"
              :viewBox="`0 0 ${trendWidth} 90`"
              preserveAspectRatio="none"
            >
              <rect
                v-for="(day, i) in requestsOverTime"
                :key="day.date"
                :x="i * (barWidth + barGap)"
                :y="70 - barHeight(day.count)"
                :width="barWidth"
                :height="barHeight(day.count) || 1"
                rx="1.5"
                fill="#0f1e3d"
              />
            </svg>
            <div class="trend-labels">
              <span>{{ requestsOverTime[0]?.label }}</span>
              <span>{{ requestsOverTime[requestsOverTime.length - 1]?.label }}</span>
            </div>
          </v-card>
        </v-col>

        <!-- ─── Requests by status ──────────────────────────────── -->
        <v-col cols="12" lg="5">
          <v-card variant="flat" class="chart-card">
            <p class="chart-title">Requests by Status</p>
            <div v-for="row in requestsByStatus" :key="row.status" class="bar-row">
              <span class="bar-label">{{ row.label }}</span>
              <div class="bar-track">
                <div
                  class="bar-fill"
                  :style="{ width: barPercent(row.count, maxStatusCount) + '%', background: STATUS_COLOR[row.status] }"
                />
              </div>
              <span class="bar-count">{{ row.count }}</span>
            </div>
          </v-card>
        </v-col>
      </v-row>

      <v-row>
        <!-- ─── Requests by document type ───────────────────────── -->
        <v-col cols="12" lg="6">
          <v-card variant="flat" class="chart-card">
            <p class="chart-title">Requests by Document Type</p>
            <p v-if="!requestsByType.length" class="empty-text">No requests yet.</p>
            <div v-for="row in requestsByType" :key="row.name" class="bar-row">
              <span class="bar-label">{{ row.name }}</span>
              <div class="bar-track">
                <div
                  class="bar-fill"
                  :style="{ width: barPercent(row.count, maxTypeCount) + '%', background: '#f5a623' }"
                />
              </div>
              <span class="bar-count">{{ row.count }}</span>
            </div>
          </v-card>
        </v-col>

        <!-- ─── Residents by purok ──────────────────────────────── -->
        <v-col cols="12" lg="6">
          <v-card variant="flat" class="chart-card">
            <p class="chart-title">Residents by Purok</p>
            <p v-if="!residentsByPurok.length" class="empty-text">No residents registered yet.</p>
            <div v-for="row in residentsByPurok" :key="row.purok" class="bar-row">
              <span class="bar-label">{{ row.purok }}</span>
              <div class="bar-track">
                <div
                  class="bar-fill"
                  :style="{ width: barPercent(row.count, maxPurokCount) + '%', background: '#2e86de' }"
                />
              </div>
              <span class="bar-count">{{ row.count }}</span>
            </div>
          </v-card>
        </v-col>
      </v-row>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { fetchAdminReports } from '@/api/adminReports'

const loading = ref(true)
const summary = ref({
  total_residents: 0,
  total_requests: 0,
  total_appointments: 0,
  fees_collected: 0,
  requests_this_month: 0,
  requests_last_month: 0,
})
const requestsByStatus = ref([])
const requestsByType = ref([])
const requestsOverTime = ref([])
const residentsByPurok = ref([])

const STATUS_COLOR = {
  pending: '#e6a417',
  processing: '#1a5fd8',
  ready_for_pickup: '#a07020',
  released: '#27ae60',
  rejected: '#c0392b',
  cancelled: '#999999',
}

const monthDelta = computed(() => summary.value.requests_this_month - summary.value.requests_last_month)

const maxStatusCount = computed(() => Math.max(1, ...requestsByStatus.value.map((r) => r.count)))
const maxTypeCount = computed(() => Math.max(1, ...requestsByType.value.map((r) => r.count)))
const maxPurokCount = computed(() => Math.max(1, ...residentsByPurok.value.map((r) => r.count)))

function barPercent(count, max) {
  return Math.round((count / max) * 100)
}

// ── Trend chart geometry ──────────────────────────────────────────────
const trendWidth = computed(() => requestsOverTime.value.length * (14 + 4))
const barWidth = 14
const barGap = 4

function barHeight(count) {
  const max = Math.max(1, ...requestsOverTime.value.map((d) => d.count))
  return Math.round((count / max) * 60)
}

async function load() {
  loading.value = true
  try {
    const data = await fetchAdminReports()
    summary.value = data.summary
    requestsByStatus.value = data.requests_by_status
    requestsByType.value = data.requests_by_type
    requestsOverTime.value = data.requests_over_time
    residentsByPurok.value = data.residents_by_purok
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<style scoped src="./ReportsCss.css">
</style>