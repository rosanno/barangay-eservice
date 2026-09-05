<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useResidentDashboard } from '@/composables/useResidentDashboard'

const router = useRouter()
const { loading, recentRequests, stats, load } = useResidentDashboard()

onMounted(load)

const quickDocuments = [
  {
    id: 'barangay-clearance',
    name: 'Barangay Clearance',
    hint: 'General-purpose clearance',
    icon: 'mdi-file-certificate-outline',
  },
  {
    id: 'certificate-indigency',
    name: 'Certificate of Indigency',
    hint: 'For medical, legal, or financial assistance',
    icon: 'mdi-hand-heart-outline',
  },
  {
    id: 'certificate-residency',
    name: 'Residency Certificate',
    hint: 'Certifies you live in this barangay',
    icon: 'mdi-home-account',
  },
]

function goRequest(documentCode) {
  router.push({ path: '/documents/request', query: { type: documentCode } })
}

const statusMeta = {
  pending: { label: 'Pending', color: 'pending' },
  processing: { label: 'Processing', color: 'processing' },
  ready_for_pickup: { label: 'Ready', color: 'ready' },
  released: { label: 'Released', color: 'released' },
  rejected: { label: 'Rejected', color: 'rejected' },
  cancelled: { label: 'Cancelled', color: 'rejected' },
}

function formatDate(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString(undefined, { month: 'short', day: 'numeric' })
}
</script>

<template>
  <div class="dashboard">
    <section class="stat-grid">
      <div class="stat-card">
        <p class="stat-card__label">My requests</p>
        <p class="stat-card__value">{{ stats.total }}</p>
        <p class="stat-card__meta">
          <v-icon icon="mdi-file-multiple-outline" size="14" />
          all time
        </p>
      </div>

      <div class="stat-card">
        <p class="stat-card__label">Pending</p>
        <p class="stat-card__value">{{ stats.pending }}</p>
        <p class="stat-card__meta stat-card__meta--amber">
          <v-icon icon="mdi-clock-outline" size="14" />
          awaiting review
        </p>
      </div>

      <div class="stat-card">
        <p class="stat-card__label">Ready for pickup</p>
        <p class="stat-card__value">{{ stats.readyForPickup }}</p>
        <p class="stat-card__meta stat-card__meta--purple">
          <v-icon icon="mdi-package-variant-closed" size="14" />
          visit the barangay hall
        </p>
      </div>

      <div class="stat-card">
        <p class="stat-card__label">Rejected</p>
        <p class="stat-card__value">{{ stats.rejected }}</p>
        <p class="stat-card__meta stat-card__meta--red">
          <v-icon icon="mdi-alert-circle-outline" size="14" />
          needs resubmission
        </p>
      </div>
    </section>

    <section class="dashboard__body">
      <div class="panel panel--main">
        <div class="panel__header">
          <h2 class="panel__title">
            <v-icon icon="mdi-file-document-outline" size="18" />
            Recent requests
          </h2>
        </div>

        <div v-if="loading" class="panel__loading">
          <v-progress-circular indeterminate size="22" />
        </div>

        <table v-else-if="recentRequests.length" class="request-table">
          <thead>
            <tr>
              <th>Document type</th>
              <th>Tracking #</th>
              <th>Status</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in recentRequests" :key="item.id">
              <td>{{ item.document_type.name }}</td>
              <td class="request-table__tracking">{{ item.tracking_number }}</td>
              <td>
                <span class="pill" :class="`pill--${statusMeta[item.status]?.color}`">
                  {{ statusMeta[item.status]?.label || item.status }}
                </span>
              </td>
              <td class="request-table__date">{{ formatDate(item.timeline.requested_at) }}</td>
            </tr>
          </tbody>
        </table>

        <p v-else class="panel__empty">
          You haven't requested any documents yet. Use "Request a document" to get started.
        </p>

        <router-link v-if="recentRequests.length" to="/documents" class="panel__view-all">
          View all requests
          <v-icon icon="mdi-arrow-right" size="16" />
        </router-link>
      </div>

      <div class="panel panel--side">
        <div class="panel__header">
          <h2 class="panel__title">
            <v-icon icon="mdi-file-plus-outline" size="18" />
            Request a document
          </h2>
        </div>

        <button
          v-for="doc in quickDocuments"
          :key="doc.id"
          type="button"
          class="quick-doc"
          @click="goRequest(doc.id)"
        >
          <span class="quick-doc__icon">
            <v-icon :icon="doc.icon" size="18" />
          </span>
          <span class="quick-doc__text">
            <span class="quick-doc__name">{{ doc.name }}</span>
            <span class="quick-doc__hint">{{ doc.hint }}</span>
          </span>
          <v-icon icon="mdi-chevron-right" size="18" class="quick-doc__chevron" />
        </button>
      </div>
    </section>
  </div>
