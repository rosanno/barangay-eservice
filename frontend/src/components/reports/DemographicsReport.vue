<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { Bar, Doughnut } from 'vue-chartjs'
import {
  Chart as ChartJS, Tooltip, Legend, BarElement, ArcElement, CategoryScale, LinearScale,
} from 'chart.js'
import { getDemographics } from '@/api/reports'

ChartJS.register(Tooltip, Legend, BarElement, ArcElement, CategoryScale, LinearScale)

// Chart text follows the admin scale (11px, muted gray) and uses the same
// font as the rest of the app instead of Chart.js's built-in Helvetica.
ChartJS.defaults.font.size = 11
ChartJS.defaults.color = '#888'
ChartJS.defaults.font.family = getComputedStyle(document.body).fontFamily || ChartJS.defaults.font.family

const NAVY = '#0B1F4B'
const GOLD = '#C9A227'
const PALETTE = [NAVY, GOLD, '#2F4B8A', '#E3C565', '#5C7CBF', '#8C6D12', '#9FB3DC']

const loading = ref(true)
const error = ref('')
const report = ref(null)
const filters = ref({ purok: null, status: 'verified' })

async function load() {
  loading.value = true
  error.value = ''
  try {
    report.value = await getDemographics({
      purok: filters.value.purok || undefined,
      status: filters.value.status,
    })
  } catch (e) {
    error.value = e?.response?.data?.message || 'Failed to load demographics.'
  } finally {
    loading.value = false
  }
}

onMounted(load)
watch(filters, load, { deep: true })

const labels = (rows) => rows.map((r) => r.label)
const values = (rows) => rows.map((r) => r.total)

const bar = (rows, color = NAVY) => ({
  labels: labels(rows),
  datasets: [{ data: values(rows), backgroundColor: color, borderRadius: 6 }],
})

const doughnut = (labs, data) => ({
  labels: labs,
  datasets: [{ data, backgroundColor: PALETTE.slice(0, labs.length), borderWidth: 0 }],
})

const vertical = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { precision: 0 } } },
}
const horizontal = {
  responsive: true,
  maintainAspectRatio: false,
  indexAxis: 'y',
  plugins: { legend: { display: false } },
  scales: { x: { beginAtZero: true, ticks: { precision: 0 } }, y: { grid: { display: false } } },
}
const donutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  cutout: '62%',
  plugins: {
    legend: {
      position: 'bottom',
      labels: { boxWidth: 10, boxHeight: 10, padding: 14, font: { size: 11 } },
    },
  },
}

const ageChart = computed(() => bar(report.value.age_groups))
const purokChart = computed(() => ({
  labels: labels(report.value.purok),
  datasets: [{ data: report.value.purok.map((p) => p.population), backgroundColor: GOLD, borderRadius: 6 }],
}))
const sexChart = computed(() => doughnut(['Male', 'Female'], [report.value.sex.male, report.value.sex.female]))
const employmentChart = computed(() =>
  doughnut(labels(report.value.employment.breakdown), values(report.value.employment.breakdown)))
const educationChart = computed(() => bar(report.value.education, GOLD))
const sizeChart = computed(() => bar(report.value.household_size))

const sexPct = (n) => {
  const total = report.value.sex.male + report.value.sex.female
  return total ? Math.round((n / total) * 100) : 0
}

const purokItems = computed(() => [{ title: 'All puroks', value: null }, ...(report.value?.filters.puroks ?? []).map((p) => ({ title: p, value: p }))])
const statusItems = [
  { title: 'Verified households', value: 'verified' },
  { title: 'All households', value: 'all' },
]
</script>

