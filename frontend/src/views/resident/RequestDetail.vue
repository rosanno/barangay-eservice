<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { STATUS, STATUS_STEPS, DOCUMENT_TYPES, formatDate } from '@/utils/requestStatus'
import { getMyRequest, cancelRequest } from '@/api/residentRequest'

const route = useRoute()
const router = useRouter()
const request = ref(null)
const loading = ref(true)
const error = ref('')
const confirmOpen = ref(false)
const cancelling = ref(false)

const typeTitle = computed(
  () => DOCUMENT_TYPES.find((t) => t.value === request.value?.document_type)?.title ?? request.value?.document_type
)
const stepIndex = computed(() => STATUS_STEPS.indexOf(request.value?.status))
const isClosed = computed(() => ['rejected', 'cancelled'].includes(request.value?.status))

async function load() {
  loading.value = true
  error.value = ''
  try {
    request.value = await getMyRequest(route.params.id)
  } catch (e) {
    error.value =
      e.response?.status === 404
        ? 'This request does not exist or belongs to another account.'
        : e.response?.data?.message ?? 'We could not load this request.'
  } finally {
    loading.value = false
  }
}

async function doCancel() {
  cancelling.value = true
  try {
    await cancelRequest(request.value.id)
    confirmOpen.value = false
    await load()
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Could not cancel this request.'
    confirmOpen.value = false
  } finally {
    cancelling.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="page">
    <v-btn variant="text" prepend-icon="mdi-arrow-left" class="back" @click="router.push('/resident/requests')">
      My requests
    </v-btn>

    <v-progress-linear v-if="loading" indeterminate color="#C9A227" />
    <v-alert v-if="error" type="error" variant="tonal" class="my-4">{{ error }}</v-alert>

    <template v-if="request">
      <header class="head">
        <div>
          <h1>{{ typeTitle }}</h1>
          <p class="ref">{{ request.reference_no }}</p>
        </div>
        <span class="pill" :style="{ color: STATUS[request.status]?.color, background: STATUS[request.status]?.bg }">
          {{ STATUS[request.status]?.label }}
        </span>
      </header>

      <section class="card" aria-label="Progress">
        <h2>Progress</h2>
        <v-alert v-if="isClosed" :type="request.status === 'rejected' ? 'error' : 'info'" variant="tonal">
          <template v-if="request.status === 'rejected'">
            Your request was rejected.
            <span v-if="request.rejection_reason">Reason: {{ request.rejection_reason }}</span>
            You can submit a new request.
          </template>
          <template v-else>You cancelled this request.</template>
        </v-alert>
        <ol v-else class="steps">
          <li
            v-for="(s, i) in STATUS_STEPS"
            :key="s"
            :class="{ done: i < stepIndex, current: i === stepIndex }"
            :aria-current="i === stepIndex ? 'step' : undefined"
          >
            <span class="dot"><v-icon v-if="i < stepIndex" size="14" color="white">mdi-check</v-icon></span>
            <span class="step-label">{{ STATUS[s].label }}</span>
          </li>
        </ol>
        <p v-if="request.status === 'ready'" class="ready-note">
          Your document is ready. Bring a valid ID to the barangay hall to collect it.
        </p>
      </section>

      <section class="card" aria-label="Request details">
        <h2>Details</h2>
        <dl class="grid">
          <div><dt>Purpose</dt><dd>{{ request.purpose }}</dd></div>
          <div><dt>Copies</dt><dd>{{ request.copies }}</dd></div>
          <div><dt>Requested on</dt><dd>{{ formatDate(request.created_at) }}</dd></div>
          <div><dt>Preferred pickup</dt><dd>{{ formatDate(request.pickup_date) }}</dd></div>
          <div v-if="request.fee != null">
            <dt>Fee</dt><dd>{{ request.fee ? `₱${Number(request.fee).toFixed(2)}` : 'Free' }}</dd>
          </div>
          <div v-if="request.remarks" class="wide"><dt>Remarks</dt><dd>{{ request.remarks }}</dd></div>
        </dl>
      </section>

      <section v-if="request.history?.length" class="card" aria-label="History">
        <h2>History</h2>
        <ul class="history">
          <li v-for="h in request.history" :key="h.id">
            <strong>{{ STATUS[h.status]?.label ?? h.status }}</strong>
            <span>{{ formatDate(h.created_at) }}</span>
            <p v-if="h.note">{{ h.note }}</p>
          </li>
        </ul>
      </section>

      <div v-if="request.status === 'pending'" class="danger-row">
        <v-btn color="error" variant="outlined" @click="confirmOpen = true">Cancel request</v-btn>
      </div>
    </template>

    <v-dialog v-model="confirmOpen" max-width="420">
      <v-card rounded="lg">
        <v-card-title>Cancel this request?</v-card-title>
        <v-card-text>{{ request?.reference_no }} will be cancelled. You can submit a new request anytime.</v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="confirmOpen = false">Keep request</v-btn>
          <v-btn color="error" variant="flat" :loading="cancelling" @click="doCancel">Cancel request</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<style scoped>
.page { max-width: 760px; margin: 0 auto; padding: 16px 16px 48px; }
.back { margin-left: -12px; text-transform: none; letter-spacing: 0; color: #4B5563; }
.head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin: 8px 0 20px; }
h1 { font-size: 1.6rem; font-weight: 700; color: #0B1F44; }
h2 { font-size: 1rem; font-weight: 700; color: #0B1F44; margin-bottom: 14px; }
.ref { color: #6B7280; font-weight: 600; margin-top: 2px; }
.pill { padding: 4px 12px; border-radius: 999px; font-size: .82rem; font-weight: 600; white-space: nowrap; }
.card { background: #fff; border: 1px solid #E5E7EB; border-radius: 14px; padding: 22px; margin-bottom: 16px; }
.steps { list-style: none; display: flex; padding: 0; margin: 4px 0 0; }
.steps li { flex: 1; position: relative; display: flex; flex-direction: column; align-items: center; gap: 8px; text-align: center; }
.steps li::before { content: ''; position: absolute; top: 11px; left: -50%; width: 100%; height: 2px; background: #E5E7EB; }
.steps li:first-child::before { display: none; }
.steps li.done::before, .steps li.current::before { background: #C9A227; }
.dot { position: relative; z-index: 1; width: 24px; height: 24px; border-radius: 50%; background: #fff; border: 2px solid #D1D5DB; display: grid; place-items: center; }
.done .dot { background: #0B1F44; border-color: #0B1F44; }
.current .dot { border-color: #C9A227; background: #C9A227; box-shadow: 0 0 0 4px #FBF1CC; }
.step-label { font-size: .82rem; color: #6B7280; }
.current .step-label, .done .step-label { color: #0B1F44; font-weight: 600; }
.ready-note { margin-top: 18px; padding: 12px 14px; background: #ECFDF5; color: #065F46; border-radius: 10px; font-size: .9rem; }
.grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px 24px; }
.grid .wide { grid-column: 1 / -1; }
dt { font-size: .8rem; color: #6B7280; }
dd { font-weight: 600; color: #1F2937; margin-top: 2px; }
.history { list-style: none; padding: 0; margin: 0; border-left: 2px solid #E5E7EB; }
.history li { padding: 0 0 14px 16px; position: relative; display: flex; flex-wrap: wrap; gap: 4px 12px; }
.history li::before { content: ''; position: absolute; left: -6px; top: 5px; width: 10px; height: 10px; border-radius: 50%; background: #C9A227; }
.history span { color: #6B7280; font-size: .85rem; }
.history p { flex-basis: 100%; font-size: .9rem; color: #374151; }
.danger-row { display: flex; justify-content: flex-end; }
@media (max-width: 560px) { .grid { grid-template-columns: 1fr; } .steps li::before { left: -50%; } }
</style>