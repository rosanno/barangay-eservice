<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { STATUS, DOCUMENT_TYPES, formatDate } from '@/utils/requestStatus'
import { getMyRequest, cancelRequest } from '@/api/residentRequest'

const route = useRoute()
const router = useRouter()
const raw = ref(null)
const loading = ref(true)
const error = ref('')
const confirmOpen = ref(false)
const cancelling = ref(false)

/**
 * Normalize the API response so the template always sees the same fields,
 * whether document_type is a string code or a nested object, and whatever
 * the backend calls copies / dates / status casing.
 */
const request = computed(() => {
  const r = raw.value
  if (!r) return null

  const dt = r.document_type
  const docObj =
    dt && typeof dt === 'object' ? dt : (r.document ?? r.documentType ?? null)
  const code = dt && typeof dt === 'object' ? dt.code : dt

  return {
    ...r,
    id: r.id,
    reference_no: r.reference_no ?? r.referenceNo ?? r.reference ?? r.tracking_no ?? '',
    status: String(r.status ?? '').toLowerCase(),
    copies: r.copies ?? r.quantity ?? r.number_of_copies ?? r.numberOfCopies ?? null,
    created_at: r.created_at ?? r.requested_at ?? r.createdAt ?? null,
    pickup_date:
      r.pickup_date ?? r.preferred_pickup_date ?? r.preferred_pickup ?? r.pickupDate ?? null,
    fee: r.fee ?? docObj?.fee ?? null,
    rejection_reason: r.rejection_reason ?? r.rejectionReason ?? '',
    history: r.history ?? r.status_history ?? r.statusHistory ?? [],
    _code: code,
    _doc: docObj,
  }
})

const docType = computed(() => request.value?._doc ?? null)

const typeTitle = computed(() => {
  const r = request.value
  if (!r) return ''
  return (
    r._doc?.name ??
    DOCUMENT_TYPES.find((t) => t.value === r._code)?.title ??
    r._code ??
    'Document request'
  )
})

// ── Status chip: same colors and labels as the admin Requests page ──────
// The API sends "ready_for_pickup"; some of your own utils use "ready".
// Treat them as the same thing so neither spelling breaks the page.
const canonicalStatus = computed(() => {
  const s = request.value?.status
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
      label: request.value?.status ?? '',
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

// ── Progress: same vertical timeline as the admin view dialog ───────────
// green dot = done, gold dot = current step, gray dot = not reached yet.
const timelineSteps = computed(() => {
  const r = request.value
  if (!r) return []
  const t = r.timeline ?? {}
  const status = canonicalStatus.value
  const requestedAt = t.requested_at ?? r.created_at

  if (status === 'rejected') {
    return [
      { key: 'requested', label: 'Requested', at: requestedAt },
      { key: 'rejected', label: 'Rejected', at: t.updated_at ?? null, isCurrent: true },
    ]
  }
  if (status === 'cancelled') {
    return [
      { key: 'requested', label: 'Requested', at: requestedAt },
      { key: 'cancelled', label: 'Cancelled', at: t.cancelled_at ?? null, isCurrent: true },
    ]
  }

  return [
    { key: 'requested', label: 'Requested', at: requestedAt },
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

async function load() {
  loading.value = true
  error.value = ''
  try {
    raw.value = await getMyRequest(route.params.id)
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
    <v-btn variant="text" prepend-icon="mdi-arrow-left" class="back" @click="router.push('/my-requests')">
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
        <span class="status-chip" :style="chipStyle">{{ chip.label }}</span>
      </header>

      <section class="card" aria-label="Progress">
        <h2>Progress</h2>

        <div v-if="request.status === 'rejected'" class="rejection-note">
          <v-icon icon="mdi-alert-circle-outline" size="15" />
          <span>
            Your request was rejected.
            <template v-if="request.rejection_reason">Reason: {{ request.rejection_reason }}.</template>
            You can submit a new request.
          </span>
        </div>

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
      </section>

      <section class="card" aria-label="Request details">
        <h2>Details</h2>
        <dl class="grid">
          <div><dt>Purpose</dt><dd>{{ request.purpose || '—' }}</dd></div>
          <div><dt>Copies</dt><dd>{{ request.copies ?? '—' }}</dd></div>
          <div><dt>Requested on</dt><dd>{{ formatDate(request.created_at) }}</dd></div>
          <div><dt>Preferred pickup</dt><dd>{{ formatDate(request.pickup_date) }}</dd></div>
          <div v-if="request.fee != null">
            <dt>Fee</dt><dd>{{ request.fee ? `₱${Number(request.fee).toFixed(2)}` : 'Free' }}</dd>
          </div>
          <div v-if="request.remarks" class="wide"><dt>Remarks</dt><dd>{{ request.remarks }}</dd></div>
        </dl>
      </section>

      <section v-if="docType" class="card" aria-label="About this document">
        <h2>About this document</h2>
        <p v-if="docType.description" class="doc-desc">{{ docType.description }}</p>
        <dl class="grid">
          <div v-if="docType.processing_days != null">
            <dt>Processing time</dt>
            <dd>{{ docType.processing_days }} {{ docType.processing_days === 1 ? 'day' : 'days' }}</dd>
          </div>
          <div v-if="docType.requirements?.length" class="wide">
            <dt>Bring when collecting</dt>
            <dd>
              <ul class="reqs">
                <li v-for="r in docType.requirements" :key="r">{{ r }}</li>
              </ul>
            </dd>
          </div>
        </dl>
      </section>

      <section v-if="request.history?.length" class="card" aria-label="History">
        <h2>History</h2>
        <ul class="history">
          <li v-for="h in request.history" :key="h.id ?? h.created_at">
            <strong>{{ STATUS[String(h.status).toLowerCase()]?.label ?? h.status }}</strong>
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
.card { background: #fff; border: 1px solid #E5E7EB; border-radius: 14px; padding: 22px; margin-bottom: 16px; }

/* Status chip: same shape and colors as the admin pages */
.status-chip { font-size: 10px; font-weight: 600; border-radius: 20px; padding: 2px 10px; display: inline-block; white-space: nowrap; margin-top: 6px; }

/* Rejection note: same tint as the admin view dialog */
.rejection-note { display: flex; align-items: flex-start; gap: 8px; background: #fdecea; color: #c0392b; font-size: 12px; padding: 10px 12px; border-radius: 8px; margin-bottom: 16px; }

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

.ready-note { margin-top: 18px; padding: 12px 14px; background: #ECFDF5; color: #065F46; border-radius: 10px; font-size: .9rem; }
.grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px 24px; }
.grid .wide { grid-column: 1 / -1; }
dt { font-size: .8rem; color: #6B7280; }
dd { font-weight: 600; color: #1F2937; margin-top: 2px; }
.doc-desc { color: #4B5563; font-size: .92rem; margin-bottom: 14px; }
.reqs { margin: 4px 0 0; padding-left: 18px; font-weight: 500; }
.history { list-style: none; padding: 0; margin: 0; border-left: 2px solid #E5E7EB; }
.history li { padding: 0 0 14px 16px; position: relative; display: flex; flex-wrap: wrap; gap: 4px 12px; }
.history li::before { content: ''; position: absolute; left: -6px; top: 5px; width: 10px; height: 10px; border-radius: 50%; background: #C9A227; }
.history span { color: #6B7280; font-size: .85rem; }
.history p { flex-basis: 100%; font-size: .9rem; color: #374151; }
.danger-row { display: flex; justify-content: flex-end; }
@media (max-width: 560px) { .grid { grid-template-columns: 1fr; } }
</style>