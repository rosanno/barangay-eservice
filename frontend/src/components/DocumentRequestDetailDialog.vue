<template>
  <v-dialog :model-value="modelValue" max-width="560" @update:model-value="$emit('update:modelValue', $event)">
    <v-card>
      <div v-if="loading" class="d-flex justify-center py-10">
        <v-progress-circular indeterminate size="24" color="#0f1e3d" />
      </div>

      <v-card-text v-else-if="data" class="pa-5">
        <div class="d-flex align-center justify-space-between mb-1">
          <span style="font-size: 15px; font-weight: 600; color: #1a1a1a">
            {{ data.document_type.name }}
          </span>
          <span class="status-chip" :class="STATUS_META[data.status]?.class">
            {{ data.status_label }}
          </span>
        </div>
        <p style="font-size: 12px; color: #999; margin: 0 0 18px">
          {{ data.tracking_number }}
        </p>

        <div class="fact-grid">
          <div class="fact">
            <span class="fact__label">Resident</span>
            <span class="fact__value">{{ data.requested_by?.name || '—' }}</span>
          </div>
          <div class="fact">
            <span class="fact__label">Purpose</span>
            <span class="fact__value">{{ data.purpose }}</span>
          </div>
          <div class="fact">
            <span class="fact__label">Fee</span>
            <span class="fact__value">
              {{ data.fee > 0 ? `₱${data.fee.toFixed(2)}` : 'No fee' }}
              <span class="fact__sub">({{ data.payment_status }})</span>
            </span>
          </div>
          <div v-if="data.remarks" class="fact">
            <span class="fact__label">Remarks</span>
            <span class="fact__value">{{ data.remarks }}</span>
          </div>
        </div>

        <div v-if="data.rejection_reason" class="rejection-note">
          <v-icon icon="mdi-alert-circle-outline" size="15" style="color: #c0392b" />
          {{ data.rejection_reason }}
        </div>

        <p class="section-label">Timeline</p>
        <ul class="timeline">
          <li
            v-for="step in timelineSteps"
            :key="step.key"
            class="timeline__item"
            :class="{ 'timeline__item--done': step.at, 'timeline__item--current': step.isCurrent }"
          >
            <span class="timeline__dot" />
            <span class="timeline__label">{{ step.label }}</span>
            <span class="timeline__date">{{ step.at ? formatDateTime(step.at) : '—' }}</span>
          </li>
        </ul>

        <template v-if="data.attachments?.length">
          <p class="section-label">Attachments</p>
          <ul class="attachment-list">
            <li v-for="file in data.attachments" :key="file.id">
              <a :href="file.url" target="_blank" rel="noopener">{{ file.original_name }}</a>
              <span v-if="file.label" class="attachment-list__label">— {{ file.label }}</span>
            </li>
          </ul>
        </template>

        <template v-if="data.status === 'released'">
          <p class="section-label">Verification QR Code</p>
          <p style="font-size: 11px; color: #999; margin: -4px 0 10px">
            Print this on the physical document — scanning it confirms authenticity without needing an account.
          </p>
          <DocumentQrCode :tracking-number="data.tracking_number" />
        </template>
      </v-card-text>

      <v-card-actions v-if="!loading && data" class="px-5 pb-5">
        <v-btn
          v-for="target in transitions"
          :key="target"
          size="small"
          variant="tonal"
          @click="handleTransition(target)"
        >
          {{ TRANSITION_LABELS[target] }}
        </v-btn>
        <v-spacer />
        <v-btn variant="text" @click="close">Close</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <!-- ─── Rejection reason sub-dialog ────────────────────────────── -->
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
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import {
  fetchAdminDocumentRequest,
  updateAdminDocumentRequestStatus,
} from '@/api/adminDocumentRequests'
import DocumentQrCode from '@/components/DocumentQrCode.vue'

const props = defineProps({
  modelValue: { type: Boolean, required: true },
  requestId: { type: String, default: null }, // the request's uuid
})

const emit = defineEmits(['update:modelValue', 'updated'])

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

const loading = ref(false)
const data = ref(null)

const rejectDialog = reactive({
  open: false,
  reason: '',
  submitting: false,
})

async function load() {
  if (!props.requestId) return
  loading.value = true
  data.value = null
  try {
    data.value = await fetchAdminDocumentRequest(props.requestId)
  } finally {
    loading.value = false
  }
}

// Fetch whenever the dialog opens with a request id — not just once on
// mount, since the same component instance is reused across different
// rows/requests by its host page.
watch(
  () => [props.modelValue, props.requestId],
  ([isOpen]) => {
    if (isOpen) load()
  }
)

