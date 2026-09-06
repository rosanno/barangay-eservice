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

<style scoped src="./DashboardCss.css">
</style>
