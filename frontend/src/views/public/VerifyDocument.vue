<template>
  <div class="verify-page">
    <div class="verify-card">
      <div class="brand">
        <div class="brand-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
            <path d="M12 2L3 7v6c0 5 3.8 8.7 9 9 5.2-.3 9-4 9-9V7l-9-5z" fill="#f5a623" />
          </svg>
        </div>
        <span>Brgy. San Roque E-Services</span>
      </div>

      <div v-if="loading" class="status-block">
        <v-progress-circular indeterminate size="28" color="#0f1e3d" />
        <p>Checking tracking number…</p>
      </div>

      <template v-else-if="verified">
        <div class="result result--verified">
          <v-icon icon="mdi-check-decagram" size="40" style="color: #27ae60" />
          <p class="result-title">Document Verified</p>
          <p class="result-subtitle">This document was genuinely issued by this barangay.</p>
        </div>

        <div class="detail-grid">
          <div class="detail-row">
            <span>Tracking Number</span>
            <strong>{{ data.tracking_number }}</strong>
          </div>
          <div class="detail-row">
            <span>Document Type</span>
            <strong>{{ data.document_type }}</strong>
          </div>
          <div class="detail-row">
            <span>Issued To</span>
            <strong>{{ data.resident_name }}</strong>
          </div>
          <div class="detail-row">
            <span>Released</span>
            <strong>{{ formatDate(data.released_at) }}</strong>
          </div>
        </div>
      </template>

      <template v-else>
        <div class="result result--invalid">
          <v-icon icon="mdi-close-circle-outline" size="40" style="color: #c0392b" />
          <p class="result-title">Not Verified</p>
          <p class="result-subtitle">{{ errorMessage }}</p>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { verifyTrackingNumber } from '@/api/verify'

const route = useRoute()
const loading = ref(true)
const verified = ref(false)
const data = ref(null)
const errorMessage = ref('')

async function load() {
  loading.value = true
  try {
    const res = await verifyTrackingNumber(route.params.trackingNumber)
    verified.value = res.data.verified
    data.value = res.data.data
  } catch (error) {
    verified.value = false
    errorMessage.value =
      error?.response?.data?.message ||
      'This tracking number does not match any released document.'
  } finally {
    loading.value = false
  }
}

function formatDate(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('en-PH', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

onMounted(load)
</script>

<style scoped>
.verify-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f4f2ed;
  padding: 24px;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

.verify-card {
  background: #ffffff;
  border-radius: 14px;
  padding: 32px 28px;
  max-width: 400px;
  width: 100%;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
}

.brand {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  font-weight: 600;
  color: #888;
  margin-bottom: 24px;
}

.brand-icon {
  width: 24px;
  height: 24px;
  border-radius: 6px;
  background: #0f1e3d;
  display: flex;
  align-items: center;
  justify-content: center;
}

.status-block {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  padding: 20px 0;
  color: #888;
  font-size: 13px;
}

.result {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 6px;
  margin-bottom: 20px;
}

.result-title {
  font-size: 18px;
  font-weight: 700;
  color: #1a1a1a;
  margin: 4px 0 0;
}

.result-subtitle {
  font-size: 12.5px;
  color: #888;
  margin: 0;
}

.detail-grid {
  border-top: 1px solid #f0ede3;
  padding-top: 16px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
  gap: 12px;
}

.detail-row span {
  color: #999;
}

.detail-row strong {
  color: #1a1a1a;
  text-align: right;
}
</style>