</template>

<style scoped>
.dashboard {
  font-family: var(--brgy-font-body);
  color: var(--brgy-ink);
}

.stat-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 18px;
  margin-bottom: 24px;
}

.stat-card {
  background: var(--brgy-paper-raised);
  border-radius: var(--brgy-radius-md);
  box-shadow: var(--brgy-shadow-card);
  padding: 20px 22px;
}

.stat-card__label {
  font-size: 0.82rem;
  color: var(--brgy-ink-muted);
  margin: 0 0 8px;
}

.stat-card__value {
  font-size: 1.9rem;
  font-weight: 700;
  margin: 0 0 6px;
}

.stat-card__meta {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 0.78rem;
  color: var(--brgy-ink-muted);
  margin: 0;
}

.stat-card__meta--amber {
  color: var(--brgy-status-pending);
}

.stat-card__meta--purple {
  color: var(--brgy-status-ready);
}

.stat-card__meta--red {
  color: var(--brgy-status-rejected);
}

.dashboard__body {
  display: grid;
  grid-template-columns: 1.7fr 1fr;
  gap: 20px;
  align-items: start;
}

.panel {
  background: var(--brgy-paper-raised);
  border-radius: var(--brgy-radius-md);
  box-shadow: var(--brgy-shadow-card);
  padding: 22px 24px;
}

.panel__header {
  margin-bottom: 14px;
}

.panel__title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 1rem;
  font-weight: 600;
  margin: 0;
  color: var(--brgy-ink);
}

.panel__loading {
  display: flex;
  justify-content: center;
  padding: 24px 0;
}

.panel__empty {
  font-size: 0.88rem;
  color: var(--brgy-ink-muted);
  padding: 12px 0 4px;
}

.request-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.86rem;
}

.request-table th {
  text-align: left;
  font-weight: 500;
  color: var(--brgy-ink-muted);
  font-size: 0.76rem;
  padding-bottom: 10px;
  border-bottom: 1px solid var(--brgy-line);
}

.request-table td {
  padding: 12px 0;
  border-bottom: 1px solid var(--brgy-line);
  color: var(--brgy-ink);
}

.request-table tr:last-child td {
  border-bottom: none;
}

.request-table__tracking {
  color: var(--brgy-ink-muted);
  font-size: 0.8rem;
}

.request-table__date {
  color: var(--brgy-ink-muted);
  white-space: nowrap;
}

.pill {
  display: inline-block;
  padding: 3px 12px;
  border-radius: 999px;
  font-size: 0.76rem;
  font-weight: 600;
}

.pill--pending {
  background: var(--brgy-status-pending-bg);
  color: var(--brgy-status-pending);
}

.pill--processing {
  background: var(--brgy-status-processing-bg);
  color: var(--brgy-status-processing);
}

.pill--ready {
  background: var(--brgy-status-ready-bg);
  color: var(--brgy-status-ready);
}

.pill--released {
  background: var(--brgy-status-released-bg);
  color: var(--brgy-status-released);
}

.pill--rejected {
  background: var(--brgy-status-rejected-bg);
  color: var(--brgy-status-rejected);
}

.panel__view-all {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  margin-top: 16px;
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--brgy-ink);
  text-decoration: none;
}

.quick-doc {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  padding: 12px 10px;
  background: none;
  border: none;
  border-radius: var(--brgy-radius-sm);
  cursor: pointer;
  text-align: left;
  transition: background 0.15s ease;
}

.quick-doc:hover {
  background: var(--brgy-paper);
}

.quick-doc__icon {
  width: 36px;
  height: 36px;
  flex-shrink: 0;
  border-radius: 10px;
  background: var(--brgy-gold-soft);
  color: var(--brgy-gold);
  display: flex;
  align-items: center;
  justify-content: center;
}

.quick-doc__text {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.quick-doc__name {
  font-size: 0.88rem;
  font-weight: 600;
  color: var(--brgy-ink);
}

.quick-doc__hint {
  font-size: 0.76rem;
  color: var(--brgy-ink-muted);
}

.quick-doc__chevron {
  margin-left: auto;
  color: var(--brgy-ink-muted);
}

@media (max-width: 960px) {
  .dashboard__body {
    grid-template-columns: 1fr;
  }
}
</style>
