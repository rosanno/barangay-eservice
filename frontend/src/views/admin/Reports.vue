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
            <div class="chart-box" style="height: 220px">
              <Bar :data="trendData" :options="trendOptions" />
            </div>
          </v-card>
        </v-col>

        <!-- ─── Requests by status ──────────────────────────────── -->
        <v-col cols="12" lg="5">
          <v-card variant="flat" class="chart-card">
            <p class="chart-title">Requests by Status</p>
            <p v-if="!requestsByStatus.length" class="empty-text">No requests yet.</p>
            <div v-else class="chart-box" :style="{ height: hBarHeight(requestsByStatus.length) }">
              <Bar :data="statusData" :options="hBarOptions" />
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
            <div v-else class="chart-box" :style="{ height: hBarHeight(requestsByType.length) }">
              <Bar :data="typeData" :options="hBarOptions" />
            </div>
          </v-card>
        </v-col>

        <!-- ─── Residents by purok ──────────────────────────────── -->
        <v-col cols="12" lg="6">
          <v-card variant="flat" class="chart-card">
            <p class="chart-title">Residents by Purok</p>
            <p v-if="!residentsByPurok.length" class="empty-text">No residents registered yet.</p>
            <div v-else class="chart-box" :style="{ height: hBarHeight(residentsByPurok.length) }">
              <Bar :data="purokData" :options="hBarOptions" />
            </div>
          </v-card>
        </v-col>
      </v-row>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { Bar } from 'vue-chartjs'
import {
  Chart as ChartJS,
  BarElement,
  CategoryScale,
  LinearScale,
  Tooltip,
} from 'chart.js'
import { fetchAdminReports } from '@/api/adminReports'

ChartJS.register(BarElement, CategoryScale, LinearScale, Tooltip)

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

// ── Chart data ────────────────────────────────────────────────────────
const trendData = computed(() => ({
  labels: requestsOverTime.value.map((d) => d.label),
  datasets: [
    {
      label: 'Requests',
      data: requestsOverTime.value.map((d) => d.count),
      backgroundColor: '#0f1e3d',
      borderRadius: 3,
      maxBarThickness: 22,
    },
  ],
}))

const statusData = computed(() => ({
  labels: requestsByStatus.value.map((r) => r.label),
  datasets: [
    {
      label: 'Requests',
      data: requestsByStatus.value.map((r) => r.count),
      backgroundColor: requestsByStatus.value.map((r) => STATUS_COLOR[r.status] ?? '#999999'),
      borderRadius: 3,
      maxBarThickness: 16,
    },
  ],
}))

const typeData = computed(() => ({
  labels: requestsByType.value.map((r) => r.name),
  datasets: [
    {
      label: 'Requests',
      data: requestsByType.value.map((r) => r.count),
      backgroundColor: '#f5a623',
      borderRadius: 3,
      maxBarThickness: 16,
    },
  ],
}))

const purokData = computed(() => ({
  labels: residentsByPurok.value.map((r) => r.purok),
  datasets: [
    {
      label: 'Residents',
      data: residentsByPurok.value.map((r) => r.count),
      backgroundColor: '#2e86de',
      borderRadius: 3,
      maxBarThickness: 16,
    },
  ],
}))

// ── Chart options ─────────────────────────────────────────────────────
const baseOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: { displayColors: false },
  },
}

const trendOptions = {
  ...baseOptions,
  scales: {
    x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 7 } },
    y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#eef0f4' } },
  },
}

const hBarOptions = {
  ...baseOptions,
  indexAxis: 'y',
  scales: {
    x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#eef0f4' } },
    y: { grid: { display: false } },
  },
}

// Give horizontal charts ~30px per row so bars stay a consistent size
function hBarHeight(rows) {
  return `${Math.max(rows, 1) * 30 + 40}px`
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