const transitions = computed(() => TRANSITIONS[data.value?.status] || [])

const timelineSteps = computed(() => {
  if (!data.value) return []
  const t = data.value.timeline
  const status = data.value.status

  if (status === 'rejected') {
    return [
      { key: 'requested', label: 'Requested', at: t.requested_at },
      { key: 'rejected', label: 'Rejected', at: t.updated_at, isCurrent: true },
    ]
  }
  if (status === 'cancelled') {
    return [
      { key: 'requested', label: 'Requested', at: t.requested_at },
      { key: 'cancelled', label: 'Cancelled', at: t.cancelled_at, isCurrent: true },
    ]
  }

  return [
    { key: 'requested', label: 'Requested', at: t.requested_at },
    { key: 'processing', label: 'Processing', at: t.processed_at, isCurrent: status === 'processing' },
    { key: 'ready', label: 'Ready for Pickup', at: t.ready_at, isCurrent: status === 'ready_for_pickup' },
    { key: 'released', label: 'Released', at: t.released_at, isCurrent: status === 'released' },
  ]
})

function handleTransition(target) {
  if (target === 'rejected') {
    rejectDialog.reason = ''
    rejectDialog.open = true
    return
  }
  applyTransition(target)
}

async function applyTransition(target, extra = {}) {
  await updateAdminDocumentRequestStatus(data.value.id, { status: target, ...extra })
  emit('updated')
  await load() // refresh this dialog's own view (e.g. new status, new timeline step)
}

async function submitRejection() {
  rejectDialog.submitting = true
  try {
    await applyTransition('rejected', { rejection_reason: rejectDialog.reason.trim() })
    rejectDialog.open = false
  } finally {
    rejectDialog.submitting = false
  }
}

function close() {
  emit('update:modelValue', false)
}

function formatDateTime(iso) {
  return new Date(iso).toLocaleString('en-PH', {
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}
</script>

<style scoped>
.status-chip {
  font-size: 10px;
  font-weight: 600;
  border-radius: 20px;
  padding: 2px 10px;
  display: inline-block;
  white-space: nowrap;
}
.status-pending {
  background: #fff3e0;
  color: #a05e00;
}
.status-approved {
  background: #e8f5e9;
  color: #1b6e34;
}
.status-processing {
  background: #e8f0fe;
  color: #1a5fd8;
}
.status-ready {
  background: #fff9ed;
  color: #a07020;
  border: 1px solid #f5a62350;
}
.status-rejected {
  background: #fdecea;
  color: #c0392b;
}

.fact-grid {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 14px 0;
  border-top: 1px solid #f0ede3;
  border-bottom: 1px solid #f0ede3;
  margin-bottom: 18px;
}

.fact {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  font-size: 12.5px;
}

.fact__label {
  color: #999;
  flex-shrink: 0;
}

.fact__value {
  color: #1a1a1a;
  font-weight: 500;
  text-align: right;
}

.fact__sub {
  color: #999;
  font-weight: 400;
}

.rejection-note {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  background: #fdecea;
  color: #c0392b;
  font-size: 12px;
  padding: 10px 12px;
  border-radius: 8px;
  margin-bottom: 18px;
}

.section-label {
  font-size: 11px;
  font-weight: 600;
  color: #999;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin: 0 0 10px;
}

.timeline {
  list-style: none;
  margin: 0 0 20px;
  padding: 0;
}

.timeline__item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 0;
  position: relative;
}

.timeline__item::before {
  content: '';
  position: absolute;
  left: 3px;
  top: -2px;
  bottom: -2px;
  width: 1px;
  background: #e8e3d8;
}

.timeline__item:first-child::before {
  top: 50%;
}

.timeline__item:last-child::before {
  bottom: 50%;
}

.timeline__dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #ccc;
  flex-shrink: 0;
  z-index: 1;
}

.timeline__item--done .timeline__dot {
  background: #27ae60;
}

.timeline__item--current .timeline__dot {
  background: #f5a623;
}

.timeline__label {
  font-size: 12.5px;
  color: #666;
  flex: 1;
}

.timeline__item--done .timeline__label,
.timeline__item--current .timeline__label {
  color: #1a1a1a;
  font-weight: 500;
}

.timeline__date {
  font-size: 11px;
  color: #999;
}

.attachment-list {
  list-style: none;
  margin: 0;
  padding: 0;
  font-size: 12.5px;
}

.attachment-list li {
  padding: 4px 0;
}

.attachment-list a {
  color: #1a5fd8;
  text-decoration: none;
}

.attachment-list__label {
  color: #999;
}
</style>