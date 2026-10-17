<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { trackRequest, getMyRequests } from '@/api/residentRequest'

const route = useRoute()
const router = useRouter()

const trackingNumber = ref('')
const result = ref(null)
const loading = ref(false)
const errorMessage = ref('')
const recent = ref([])

// ── Lookup ───────────────────────────────────────────────────────────
async function lookup(number = trackingNumber.value) {
  const value = String(number ?? '').trim().toUpperCase()
  if (!value) return

  trackingNumber.value = value
  errorMessage.value = ''
  result.value = null
  loading.value = true

  try {
    result.value = await trackRequest(value)
  } catch (e) {
    const status = e.response?.status
    errorMessage.value =
      status === 404
        ? 'No request found with that tracking number. Check it and try again.'
        : status === 403
          ? "That tracking number belongs to another account, so it can't be shown here."
          : e.response?.data?.message ?? 'Could not look up that tracking number. Please try again.'
  } finally {
    loading.value = false
  }
}

// Tracking numbers are long (BRGY-2026-634529) — offering the resident's own
// latest ones as one-tap shortcuts saves them typing or copying.
async function loadRecent() {
  try {
    const list = await getMyRequests()
    recent.value = list
      .slice(0, 5)
      .map((r) => ({
        number: r.tracking_number ?? r.reference_no,
        name: r.document_type && typeof r.document_type === 'object' ? r.document_type.name : r.document_type,
      }))
      .filter((r) => r.number)
  } catch {
    recent.value = [] // shortcuts are a nicety; the search box still works
  }
}

// ── Status chip: same colors and labels as the admin pages ───────────
const canonicalStatus = computed(() => {
  const s = result.value?.status
  return s === 'ready_for_pickup' ? 'ready' : s
})

const CHIP = {
  pending: { label: 'Pending', color: '#a05e00', bg: '#fff3e0' },
  processing: { label: 'Processing', color: '#1a5fd8', bg: '#e8f0fe' },
  ready: { label: 'Ready', color: '#a07020', bg: '#fff9ed', border: '#f5a62350' },
  released: { label: 'Released', color: '#1b6e34', bg: '#e8f5e9' },
  rejected: { label: 'Rejected', color: '#c0392b', bg: '#fdecea' },
  cancelled: { label: 'Cancelled', color: '#c0392b', bg: '#fdecea' },
}

const chip = computed(
  () =>
    CHIP[canonicalStatus.value] ?? {
      label: result.value?.status_label ?? '',
      color: '#a05e00',
      bg: '#fff3e0',
    }
)

const chipStyle = computed(() => ({
  color: chip.value.color,
  background: chip.value.bg,
  border: chip.value.border ? `1px solid ${chip.value.border}` : 'none',
}))

const isReady = computed(() => canonicalStatus.value === 'ready')

// ── Progress: same vertical timeline as the admin view dialog ────────
const timelineSteps = computed(() => {
  if (!result.value) return []
  const t = result.value.timeline ?? {}
  const status = canonicalStatus.value

  if (status === 'rejected') {
    return [
      { key: 'requested', label: 'Requested', at: t.requested_at },
      { key: 'rejected', label: 'Rejected', at: t.updated_at ?? null, isCurrent: true },
    ]
  }
  if (status === 'cancelled') {
    return [
      { key: 'requested', label: 'Requested', at: t.requested_at },
      { key: 'cancelled', label: 'Cancelled', at: t.cancelled_at ?? null, isCurrent: true },
    ]
  }

  return [
    { key: 'requested', label: 'Requested', at: t.requested_at },
    { key: 'processing', label: 'Processing', at: t.processed_at ?? null, isCurrent: status === 'processing' },
    { key: 'ready', label: 'Ready for Pickup', at: t.ready_at ?? null, isCurrent: status === 'ready' },
    { key: 'released', label: 'Released', at: t.released_at ?? null, isCurrent: status === 'released' },
  ]
})