<template>
  <div class="demographics">
    <div class="header">
      <div>
        <h2>Population & Demographics</h2>
        <p class="sub">{{ report ? report.summary.population.toLocaleString() : 0 }} residents total</p>
      </div>
    </div>

    <div class="filter-row">
      <v-select
        v-model="filters.purok"
        :items="purokItems"
        density="compact"
        variant="outlined"
        hide-details
        label="Purok"
        class="filter-input"
        style="max-width: 220px"
        :menu-props="{ contentClass: 'filter-select-menu' }"
      />
      <v-select
        v-model="filters.status"
        :items="statusItems"
        density="compact"
        variant="outlined"
        hide-details
        label="Records"
        class="filter-input"
        style="max-width: 240px"
        :menu-props="{ contentClass: 'filter-select-menu' }"
      />
    </div>

    <v-alert v-if="error" type="error" variant="tonal" class="mb-4">{{ error }}</v-alert>
    <div v-if="loading && !report" class="loading"><v-progress-circular indeterminate color="#C9A227" /></div>

    <template v-if="report">
      <!-- Stat cards -->
      <v-row class="mb-2">
        <v-col cols="6" md="3"><div class="stat"><span>Population</span><strong>{{ report.summary.population.toLocaleString() }}</strong></div></v-col>
        <v-col cols="6" md="3"><div class="stat"><span>Households</span><strong>{{ report.summary.households.toLocaleString() }}</strong></div></v-col>
        <v-col cols="6" md="3"><div class="stat"><span>Senior Citizens (60+)</span><strong>{{ report.sectors.seniors.toLocaleString() }}</strong></div></v-col>
        <v-col cols="6" md="3"><div class="stat"><span>PWD</span><strong>{{ report.sectors.pwd.toLocaleString() }}</strong></div></v-col>
      </v-row>

      <v-row>
        <v-col cols="12" md="6">
          <div class="card"><h3>Population by Age Group</h3>
            <div class="chart"><Bar :data="ageChart" :options="vertical" /></div></div>
        </v-col>
        <v-col cols="12" md="6">
          <div class="card"><h3>Population by Purok</h3>
            <div class="chart"><Bar :data="purokChart" :options="vertical" /></div></div>
        </v-col>

        <v-col cols="12" md="4">
          <div class="card"><h3>Male-to-Female Distribution</h3>
            <div class="chart small"><Doughnut :data="sexChart" :options="donutOptions" /></div>
            <p class="note">
              Male {{ report.sex.male.toLocaleString() }} ({{ sexPct(report.sex.male) }}%) ·
              Female {{ report.sex.female.toLocaleString() }} ({{ sexPct(report.sex.female) }}%)
            </p></div>
        </v-col>
        <v-col cols="12" md="8">
          <div class="card"><h3>Educational Attainment</h3>
            <div class="chart"><Bar :data="educationChart" :options="horizontal" /></div></div>
        </v-col>

        <v-col cols="12" md="6">
          <div class="card"><h3>Employment Statistics <span class="hint">(age 15+)</span></h3>
            <div class="chart small"><Doughnut :data="employmentChart" :options="donutOptions" /></div>
            <p class="note">Employment rate (of labor force): <strong>{{ report.employment.employment_rate }}%</strong></p>
            <div v-if="report.employment.top_occupations.length" class="occupations">
              <div v-for="o in report.employment.top_occupations" :key="o.label" class="occ-row">
                <span>{{ o.label }}</span><strong>{{ o.total }}</strong>
              </div>
            </div></div>
        </v-col>
        <v-col cols="12" md="6">
          <div class="card"><h3>Household Size Distribution</h3>
            <div class="chart"><Bar :data="sizeChart" :options="vertical" /></div>
            <p class="note">Average household size: <strong>{{ report.summary.avg_household_size }}</strong> members</p></div>
        </v-col>

        <v-col cols="12">
          <div class="card"><h3>Senior Citizen & PWD Population</h3>
            <v-row>
              <v-col cols="6" md="3"><div class="mini"><span>Senior citizens</span><strong>{{ report.sectors.seniors }}</strong></div></v-col>
              <v-col cols="6" md="3"><div class="mini"><span>Senior men / women</span><strong>{{ report.sectors.seniors_male }} / {{ report.sectors.seniors_female }}</strong></div></v-col>
              <v-col cols="6" md="3"><div class="mini"><span>PWD</span><strong>{{ report.sectors.pwd }}</strong></div></v-col>
              <v-col cols="6" md="3"><div class="mini"><span>Senior PWD</span><strong>{{ report.sectors.senior_pwd }}</strong></div></v-col>
            </v-row></div>
        </v-col>
      </v-row>
    </template>
  </div>
</template>

<style scoped>
/* Type scale matches the admin pages: 14px/600 page title, 11px muted
   subtitle, 13px/600 card titles, 12px body text, 11px captions. */
.header h2 { color: #1a1a1a; font-size: 14px; font-weight: 600; margin: 0; }
.sub { color: #aaa; font-size: 11px; margin: 2px 0 16px; }
.filter-row { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
.filter-input :deep(.v-field__input),
.filter-input :deep(input),
.filter-input :deep(.v-select__selection-text),
.filter-input :deep(.v-label) { font-size: 12.5px; }
.filter-input :deep(.v-field__input) { min-height: 36px; }
.loading { display: flex; justify-content: center; padding: 48px; }
.card { background: #fff; border-radius: 16px; padding: 20px; box-shadow: 0 1px 3px rgba(11, 31, 75, 0.08); height: 100%; }
.card h3 { color: #1a1a1a; font-size: 13px; font-weight: 600; margin: 0 0 12px; }
.hint { color: #999; font-weight: 400; font-size: 11px; }
.chart { height: 280px; position: relative; }
.chart.small { height: 240px; }
.note { margin: 12px 0 0; color: #666; font-size: 12px; text-align: center; }
.note strong { color: #1a1a1a; font-weight: 600; }
.stat { background: #0B1F4B; color: #fff; border-radius: 16px; padding: 16px 20px; display: flex; flex-direction: column; gap: 4px; }
.stat span { font-size: 12px; font-weight: 500; color: #E3C565; }
.stat strong { font-size: 26px; font-weight: 600; line-height: 1.1; }
.mini { background: #f8f6ee; border-left: 4px solid #C9A227; border-radius: 10px; padding: 12px 16px; display: flex; flex-direction: column; gap: 2px; }
.mini span { font-size: 11px; color: #888; }
.mini strong { font-size: 18px; font-weight: 600; color: #0B1F4B; }
.occupations { margin-top: 12px; border-top: 1px solid #eef0f4; padding-top: 8px; }
.occ-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 12px; color: #666; }
.occ-row strong { font-weight: 600; color: #1a1a1a; }
</style>

<style>
.filter-select-menu .v-list-item-title { font-size: 12.5px; }
.filter-select-menu .v-list-item { min-height: 34px; }
</style>