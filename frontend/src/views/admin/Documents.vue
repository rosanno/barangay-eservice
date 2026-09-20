<template>
  <div style="max-width: 720px">
    <!-- ─── Search ───────────────────────────────────────────────── -->
    <v-card variant="flat" style="background: #ffffff; border-radius: 10px; box-shadow: none">
      <v-card-text class="px-5 py-5">
        <div class="d-flex align-center ga-2">
          <v-text-field
            v-model="trackingNumber"
            density="compact"
            variant="outlined"
            placeholder="Enter tracking number, e.g. BRGY-2026-000123"
            prepend-inner-icon="mdi-magnify"
            hide-details
            class="filter-input"
            @keyup.enter="lookup"
          />
          <v-btn
            style="background: #0f1e3d; color: #fff; text-transform: none"
            :loading="loading"
            :disabled="!trackingNumber.trim()"
            @click="lookup"
          >
            Track
          </v-btn>
        </div>

        <p v-if="errorMessage" style="font-size: 12px; color: #c0392b; margin: 10px 0 0">
          {{ errorMessage }}
        </p>
      </v-card-text>
    </v-card>

    <!-- ─── Result ───────────────────────────────────────────────── -->
    <v-card
      v-if="result"
      variant="flat"
      class="mt-4"
      style="background: #ffffff; border-radius: 10px; box-shadow: none"
    >
      <v-card-text class="px-5 py-5">
        <div class="d-flex align-center justify-space-between mb-1">
          <span style="font-size: 15px; font-weight: 600; color: #1a1a1a">
            {{ result.document_type.name }}
          </span>
          <span class="status-chip" :class="statusClass">{{ result.status_label }}</span>
        </div>
        <p style="font-size: 12px; color: #999; margin: 0 0 18px">
          {{ result.tracking_number }}
        </p>

        <!-- Facts -->
        <div class="fact-grid">
          <div class="fact">
            <span class="fact__label">Resident</span>
            <span class="fact__value">{{ result.requested_by?.name || '—' }}</span>
          </div>
          <div class="fact">
            <span class="fact__label">Purpose</span>
            <span class="fact__value">{{ result.purpose }}</span>
          </div>
          <div class="fact">
            <span class="fact__label">Fee</span>
            <span class="fact__value">
              {{ result.fee > 0 ? `₱${result.fee.toFixed(2)}` : 'No fee' }}
              <span class="fact__sub">({{ result.payment_status }})</span>
            </span>
          </div>
        </div>

        <div v-if="result.rejection_reason" class="rejection-note">
          <v-icon icon="mdi-alert-circle-outline" size="15" style="color: #c0392b" />
          {{ result.rejection_reason }}
        </div>

        <!-- Timeline -->
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
            <span class="timeline__date">{{ step.at ? formatDate(step.at) : '—' }}</span>
          </li>
        </ul>

        <!-- Attachments -->
        <template v-if="result.attachments?.length">
          <p class="section-label">Attachments</p>
          <ul class="attachment-list">
            <li v-for="file in result.attachments" :key="file.id">
              <a :href="file.url" target="_blank" rel="noopener">{{ file.original_name }}</a>
              <span v-if="file.label" class="attachment-list__label">— {{ file.label }}</span>
            </li>
          </ul>
        </template>
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { trackDocumentRequestByNumber } from '@/api/documentRequests'

const trackingNumber = ref('')
const result = ref(null)
const loading = ref(false)
const errorMessage = ref('')

const STATUS_CLASS = {
  pending: 'status-pending',
  processing: 'status-processing',
  ready_for_pickup: 'status-ready',
  released: 'status-approved',
  rejected: 'status-rejected',
  cancelled: 'status-rejected',
}

const statusClass = computed(() => STATUS_CLASS[result.value?.status] || 'status-pending')

const timelineSteps = computed(() => {
  if (!result.value) return []
  const t = result.value.timeline
  const status = result.value.status

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

async function lookup() {
  if (!trackingNumber.value.trim()) return
  errorMessage.value = ''
  result.value = null
  loading.value = true
  try {
    result.value = await trackDocumentRequestByNumber(trackingNumber.value.trim())
  } catch (error) {
    errorMessage.value =
      error?.response?.status === 404
        ? 'No request found with that tracking number.'
        : 'Could not look up that tracking number. Please try again.'
  } finally {
    loading.value = false
  }
}

function formatDate(iso) {
  return new Date(iso).toLocaleString('en-PH', {
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}
</script>

<style scoped src="./DashboardCss.css"></style>

<style scoped>
.filter-input :deep(.v-field__input),
.filter-input :deep(input) {
  font-size: 13px;
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
  font-size: 12.5px;
}

.fact__label {
  color: #999;
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