function formatDateTime(iso) {
  return new Date(iso).toLocaleString('en-PH', {
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}

function formatFull(iso) {
  return new Date(iso).toLocaleString('en-PH', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}

onMounted(() => {
  loadRecent()

  // Lets other pages deep-link here, e.g. /resident/track?number=BRGY-2026-000123
  const fromUrl = route.query.number
  if (typeof fromUrl === 'string' && fromUrl.trim()) lookup(fromUrl)
})
</script>

<template>
  <div class="page">
    <header class="head">
      <h1>Track a request</h1>
      <p class="sub">Enter the tracking number from your request to see where it stands.</p>
    </header>

    <!-- ─── Search ──────────────────────────────────────────────── -->
    <section class="card" aria-label="Look up a tracking number">
      <div class="search-row">
        <v-text-field
          v-model="trackingNumber"
          placeholder="e.g. BRGY-2026-000123"
          prepend-inner-icon="mdi-magnify"
          density="comfortable"
          variant="outlined"
          hide-details
          clearable
          autocomplete="off"
          aria-label="Tracking number"
          @keyup.enter="lookup()"
        />
        <v-btn
          class="track-btn"
          :loading="loading"
          :disabled="!trackingNumber || !trackingNumber.trim()"
          @click="lookup()"
        >
          Track
        </v-btn>
      </div>

      <p v-if="errorMessage" class="error-text" role="alert">{{ errorMessage }}</p>

      <div v-if="recent.length" class="recent">
        <span class="recent__label">Your recent requests</span>
        <div class="recent__list">
          <button
            v-for="r in recent"
            :key="r.number"
            type="button"
            class="recent__item"
            @click="lookup(r.number)"
          >
            <span class="recent__number">{{ r.number }}</span>
            <span v-if="r.name" class="recent__name">{{ r.name }}</span>
          </button>
        </div>
      </div>
    </section>

    <!-- ─── Result ──────────────────────────────────────────────── -->
    <section v-if="result" class="card" aria-label="Request status">
      <div class="result-head">
        <div>
          <h2>{{ result.document_type?.name ?? 'Document request' }}</h2>
          <p class="ref">{{ result.tracking_number }}</p>
        </div>
        <span class="status-chip" :style="chipStyle">{{ chip.label }}</span>
      </div>

      <div v-if="result.status === 'rejected'" class="rejection-note">
        <v-icon icon="mdi-alert-circle-outline" size="15" />
        <span>
          Your request was rejected.
          <template v-if="result.rejection_reason">Reason: {{ result.rejection_reason }}.</template>
          You can submit a new request.
        </span>
      </div>

      <p class="section-label">Progress</p>
      <ul class="timeline">
        <li
          v-for="step in timelineSteps"
          :key="step.key"
          class="timeline__item"
          :class="{ 'timeline__item--done': step.at, 'timeline__item--current': step.isCurrent }"
          :aria-current="step.isCurrent ? 'step' : undefined"
        >
          <span class="timeline__dot" />
          <span class="timeline__label">{{ step.label }}</span>
          <span class="timeline__date">{{ step.at ? formatDateTime(step.at) : '—' }}</span>
        </li>
      </ul>

      <p v-if="isReady" class="ready-note">
        Your document is ready. Bring a valid ID to the barangay hall to collect it.
      </p>

      <dl class="facts">
        <div>
          <dt>Purpose</dt>
          <dd>{{ result.purpose || '—' }}</dd>
        </div>
        <div>
          <dt>Requested</dt>
          <dd>{{ result.timeline?.requested_at ? formatFull(result.timeline.requested_at) : '—' }}</dd>
        </div>
      </dl>

      <div class="result-actions">
        <v-btn
          variant="outlined"
          style="text-transform: none"
          prepend-icon="mdi-open-in-new"
          @click="router.push(`/resident/requests/${result.id}`)"
        >
          View full details
        </v-btn>
        <v-btn
          v-if="result.status === 'rejected'"
          class="track-btn"
          prepend-icon="mdi-file-plus-outline"
          @click="router.push('/resident/requests/new')"
        >
          Submit a new request
        </v-btn>
      </div>
    </section>
  </div>
</template>

<style scoped>
.page { max-width: 760px; margin: 0 auto; padding: 24px 16px 48px; }
.head { margin-bottom: 20px; }
h1 { font-size: 1.6rem; font-weight: 700; color: #0B1F44; }
h2 { font-size: 1.05rem; font-weight: 700; color: #0B1F44; }
.sub { color: #6B7280; margin-top: 2px; font-size: .92rem; }
.card { background: #fff; border: 1px solid #E5E7EB; border-radius: 14px; padding: 22px; margin-bottom: 16px; }

.search-row { display: flex; gap: 10px; align-items: flex-start; }
.search-row > :first-child { flex: 1; }
.track-btn { background: #0B1F44 !important; color: #fff !important; text-transform: none; letter-spacing: 0; font-weight: 600; height: 48px !important; }
.error-text { color: #c0392b; font-size: .85rem; margin-top: 10px; }

.recent { margin-top: 18px; padding-top: 16px; border-top: 1px solid #F1F2F4; }
.recent__label { display: block; font-size: .78rem; color: #6B7280; margin-bottom: 8px; }
.recent__list { display: flex; flex-wrap: wrap; gap: 8px; }
.recent__item { display: flex; flex-direction: column; align-items: flex-start; gap: 1px; padding: 8px 12px; background: #fff; border: 1px solid #E5E7EB; border-radius: 10px; cursor: pointer; text-align: left; transition: border-color .15s, background .15s; }
.recent__item:hover { border-color: #f5a623; background: #fffaf0; }
.recent__number { font-size: .8rem; font-weight: 600; color: #0B1F44; }
.recent__name { font-size: .72rem; color: #6B7280; }

.result-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 16px; }
.ref { color: #6B7280; font-weight: 600; font-size: .85rem; margin-top: 2px; }

/* Status chip: same shape and colors as the admin pages */
.status-chip { font-size: 10px; font-weight: 600; border-radius: 20px; padding: 2px 10px; display: inline-block; white-space: nowrap; margin-top: 4px; }

/* Rejection note: same tint as the admin view dialog */
.rejection-note { display: flex; align-items: flex-start; gap: 8px; background: #fdecea; color: #c0392b; font-size: 12px; padding: 10px 12px; border-radius: 8px; margin-bottom: 16px; }

.section-label { font-size: 11px; font-weight: 600; color: #999; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 10px; }

/* Progress timeline: same as the admin view dialog */
.timeline { list-style: none; margin: 0; padding: 0; }
.timeline__item { display: flex; align-items: center; gap: 10px; padding: 8px 0; position: relative; }
.timeline__item::before { content: ''; position: absolute; left: 3px; top: -2px; bottom: -2px; width: 1px; background: #e8e3d8; }
.timeline__item:first-child::before { top: 50%; }
.timeline__item:last-child::before { bottom: 50%; }
.timeline__dot { width: 8px; height: 8px; border-radius: 50%; background: #ccc; flex-shrink: 0; z-index: 1; }
.timeline__item--done .timeline__dot { background: #27ae60; }
.timeline__item--current .timeline__dot { background: #f5a623; }
.timeline__label { font-size: 12.5px; color: #666; flex: 1; }
.timeline__item--done .timeline__label,
.timeline__item--current .timeline__label { color: #1a1a1a; font-weight: 500; }
.timeline__date { font-size: 11px; color: #999; }

.ready-note { margin-top: 14px; padding: 12px 14px; background: #ECFDF5; color: #065F46; border-radius: 10px; font-size: .9rem; }

.facts { display: grid; grid-template-columns: 1fr 1fr; gap: 16px 24px; margin: 20px 0 0; padding-top: 16px; border-top: 1px solid #f0ede3; }
dt { font-size: .8rem; color: #6B7280; }
dd { font-weight: 600; color: #1F2937; margin-top: 2px; font-size: .92rem; }

.result-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px; }

@media (max-width: 560px) {
  .search-row { flex-direction: column; }
  .search-row .track-btn { width: 100%; }
  .facts { grid-template-columns: 1fr; }
}
</style>